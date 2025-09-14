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
    $order_number = $_GET['order_number'] ?? '';

    if (empty($order_id) && empty($order_number)) {
        throw new Exception("訂單編號不能為空");
    }
    
    $pdo = openpdo();
    
    // 查詢本地訂單資訊
    if ($order_number) {
        // 如果有ANT訂單編號，先用third_party_order_id查詢
        $query = $pdo->prepare("SELECT * FROM servers_log WHERE third_party_order_id = ?");
        $query->execute(array($order_number));
        $order_data = $query->fetch();
    } else {
        // 使用商店訂單編號查詢
        $query = $pdo->prepare("SELECT * FROM servers_log WHERE orderid = ?");
        $query->execute(array($order_id));
        $order_data = $query->fetch();
    }
    
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
    
    // 使用資料庫的參數 (依據點109要求)
    $ant_username = $payment_info['payment_config']['merchant_id'] ?? 'antpay018';
    $ant_hashkey = $payment_info['payment_config']['hashkey'] ?? 'lyAJwWnVAKNScXjE6t2rxUOAeesvIP9S';
    $ant_hashiv = $payment_info['payment_config']['hashiv'] ?? 'yhncs1WpMo60azxEczokzIlVVvVuW69p';
    $is_production = ($payment_info['server_info']['gstats_bank'] == 1);

    // 初始化ANT API服務
    $ant_api = new ANTApiService($ant_username, $ant_hashkey, $ant_hashiv, $is_production);
    
    // 查詢ANT支付狀態
    $query_order_number = $order_number ?: $order_data['third_party_order_id'];
    if (empty($query_order_number)) {
        throw new Exception("找不到ANT訂單編號");
    }

    $status_result = $ant_api->queryPaymentStatus($query_order_number);
    
    if ($status_result['success']) {
        $order_info = $status_result['order_info'] ?? [];
        $ant_status_code = $order_info['status'] ?? 0;
        $local_status = $order_data['stats'];

        // 根據ANT狀態代碼更新本地訂單狀態
        $new_local_status = $local_status;
        $ant_status = 'unknown';
        $status_message = '處理中';

        switch ($ant_status_code) {
            case 4: // ANT: 已完成
                $new_local_status = 2; // 本地: 支付成功
                $ant_status = 'completed';
                $status_message = '支付成功';
                break;

            case 5: // ANT: 已取消
                $new_local_status = -2; // 本地: 支付取消
                $ant_status = 'cancelled';
                $status_message = '支付已取消';
                break;

            case 6: // ANT: 已退款
                $new_local_status = -4; // 本地: 已退款
                $ant_status = 'refunded';
                $status_message = '已退款';
                break;

            case 7: // ANT: 金額不符合
                $new_local_status = -1; // 本地: 支付失敗
                $ant_status = 'failed';
                $status_message = '支付失敗 - 金額不符合';
                break;

            case 8: // ANT: 銀行不符合
                $new_local_status = -1; // 本地: 支付失敗
                $ant_status = 'failed';
                $status_message = '支付失敗 - 銀行不符合';
                break;

            case 1: // ANT: 已建立
            case 2: // ANT: 處理中
            case 3: // ANT: 待繳費
            default:
                $new_local_status = 1; // 本地: 處理中
                $ant_status = 'pending';
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
                $order_data['orderid'],
                json_encode([
                    'source' => 'status_check',
                    'ant_status_code' => $ant_status_code,
                    'ant_status' => $ant_status,
                    'order_info' => $order_info
                ], JSON_UNESCAPED_UNICODE),
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