<?php
/**
 * Bank Funds SQL Logger API
 * 記錄銀行轉帳金流的 SQL 執行日誌
 */

// 啟動會話
if (!isset($_SESSION)) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://test.paygo.tw');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// 處理 OPTIONS 預檢請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include("../include.php");

// 日誌檔案路徑
$log_dir = dirname(__DIR__) . '/logs';
$log_file = $log_dir . '/bank_funds_sql_' . date('Y-m-d') . '.log';

// 確保 logs 目錄存在
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// 錯誤處理函數
function api_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// 成功響應函數
function api_success($data = null, $message = null) {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// 寫入日誌
function write_sql_log($log_file, $data) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'operation' => $data['operation'] ?? 'UNKNOWN',
        'sql' => $data['sql'] ?? '',
        'parameters' => $data['parameters'] ?? [],
        'server_id' => $data['server_id'] ?? '',
        'result' => $data['result'] ?? '',
        'user' => $_SESSION["adminid"] ?? 'UNKNOWN'
    ];

    $log_line = json_encode($log_entry, JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);

    return true;
}

// 讀取日誌
function read_sql_logs($log_file, $limit = 100) {
    if (!file_exists($log_file)) {
        return [];
    }

    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $logs = [];

    // 反轉陣列以最新的在前面
    $lines = array_reverse($lines);
    $lines = array_slice($lines, 0, $limit);

    foreach ($lines as $line) {
        $log = json_decode($line, true);
        if ($log) {
            $logs[] = $log;
        }
    }

    return $logs;
}

// 取得請求方法和動作
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

try {
    switch ($method) {
        case 'POST':
            // 記錄 SQL
            if ($action === 'log') {
                $input = json_decode(file_get_contents('php://input'), true);

                if (!$input) {
                    $input = $_POST;
                }

                if (empty($input['operation']) || empty($input['sql'])) {
                    api_error('Missing required fields: operation and sql', 400);
                }

                write_sql_log($log_file, $input);
                api_success(null, 'SQL logged successfully');
            } else {
                api_error('Invalid action for POST request', 400);
            }
            break;

        case 'GET':
            // 查詢日誌
            if ($action === 'view' || empty($action)) {
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
                $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

                // 驗證日期格式
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    api_error('Invalid date format. Use YYYY-MM-DD', 400);
                }

                $log_file_for_date = $log_dir . '/bank_funds_sql_' . $date . '.log';
                $logs = read_sql_logs($log_file_for_date, $limit);

                api_success([
                    'date' => $date,
                    'count' => count($logs),
                    'logs' => $logs
                ], 'Logs retrieved successfully');
            } elseif ($action === 'dates') {
                // 列出所有可用的日期
                $files = glob($log_dir . '/bank_funds_sql_*.log');
                $dates = [];

                foreach ($files as $file) {
                    if (preg_match('/bank_funds_sql_(\d{4}-\d{2}-\d{2})\.log$/', basename($file), $matches)) {
                        $dates[] = $matches[1];
                    }
                }

                rsort($dates); // 最新的日期在前面

                api_success([
                    'dates' => $dates,
                    'count' => count($dates)
                ], 'Available dates retrieved');
            } else {
                api_error('Invalid action for GET request', 400);
            }
            break;

        default:
            api_error('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log("Bank Funds SQL Logger API Error: " . $e->getMessage());
    api_error('Internal server error: ' . $e->getMessage(), 500);
}
?>
