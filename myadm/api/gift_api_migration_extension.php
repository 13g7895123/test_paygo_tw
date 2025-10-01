<?php
/**
 * gift_api.php 遷移擴展
 *
 * 將以下代碼添加到 gift_api.php 中，以支援 JSON 格式遷移功能
 * 在 switch ($action) 中新增以下 case
 */

// 在現有的 switch ($action) 中新增以下 case：

case 'migration_check_requirements':
    handle_migration_check_requirements($pdo);
    break;

case 'migration_check_status':
    handle_migration_check_status($pdo);
    break;

case 'migration_backup_data':
    handle_migration_backup_data($pdo);
    break;

case 'migration_create_structure':
    handle_migration_create_structure($pdo);
    break;

case 'migration_execute':
    handle_migration_execute($pdo);
    break;

case 'migration_validate':
    handle_migration_validate($pdo);
    break;

case 'migration_full':
    handle_migration_full($pdo);
    break;

case 'migration_rollback':
    handle_migration_rollback($pdo);
    break;

// ===== 在檔案末尾新增以下遷移處理函數 =====

/**
 * 檢查遷移系統需求
 */
function handle_migration_check_requirements($pdo) {
    // 檢查權限 - 只有管理員可以執行遷移
    if (empty($_SESSION["adminid"])) {
        api_error('Access denied: Only administrators can perform migration operations', 403);
    }

    try {
        $requirements = [
            'php_version' => [
                'name' => 'PHP 版本',
                'required' => '7.0.0',
                'current' => PHP_VERSION,
                'passed' => version_compare(PHP_VERSION, '7.0.0', '>=')
            ],
            'pdo_extension' => [
                'name' => 'PDO 擴展',
                'required' => 'Yes',
                'current' => extension_loaded('pdo') ? 'Yes' : 'No',
                'passed' => extension_loaded('pdo')
            ],
            'mysql_pdo' => [
                'name' => 'MySQL PDO 驅動',
                'required' => 'Yes',
                'current' => extension_loaded('pdo_mysql') ? 'Yes' : 'No',
                'passed' => extension_loaded('pdo_mysql')
            ]
        ];

        // 測試資料庫連線
        $db_test = ['name' => '資料庫連線', 'required' => 'Connected', 'current' => 'Testing...', 'passed' => false];
        try {
            $pdo->query("SELECT 1");
            $db_test['current'] = 'Connected';
            $db_test['passed'] = true;
        } catch (Exception $e) {
            $db_test['current'] = 'Failed: ' . $e->getMessage();
        }
        $requirements['database_connection'] = $db_test;

        // 檢查資料表狀態
        $table_status = [];
        try {
            $original_table = $pdo->query("SHOW TABLES LIKE 'send_gift_logs'")->fetchAll();
            $table_status['send_gift_logs'] = !empty($original_table);
        } catch (Exception $e) {
            $table_status['send_gift_logs'] = false;
        }

        try {
            $migration_table = $pdo->query("SHOW TABLES LIKE 'send_gift_log_items'")->fetchAll();
            $table_status['send_gift_log_items'] = !empty($migration_table);
        } catch (Exception $e) {
            $table_status['send_gift_log_items'] = false;
        }

        // 檢查資料量
        $data_info = [];
        if ($table_status['send_gift_logs']) {
            try {
                $count_query = $pdo->query("
                    SELECT
                        COUNT(*) as total_logs,
                        COUNT(CASE WHEN items IS NOT NULL AND items != '' THEN 1 END) as json_logs
                    FROM send_gift_logs
                ");
                $counts = $count_query->fetch(PDO::FETCH_ASSOC);
                $data_info = [
                    'total_logs' => intval($counts['total_logs']),
                    'logs_with_json' => intval($counts['json_logs']),
                    'need_migration' => intval($counts['json_logs']) > 0
                ];
            } catch (Exception $e) {
                $data_info = ['error' => $e->getMessage()];
            }
        }

        $all_passed = array_reduce($requirements, function($carry, $req) {
            return $carry && $req['passed'];
        }, true);

        api_success([
            'requirements' => $requirements,
            'table_status' => $table_status,
            'data_info' => $data_info,
            'all_requirements_passed' => $all_passed,
            'ready_for_migration' => $all_passed && $table_status['send_gift_logs']
        ], 'Migration requirements checked');

    } catch (Exception $e) {
        api_error('Failed to check migration requirements: ' . $e->getMessage());
    }
}

/**
 * 檢查遷移狀態
 */
function handle_migration_check_status($pdo) {
    // 檢查權限
    if (empty($_SESSION["adminid"])) {
        api_error('Access denied: Only administrators can check migration status', 403);
    }

    try {
        $status = [
            'migration_completed' => false,
            'migration_status' => null,
            'backup_exists' => false,
            'new_structure_exists' => false,
            'data_migrated' => false,
            'backup_tables' => []
        ];

        // 檢查遷移狀態表
        try {
            $migration_check = $pdo->query("SHOW TABLES LIKE 'migration_status'")->fetchAll();
            if (!empty($migration_check)) {
                $migration_query = $pdo->prepare("
                    SELECT status, records_processed, started_at, completed_at, error_message
                    FROM migration_status
                    WHERE migration_name = 'json_to_relational_gift_logs'
                ");
                $migration_query->execute();
                $migration_result = $migration_query->fetch(PDO::FETCH_ASSOC);

                if ($migration_result) {
                    $status['migration_status'] = $migration_result;
                    $status['migration_completed'] = ($migration_result['status'] === 'completed');
                }
            }
        } catch (Exception $e) {
            // 表格不存在是正常的
        }

        // 檢查新結構
        try {
            $new_table_check = $pdo->query("SHOW TABLES LIKE 'send_gift_log_items'")->fetchAll();
            $status['new_structure_exists'] = !empty($new_table_check);
        } catch (Exception $e) {
            $status['new_structure_exists'] = false;
        }

        // 檢查備份欄位
        try {
            $backup_check = $pdo->query("SHOW COLUMNS FROM send_gift_logs LIKE 'items_backup'")->fetchAll();
            $status['backup_exists'] = !empty($backup_check);
        } catch (Exception $e) {
            $status['backup_exists'] = false;
        }

        // 檢查資料是否已遷移
        if ($status['new_structure_exists']) {
            try {
                $data_check = $pdo->query("SELECT COUNT(*) as count FROM send_gift_log_items")->fetch(PDO::FETCH_ASSOC);
                $status['data_migrated'] = intval($data_check['count']) > 0;
                $status['migrated_items_count'] = intval($data_check['count']);
            } catch (Exception $e) {
                $status['data_migrated'] = false;
            }
        }

        // 檢查備份表
        try {
            $backup_tables = $pdo->query("SHOW TABLES LIKE 'send_gift_logs_backup%'")->fetchAll(PDO::FETCH_COLUMN);
            $status['backup_tables'] = $backup_tables;
            $status['has_backup_tables'] = !empty($backup_tables);
        } catch (Exception $e) {
            $status['backup_tables'] = [];
            $status['has_backup_tables'] = false;
        }

        api_success($status, 'Migration status retrieved');

    } catch (Exception $e) {
        api_error('Failed to check migration status: ' . $e->getMessage());
    }
}

/**
 * 備份現有資料
 */
function handle_migration_backup_data($pdo) {
    // 檢查權限
    if (empty($_SESSION["adminid"])) {
        api_error('Access denied: Only administrators can backup data', 403);
    }

    try {
        $backup_table = 'send_gift_logs_backup_' . date('Ymd_His');
        $pdo->exec("CREATE TABLE {$backup_table} AS SELECT * FROM send_gift_logs");

        // 驗證備份
        $original_count = $pdo->query("SELECT COUNT(*) FROM send_gift_logs")->fetchColumn();
        $backup_count = $pdo->query("SELECT COUNT(*) FROM {$backup_table}")->fetchColumn();

        if ($original_count != $backup_count) {
            throw new Exception("備份驗證失敗：記錄數不一致");
        }

        api_success([
            'backup_table' => $backup_table,
            'records_backed_up' => intval($backup_count)
        ], "資料備份完成: {$backup_table}");

    } catch (Exception $e) {
        api_error('Failed to backup data: ' . $e->getMessage());
    }
}

/**
 * 建立遷移資料結構
 */
function handle_migration_create_structure($pdo) {
    // 檢查權限
    if (empty($_SESSION["adminid"])) {
        api_error('Access denied: Only administrators can create migration structure', 403);
    }

    try {
        // 建立道具明細表
        $create_items_table = "
            CREATE TABLE IF NOT EXISTS send_gift_log_items (
                id INT AUTO_INCREMENT PRIMARY KEY COMMENT '流水號',
                log_id INT NOT NULL COMMENT '派獎記錄ID',
                item_code VARCHAR(50) NOT NULL COMMENT '道具編號',
                item_name VARCHAR(200) DEFAULT NULL COMMENT '道具名稱',
                quantity INT NOT NULL DEFAULT 1 COMMENT '數量',
                sort_order INT DEFAULT 0 COMMENT '排序順序',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',

                INDEX idx_log_id (log_id),
                INDEX idx_item_code (item_code),
                INDEX idx_log_item (log_id, item_code),

                FOREIGN KEY (log_id) REFERENCES send_gift_logs(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='派獎記錄道具明細表'
        ";
        $pdo->exec($create_items_table);

        // 新增備份和摘要欄位
        try {
            $check_backup_column = $pdo->query("SHOW COLUMNS FROM send_gift_logs LIKE 'items_backup'")->fetchAll();
            if (empty($check_backup_column)) {
                try {
                    $pdo->exec("ALTER TABLE send_gift_logs ADD COLUMN items_backup JSON DEFAULT NULL COMMENT 'JSON備份(遷移後可刪除)' AFTER items");
                } catch (Exception $e) {
                    // 如果不支援 JSON，使用 TEXT
                    $pdo->exec("ALTER TABLE send_gift_logs ADD COLUMN items_backup TEXT DEFAULT NULL COMMENT 'JSON備份(遷移後可刪除)' AFTER items");
                }
            }
        } catch (Exception $e) {
            // 忽略欄位已存在的錯誤
        }

        try {
            $check_summary_column = $pdo->query("SHOW COLUMNS FROM send_gift_logs LIKE 'items_summary'")->fetchAll();
            if (empty($check_summary_column)) {
                $pdo->exec("ALTER TABLE send_gift_logs ADD COLUMN items_summary TEXT DEFAULT NULL COMMENT '道具摘要文字' AFTER items_backup");
            }
        } catch (Exception $e) {
            // 忽略欄位已存在的錯誤
        }

        // 建立遷移狀態表
        $create_migration_table = "
            CREATE TABLE IF NOT EXISTS migration_status (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration_name VARCHAR(100) NOT NULL UNIQUE,
                status ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending',
                started_at TIMESTAMP NULL,
                completed_at TIMESTAMP NULL,
                error_message TEXT,
                records_processed INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='遷移狀態追蹤表'
        ";
        $pdo->exec($create_migration_table);

        // 插入遷移記錄
        $insert_migration = $pdo->prepare("
            INSERT IGNORE INTO migration_status (migration_name, status)
            VALUES ('json_to_relational_gift_logs', 'pending')
        ");
        $insert_migration->execute();

        // 備份現有資料
        $pdo->exec("UPDATE send_gift_logs SET items_backup = items WHERE items IS NOT NULL AND items_backup IS NULL");

        api_success([
            'tables_created' => ['send_gift_log_items', 'migration_status'],
            'columns_added' => ['items_backup', 'items_summary']
        ], '遷移資料結構建立完成');

    } catch (Exception $e) {
        api_error('Failed to create migration structure: ' . $e->getMessage());
    }
}

/**
 * 執行資料遷移
 */
function handle_migration_execute($pdo) {
    // 檢查權限
    if (empty($_SESSION["adminid"])) {
        api_error('Access denied: Only administrators can execute migration', 403);
    }

    try {
        // 檢查是否已完成遷移
        $migration_check = $pdo->prepare("
            SELECT status, records_processed
            FROM migration_status
            WHERE migration_name = 'json_to_relational_gift_logs'
        ");
        $migration_check->execute();
        $migration_status = $migration_check->fetch(PDO::FETCH_ASSOC);

        if ($migration_status && $migration_status['status'] === 'completed') {
            api_success([
                'already_completed' => true,
                'records_processed' => $migration_status['records_processed']
            ], '遷移已完成');
            return;
        }

        // 更新遷移狀態
        $update_status = $pdo->prepare("
            UPDATE migration_status
            SET status = 'running', started_at = NOW()
            WHERE migration_name = 'json_to_relational_gift_logs'
        ");
        $update_status->execute();

        // 取得需要遷移的記錄
        $count_query = $pdo->query("
            SELECT COUNT(*) as total
            FROM send_gift_logs
            WHERE items_backup IS NOT NULL AND items_backup != ''
        ");
        $total_records = $count_query->fetch(PDO::FETCH_ASSOC)['total'];

        if ($total_records === 0) {
            // 沒有需要遷移的記錄
            $complete_status = $pdo->prepare("
                UPDATE migration_status
                SET status = 'completed', completed_at = NOW(), records_processed = 0
                WHERE migration_name = 'json_to_relational_gift_logs'
            ");
            $complete_status->execute();

            api_success([
                'records_processed' => 0,
                'total_records' => 0
            ], '沒有需要遷移的記錄');
            return;
        }

        // 開始遷移
        $pdo->beginTransaction();
        $processed = 0;
        $batch_size = 50;
        $offset = 0;

        while ($offset < $total_records) {
            $logs_query = $pdo->prepare("
                SELECT id, items_backup
                FROM send_gift_logs
                WHERE items_backup IS NOT NULL AND items_backup != ''
                LIMIT :limit OFFSET :offset
            ");
            $logs_query->bindValue(':limit', $batch_size, PDO::PARAM_INT);
            $logs_query->bindValue(':offset', $offset, PDO::PARAM_INT);
            $logs_query->execute();

            $batch_logs = $logs_query->fetchAll(PDO::FETCH_ASSOC);
            if (empty($batch_logs)) break;

            foreach ($batch_logs as $log) {
                try {
                    $items = json_decode($log['items_backup'], true);
                    if (!is_array($items)) continue;

                    $summary_parts = [];
                    $sort_order = 0;

                    foreach ($items as $item) {
                        $item_code = $item['itemCode'] ?? $item['item_code'] ?? '';
                        $item_name = $item['itemName'] ?? $item['item_name'] ?? '';
                        $quantity = intval($item['quantity'] ?? 1);

                        if (empty($item_code)) continue;

                        // 插入道具明細
                        $item_query = $pdo->prepare("
                            INSERT INTO send_gift_log_items (log_id, item_code, item_name, quantity, sort_order)
                            VALUES (:log_id, :item_code, :item_name, :quantity, :sort_order)
                        ");
                        $item_query->execute([
                            ':log_id' => $log['id'],
                            ':item_code' => $item_code,
                            ':item_name' => $item_name,
                            ':quantity' => $quantity,
                            ':sort_order' => $sort_order++
                        ]);

                        $display_name = !empty($item_name) ? $item_name : $item_code;
                        $summary_parts[] = $display_name . ' x' . $quantity;
                    }

                    // 更新摘要
                    $summary = implode(', ', $summary_parts);
                    $update_query = $pdo->prepare("
                        UPDATE send_gift_logs SET items_summary = :summary WHERE id = :id
                    ");
                    $update_query->execute([':summary' => $summary, ':id' => $log['id']]);

                    $processed++;
                } catch (Exception $e) {
                    error_log("Error processing log ID {$log['id']}: " . $e->getMessage());
                    continue;
                }
            }

            $offset += $batch_size;
        }

        $pdo->commit();

        // 更新狀態為完成
        $complete_status = $pdo->prepare("
            UPDATE migration_status
            SET status = 'completed', completed_at = NOW(), records_processed = :processed
            WHERE migration_name = 'json_to_relational_gift_logs'
        ");
        $complete_status->execute([':processed' => $processed]);

        api_success([
            'records_processed' => $processed,
            'total_records' => $total_records
        ], "資料遷移完成，共處理 {$processed} 筆記錄");

    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollback();

            // 更新狀態為失敗
            $error_status = $pdo->prepare("
                UPDATE migration_status
                SET status = 'failed', error_message = :error
                WHERE migration_name = 'json_to_relational_gift_logs'
            ");
            $error_status->execute([':error' => $e->getMessage()]);
        }

        api_error('Migration execution failed: ' . $e->getMessage());
    }
}

/**
 * 驗證遷移結果
 */
function handle_migration_validate($pdo) {
    // 檢查權限
    if (empty($_SESSION["adminid"])) {
        api_error('Access denied: Only administrators can validate migration', 403);
    }

    try {
        // 檢查遷移狀態
        $migration_query = $pdo->prepare("
            SELECT status, records_processed
            FROM migration_status
            WHERE migration_name = 'json_to_relational_gift_logs'
        ");
        $migration_query->execute();
        $migration_status = $migration_query->fetch(PDO::FETCH_ASSOC);

        // 檢查資料完整性
        $verification_query = $pdo->query("
            SELECT
                (SELECT COUNT(*) FROM send_gift_logs WHERE items_backup IS NOT NULL AND items_backup != '') as source_count,
                (SELECT COUNT(*) FROM send_gift_logs WHERE items_summary IS NOT NULL) as migrated_count,
                (SELECT COUNT(*) FROM send_gift_log_items) as items_count
        ");
        $verification = $verification_query->fetch(PDO::FETCH_ASSOC);

        $validation_result = [
            'migration_status' => $migration_status,
            'data_integrity' => $verification,
            'validation_passed' => false,
            'issues' => []
        ];

        // 驗證規則
        if (!$migration_status || $migration_status['status'] !== 'completed') {
            $validation_result['issues'][] = '遷移狀態不是已完成';
        }

        if ($verification['source_count'] != $verification['migrated_count']) {
            $validation_result['issues'][] = '原始記錄數與已遷移記錄數不一致';
        }

        if ($verification['items_count'] == 0 && $verification['source_count'] > 0) {
            $validation_result['issues'][] = '沒有道具明細記錄，但有原始記錄';
        }

        $validation_result['validation_passed'] = empty($validation_result['issues']);

        api_success($validation_result,
            $validation_result['validation_passed'] ? '驗證通過' : '驗證發現問題');

    } catch (Exception $e) {
        api_error('Failed to validate migration: ' . $e->getMessage());
    }
}

/**
 * 一鍵完整遷移
 */
function handle_migration_full($pdo) {
    // 檢查權限
    if (empty($_SESSION["adminid"])) {
        api_error('Access denied: Only administrators can execute full migration', 403);
    }

    try {
        $results = [];

        // 1. 備份資料
        $backup_table = 'send_gift_logs_backup_' . date('Ymd_His');
        $pdo->exec("CREATE TABLE {$backup_table} AS SELECT * FROM send_gift_logs");
        $results['backup'] = $backup_table;

        // 2. 建立結構
        handle_migration_create_structure($pdo);
        $results['structure'] = 'Created';

        // 3. 執行遷移
        handle_migration_execute($pdo);
        $results['migration'] = 'Completed';

        // 4. 驗證結果
        handle_migration_validate($pdo);
        $results['validation'] = 'Verified';

        api_success($results, '一鍵完整遷移執行完成');

    } catch (Exception $e) {
        api_error('Full migration failed: ' . $e->getMessage());
    }
}

/**
 * 回滾遷移
 */
function handle_migration_rollback($pdo) {
    // 檢查權限
    if (empty($_SESSION["adminid"])) {
        api_error('Access denied: Only administrators can rollback migration', 403);
    }

    $backup_table = _r('backup_table') ?? '';

    if (empty($backup_table)) {
        api_error('Backup table name is required for rollback');
    }

    try {
        $pdo->beginTransaction();

        // 檢查備份表是否存在
        $check_backup = $pdo->query("SHOW TABLES LIKE '{$backup_table}'")->fetchAll();
        if (empty($check_backup)) {
            throw new Exception("備份表 {$backup_table} 不存在");
        }

        // 刪除新建的表格
        $pdo->exec("DROP TABLE IF EXISTS send_gift_log_items");
        $pdo->exec("DROP TABLE IF EXISTS migration_status");

        // 恢復原始資料
        $pdo->exec("DROP TABLE send_gift_logs");
        $pdo->exec("CREATE TABLE send_gift_logs AS SELECT * FROM {$backup_table}");

        // 重新建立主鍵
        $pdo->exec("ALTER TABLE send_gift_logs ADD PRIMARY KEY (id)");

        $pdo->commit();

        api_success([
            'backup_table_used' => $backup_table
        ], '回滾完成');

    } catch (Exception $e) {
        $pdo->rollback();
        api_error('Rollback failed: ' . $e->getMessage());
    }
}

?>