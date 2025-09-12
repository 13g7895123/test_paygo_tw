<?php
/**
 * 動態欄位API測試程式
 * Dynamic Fields API Test Program
 */

require_once('inc/db.php');

// 測試配置
$test_server_id = 145; // 測試用伺服器ID
$api_url = 'http://localhost/dynamic_fields_api.php';

// 測試結果統計
$test_results = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'details' => []
];

/**
 * 執行測試並記錄結果
 */
function runTest($testName, $expected, $actual, $description = '') {
    global $test_results;
    
    $test_results['total']++;
    $passed = ($expected === $actual);
    
    if ($passed) {
        $test_results['passed']++;
        $status = '✅ PASS';
    } else {
        $test_results['failed']++;
        $status = '❌ FAIL';
    }
    
    $test_results['details'][] = [
        'name' => $testName,
        'status' => $status,
        'expected' => $expected,
        'actual' => $actual,
        'description' => $description
    ];
    
    echo sprintf("%-40s %s\n", $testName, $status);
    if (!$passed) {
        echo "  期望值: " . json_encode($expected) . "\n";
        echo "  實際值: " . json_encode($actual) . "\n";
        if ($description) echo "  說明: $description\n";
    }
    echo "\n";
}

/**
 * 發送HTTP請求
 */
function sendRequest($url, $method = 'GET', $data = null) {
    $options = [
        'http' => [
            'method' => $method,
            'header' => "Content-Type: application/json\r\n",
        ]
    ];
    
    if ($data && $method === 'POST') {
        $options['http']['content'] = json_encode($data);
    } elseif ($data && $method === 'GET') {
        $url .= '?' . http_build_query($data);
    }
    
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    
    return json_decode($response, true);
}

echo "=== 動態欄位API測試開始 ===\n\n";

// 測試 1: GET - 取得不存在的伺服器資料
echo "測試 1: GET - 取得不存在的伺服器\n";
$response = sendRequest($api_url, 'GET', ['action' => 'get', 'server_id' => 99999]);
runTest('GET_non_existent_server', false, $response['success'], '應該返回伺服器不存在錯誤');

// 測試 2: POST - 儲存動態欄位資料
echo "測試 2: POST - 儲存動態欄位資料\n";
$testData = [
    'action' => 'save',
    'server_id' => $test_server_id,
    'table_name' => 'test_table',
    'account_field' => 'test_account',
    'dynamic_fields' => [
        ['field_name' => '測試欄位1', 'field_value' => '測試值1'],
        ['field_name' => '測試欄位2', 'field_value' => '測試值2'],
        ['field_name' => '測試欄位3', 'field_value' => '測試值3']
    ]
];
$response = sendRequest($api_url, 'POST', $testData);
runTest('POST_save_dynamic_fields', true, $response['success'], '應該成功儲存動態欄位資料');

// 測試 3: GET - 取得剛儲存的資料
echo "測試 3: GET - 取得剛儲存的資料\n";
$response = sendRequest($api_url, 'GET', ['action' => 'get', 'server_id' => $test_server_id]);
runTest('GET_saved_data_success', true, $response['success'], '應該成功取得資料');
runTest('GET_table_name', 'test_table', $response['data']['server']['table_name'] ?? '', '資料表名稱應該正確');
runTest('GET_account_field', 'test_account', $response['data']['server']['account_field'] ?? '', '帳號欄位應該正確');
runTest('GET_dynamic_fields_count', 3, count($response['data']['dynamic_fields'] ?? []), '動態欄位數量應該正確');

// 測試 4: POST - 更新單一欄位（需要先取得欄位ID）
if (isset($response['data']['dynamic_fields'][0])) {
    echo "測試 4: POST - 更新單一動態欄位\n";
    $updateData = [
        'action' => 'update_field',
        'id' => 1, // 假設第一個欄位ID為1
        'field_name' => '更新測試欄位',
        'field_value' => '更新測試值'
    ];
    $response = sendRequest($api_url, 'POST', $updateData);
    runTest('POST_update_field', true, $response['success'], '應該成功更新單一欄位');
}

// 測試 5: POST - 清空動態欄位
echo "測試 5: POST - 儲存空的動態欄位\n";
$emptyData = [
    'action' => 'save',
    'server_id' => $test_server_id,
    'table_name' => 'empty_table',
    'account_field' => 'empty_account',
    'dynamic_fields' => []
];
$response = sendRequest($api_url, 'POST', $emptyData);
runTest('POST_empty_dynamic_fields', true, $response['success'], '應該成功儲存空的動態欄位');

// 測試 6: GET - 驗證清空結果
echo "測試 6: GET - 驗證清空結果\n";
$response = sendRequest($api_url, 'GET', ['action' => 'get', 'server_id' => $test_server_id]);
runTest('GET_empty_fields', 0, count($response['data']['dynamic_fields'] ?? []), '動態欄位應該為空');

// 測試 7: POST - 刪除整個伺服器的動態欄位
echo "測試 7: POST - 刪除伺服器動態欄位\n";
$deleteData = [
    'action' => 'delete',
    'server_id' => $test_server_id
];
$response = sendRequest($api_url, 'POST', $deleteData);
runTest('POST_delete_server_fields', true, $response['success'], '應該成功刪除伺服器動態欄位');

// 測試 8: GET - 驗證刪除結果
echo "測試 8: GET - 驗證刪除結果\n";
$response = sendRequest($api_url, 'GET', ['action' => 'get', 'server_id' => $test_server_id]);
if ($response['success']) {
    runTest('GET_deleted_table_name', null, $response['data']['server']['table_name'], '資料表名稱應該為空');
    runTest('GET_deleted_account_field', null, $response['data']['server']['account_field'], '帳號欄位應該為空');
}

// 測試 9: 錯誤處理測試
echo "測試 9: 錯誤處理測試\n";

// 缺少action參數的GET請求
$response = sendRequest($api_url, 'GET', ['server_id' => $test_server_id]);
runTest('GET_missing_action', false, $response['success'], '缺少action參數應該失敗');

// 無效的action的POST請求
$response = sendRequest($api_url, 'POST', ['action' => 'invalid_action']);
runTest('POST_invalid_action', false, $response['success'], '無效的action應該失敗');

// 缺少server_id的save請求
$response = sendRequest($api_url, 'POST', ['action' => 'save']);
runTest('POST_missing_server_id', false, $response['success'], '缺少server_id應該失敗');

// 測試 10: 壓力測試 - 大量動態欄位
echo "測試 10: 壓力測試 - 大量動態欄位\n";
$largeData = [
    'action' => 'save',
    'server_id' => $test_server_id,
    'table_name' => 'large_test',
    'account_field' => 'large_account',
    'dynamic_fields' => []
];

// 建立100個動態欄位
for ($i = 1; $i <= 100; $i++) {
    $largeData['dynamic_fields'][] = [
        'field_name' => "大量測試欄位_{$i}",
        'field_value' => "大量測試值_{$i}"
    ];
}

$response = sendRequest($api_url, 'POST', $largeData);
runTest('POST_large_data', true, $response['success'], '應該能處理大量動態欄位');

// 驗證大量資料
$response = sendRequest($api_url, 'GET', ['action' => 'get', 'server_id' => $test_server_id]);
runTest('GET_large_data_count', 100, count($response['data']['dynamic_fields'] ?? []), '應該正確儲存100個欄位');

// 測試結果摘要
echo "\n=== 測試結果摘要 ===\n";
echo "總測試數: {$test_results['total']}\n";
echo "通過: {$test_results['passed']}\n";
echo "失敗: {$test_results['failed']}\n";
echo "成功率: " . round(($test_results['passed'] / $test_results['total']) * 100, 2) . "%\n\n";

// 清理測試資料
echo "=== 清理測試資料 ===\n";
$cleanupData = [
    'action' => 'delete',
    'server_id' => $test_server_id
];
$response = sendRequest($api_url, 'POST', $cleanupData);
if ($response['success']) {
    echo "✅ 測試資料清理完成\n";
} else {
    echo "❌ 測試資料清理失敗\n";
}

echo "\n=== API測試完成 ===\n";
?>