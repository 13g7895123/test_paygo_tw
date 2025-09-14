<?php
/**
 * ANT 支付狀態查詢端點
 * 用於前端定期檢查支付狀態
 */
include("myadm/include.php");
include_once('./pay_bank.php');
include_once('./ant_api_service.php');

header('Content-Type: application/json');

try {
    // 檢查必要參數
    $order_id = $_GET['order_id'] ?? '';
    
    if (empty($order_id)) {
        throw new Exception("訂單編號不能為空");
    }
    
    $pdo = openpdo();
    
    // 查詢本地訂單資訊
    $query = $pdo->prepare("SELECT * FROM servers_log WHERE orderid = ?");
    $query->execute(array($order_id));
    $order_data = $query->fetch();
    
    if (!$order_data) {
        throw new Exception("找不到訂單資訊");
    }
    
    // 檢查是否為ANT支付
    if ($order_data['pay_cp'] !== 'ant') {
        throw new Exception("非ANT支付訂單");
    }
    
    // 取得ANT設定
    $payment_info = getSpecificBankPaymentInfo($pdo, $order_data['auton'], 'ant');
    
    if (!$payment_info || !isset($payment_info['payment_config'])) {
        throw new Exception("ANT設定錯誤");
    }
    
    $ant_merchant_id = $payment_info['payment_config']['merchant_id'];
    $ant_hashkey = $payment_info['payment_config']['hashkey'];
    $ant_hashiv = $payment_info['payment_config']['hashiv'];
    $is_production = ($payment_info['server_info']['gstats_bank'] == 1);

    // 初始化ANT API服務
    $ant_api = new ANTApiService($ant_merchant_id, $ant_hashkey, $ant_hashiv, $is_production);
    
    // 查詢ANT支付狀態
    $status_result = $ant_api->queryPaymentStatus($order_id);
    
    if ($status_result['success']) {
        $ant_status = $status_result['status'] ?? 'unknown';
        $local_status = $order_data['stats'];
        
        // 根據ANT狀態更新本地訂單狀態
        $new_local_status = $local_status;
        $status_message = '處理中';
        
        switch ($ant_status) {
            case 'completed':
            case 'success':
                $new_local_status = 2; // 支付成功
                $status_message = '支付成功';
                break;
                
            case 'failed':
            case 'error':
                $new_local_status = -1; // 支付失敗
                $status_message = '支付失敗';
                break;
                
            case 'cancelled':
                $new_local_status = -2; // 支付取消
                $status_message = '支付已取消';
                break;
                
            case 'timeout':
                $new_local_status = -3; // 支付超時
                $status_message = '支付超時';
                break;
                
            case 'pending':
            case 'processing':
            default:
                $new_local_status = 1; // 處理中
                $status_message = '處理中';
                break;
        }
        
        // 如果狀態有變更，更新本地資料庫
        if ($new_local_status !== $local_status) {
            $update_query = $pdo->prepare("UPDATE servers_log SET stats = ? WHERE auton = ?");
            $update_query->execute(array($new_local_status, $order_data['auton']));
            
            // 記錄狀態查詢結果到回調日誌表
            $callback_log_query = $pdo->prepare("
                INSERT INTO ant_callback_logs 
                (servers_log_id, order_id, callback_data, callback_time, status_before, status_after) 
                VALUES (?, ?, ?, NOW(), ?, ?)
            ");
            
            $callback_log_query->execute([
                $order_data['auton'],
                $order_id,
                json_encode(['source' => 'status_check', 'ant_status' => $ant_status], JSON_UNESCAPED_UNICODE),
                $local_status,
                $new_local_status
            ]);
        }
        
        // 回應狀態資訊
        echo json_encode([
            'success' => true,
            'order_id' => $order_id,
            'status' => $ant_status,
            'local_status' => $new_local_status,
            'message' => $status_message,
            'amount' => $order_data['money'],
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
    } else {
        // ANT API查詢失敗
        echo json_encode([
            'success' => false,
            'error' => $status_result['error'] ?? 'API查詢失敗',
            'code' => $status_result['code'] ?? 'API_ERROR'
        ]);
    }
    
} catch (Exception $e) {
    // 系統錯誤
    error_log("ANT Status Check Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => 'SYSTEM_ERROR'
    ]);
}
?>