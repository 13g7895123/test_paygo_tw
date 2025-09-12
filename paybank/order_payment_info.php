<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理 OPTIONS 請求 (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 引入必要的檔案
include_once('../myadm/include.php');
include_once('../pay_bank.php');

// 初始化除錯資訊
$debug_info = [];
$debug_info['step_1'] = 'API 啟動';
$debug_info['timestamp'] = date('Y-m-d H:i:s');

try {
    // 步驟 1: 檢查輸入參數
    $debug_info['step_2'] = '檢查輸入參數';
    
    $order_id = '';
    if (isset($_GET['order_id'])) {
        $order_id = trim($_GET['order_id']);
    } elseif (isset($_POST['order_id'])) {
        $order_id = trim($_POST['order_id']);
    }
    
    if (empty($order_id)) {
        throw new Exception('訂單號碼 (order_id) 參數必填');
    }
    
    $debug_info['input_order_id'] = $order_id;
    $debug_info['step_3'] = '參數驗證通過';
    
    // 步驟 2: 建立資料庫連線
    $debug_info['step_4'] = '建立資料庫連線';
    $pdo = openpdo();
    if (!$pdo) {
        throw new Exception('資料庫連線失敗');
    }
    $debug_info['step_5'] = '資料庫連線成功';
    
    // 步驟 3: 查詢 servers_log 取得 auton
    $debug_info['step_6'] = '查詢 servers_log 資料表';
    $query = $pdo->prepare("SELECT auton, orderid, serverid, pay_cp, is_bank, money, paytype, userip, gameid, foran, forname, stats FROM servers_log WHERE orderid = :order_id");
    $query->bindValue(':order_id', $order_id, PDO::PARAM_STR);
    $query->execute();
    
    $order_data = $query->fetch(PDO::FETCH_ASSOC);
    if (!$order_data) {
        throw new Exception('找不到訂單號碼: ' . $order_id);
    }
    
    $debug_info['step_7'] = '找到訂單資料';
    $debug_info['order_basic_info'] = $order_data;
    
    $auton = $order_data['auton'];
    $debug_info['found_auton'] = $auton;
    
    // 步驟 4: 使用 pay_bank.php 的函數取得詳細支付資訊
    $debug_info['step_8'] = '呼叫 getBankPaymentInfo 函數';
    $bank_payment_info = getBankPaymentInfo($pdo, $auton);
    
    if (!$bank_payment_info) {
        $debug_info['step_9'] = 'getBankPaymentInfo 回傳 false';
        $debug_info['warning'] = '無法取得銀行支付資訊，可能是資料不完整';
    } else {
        $debug_info['step_9'] = 'getBankPaymentInfo 成功';
    }
    
    // 步驟 5: 取得完整支付資訊
    $debug_info['step_10'] = '呼叫 getCompletePaymentInfo 函數';
    $complete_payment_info = getCompletePaymentInfo($pdo, $auton);
    
    if (!$complete_payment_info) {
        $debug_info['step_11'] = 'getCompletePaymentInfo 回傳 false';
    } else {
        $debug_info['step_11'] = 'getCompletePaymentInfo 成功';
    }
    
    // 步驟 6: 檢查是否為銀行轉帳
    $debug_info['step_12'] = '檢查是否為銀行轉帳';
    $is_bank_transfer = isBankTransfer($pdo, $auton);
    $debug_info['is_bank_transfer'] = $is_bank_transfer;
    
    // 步驟 7: 如果有 pay_cp，嘗試取得特定支付方式資訊
    $specific_payment_info = null;
    if (!empty($order_data['pay_cp'])) {
        $debug_info['step_13'] = '嘗試取得特定支付方式資訊: ' . $order_data['pay_cp'];
        $specific_payment_info = getSpecificBankPaymentInfo($pdo, $auton, $order_data['pay_cp']);
        
        if ($specific_payment_info) {
            $debug_info['step_14'] = '特定支付方式資訊取得成功';
        } else {
            $debug_info['step_14'] = '特定支付方式資訊取得失敗或不存在';
        }
    } else {
        $debug_info['step_13'] = 'pay_cp 為空，跳過特定支付方式查詢';
        $debug_info['step_14'] = '跳過';
    }
    
    // 步驟 8: 組織回傳資料
    $debug_info['step_15'] = '組織回傳資料';
    
    $response = [
        'status' => 'success',
        'message' => '訂單支付資訊查詢成功',
        'data' => [
            'order_id' => $order_id,
            'auton' => $auton,
            'basic_order_info' => $order_data,
            'bank_payment_info' => $bank_payment_info,
            'complete_payment_info' => $complete_payment_info,
            'specific_payment_info' => $specific_payment_info,
            'is_bank_transfer' => $is_bank_transfer
        ],
        'debug_info' => $debug_info
    ];
    
    $debug_info['step_16'] = '資料組織完成';
    
} catch (Exception $e) {
    $debug_info['error'] = $e->getMessage();
    $debug_info['error_line'] = $e->getLine();
    $debug_info['error_file'] = $e->getFile();
    
    $response = [
        'status' => 'error',
        'message' => $e->getMessage(),
        'debug_info' => $debug_info
    ];
    
    http_response_code(400);
}

// 輸出 JSON 回應
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>