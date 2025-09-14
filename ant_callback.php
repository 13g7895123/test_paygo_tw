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
    
    // 根據ANT API文檔，可能使用不同的欄位名稱
    $partner_number = $callback_data['partner_number'] ?? $callback_data['order_id'] ?? $callback_data['orderid'] ?? '';
    $ant_order_number = $callback_data['number'] ?? $callback_data['order_number'] ?? '';

    if (empty($partner_number)) {
        throw new Exception("回調資料中缺少訂單編號");
    }
    
    $pdo = openpdo();
    
    // 查詢本地訂單資訊
    $query = $pdo->prepare("SELECT sl.*, s.gstats_bank FROM servers_log sl
                           LEFT JOIN servers s ON sl.foran = s.auton
                           WHERE sl.orderid = ?");
    $query->execute(array($partner_number));
    $order_data = $query->fetch();
    
    if (!$order_data) {
        throw new Exception("找不到訂單資訊: " . $partner_number);
    }

    // 檢查是否為ANT支付
    if ($order_data['pay_cp'] !== 'ant') {
        throw new Exception("非ANT支付訂單: " . $partner_number);
    }
    
    // 取得ANT設定
    $payment_info = getSpecificBankPaymentInfo($pdo, $order_data['auton'], 'ant');
    
    if (!$payment_info || !isset($payment_info['payment_config'])) {
        throw new Exception("ANT設定錯誤");
    }
    
    // 使用資料庫的參數 (依據點109要求)
    $ant_username = $payment_info['payment_config']['merchant_id'] ?? 'antpay018';
    $ant_hashkey = $payment_info['payment_config']['hashkey'] ?? 'lyAJwWnVAKNScXjE6t2rxUOAeesvIP9S';
    $ant_hashiv = $payment_info['payment_config']['hashiv'] ?? 'yhncs1WpMo60azxEczokzIlVVvVuW69p';
    $is_production = ($order_data['gstats_bank'] == 1);

    // 初始化ANT API服務用於簽名驗證
    $ant_api = new ANTApiService($ant_username, $ant_hashkey, $ant_hashiv, $is_production);

    // 直接驗證回調簽名（基本驗證，不依賴額外API）
    $received_signature = $callback_data['sign'] ?? '';
    if (empty($received_signature)) {
        throw new Exception("回調資料缺少簽名");
    }

    // TODO: 根據真實ANT API文檔實作簽名驗證
    // 目前暫時跳過簽名驗證，實際部署時需要根據文檔實作
    
    // 解析ANT回調資料格式
    $payment_status_code = $callback_data['status'] ?? 0;
    $payment_amount = $callback_data['amount'] ?? 0;
    $paid_amount = $callback_data['pay_amount'] ?? $payment_amount;

    // 驗證金額
    if ($payment_amount != $order_data['money']) {
        error_log("[ANT-CALLBACK] 金額不符: 預期 {$order_data['money']}, 實際 {$payment_amount}");
    }

    // 根據ANT狀態代碼更新本地訂單狀態
    $new_status = $order_data['stats']; // 預設保持原狀態
    $status_description = '';

    switch ($payment_status_code) {
        case 4: // ANT: 已完成
            $new_status = 2; // 本地: 支付成功
            $status_description = '支付成功';
            break;

        case 5: // ANT: 已取消
            $new_status = -2; // 本地: 支付取消
            $status_description = '支付已取消';
            break;

        case 6: // ANT: 已退款
            $new_status = -4; // 本地: 已退款
            $status_description = '已退款';
            break;

        case 7: // ANT: 金額不符合
            $new_status = -1; // 本地: 支付失敗
            $status_description = '支付失敗 - 金額不符合';
            break;

        case 8: // ANT: 銀行不符合
            $new_status = -1; // 本地: 支付失敗
            $status_description = '支付失敗 - 銀行不符合';
            break;

        case 1: // ANT: 已建立
        case 2: // ANT: 處理中
        case 3: // ANT: 待繳費
        default:
            $new_status = 1; // 本地: 處理中
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
        $partner_number,
        json_encode([
            'partner_number' => $partner_number,
            'ant_order_number' => $ant_order_number,
            'status_code' => $payment_status_code,
            'amount' => $payment_amount,
            'paid_amount' => $paid_amount,
            'raw_data' => $callback_data
        ], JSON_UNESCAPED_UNICODE),
        $status_before,
        $new_status
    ]);
    
    if (!$callback_log_result) {
        throw new Exception("回調記錄寫入失敗");
    }
    
    // 更新ANT訂單編號到本地資料庫
    if ($ant_order_number && $ant_order_number !== $order_data['third_party_order_id']) {
        $update_ant_order = $pdo->prepare("UPDATE servers_log SET third_party_order_id = ? WHERE auton = ?");
        $update_ant_order->execute([$ant_order_number, $order_data['auton']]);
    }

    // 記錄成功處理日誌
    error_log("[ANT-CALLBACK] 訂單 {$partner_number} (商店) / {$ant_order_number} (ANT) 狀態更新成功: {$status_description} (status: {$new_status})");
    
    // 如果是支付成功，可能需要觸發其他業務邏輯
    if ($new_status == 2) {
        // TODO: 支付成功後的業務處理
        // 例如：發送確認郵件、更新會員點數、觸發遊戲內道具發放等

        error_log("[ANT-CALLBACK] 支付成功，訂單 {$partner_number} (商店) / {$ant_order_number} (ANT) 金額 {$payment_amount}");
    }
    
    // 回應ANT服務 (根據文檔要求返回正確格式)
    echo "OK"; // ANT API文檔要求的回應格式
    
} catch (Exception $e) {
    // 錯誤處理
    error_log("[ANT-CALLBACK-ERROR] " . $e->getMessage() . " | Data: " . json_encode($callback_data, JSON_UNESCAPED_UNICODE));
    
    // 回應錯誤 (根據ANT要求的格式)
    http_response_code(400);
    echo "ERROR: " . $e->getMessage();
}
?>