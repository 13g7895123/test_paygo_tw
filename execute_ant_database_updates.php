<?php
/**
 * 執行 ANT 資料庫更新腳本
 * 執行完成後請刪除此檔案
 */
include("myadm/include.php");

try {
    $pdo = openpdo();
    
    // 開始交易
    $pdo->beginTransaction();
    
    // 1. 更新欄位註解
    echo "1. 正在更新 user_bank_code 和 user_bank_account 欄位註解...\n";
    $pdo->exec("ALTER TABLE servers_log 
               MODIFY COLUMN user_bank_code VARCHAR(20) NULL COMMENT 'ANT使用者銀行代號',
               MODIFY COLUMN user_bank_account VARCHAR(50) NULL COMMENT 'ANT使用者銀行帳號'");
    echo "   ✓ 欄位註解更新完成\n";
    
    // 2. 建立 ANT 回調記錄表
    echo "2. 正在建立 ANT 回調記錄表...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS ant_callback_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        servers_log_id INT NOT NULL COMMENT 'servers_log 的 auton',
        order_id VARCHAR(50) NOT NULL COMMENT '訂單編號',
        callback_data TEXT COMMENT 'ANT回調資料JSON',
        callback_time DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'ANT回調時間',
        status_before INT COMMENT '回調前狀態',
        status_after INT COMMENT '回調後狀態',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_servers_log_id (servers_log_id),
        INDEX idx_order_id (order_id),
        INDEX idx_callback_time (callback_time),
        INDEX idx_created_at (created_at),
        FOREIGN KEY (servers_log_id) REFERENCES servers_log(auton) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ANT支付回調記錄'");
    echo "   ✓ ANT 回調記錄表建立完成\n";
    
    // 3. 建立索引（如果不存在）
    echo "3. 正在建立索引...\n";
    
    $indexes = [
        'idx_servers_log_orderid' => 'orderid',
        'idx_servers_log_pay_cp' => 'pay_cp', 
        'idx_servers_log_stats' => 'stats'
    ];
    
    foreach ($indexes as $index_name => $column) {
        $check_index = $pdo->query("SHOW INDEX FROM servers_log WHERE Key_name = '$index_name'");
        if ($check_index->rowCount() == 0) {
            $pdo->exec("CREATE INDEX $index_name ON servers_log($column)");
            echo "   ✓ 索引 $index_name 建立完成\n";
        } else {
            echo "   ✓ 索引 $index_name 已存在，跳過\n";
        }
    }
    
    // 4. 建立 ANT 交易日誌表
    echo "4. 正在建立 ANT 交易日誌表...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS ant_transaction_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id VARCHAR(50) NOT NULL COMMENT '訂單編號',
        merchant_id VARCHAR(50) NOT NULL COMMENT 'ANT商店代號',
        api_method VARCHAR(50) NOT NULL COMMENT 'API方法',
        request_data TEXT COMMENT '請求資料JSON',
        response_data TEXT COMMENT '回應資料JSON',
        status VARCHAR(20) DEFAULT 'pending' COMMENT '狀態',
        error_message TEXT COMMENT '錯誤訊息',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_order_id (order_id),
        INDEX idx_merchant_id (merchant_id),
        INDEX idx_api_method (api_method),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ANT交易API調用日誌'");
    echo "   ✓ ANT 交易日誌表建立完成\n";
    
    // 5. 建立 ANT 支付狀態變更日誌表
    echo "5. 正在建立 ANT 支付狀態變更日誌表...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS ant_status_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        servers_log_id INT NOT NULL COMMENT 'servers_log 的 auton',
        order_id VARCHAR(50) NOT NULL COMMENT '訂單編號',
        old_status INT COMMENT '舊狀態',
        new_status INT COMMENT '新狀態',
        ant_status VARCHAR(50) COMMENT 'ANT回傳狀態',
        change_reason VARCHAR(100) COMMENT '狀態變更原因',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_servers_log_id (servers_log_id),
        INDEX idx_order_id (order_id),
        INDEX idx_created_at (created_at),
        FOREIGN KEY (servers_log_id) REFERENCES servers_log(auton) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ANT支付狀態變更歷史'");
    echo "   ✓ ANT 支付狀態變更日誌表建立完成\n";
    
    // 6. 建立 ANT 退款記錄表
    echo "6. 正在建立 ANT 退款記錄表...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS ant_refunds (
        id INT AUTO_INCREMENT PRIMARY KEY,
        original_order_id VARCHAR(50) NOT NULL COMMENT '原始訂單編號',
        refund_order_id VARCHAR(50) NOT NULL COMMENT '退款訂單編號',
        servers_log_id INT NOT NULL COMMENT 'servers_log 的 auton',
        refund_amount DECIMAL(10,2) NOT NULL COMMENT '退款金額',
        refund_reason VARCHAR(200) COMMENT '退款原因',
        ant_refund_id VARCHAR(100) COMMENT 'ANT退款交易ID',
        status VARCHAR(20) DEFAULT 'pending' COMMENT '退款狀態',
        ant_response TEXT COMMENT 'ANT回應資料',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_original_order_id (original_order_id),
        INDEX idx_refund_order_id (refund_order_id),
        INDEX idx_servers_log_id (servers_log_id),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at),
        FOREIGN KEY (servers_log_id) REFERENCES servers_log(auton) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ANT退款記錄'");
    echo "   ✓ ANT 退款記錄表建立完成\n";
    
    // 提交交易
    $pdo->commit();
    
    echo "\n=== ANT 資料庫更新完成 ===\n";
    echo "所有資料表和欄位已成功建立！\n";
    echo "請執行以下驗證查詢確認結果：\n\n";
    echo "-- 1. 檢查 servers_log 表欄位\n";
    echo "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_COMMENT \n";
    echo "FROM INFORMATION_SCHEMA.COLUMNS \n";
    echo "WHERE TABLE_NAME = 'servers_log' \n";
    echo "AND COLUMN_NAME IN ('user_bank_code', 'user_bank_account');\n\n";
    echo "-- 2. 檢查新建立的 ant_callback_logs 表\n";
    echo "DESC ant_callback_logs;\n\n";
    echo "執行完成後請刪除此檔案：execute_ant_database_updates.php\n";
    
} catch (Exception $e) {
    // 回滾交易
    $pdo->rollBack();
    echo "錯誤：" . $e->getMessage() . "\n";
    echo "資料庫更新失敗，所有變更已回滾。\n";
}
?>