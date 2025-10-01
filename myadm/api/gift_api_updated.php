<?php
/**
 * 修改後的 gift_api.php - 支援關聯式資料表
 *
 * 此檔案包含修改後的函式，支援新的分離式道具明細表
 * 遷移完成後，請將此檔案內容替換原有的 gift_api.php 相關函式
 */

/**
 * 取得派獎記錄 - 修改版（支援關聯式資料表）
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
        SELECT id, server_id, server_name, game_account, items_summary, total_items,
               status, error_message, operator_name, operator_ip, created_at, updated_at
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

    // 對每筆記錄取得道具明細
    foreach ($logs as &$log) {
        $items_query = $pdo->prepare("
            SELECT item_code, item_name, quantity, sort_order
            FROM send_gift_log_items
            WHERE log_id = :log_id
            ORDER BY sort_order ASC, id ASC
        ");
        $items_query->execute([':log_id' => $log['id']]);
        $items = $items_query->fetchAll(PDO::FETCH_ASSOC);

        // 轉換為前端期望的格式
        $log['items'] = array_map(function($item) {
            return [
                'itemCode' => $item['item_code'],
                'itemName' => $item['item_name'],
                'quantity' => intval($item['quantity'])
            ];
        }, $items);

        // 如果沒有道具明細且有舊的 JSON 資料，嘗試解析（向後兼容）
        if (empty($log['items']) && isset($log['items_backup'])) {
            $legacy_items = json_decode($log['items_backup'], true);
            if (is_array($legacy_items)) {
                $log['items'] = $legacy_items;
            }
        }
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
 * 取得派獎記錄詳情 - 修改版
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

    // 取得道具明細
    $items_query = $pdo->prepare("
        SELECT item_code, item_name, quantity, sort_order
        FROM send_gift_log_items
        WHERE log_id = :log_id
        ORDER BY sort_order ASC, id ASC
    ");
    $items_query->execute([':log_id' => $log_id]);
    $items = $items_query->fetchAll(PDO::FETCH_ASSOC);

    // 轉換為前端期望的格式
    $log['items'] = array_map(function($item) {
        return [
            'itemCode' => $item['item_code'],
            'itemName' => $item['item_name'],
            'quantity' => intval($item['quantity'])
        ];
    }, $items);

    // 如果沒有道具明細且有舊的 JSON 資料，嘗試解析（向後兼容）
    if (empty($log['items']) && isset($log['items_backup'])) {
        $legacy_items = json_decode($log['items_backup'], true);
        if (is_array($legacy_items)) {
            $log['items'] = $legacy_items;
        }
    }

    api_success($log, 'Gift log detail retrieved successfully');
}

/**
 * 取得派獎執行記錄和SQL資訊 - 修改版
 */
function handle_get_gift_execution_log($pdo) {
    $log_id = isset($_GET['log_id']) ? $_GET['log_id'] : (isset($_POST['log_id']) ? $_POST['log_id'] : '');

    if (empty($log_id)) {
        api_error('Log ID is required');
    }

    // 取得派獎記錄詳情
    $query = $pdo->prepare("
        SELECT sgl.*, s.names as server_full_name, s.db_ip, s.db_port, s.db_name, s.db_user, s.db_pid
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

    // 取得道具明細
    $items_query = $pdo->prepare("
        SELECT item_code, item_name, quantity, sort_order
        FROM send_gift_log_items
        WHERE log_id = :log_id
        ORDER BY sort_order ASC, id ASC
    ");
    $items_query->execute([':log_id' => $log_id]);
    $items_data = $items_query->fetchAll(PDO::FETCH_ASSOC);

    // 轉換為前端期望的格式
    $items = array_map(function($item) {
        return [
            'itemCode' => $item['item_code'],
            'itemName' => $item['item_name'],
            'quantity' => intval($item['quantity'])
        ];
    }, $items_data);

    // 如果沒有道具明細且有舊的 JSON 資料，嘗試解析（向後兼容）
    if (empty($items) && isset($log['items_backup'])) {
        $legacy_items = json_decode($log['items_backup'], true);
        if (is_array($legacy_items)) {
            $items = $legacy_items;
        }
    }

    // 取得派獎設定資訊
    $settings_query = $pdo->prepare("SELECT * FROM send_gift_settings WHERE server_id = :server_id");
    $settings_query->bindValue(':server_id', $log['server_id'], PDO::PARAM_STR);
    $settings_query->execute();
    $settings = $settings_query->fetch(PDO::FETCH_ASSOC);

    // 取得動態欄位
    $fields_query = $pdo->prepare("SELECT * FROM send_gift_fields WHERE server_id = :server_id ORDER BY sort_order");
    $fields_query->bindValue(':server_id', $log['server_id'], PDO::PARAM_STR);
    $fields_query->execute();
    $fields = $fields_query->fetchAll(PDO::FETCH_ASSOC);

    // 生成可能的執行SQL
    $execution_sqls = [];

    if ($settings && !empty($settings['table_name']) && !empty($settings['account_field'])) {
        $table_name = $settings['table_name'];
        $account_field = $settings['account_field'];
        $item_field = $settings['item_field'] ?: 'item_id';
        $quantity_field = $settings['quantity_field'] ?: 'quantity';
        $game_account = $log['game_account'];

        // 為每個物品生成INSERT SQL
        foreach ($items as $item) {
            $item_code = $item['itemCode'];
            $quantity = $item['quantity'];

            // 基本SQL模板
            $base_sql = "INSERT INTO `{$table_name}` (`{$account_field}`, `{$item_field}`, `{$quantity_field}`) VALUES ('{$game_account}', '{$item_code}', {$quantity})";

            // 如果有動態欄位，加入到SQL中
            if (!empty($fields)) {
                $additional_fields = [];
                $additional_values = [];

                foreach ($fields as $field) {
                    $additional_fields[] = "`{$field['field_name']}`";
                    $additional_values[] = "'{$field['field_value']}'";
                }

                if (!empty($additional_fields)) {
                    $base_sql = "INSERT INTO `{$table_name}` (`{$account_field}`, `{$item_field}`, `{$quantity_field}`, " .
                               implode(', ', $additional_fields) . ") VALUES ('{$game_account}', '{$item_code}', {$quantity}, " .
                               implode(', ', $additional_values) . ")";
                }
            }

            $execution_sqls[] = [
                'item' => $item,
                'sql' => $base_sql,
                'description' => "為帳號 {$game_account} 新增物品 {$item['itemName']} x{$quantity}"
            ];
        }
    }

    // 整理回傳資料
    $result = [
        'log' => [
            'id' => $log['id'],
            'server_id' => $log['server_id'],
            'server_name' => $log['server_name'],
            'server_full_name' => $log['server_full_name'],
            'game_account' => $log['game_account'],
            'total_items' => $log['total_items'],
            'status' => $log['status'],
            'error_message' => $log['error_message'],
            'operator_name' => $log['operator_name'],
            'created_at' => $log['created_at'],
            'updated_at' => $log['updated_at']
        ],
        'items' => $items,
        'server_info' => [
            'db_ip' => $log['db_ip'],
            'db_port' => $log['db_port'],
            'db_name' => $log['db_name'],
            'db_user' => $log['db_user'],
            'db_pid' => $log['db_pid']
        ],
        'gift_settings' => $settings,
        'dynamic_fields' => $fields,
        'execution_sqls' => $execution_sqls,
        'summary' => [
            'total_sqls' => count($execution_sqls),
            'target_database' => $log['db_name'] ?? 'Not configured',
            'target_table' => $settings['table_name'] ?? 'Not configured',
            'account_field' => $settings['account_field'] ?? 'Not configured'
        ]
    ];

    api_success($result, 'Gift execution log retrieved successfully');
}

/**
 * 處理禮物派發 - 修改版（支援新的資料表結構）
 */
function handle_send_gift_updated($pdo) {
    $server_id = _r('server_id') ?? '';
    $server_name = _r('server_name') ?? '';
    $game_account = _r('game_account') ?? '';
    $game_accounts_json = _r('game_accounts') ?? '';
    $items_json = _r('items') ?? '';
    $operator_id = isset($_SESSION['login_id']) ? $_SESSION['login_id'] : null;
    $operator_name = isset($_SESSION['login_name']) ? $_SESSION['login_name'] : 'System';

    if (empty($server_id) || empty($game_account) || empty($items_json)) {
        api_error('Server ID, game account and items are required');
    }

    // 檢查用戶權限
    $is_admin = !empty($_SESSION["adminid"]);
    $user_id = !empty($_SESSION["shareid"]) ? $_SESSION["shareid"] : null;

    if (!check_server_permission($pdo, $server_id, $user_id, $is_admin)) {
        api_error('Access denied: You do not have permission to manage this server', 403);
    }

    // 解析物品資料
    $items = json_decode($items_json, true);
    if (!is_array($items) || empty($items)) {
        api_error('Invalid items data');
    }

    // 處理多個帳號
    $accounts_to_process = [];
    if (!empty($game_accounts_json)) {
        $game_accounts = json_decode($game_accounts_json, true);
        if (is_array($game_accounts) && !empty($game_accounts)) {
            $accounts_to_process = $game_accounts;
        } else {
            $accounts_to_process = [$game_account];
        }
    } else {
        $accounts_to_process = [$game_account];
    }

    // 過濾空帳號
    $accounts_to_process = array_filter($accounts_to_process, function($account) {
        return !empty(trim($account));
    });

    if (empty($accounts_to_process)) {
        api_error('No valid accounts to process');
    }

    // 計算總物品數量和摘要
    $total_items = array_sum(array_column($items, 'quantity'));
    $summary_parts = [];
    foreach ($items as $item) {
        $display_name = !empty($item['itemName']) ? $item['itemName'] : $item['itemCode'];
        $summary_parts[] = $display_name . ' x' . $item['quantity'];
    }
    $items_summary = implode(', ', $summary_parts);

    // 開始交易
    $pdo->beginTransaction();

    try {
        $log_ids = [];
        $success_count = 0;
        $error_messages = [];

        // 為每個帳號分別處理派獎
        foreach ($accounts_to_process as $account) {
            $account = trim($account);
            if (empty($account)) continue;

            // 記錄派獎日誌
            $client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            if (strpos($client_ip, ',') !== false) {
                $client_ip = explode(',', $client_ip)[0];
            }
            $client_ip = trim($client_ip);

            // 插入主記錄（不再使用JSON）
            $log_query = $pdo->prepare("
                INSERT INTO send_gift_logs (
                    server_id, server_name, game_account, items_summary, total_items,
                    status, operator_id, operator_name, operator_ip
                ) VALUES (
                    :server_id, :server_name, :game_account, :items_summary, :total_items,
                    'pending', :operator_id, :operator_name, :operator_ip
                )
            ");
            $log_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
            $log_query->bindValue(':server_name', $server_name, PDO::PARAM_STR);
            $log_query->bindValue(':game_account', $account, PDO::PARAM_STR);
            $log_query->bindValue(':items_summary', $items_summary, PDO::PARAM_STR);
            $log_query->bindValue(':total_items', $total_items, PDO::PARAM_INT);
            $log_query->bindValue(':operator_id', $operator_id, PDO::PARAM_STR);
            $log_query->bindValue(':operator_name', $operator_name, PDO::PARAM_STR);
            $log_query->bindValue(':operator_ip', $client_ip, PDO::PARAM_STR);
            $log_query->execute();

            $log_id = $pdo->lastInsertId();
            $log_ids[] = $log_id;

            // 插入道具明細
            $sort_order = 0;
            foreach ($items as $item) {
                $item_query = $pdo->prepare("
                    INSERT INTO send_gift_log_items (log_id, item_code, item_name, quantity, sort_order)
                    VALUES (:log_id, :item_code, :item_name, :quantity, :sort_order)
                ");
                $item_query->execute([
                    ':log_id' => $log_id,
                    ':item_code' => $item['itemCode'],
                    ':item_name' => $item['itemName'] ?? '',
                    ':quantity' => $item['quantity'],
                    ':sort_order' => $sort_order++
                ]);
            }

            // 實際派發物品到遊戲伺服器
            $game_result = execute_gift_to_game_server($pdo, $server_id, $account, $items, $log_id);

            // 更新派獎狀態
            $update_query = $pdo->prepare("
                UPDATE send_gift_logs
                SET status = :status, error_message = :error_message, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $update_query->bindValue(':id', $log_id, PDO::PARAM_INT);
            $update_query->bindValue(':status', $game_result['success'] ? 'success' : 'failed', PDO::PARAM_STR);
            $update_query->bindValue(':error_message', $game_result['error'] ?? null, PDO::PARAM_STR);
            $update_query->execute();

            if ($game_result['success']) {
                $success_count++;
            } else {
                $error_messages[] = "帳號 {$account}: " . $game_result['error'];
            }
        }

        // 檢查結果
        if ($success_count === 0) {
            $pdo->rollback();
            api_error('All gift distributions failed: ' . implode('; ', $error_messages));
        } else if ($success_count < count($accounts_to_process)) {
            // 部分成功，提交但返回警告
            $pdo->commit();
            api_success([
                'log_id' => $log_ids[0],
                'log_ids' => $log_ids,
                'success_count' => $success_count,
                'total_count' => count($accounts_to_process),
                'error_messages' => $error_messages
            ], "部分派獎成功：{$success_count}/" . count($accounts_to_process) . " 個帳號派獎成功");
        } else {
            // 全部成功
            $pdo->commit();

            api_success([
                'log_id' => $log_ids[0],
                'log_ids' => $log_ids,
                'server_id' => $server_id,
                'server_name' => $server_name,
                'game_account' => $game_account,
                'success_count' => $success_count,
                'total_count' => count($accounts_to_process),
                'total_items' => $total_items,
                'status' => 'success'
            ], count($accounts_to_process) > 1 ? "全部派獎成功：{$success_count} 個帳號派獎完成" : 'Gift sent successfully');
        }

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
 * 向後兼容的道具資料處理函式
 * 自動檢測是否使用新的關聯式資料表或舊的JSON格式
 */
function get_gift_log_items($pdo, $log_id, $legacy_items_json = null) {
    // 先嘗試從新的明細表取得資料
    $items_query = $pdo->prepare("
        SELECT item_code, item_name, quantity, sort_order
        FROM send_gift_log_items
        WHERE log_id = :log_id
        ORDER BY sort_order ASC, id ASC
    ");
    $items_query->execute([':log_id' => $log_id]);
    $items_data = $items_query->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($items_data)) {
        // 新格式：從關聯式資料表
        return array_map(function($item) {
            return [
                'itemCode' => $item['item_code'],
                'itemName' => $item['item_name'],
                'quantity' => intval($item['quantity'])
            ];
        }, $items_data);
    }

    // 向後兼容：如果新表格沒有資料，嘗試解析JSON
    if (!empty($legacy_items_json)) {
        $legacy_items = json_decode($legacy_items_json, true);
        if (is_array($legacy_items)) {
            return $legacy_items;
        }
    }

    return [];
}

?>