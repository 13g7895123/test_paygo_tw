<?php
/**
 * 測試 getSpecificBankPaymentInfo 函數的 API
 * 用於檢查 ANT 支付資訊取得的資料結構
 */

// 啟動輸出緩衝，防止意外輸出干擾 JSON
ob_start();

// 設定錯誤報告等級，避免 notice 干擾 JSON 輸出
error_reporting(E_ERROR | E_WARNING | E_PARSE);

// 設定錯誤處理函數，確保輸出 JSON 格式的錯誤
function handleError($errno, $errstr, $errfile, $errline) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'PHP Error: ' . $errstr,
        'error_details' => [
            'file' => $errfile,
            'line' => $errline,
            'errno' => $errno
        ]
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit;
}

set_error_handler('handleError');

// 設定 JSON 回應標頭
header('Content-Type: application/json; charset=utf-8');

try {
    // 引入必要的檔案
    include_once('myadm/include.php');
    include_once('pay_bank.php');

    // 清除任何意外的輸出
    ob_clean();

    // 取得參數
    $auton = $_REQUEST['auton'] ?? null;
    $payment_type = $_REQUEST['payment_type'] ?? 'ant';

    if (!$auton) {
        throw new Exception("缺少 auton 參數");
    }

    // 建立資料庫連線
    $pdo = openpdo();

    if (!$pdo) {
        throw new Exception("資料庫連線失敗");
    }

    // 測試原始函數調用
    $original_call = '$payment_info = getSpecificBankPaymentInfo($pdo, $order_data[\'auton\'], \'ant\');';

    // 執行函數調用
    $payment_info = getSpecificBankPaymentInfo($pdo, $auton, $payment_type);

    // 準備回應資料
    $response = [
        'success' => true,
        'test_info' => [
            'function_call' => $original_call,
            'parameters' => [
                'auton' => $auton,
                'payment_type' => $payment_type
            ],
            'execution_time' => date('Y-m-d H:i:s')
        ],
        'function_result' => $payment_info,
        'result_analysis' => [
            'has_data' => !empty($payment_info),
            'is_array' => is_array($payment_info),
            'data_type' => gettype($payment_info)
        ]
    ];

    // 如果有資料，進行進一步分析
    if ($payment_info && is_array($payment_info)) {
        $response['result_analysis']['keys'] = array_keys($payment_info);

        // 分析各個部分的內容
        if (isset($payment_info['servers_log_info'])) {
            $response['result_analysis']['servers_log_keys'] = array_keys($payment_info['servers_log_info']);
        }

        if (isset($payment_info['server_info'])) {
            $response['result_analysis']['server_info_keys'] = array_keys($payment_info['server_info']);
        }

        if (isset($payment_info['payment_config'])) {
            $response['result_analysis']['payment_config_keys'] = array_keys($payment_info['payment_config']);
        }
    }

    // 額外測試：取得完整的銀行支付資訊
    $complete_info = getBankPaymentInfo($pdo, $auton);
    $response['complete_bank_info'] = [
        'has_data' => !empty($complete_info),
        'available_payment_types' => []
    ];

    if ($complete_info && isset($complete_info['bank_funds'])) {
        $response['complete_bank_info']['available_payment_types'] = array_keys($complete_info['bank_funds']);
        $response['complete_bank_info']['full_data'] = $complete_info;
    }

    // 檢查是否為銀行轉帳
    $is_bank_transfer = isBankTransfer($pdo, $auton);
    $response['transaction_check'] = [
        'is_bank_transfer' => $is_bank_transfer,
        'function_call' => 'isBankTransfer($pdo, ' . $auton . ')'
    ];

    // 清除緩衝區並輸出 JSON
    ob_clean();
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    ob_end_flush();

} catch (Exception $e) {
    // 清除任何輸出緩衝
    ob_clean();

    $error_response = [
        'success' => false,
        'error' => $e->getMessage(),
        'error_details' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ],
        'test_info' => [
            'function_call' => '$payment_info = getSpecificBankPaymentInfo($pdo, $order_data[\'auton\'], \'ant\');',
            'parameters' => [
                'auton' => $_REQUEST['auton'] ?? 'not_provided',
                'payment_type' => $_REQUEST['payment_type'] ?? 'ant'
            ]
        ]
    ];

    http_response_code(500);
    echo json_encode($error_response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    ob_end_flush();
}
?>