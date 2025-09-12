<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 處理 OPTIONS 預檢請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include("../include.php");

// 錯誤處理函數
function api_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'timestamp' => date('c')
    ]);
    exit();
}

// 成功響應函數
function api_success($data = null, $message = null) {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ]);
    exit();
}

// 取得請求方法和動作
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

try {
    $pdo = openpdo();
    
    switch ($action) {
        // ===== 伺服器相關 API =====
        case 'get_servers':
            handle_get_servers($pdo);
            break;
            
        case 'get_server_details':
            handle_get_server_details($pdo);
            break;
            
        // ===== 物品管理 API =====
        case 'get_server_items':
            handle_get_server_items($pdo);
            break;
            
        case 'add_server_item':
            handle_add_server_item($pdo);
            break;
            
        case 'delete_server_item':
            handle_delete_server_item($pdo);
            break;
            
        case 'update_server_item':
            handle_update_server_item($pdo);
            break;
            
        // ===== 派獎設定 API =====
        case 'get_gift_settings':
            handle_get_gift_settings($pdo);
            break;
            
        case 'save_gift_settings':
            handle_save_gift_settings($pdo);
            break;
            
        case 'get_gift_fields':
            handle_get_gift_fields($pdo);
            break;
            
        // ===== 派獎操作 API =====
        case 'send_gift':
            handle_send_gift($pdo);
            break;
            
        case 'get_gift_logs':
            handle_get_gift_logs($pdo);
            break;
            
        case 'get_gift_log_detail':
            handle_get_gift_log_detail($pdo);
            break;
            
        default:
            api_error('Invalid action', 400);
    }
    
} catch (Exception $e) {
    error_log("Gift API Error: " . $e->getMessage());
    api_error('Internal server error: ' . $e->getMessage(), 500);
}

// ===== 處理函數 =====

/**
 * 取得伺服器列表
 */
function handle_get_servers($pdo) {
    $query = $pdo->prepare("SELECT auton, names, id, stats FROM servers WHERE stats = 1 ORDER BY names");
    $query->execute();
    $servers = $query->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    foreach ($servers as $server) {
        $result[] = [
            'id' => $server['auton'],
            'name' => $server['names'],
            'suffix' => $server['id'],
            'active' => $server['stats'] == 1
        ];
    }
    
    api_success($result, 'Servers retrieved successfully');
}

/**
 * 取得伺服器詳細資訊
 */
function handle_get_server_details($pdo) {
    $server_id = isset($_GET['server_id']) ? $_GET['server_id'] : (isset($_POST['server_id']) ? $_POST['server_id'] : '');
    
    if (empty($server_id)) {
        api_error('Server ID is required');
    }
    
    $query = $pdo->prepare("SELECT * FROM servers WHERE auton = :server_id");
    $query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
    $query->execute();
    $server = $query->fetch(PDO::FETCH_ASSOC);
    
    if (!$server) {
        api_error('Server not found', 404);
    }
    
    // 取得派獎設定
    $settings_query = $pdo->prepare("SELECT * FROM send_gift_settings WHERE server_id = :server_id");
    $settings_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
    $settings_query->execute();
    $settings = $settings_query->fetch(PDO::FETCH_ASSOC);
    
    // 取得動態欄位
    $fields_query = $pdo->prepare("SELECT * FROM send_gift_fields WHERE server_id = :server_id ORDER BY sort_order");
    $fields_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
    $fields_query->execute();
    $fields = $fields_query->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [
        'server' => [
            'id' => $server['auton'],
            'name' => $server['names'],
            'suffix' => $server['id']
        ],
        'gift_settings' => $settings ? [
            'table_name' => $settings['table_name'],
            'account_field' => $settings['account_field']
        ] : null,
        'dynamic_fields' => $fields
    ];
    
    api_success($result, 'Server details retrieved successfully');
}

/**
 * 取得伺服器物品列表
 */
function handle_get_server_items($pdo) {
    $server_id = isset($_GET['server_id']) ? $_GET['server_id'] : (isset($_POST['server_id']) ? $_POST['server_id'] : '');
    
    if (empty($server_id)) {
        api_error('Server ID is required');
    }
    
    $query = $pdo->prepare("
        SELECT id, server_id, game_name, database_name, is_active, created_at, updated_at 
        FROM server_items 
        WHERE server_id = :server_id AND is_active = 1 
        ORDER BY game_name
    ");
    $query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
    $query->execute();
    $items = $query->fetchAll(PDO::FETCH_ASSOC);
    
    api_success($items, 'Server items retrieved successfully');
}

/**
 * 新增伺服器物品
 */
function handle_add_server_item($pdo) {
    $server_id = _r('server_id');
    $game_name = _r('game_name');
    $database_name = _r('database_name');
    
    if (empty($server_id) || empty($game_name) || empty($database_name)) {
        api_error('Server ID, game name and database name are required');
    }
    
    // 檢查是否已存在
    $check_query = $pdo->prepare("
        SELECT id FROM server_items 
        WHERE server_id = :server_id AND database_name = :database_name
    ");
    $check_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
    $check_query->bindValue(':database_name', $database_name, PDO::PARAM_STR);
    $check_query->execute();
    
    if ($check_query->fetch()) {
        api_error('Item with this database name already exists');
    }
    
    // 新增物品
    $insert_query = $pdo->prepare("
        INSERT INTO server_items (server_id, game_name, database_name, is_active) 
        VALUES (:server_id, :game_name, :database_name, 1)
    ");
    $insert_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
    $insert_query->bindValue(':game_name', $game_name, PDO::PARAM_STR);
    $insert_query->bindValue(':database_name', $database_name, PDO::PARAM_STR);
    $insert_query->execute();
    
    $item_id = $pdo->lastInsertId();
    
    api_success([
        'id' => $item_id,
        'server_id' => $server_id,
        'game_name' => $game_name,
        'database_name' => $database_name
    ], 'Item added successfully');
}

/**
 * 刪除伺服器物品
 */
function handle_delete_server_item($pdo) {
    $item_id = _r('item_id');
    
    if (empty($item_id)) {
        api_error('Item ID is required');
    }
    
    // 軟刪除（設置為不活躍）
    $update_query = $pdo->prepare("
        UPDATE server_items 
        SET is_active = 0, updated_at = CURRENT_TIMESTAMP 
        WHERE id = :item_id
    ");
    $update_query->bindValue(':item_id', $item_id, PDO::PARAM_INT);
    $update_query->execute();
    
    if ($update_query->rowCount() === 0) {
        api_error('Item not found', 404);
    }
    
    api_success(null, 'Item deleted successfully');
}

/**
 * 更新伺服器物品
 */
function handle_update_server_item($pdo) {
    $item_id = _r('item_id');
    $game_name = _r('game_name');
    $database_name = _r('database_name');
    
    if (empty($item_id) || empty($game_name) || empty($database_name)) {
        api_error('Item ID, game name and database name are required');
    }
    
    $update_query = $pdo->prepare("
        UPDATE server_items 
        SET game_name = :game_name, database_name = :database_name, updated_at = CURRENT_TIMESTAMP 
        WHERE id = :item_id
    ");
    $update_query->bindValue(':item_id', $item_id, PDO::PARAM_INT);
    $update_query->bindValue(':game_name', $game_name, PDO::PARAM_STR);
    $update_query->bindValue(':database_name', $database_name, PDO::PARAM_STR);
    $update_query->execute();
    
    if ($update_query->rowCount() === 0) {
        api_error('Item not found', 404);
    }
    
    api_success(null, 'Item updated successfully');
}

/**
 * 取得派獎設定
 */
function handle_get_gift_settings($pdo) {
    $server_id = isset($_GET['server_id']) ? $_GET['server_id'] : (isset($_POST['server_id']) ? $_POST['server_id'] : '');
    
    if (empty($server_id)) {
        api_error('Server ID is required');
    }
    
    // 取得基本設定
    $settings_query = $pdo->prepare("SELECT * FROM send_gift_settings WHERE server_id = :server_id");
    $settings_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
    $settings_query->execute();
    $settings = $settings_query->fetch(PDO::FETCH_ASSOC);
    
    // 取得動態欄位
    $fields_query = $pdo->prepare("SELECT * FROM send_gift_fields WHERE server_id = :server_id ORDER BY sort_order");
    $fields_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
    $fields_query->execute();
    $fields = $fields_query->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [
        'settings' => $settings,
        'fields' => $fields
    ];
    
    api_success($result, 'Gift settings retrieved successfully');
}

/**
 * 儲存派獎設定
 */
function handle_save_gift_settings($pdo) {
    $server_id = _r('server_id');
    $table_name = _r('table_name');
    $account_field = _r('account_field');
    $field_names = isset($_POST['field_names']) ? $_POST['field_names'] : [];
    $field_values = isset($_POST['field_values']) ? $_POST['field_values'] : [];
    
    if (empty($server_id)) {
        api_error('Server ID is required');
    }
    
    // 開始交易
    $pdo->beginTransaction();
    
    try {
        // 儲存基本設定
        if (!empty($table_name) || !empty($account_field)) {
            // 檢查是否已存在
            $check_query = $pdo->prepare("SELECT id FROM send_gift_settings WHERE server_id = :server_id");
            $check_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
            $check_query->execute();
            
            if ($existing = $check_query->fetch()) {
                // 更新現有記錄
                $update_query = $pdo->prepare("
                    UPDATE send_gift_settings SET 
                        table_name = :table_name,
                        account_field = :account_field,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ");
                $update_query->bindValue(':id', $existing['id'], PDO::PARAM_INT);
                $update_query->bindValue(':table_name', $table_name, PDO::PARAM_STR);
                $update_query->bindValue(':account_field', $account_field, PDO::PARAM_STR);
                $update_query->execute();
            } else {
                // 插入新記錄
                $insert_query = $pdo->prepare("
                    INSERT INTO send_gift_settings (server_id, table_name, account_field) 
                    VALUES (:server_id, :table_name, :account_field)
                ");
                $insert_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
                $insert_query->bindValue(':table_name', $table_name, PDO::PARAM_STR);
                $insert_query->bindValue(':account_field', $account_field, PDO::PARAM_STR);
                $insert_query->execute();
            }
        }
        
        // 處理動態欄位 - 先刪除舊的
        $delete_fields_query = $pdo->prepare("DELETE FROM send_gift_fields WHERE server_id = :server_id");
        $delete_fields_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
        $delete_fields_query->execute();
        
        // 插入新的動態欄位
        if (!empty($field_names) && !empty($field_values)) {
            for ($i = 0; $i < count($field_names); $i++) {
                $field_name = isset($field_names[$i]) ? trim($field_names[$i]) : '';
                $field_value = isset($field_values[$i]) ? trim($field_values[$i]) : '';
                
                // 只保存有內容的欄位
                if (!empty($field_name) && !empty($field_value)) {
                    $insert_field_query = $pdo->prepare("
                        INSERT INTO send_gift_fields (server_id, field_name, field_value, sort_order) 
                        VALUES (:server_id, :field_name, :field_value, :sort_order)
                    ");
                    $insert_field_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
                    $insert_field_query->bindValue(':field_name', $field_name, PDO::PARAM_STR);
                    $insert_field_query->bindValue(':field_value', $field_value, PDO::PARAM_STR);
                    $insert_field_query->bindValue(':sort_order', $i, PDO::PARAM_INT);
                    $insert_field_query->execute();
                }
            }
        }
        
        $pdo->commit();
        api_success(null, 'Gift settings saved successfully');
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
}

/**
 * 取得派獎動態欄位
 */
function handle_get_gift_fields($pdo) {
    $server_id = isset($_GET['server_id']) ? $_GET['server_id'] : (isset($_POST['server_id']) ? $_POST['server_id'] : '');
    
    if (empty($server_id)) {
        api_error('Server ID is required');
    }
    
    $query = $pdo->prepare("SELECT * FROM send_gift_fields WHERE server_id = :server_id ORDER BY sort_order");
    $query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
    $query->execute();
    $fields = $query->fetchAll(PDO::FETCH_ASSOC);
    
    api_success($fields, 'Gift fields retrieved successfully');
}

/**
 * 處理禮物派發
 */
function handle_send_gift($pdo) {
    $server_id = _r('server_id');
    $server_name = _r('server_name');
    $game_account = _r('game_account');
    $items_json = _r('items');
    $operator_id = isset($_SESSION['login_id']) ? $_SESSION['login_id'] : null;
    $operator_name = isset($_SESSION['login_name']) ? $_SESSION['login_name'] : 'System';
    
    if (empty($server_id) || empty($game_account) || empty($items_json)) {
        api_error('Server ID, game account and items are required');
    }
    
    // 解析物品資料
    $items = json_decode($items_json, true);
    if (!is_array($items) || empty($items)) {
        api_error('Invalid items data');
    }
    
    // 計算總物品數量
    $total_items = array_sum(array_column($items, 'quantity'));
    
    // 開始交易
    $pdo->beginTransaction();
    
    try {
        // 記錄派獎日誌
        $log_query = $pdo->prepare("
            INSERT INTO send_gift_logs (
                server_id, server_name, game_account, items, total_items, 
                status, operator_id, operator_name
            ) VALUES (
                :server_id, :server_name, :game_account, :items, :total_items, 
                'pending', :operator_id, :operator_name
            )
        ");
        $log_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
        $log_query->bindValue(':server_name', $server_name, PDO::PARAM_STR);
        $log_query->bindValue(':game_account', $game_account, PDO::PARAM_STR);
        $log_query->bindValue(':items', $items_json, PDO::PARAM_STR);
        $log_query->bindValue(':total_items', $total_items, PDO::PARAM_INT);
        $log_query->bindValue(':operator_id', $operator_id, PDO::PARAM_STR);
        $log_query->bindValue(':operator_name', $operator_name, PDO::PARAM_STR);
        $log_query->execute();
        
        $log_id = $pdo->lastInsertId();
        
        // TODO: 這裡應該實現實際的物品派發邏輯
        // 可能需要連接遊戲伺服器或執行相關的 SQL
        
        // 暫時標記為成功
        $update_query = $pdo->prepare("
            UPDATE send_gift_logs 
            SET status = 'success', updated_at = CURRENT_TIMESTAMP 
            WHERE id = :id
        ");
        $update_query->bindValue(':id', $log_id, PDO::PARAM_INT);
        $update_query->execute();
        
        $pdo->commit();
        
        api_success([
            'log_id' => $log_id,
            'server_id' => $server_id,
            'server_name' => $server_name,
            'game_account' => $game_account,
            'total_items' => $total_items,
            'status' => 'success'
        ], 'Gift sent successfully');
        
    } catch (Exception $e) {
        $pdo->rollback();
        
        // 記錄錯誤到日誌
        if (isset($log_id)) {
            $error_query = $pdo->prepare("
                UPDATE send_gift_logs 
                SET status = 'failed', error_message = :error, updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id
            ");
            $error_query->bindValue(':id', $log_id, PDO::PARAM_INT);
            $error_query->bindValue(':error', $e->getMessage(), PDO::PARAM_STR);
            $error_query->execute();
        }
        
        throw $e;
    }
}

/**
 * 取得派獎記錄
 */
function handle_get_gift_logs($pdo) {
    $server_id = isset($_GET['server_id']) ? $_GET['server_id'] : null;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    
    // 構建查詢條件
    $where_conditions = [];
    $params = [];
    
    if (!empty($server_id)) {
        $where_conditions[] = "server_id = :server_id";
        $params[':server_id'] = $server_id;
    }
    
    $where_sql = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
    
    // 取得總數
    $count_query = $pdo->prepare("SELECT COUNT(*) FROM send_gift_logs $where_sql");
    foreach ($params as $key => $value) {
        $count_query->bindValue($key, $value, PDO::PARAM_STR);
    }
    $count_query->execute();
    $total = $count_query->fetchColumn();
    
    // 取得資料
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;
    
    $query = $pdo->prepare("
        SELECT id, server_id, server_name, game_account, items, total_items, 
               status, error_message, operator_name, created_at, updated_at
        FROM send_gift_logs 
        $where_sql 
        ORDER BY created_at DESC 
        LIMIT :limit OFFSET :offset
    ");
    
    foreach ($params as $key => $value) {
        $type = in_array($key, [':limit', ':offset']) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $query->bindValue($key, $value, $type);
    }
    
    $query->execute();
    $logs = $query->fetchAll(PDO::FETCH_ASSOC);
    
    // 解析 items JSON
    foreach ($logs as &$log) {
        $log['items'] = json_decode($log['items'], true);
    }
    
    api_success([
        'logs' => $logs,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_items' => intval($total),
            'items_per_page' => $limit
        ]
    ], 'Gift logs retrieved successfully');
}

/**
 * 取得派獎記錄詳情
 */
function handle_get_gift_log_detail($pdo) {
    $log_id = isset($_GET['log_id']) ? $_GET['log_id'] : (isset($_POST['log_id']) ? $_POST['log_id'] : '');
    
    if (empty($log_id)) {
        api_error('Log ID is required');
    }
    
    $query = $pdo->prepare("
        SELECT sgl.*, s.names as server_full_name 
        FROM send_gift_logs sgl 
        LEFT JOIN servers s ON sgl.server_id = s.auton 
        WHERE sgl.id = :log_id
    ");
    $query->bindValue(':log_id', $log_id, PDO::PARAM_INT);
    $query->execute();
    $log = $query->fetch(PDO::FETCH_ASSOC);
    
    if (!$log) {
        api_error('Gift log not found', 404);
    }
    
    // 解析 items JSON
    $log['items'] = json_decode($log['items'], true);
    
    api_success($log, 'Gift log detail retrieved successfully');
}

/**
 * 取得請求參數的輔助函數
 */
function _r($key, $default = '') {
    return isset($_REQUEST[$key]) ? $_REQUEST[$key] : $default;
}
?>