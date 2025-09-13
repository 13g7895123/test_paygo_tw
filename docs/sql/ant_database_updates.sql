-- ANT 支付系統資料庫更新 SQL
-- 為支援ANT支付回調和狀態追蹤所需的資料庫結構更新

-- 1. 為 servers_log 表的 user_bank_code 和 user_bank_account 欄位新增註解（如果還沒有的話）
ALTER TABLE servers_log 
MODIFY COLUMN user_bank_code VARCHAR(20) NULL COMMENT 'ANT使用者銀行代號',
MODIFY COLUMN user_bank_account VARCHAR(50) NULL COMMENT 'ANT使用者銀行帳號';

-- 2. 建立 ANT 回調記錄表（替代在 servers_log 中新增欄位）
CREATE TABLE IF NOT EXISTS ant_callback_logs (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ANT支付回調記錄';

-- 3. 為 ANT 相關查詢建立索引以提升效能
CREATE INDEX idx_servers_log_orderid ON servers_log(orderid);
CREATE INDEX idx_servers_log_pay_cp ON servers_log(pay_cp);
CREATE INDEX idx_servers_log_stats ON servers_log(stats);

-- 4. 新增 ANT 支付狀態說明註解
-- stats 欄位狀態說明：
-- 0: 待支付
-- 1: 處理中
-- 2: 支付成功
-- -1: 支付失敗
-- -2: 支付取消
-- -3: 支付超時

-- 5. 確保 bank_funds 表有 ANT 相關設定的範例資料（僅供參考，實際使用時請根據真實設定修改）
-- INSERT INTO bank_funds (server_code, third_party_payment, merchant_id, verify_key, created_at, updated_at) 
-- VALUES 
-- (1, 'ant', 'ANT_MERCHANT_ID', 'ANT_VERIFY_KEY', NOW(), NOW());

-- 6. 建立 ANT 交易日誌表（可選，用於詳細的 API 調用記錄）
CREATE TABLE IF NOT EXISTS ant_transaction_logs (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ANT交易API調用日誌';

-- 7. 建立 ANT 支付狀態變更日誌表（可選，用於追蹤狀態變化）
CREATE TABLE IF NOT EXISTS ant_status_history (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ANT支付狀態變更歷史';

-- 8. 建立 ANT 退款記錄表（可選，用於記錄退款操作）
CREATE TABLE IF NOT EXISTS ant_refunds (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ANT退款記錄';

-- 9. 新增系統設定表項目（可選，用於儲存ANT全域設定）
-- INSERT INTO system_settings (setting_key, setting_value, description, created_at, updated_at) 
-- VALUES 
-- ('ant_api_timeout', '30', 'ANT API 請求超時時間（秒）', NOW(), NOW()),
-- ('ant_callback_retry_times', '3', 'ANT 回調處理重試次數', NOW(), NOW()),
-- ('ant_status_check_interval', '300', 'ANT 狀態檢查間隔（秒）', NOW(), NOW())
-- ON DUPLICATE KEY UPDATE updated_at = NOW();

-- 執行完成後請檢查：
-- 1. 所有新增欄位是否正確建立
-- 2. 索引是否正確建立
-- 3. 外鍵約束是否正常
-- 4. 確認既有資料不受影響

-- 驗證查詢：
-- 1. 檢查 servers_log 表欄位
-- SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_COMMENT 
-- FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_NAME = 'servers_log' 
-- AND COLUMN_NAME IN ('user_bank_code', 'user_bank_account');

-- 2. 檢查新建立的 ant_callback_logs 表
-- SELECT * FROM ant_callback_logs LIMIT 1;
-- DESC ant_callback_logs;