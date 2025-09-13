<?php
/**
 * ANT 支付結果回調處理端點
 * 接收ANT服務的支付結果通知
 */
include("myadm/include.php");
include_once('./pay_bank.php');
include_once('./ant_api_service.php');

// 記錄所有接收到的資料
$raw_input = file_get_contents('php://input');
$callback_data = json_decode($raw_input, true);

// 如果是POST form data
if (empty($callback_data)) {
    $callback_data = $_POST;
}

// 記錄回調日誌
error_log('[ANT-CALLBACK] Received: ' . json_encode($callback_data, JSON_UNESCAPED_UNICODE));

try {
    // 檢查必要參數
    if (empty($callback_data)) {
        throw new Exception("沒有收到回調資料");
    }
    
    $order_id = $callback_data['order_id'] ?? $callback_data['orderid'] ?? '';
    if (empty($order_id)) {
        throw new Exception("回調資料中缺少訂單編號");
    }
    
    $pdo = openpdo();
    
    // 查詢本地訂單資訊
    $query = $pdo->prepare("SELECT sl.*, s.gstats_bank FROM servers_log sl 
                           LEFT JOIN servers s ON sl.foran = s.auton 
                           WHERE sl.orderid = ?");
    $query->execute(array($order_id));
    $order_data = $query->fetch();
    
    if (!$order_data) {
        throw new Exception("找不到訂單資訊: " . $order_id);
    }
    
    // 檢查是否為ANT支付
    if ($order_data['pay_cp'] !== 'ant') {
        throw new Exception("非ANT支付訂單: " . $order_id);
    }
    
    // 取得ANT設定
    $payment_info = getSpecificBankPaymentInfo($pdo, $order_data['auton'], 'ant');
    
    if (!$payment_info || !isset($payment_info['payment_config'])) {
        throw new Exception("ANT設定錯誤");
    }
    
    $ant_shop_id = $payment_info['payment_config']['merchant_id'];
    $ant_key = $payment_info['payment_config']['verify_key'];
    $is_production = ($order_data['gstats_bank'] == 1);
    
    // 初始化ANT API服務
    $ant_api = new ANTApiService($ant_shop_id, $ant_key, $is_production);
    
    // 處理ANT通知
    $notification_result = $ant_api->handleNotification($callback_data);
    
    if (!$notification_result['success']) {
        throw new Exception("通知處理失敗: " . $notification_result['error']);
    }
    
    // 解析支付狀態
    $payment_status = $callback_data['status'] ?? $callback_data['trade_status'] ?? 'unknown';
    $payment_amount = $callback_data['amount'] ?? $callback_data['total_amount'] ?? 0;
    
    // 驗證金額
    if ($payment_amount != $order_data['money']) {
        error_log("[ANT-CALLBACK] 金額不符: 預期 {$order_data['money']}, 實際 {$payment_amount}");
    }
    
    // 根據ANT狀態更新本地訂單
    $new_status = $order_data['stats']; // 預設保持原狀態
    $status_description = '';
    
    switch (strtolower($payment_status)) {
        case 'success':
        case 'completed':
        case 'trade_success':
            $new_status = 2; // 支付成功
            $status_description = '支付成功';
            break;
            
        case 'failed':
        case 'error':
        case 'trade_failed':
            $new_status = -1; // 支付失敗
            $status_description = '支付失敗';
            break;
            
        case 'cancelled':
        case 'trade_closed':
            $new_status = -2; // 支付取消
            $status_description = '支付已取消';
            break;
            
        case 'timeout':
            $new_status = -3; // 支付超時
            $status_description = '支付超時';
            break;
            
        default:
            $new_status = 1; // 處理中
            $status_description = '處理中';
            break;
    }
    
    // 記錄回調前的狀態
    $status_before = $order_data['stats'];
    
    // 更新訂單狀態
    $update_query = $pdo->prepare("UPDATE servers_log SET stats = ? WHERE auton = ?");
    $update_result = $update_query->execute([$new_status, $order_data['auton']]);
    
    if (!$update_result) {
        throw new Exception("訂單狀態更新失敗");
    }
    
    // 記錄回調資訊到專用表
    $callback_log_query = $pdo->prepare("
        INSERT INTO ant_callback_logs 
        (servers_log_id, order_id, callback_data, callback_time, status_before, status_after) 
        VALUES (?, ?, ?, NOW(), ?, ?)
    ");
    
    $callback_log_result = $callback_log_query->execute([
        $order_data['auton'],
        $order_id,
        json_encode($callback_data, JSON_UNESCAPED_UNICODE),
        $status_before,
        $new_status
    ]);
    
    if (!$callback_log_result) {
        throw new Exception("回調記錄寫入失敗");
    }
    
    // 記錄成功處理日誌
    error_log("[ANT-CALLBACK] 訂單 {$order_id} 狀態更新成功: {$status_description} (status: {$new_status})");
    
    // 如果是支付成功，可能需要觸發其他業務邏輯
    if ($new_status == 2) {
        // TODO: 支付成功後的業務處理
        // 例如：發送確認郵件、更新會員點數、觸發遊戲內道具發放等
        
        error_log("[ANT-CALLBACK] 支付成功，訂單 {$order_id} 金額 {$payment_amount}");
    }
    
    // 回應ANT服務 (通常需要回應特定格式)
    echo "SUCCESS"; // 或根據ANT要求的格式回應
    
} catch (Exception $e) {
    // 錯誤處理
    error_log("[ANT-CALLBACK-ERROR] " . $e->getMessage() . " | Data: " . json_encode($callback_data, JSON_UNESCAPED_UNICODE));
    
    // 回應錯誤 (根據ANT要求的格式)
    http_response_code(400);
    echo "ERROR: " . $e->getMessage();
}
?>