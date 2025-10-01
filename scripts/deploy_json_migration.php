<?php
/**
 * 派獎記錄 JSON 格式遷移自動化部署腳本
 *
 * 此腳本會自動執行完整的遷移流程：
 * 1. 檢查系統需求
 * 2. 備份現有資料
 * 3. 建立新的資料表結構
 * 4. 執行資料遷移
 * 5. 驗證遷移結果
 *
 * 使用方式：
 * php deploy_json_migration.php [options]
 *
 * 選項：
 * --dry-run          只檢查不執行
 * --skip-backup      跳過自動備份
 * --force            強制執行（跳過確認）
 */

// 設定執行環境
set_time_limit(0);
ini_set('memory_limit', '1G');

// 引入必要檔案
$script_dir = dirname(__FILE__);
$project_root = dirname($script_dir);

require_once $project_root . '/myadm/include.php';

class JsonMigrationDeployer {
    private $pdo;
    private $options;
    private $project_root;

    public function __construct($project_root, $options = []) {
        $this->project_root = $project_root;
        $this->options = $options;
        $this->pdo = openpdo();
    }

    /**
     * 輸出訊息
     */
    private function output($message, $type = 'info') {
        $colors = [
            'info' => "\033[0;37m",     // 白色
            'success' => "\033[0;32m",  // 綠色
            'warning' => "\033[0;33m",  // 黃色
            'error' => "\033[0;31m",    // 紅色
            'reset' => "\033[0m"        // 重設
        ];

        $prefix = [
            'info' => '[INFO]',
            'success' => '[SUCCESS]',
            'warning' => '[WARNING]',
            'error' => '[ERROR]'
        ];

        $timestamp = date('Y-m-d H:i:s');
        $color = $colors[$type] ?? $colors['info'];
        $reset = $colors['reset'];

        echo "{$color}[{$timestamp}] {$prefix[$type]} {$message}{$reset}\n";
    }

    /**
     * 檢查系統需求
     */
    public function checkRequirements() {
        $this->output("檢查系統需求...");

        $requirements = [
            'PHP版本' => version_compare(PHP_VERSION, '7.0.0', '>='),
            'PDO擴展' => extension_loaded('pdo'),
            'MySQL PDO驅動' => extension_loaded('pdo_mysql'),
            '資料庫連線' => $this->testDatabaseConnection()
        ];

        $all_passed = true;
        foreach ($requirements as $requirement => $passed) {
            if ($passed) {
                $this->output("✓ {$requirement}", 'success');
            } else {
                $this->output("✗ {$requirement}", 'error');
                $all_passed = false;
            }
        }

        if (!$all_passed) {
            throw new Exception("系統需求檢查失敗，請解決上述問題後重新執行");
        }

        $this->output("系統需求檢查通過", 'success');
        return true;
    }

    /**
     * 測試資料庫連線
     */
    private function testDatabaseConnection() {
        try {
            $this->pdo->query("SELECT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 檢查現有資料
     */
    public function checkExistingData() {
        $this->output("檢查現有資料...");

        try {
            // 檢查 send_gift_logs 表是否存在
            $tables = $this->pdo->query("SHOW TABLES LIKE 'send_gift_logs'")->fetchAll();
            if (empty($tables)) {
                throw new Exception("send_gift_logs 表不存在");
            }

            // 檢查是否有 JSON 資料
            $json_count = $this->pdo->query("
                SELECT COUNT(*) as count
                FROM send_gift_logs
                WHERE items IS NOT NULL
                AND items != ''
            ")->fetch(PDO::FETCH_ASSOC);

            $this->output("發現 {$json_count['count']} 筆需要遷移的記錄");

            // 檢查是否已經遷移過
            $migrated_check = $this->pdo->query("SHOW TABLES LIKE 'send_gift_log_items'")->fetchAll();
            if (!empty($migrated_check)) {
                $this->output("檢測到已存在 send_gift_log_items 表，可能已經遷移過", 'warning');

                $migration_status = $this->pdo->query("
                    SELECT status FROM migration_status
                    WHERE migration_name = 'json_to_relational_gift_logs'
                ")->fetch(PDO::FETCH_ASSOC);

                if ($migration_status && $migration_status['status'] === 'completed') {
                    if (!isset($this->options['force'])) {
                        throw new Exception("遷移已完成，如需重新執行請使用 --force 選項");
                    }
                    $this->output("使用強制模式，將重新執行遷移", 'warning');
                }
            }

            return $json_count['count'];

        } catch (Exception $e) {
            $this->output("檢查現有資料失敗: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * 備份現有資料
     */
    public function backupData() {
        if (isset($this->options['skip-backup'])) {
            $this->output("跳過自動備份", 'warning');
            return true;
        }

        $this->output("備份現有資料...");

        try {
            $backup_table = 'send_gift_logs_backup_' . date('Ymd_His');

            $this->pdo->exec("CREATE TABLE {$backup_table} AS SELECT * FROM send_gift_logs");

            // 驗證備份
            $original_count = $this->pdo->query("SELECT COUNT(*) FROM send_gift_logs")->fetchColumn();
            $backup_count = $this->pdo->query("SELECT COUNT(*) FROM {$backup_table}")->fetchColumn();

            if ($original_count != $backup_count) {
                throw new Exception("備份驗證失敗：記錄數不一致");
            }

            $this->output("資料備份完成: {$backup_table} ({$backup_count} 筆記錄)", 'success');
            return $backup_table;

        } catch (Exception $e) {
            $this->output("資料備份失敗: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * 建立新的資料表結構
     */
    public function createTables() {
        $this->output("建立新的資料表結構...");

        try {
            $sql_file = $this->project_root . '/docs/sql/send_gift_migration.sql';

            if (!file_exists($sql_file)) {
                throw new Exception("找不到 SQL 檔案: {$sql_file}");
            }

            $sql_content = file_get_contents($sql_file);
            $statements = preg_split('/;\s*$/m', $sql_content);

            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (empty($statement) || strpos($statement, '--') === 0) {
                    continue;
                }

                try {
                    $this->pdo->exec($statement);
                } catch (Exception $e) {
                    // 忽略 "表已存在" 等非關鍵錯誤
                    if (strpos($e->getMessage(), 'already exists') === false &&
                        strpos($e->getMessage(), 'Duplicate') === false) {
                        throw $e;
                    }
                    $this->output("跳過已存在的結構: " . substr($e->getMessage(), 0, 100), 'warning');
                }
            }

            $this->output("資料表結構建立完成", 'success');
            return true;

        } catch (Exception $e) {
            $this->output("建立資料表結構失敗: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * 執行資料遷移
     */
    public function migrateData() {
        $this->output("開始執行資料遷移...");

        try {
            // 引入遷移腳本
            require_once $this->project_root . '/myadm/migration/migrate_gift_logs.php';

            // 執行遷移
            $result = migrate_json_to_relational();

            if (!$result['success']) {
                throw new Exception($result['error'] ?? '遷移失敗');
            }

            $this->output("資料遷移完成: {$result['message']}", 'success');
            return $result;

        } catch (Exception $e) {
            $this->output("資料遷移失敗: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * 驗證遷移結果
     */
    public function validateMigration() {
        $this->output("驗證遷移結果...");

        try {
            // 檢查遷移狀態
            $migration_status = $this->pdo->query("
                SELECT status, records_processed
                FROM migration_status
                WHERE migration_name = 'json_to_relational_gift_logs'
            ")->fetch(PDO::FETCH_ASSOC);

            if (!$migration_status || $migration_status['status'] !== 'completed') {
                throw new Exception("遷移狀態異常");
            }

            // 檢查資料完整性
            $integrity_check = $this->pdo->query("
                SELECT
                    (SELECT COUNT(*) FROM send_gift_logs WHERE items_backup IS NOT NULL) as source_count,
                    (SELECT COUNT(*) FROM send_gift_logs WHERE items_summary IS NOT NULL) as migrated_count,
                    (SELECT COUNT(*) FROM send_gift_log_items) as items_count
            ")->fetch(PDO::FETCH_ASSOC);

            $this->output("驗證結果:");
            $this->output("- 原始記錄數: {$integrity_check['source_count']}");
            $this->output("- 已遷移記錄數: {$integrity_check['migrated_count']}");
            $this->output("- 道具明細記錄數: {$integrity_check['items_count']}");

            if ($integrity_check['source_count'] != $integrity_check['migrated_count']) {
                throw new Exception("資料完整性驗證失敗：記錄數不一致");
            }

            $this->output("遷移結果驗證通過", 'success');
            return true;

        } catch (Exception $e) {
            $this->output("遷移結果驗證失敗: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * 更新應用程式代碼提醒
     */
    public function updateCodeReminder() {
        $this->output("", 'info');
        $this->output("=== 重要提醒 ===", 'warning');
        $this->output("資料遷移已完成，但您仍需要手動更新應用程式代碼：", 'warning');
        $this->output("", 'info');
        $this->output("1. 備份現有的 gift_api.php:", 'info');
        $this->output("   cp myadm/api/gift_api.php myadm/api/gift_api_backup.php", 'info');
        $this->output("", 'info');
        $this->output("2. 參考 gift_api_updated.php 更新以下函式:", 'info');
        $this->output("   - handle_get_gift_logs()", 'info');
        $this->output("   - handle_get_gift_log_detail()", 'info');
        $this->output("   - handle_get_gift_execution_log()", 'info');
        $this->output("   - handle_send_gift() -> handle_send_gift_updated()", 'info');
        $this->output("", 'info');
        $this->output("3. 詳細步驟請參考:", 'info');
        $this->output("   docs/deployment/json_to_relational_deployment.md", 'info');
        $this->output("", 'info');
    }

    /**
     * 執行完整的遷移流程
     */
    public function deploy() {
        try {
            $this->output("開始 JSON 格式遷移部署流程", 'info');
            $this->output("====================================", 'info');

            if (isset($this->options['dry-run'])) {
                $this->output("執行模擬運行 (Dry Run)", 'warning');
            }

            // 1. 檢查系統需求
            $this->checkRequirements();

            // 2. 檢查現有資料
            $record_count = $this->checkExistingData();

            if ($record_count === 0) {
                $this->output("沒有需要遷移的資料，但仍會建立新的資料表結構", 'warning');
            }

            // 3. 確認執行（除非使用 force 選項）
            if (!isset($this->options['force']) && !isset($this->options['dry-run'])) {
                echo "\n即將開始遷移 {$record_count} 筆記錄，是否繼續？(y/N): ";
                $confirm = trim(fgets(STDIN));
                if (strtolower($confirm) !== 'y') {
                    $this->output("使用者取消執行", 'info');
                    return false;
                }
            }

            if (isset($this->options['dry-run'])) {
                $this->output("模擬運行完成，實際遷移請移除 --dry-run 選項", 'success');
                return true;
            }

            // 4. 備份現有資料
            $backup_table = $this->backupData();

            // 5. 建立新的資料表結構
            $this->createTables();

            // 6. 執行資料遷移
            if ($record_count > 0) {
                $migration_result = $this->migrateData();
            }

            // 7. 驗證遷移結果
            $this->validateMigration();

            // 8. 程式碼更新提醒
            $this->updateCodeReminder();

            $this->output("====================================", 'info');
            $this->output("遷移部署完成！", 'success');

            return true;

        } catch (Exception $e) {
            $this->output("部署失敗: " . $e->getMessage(), 'error');
            $this->output("如需回滾，請參考部署文件中的回滾步驟", 'warning');
            return false;
        }
    }
}

// 主執行邏輯
if (php_sapi_name() !== 'cli') {
    die("此腳本只能在命令行中執行\n");
}

// 解析命令行參數
$options = [];
$args = array_slice($argv, 1);

foreach ($args as $arg) {
    if (strpos($arg, '--') === 0) {
        $option = substr($arg, 2);
        $options[$option] = true;
    }
}

// 顯示說明
if (isset($options['help'])) {
    echo "派獎記錄 JSON 格式遷移自動化部署腳本\n";
    echo "========================================\n\n";
    echo "使用方式：\n";
    echo "  php deploy_json_migration.php [options]\n\n";
    echo "選項：\n";
    echo "  --dry-run          只檢查不執行\n";
    echo "  --skip-backup      跳過自動備份\n";
    echo "  --force            強制執行（跳過確認）\n";
    echo "  --help             顯示此說明\n\n";
    echo "範例：\n";
    echo "  php deploy_json_migration.php --dry-run\n";
    echo "  php deploy_json_migration.php --force --skip-backup\n\n";
    exit(0);
}

// 執行部署
try {
    $deployer = new JsonMigrationDeployer($project_root, $options);
    $result = $deployer->deploy();

    exit($result ? 0 : 1);

} catch (Exception $e) {
    echo "\033[0;31m[FATAL] {$e->getMessage()}\033[0m\n";
    exit(1);
}
?>