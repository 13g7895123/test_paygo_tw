<?php
/**
 * 動態欄位完整整合測試程式
 * Dynamic Fields Complete Integration Test Program
 */

require_once('inc/db.php');

// 測試配置
$test_server_id = 999; // 使用不存在的測試伺服器ID
$api_url = 'http://localhost/dynamic_fields_api.php';

// 測試統計
$stats = [
    'database' => ['total' => 0, 'passed' => 0, 'failed' => 0],
    'api' => ['total' => 0, 'passed' => 0, 'failed' => 0],
    'integration' => ['total' => 0, 'passed' => 0, 'failed' => 0]
];

/**
 * 輸出測試結果
 */
function outputResult($category, $testName, $passed, $message = '', $details = null) {
    global $stats;
    
    $stats[$category]['total']++;
    if ($passed) {
        $stats[$category]['passed']++;
        $icon = '✅';
        $status = 'PASS';
    } else {
        $stats[$category]['failed']++;
        $icon = '❌';
        $status = 'FAIL';
    }
    
    echo sprintf("%-50s %s %s\n", $testName, $icon, $status);
    if ($message) echo "   信息: $message\n";
    if ($details && !$passed) echo "   詳情: " . json_encode($details, JSON_UNESCAPED_UNICODE) . "\n";
    echo "\n";
    
    return $passed;
}

/**
 * 發送API請求
 */
function apiRequest($url, $method = 'GET', $data = null) {
    $options = [
        'http' => [
            'method' => $method,
            'header' => "Content-Type: application/json\r\n",
            'timeout' => 30
        ]
    ];
    
    if ($data && $method === 'POST') {
        $options['http']['content'] => json_encode($data);
    } elseif ($data && $method === 'GET') {
        $url .= '?' . http_build_query($data);
    }
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return ['success' => false, 'message' => 'API請求失敗'];
    }
    
    return json_decode($response, true) ?: ['success' => false, 'message' => '無效的JSON回應'];
}

/**
 * 設置測試環境
 */
function setupTestEnvironment() {
    global $pdo, $test_server_id;
    
    try {
        // 建立測試伺服器記錄
        $stmt = $pdo->prepare("INSERT INTO servers (id, names, table_name, account_field) VALUES (?, ?, NULL, NULL) ON DUPLICATE KEY UPDATE names = VALUES(names)");
        $stmt->execute([$test_server_id, '測試伺服器_整合測試']);
        
        // 清理可能存在的測試資料
        $stmt = $pdo->prepare("DELETE FROM server_dynamic_field_details WHERE server_id = ?");
        $stmt->execute([$test_server_id]);
        
        return true;
    } catch (Exception $e) {
        echo "設置測試環境失敗: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * 清理測試環境
 */
function cleanupTestEnvironment() {
    global $pdo, $test_server_id;
    
    try {
        // 清理動態欄位資料
        $stmt = $pdo->prepare("DELETE FROM server_dynamic_field_details WHERE server_id = ?");
        $stmt->execute([$test_server_id]);
        
        // 清理測試伺服器
        $stmt = $pdo->prepare("DELETE FROM servers WHERE id = ?");
        $stmt->execute([$test_server_id]);
        
        return true;
    } catch (Exception $e) {
        echo "清理測試環境失敗: " . $e->getMessage() . "\n";
        return false;
    }
}

echo "=== 動態欄位完整整合測試 ===\n\n";

// 設置測試環境
if (!setupTestEnvironment()) {
    exit("測試環境設置失敗，退出測試\n");
}

// === 資料庫層測試 ===
echo "=== 資料庫層測試 ===\n\n";

// 測試 1: 資料庫連線
try {
    $pdo->query("SELECT 1");
    outputResult('database', '資料庫連線測試', true, '成功連接到資料庫');
} catch (Exception $e) {
    outputResult('database', '資料庫連線測試', false, '資料庫連線失敗', $e->getMessage());
}

// 測試 2: servers 表結構檢查
try {
    $stmt = $pdo->query("DESCRIBE servers");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['table_name', 'account_field'];
    $hasRequired = true;
    
    foreach ($requiredColumns as $col) {
        if (!in_array($col, $columns)) {
            $hasRequired = false;
            break;
        }
    }
    
    outputResult('database', 'servers表結構檢查', $hasRequired, 
        $hasRequired ? '必要欄位存在' : '缺少必要欄位');
} catch (Exception $e) {
    outputResult('database', 'servers表結構檢查', false, '檢查表結構失敗', $e->getMessage());
}

// 測試 3: server_dynamic_field_details 表檢查
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'server_dynamic_field_details'");
    $exists = $stmt->rowCount() > 0;
    
    outputResult('database', '動態欄位詳細表檢查', $exists, 
        $exists ? '動態欄位詳細表存在' : '動態欄位詳細表不存在');
        
    if ($exists) {
        // 檢查表結構
        $stmt = $pdo->query("DESCRIBE server_dynamic_field_details");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $requiredColumns = ['server_id', 'field_name', 'field_value', 'sort_order'];
        
        $hasAllColumns = true;
        foreach ($requiredColumns as $col) {
            if (!in_array($col, $columns)) {
                $hasAllColumns = false;
                break;
            }
        }
        
        outputResult('database', '動態欄位詳細表結構', $hasAllColumns,
            $hasAllColumns ? '表結構完整' : '表結構不完整');
    }
} catch (Exception $e) {
    outputResult('database', '動態欄位詳細表檢查', false, '檢查失敗', $e->getMessage());
}

// 測試 4: 資料庫CRUD操作
try {
    // 插入測試
    $stmt = $pdo->prepare("UPDATE servers SET table_name = ?, account_field = ? WHERE id = ?");
    $stmt->execute(['test_table', 'test_account', $test_server_id]);
    
    // 插入動態欄位
    $stmt = $pdo->prepare("INSERT INTO server_dynamic_field_details (server_id, field_name, field_value, sort_order) VALUES (?, ?, ?, ?)");
    $stmt->execute([$test_server_id, 'test_field_1', 'test_value_1', 1]);
    $stmt->execute([$test_server_id, 'test_field_2', 'test_value_2', 2]);
    
    // 查詢驗證
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM server_dynamic_field_details WHERE server_id = ?");
    $stmt->execute([$test_server_id]);
    $count = $stmt->fetchColumn();
    
    outputResult('database', '資料庫CRUD操作', $count == 2, "插入了 {$count} 筆記錄");
    
} catch (Exception $e) {
    outputResult('database', '資料庫CRUD操作', false, 'CRUD操作失敗', $e->getMessage());
}

// === API層測試 ===
echo "=== API層測試 ===\n\n";

// 測試 5: API基本連線
$response = apiRequest($api_url, 'GET', ['action' => 'get', 'server_id' => $test_server_id]);
outputResult('api', 'API基本連線', 
    isset($response['success']), 
    isset($response['success']) ? '成功獲得API回應' : 'API無回應');

// 測試 6: GET請求功能
if (isset($response['success'])) {
    $hasServerData = isset($response['data']['server']);
    $hasDynamicFields = isset($response['data']['dynamic_fields']);
    
    outputResult('api', 'GET請求資料結構', 
        $hasServerData && $hasDynamicFields,
        '資料結構完整性檢查');
        
    // 驗證之前插入的資料
    if ($hasDynamicFields) {
        $fieldCount = count($response['data']['dynamic_fields']);
        outputResult('api', 'GET請求資料正確性', 
            $fieldCount == 2,
            "取得 {$fieldCount} 個動態欄位");
    }
}

// 測試 7: POST儲存功能
$saveData = [
    'action' => 'save',
    'server_id' => $test_server_id,
    'table_name' => 'api_test_table',
    'account_field' => 'api_test_account',
    'dynamic_fields' => [
        ['field_name' => 'API測試欄位1', 'field_value' => 'API測試值1'],
        ['field_name' => 'API測試欄位2', 'field_value' => 'API測試值2'],
        ['field_name' => 'API測試欄位3', 'field_value' => 'API測試值3']
    ]
];

$response = apiRequest($api_url, 'POST', $saveData);
outputResult('api', 'POST儲存功能', 
    $response['success'] ?? false,
    $response['message'] ?? '無錯誤訊息');

// 測試 8: POST儲存後驗證
$response = apiRequest($api_url, 'GET', ['action' => 'get', 'server_id' => $test_server_id]);
if ($response['success'] ?? false) {
    $serverData = $response['data']['server'] ?? [];
    $tableNameCorrect = ($serverData['table_name'] ?? '') === 'api_test_table';
    $accountFieldCorrect = ($serverData['account_field'] ?? '') === 'api_test_account';
    $fieldCount = count($response['data']['dynamic_fields'] ?? []);
    
    outputResult('api', 'POST儲存驗證', 
        $tableNameCorrect && $accountFieldCorrect && $fieldCount == 3,
        "表名: {$serverData['table_name']}, 帳號欄位: {$serverData['account_field']}, 動態欄位數: {$fieldCount}");
}

// 測試 9: POST刪除功能
$deleteData = [
    'action' => 'delete',
    'server_id' => $test_server_id
];

$response = apiRequest($api_url, 'POST', $deleteData);
outputResult('api', 'POST刪除功能', 
    $response['success'] ?? false,
    $response['message'] ?? '無錯誤訊息');

// === 整合測試 ===
echo "=== 整合測試 ===\n\n";

// 測試 10: 完整工作流程
$workflowData = [
    'action' => 'save',
    'server_id' => $test_server_id,
    'table_name' => 'workflow_table',
    'account_field' => 'workflow_account',
    'dynamic_fields' => []
];

// 建立50個動態欄位
for ($i = 1; $i <= 50; $i++) {
    $workflowData['dynamic_fields'][] = [
        'field_name' => "工作流程欄位_{$i}",
        'field_value' => "工作流程值_{$i}"
    ];
}

// API儲存
$response = apiRequest($api_url, 'POST', $workflowData);
$apiSaveSuccess = $response['success'] ?? false;

// 資料庫驗證
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM server_dynamic_field_details WHERE server_id = ?");
    $stmt->execute([$test_server_id]);
    $dbCount = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT table_name, account_field FROM servers WHERE id = ?");
    $stmt->execute([$test_server_id]);
    $serverInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $dbCheckSuccess = ($dbCount == 50) && 
                     ($serverInfo['table_name'] === 'workflow_table') && 
                     ($serverInfo['account_field'] === 'workflow_account');
    
    outputResult('integration', '完整工作流程測試', 
        $apiSaveSuccess && $dbCheckSuccess,
        "API儲存: " . ($apiSaveSuccess ? '成功' : '失敗') . 
        ", 資料庫驗證: " . ($dbCheckSuccess ? '成功' : '失敗') . 
        ", 動態欄位數: {$dbCount}");
        
} catch (Exception $e) {
    outputResult('integration', '完整工作流程測試', false, '資料庫驗證失敗', $e->getMessage());
}

// 測試 11: 錯誤處理測試
$errorTests = [
    // 無效的伺服器ID
    ['data' => ['action' => 'get', 'server_id' => 99999], 'method' => 'GET'],
    // 缺少action參數
    ['data' => ['server_id' => $test_server_id], 'method' => 'GET'],
    // 無效的action
    ['data' => ['action' => 'invalid_action'], 'method' => 'POST'],
    // 缺少server_id
    ['data' => ['action' => 'save'], 'method' => 'POST']
];

$errorHandlingPassed = 0;
$errorHandlingTotal = count($errorTests);

foreach ($errorTests as $test) {
    $response = apiRequest($api_url, $test['method'], $test['data']);
    if (!($response['success'] ?? true)) {
        $errorHandlingPassed++;
    }
}

outputResult('integration', '錯誤處理測試', 
    $errorHandlingPassed == $errorHandlingTotal,
    "通過 {$errorHandlingPassed}/{$errorHandlingTotal} 個錯誤處理測試");

// 測試 12: 效能測試
$startTime = microtime(true);

for ($i = 0; $i < 10; $i++) {
    $response = apiRequest($api_url, 'GET', ['action' => 'get', 'server_id' => $test_server_id]);
}

$endTime = microtime(true);
$avgTime = ($endTime - $startTime) / 10;

outputResult('integration', '效能測試', 
    $avgTime < 1.0, // 平均回應時間應小於1秒
    sprintf("10次GET請求平均時間: %.3f秒", $avgTime));

// 清理測試環境
cleanupTestEnvironment();

// 輸出測試摘要
echo "\n=== 測試摘要 ===\n";
$totalTests = 0;
$totalPassed = 0;
$totalFailed = 0;

foreach ($stats as $category => $stat) {
    $successRate = $stat['total'] > 0 ? round(($stat['passed'] / $stat['total']) * 100, 1) : 0;
    echo sprintf("%-15s: %2d/%2d 通過 (%s%%)\n", 
        ucfirst($category), $stat['passed'], $stat['total'], $successRate);
    
    $totalTests += $stat['total'];
    $totalPassed += $stat['passed'];
    $totalFailed += $stat['failed'];
}

echo str_repeat('-', 40) . "\n";
$overallRate = $totalTests > 0 ? round(($totalPassed / $totalTests) * 100, 1) : 0;
echo sprintf("%-15s: %2d/%2d 通過 (%s%%)\n", '總計', $totalPassed, $totalTests, $overallRate);

if ($overallRate >= 90) {
    echo "\n🎉 整合測試結果: 優秀 (≥90%)\n";
} elseif ($overallRate >= 80) {
    echo "\n👍 整合測試結果: 良好 (≥80%)\n";
} elseif ($overallRate >= 70) {
    echo "\n⚠️  整合測試結果: 需要改進 (≥70%)\n";
} else {
    echo "\n❌ 整合測試結果: 不合格 (<70%)\n";
}

echo "\n=== 整合測試完成 ===\n";
?>