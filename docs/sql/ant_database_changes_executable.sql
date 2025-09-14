-- ANT 支付系統資料庫修改執行腳本
-- 執行日期: 2024年
-- 修改範圍: Points 88-90 ANT API 整合功能
-- 注意：執行前請備份資料庫

-- ===================================
-- 第一部分：修改現有表格
-- ===================================

-- 1. 為 servers_log 表新增 ANT 相關欄位
ALTER TABLE servers_log
ADD COLUMN user_bank_code VARCHAR(20) NULL COMMENT 'ANT使用者銀行代號',
ADD COLUMN user_bank_account VARCHAR(50) NULL COMMENT 'ANT使用者銀行帳號',
ADD COLUMN third_party_order_id VARCHAR(50) NULL COMMENT 'ANT系統訂單編號';

-- 2. 為 servers_log 表新增索引以提升查詢效能
CREATE INDEX idx_servers_log_orderid ON servers_log(orderid);
CREATE INDEX idx_servers_log_pay_cp ON servers_log(pay_cp);
CREATE INDEX idx_servers_log_stats ON servers_log(stats);

-- ===================================
-- 第二部分：建立 ANT 相關新表格
-- ===================================

-- 3. 建立 ANT 回調記錄表
CREATE TABLE ant_callback_logs (
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

-- 4. 建立 ANT 交易 API 調用日誌表
CREATE TABLE ant_transaction_logs (
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

-- 5. 建立 ANT 支付狀態變更歷史表
CREATE TABLE ant_status_history (
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

-- 6. 建立 ANT 退款記錄表（可選）
CREATE TABLE ant_refunds (
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

-- ===================================
-- 執行完成檢查
-- ===================================

-- 檢查 servers_log 表是否成功新增欄位
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'servers_log'
AND COLUMN_NAME IN ('user_bank_code', 'user_bank_account', 'third_party_order_id');

-- 檢查新建立的表格是否存在
SELECT TABLE_NAME, TABLE_COMMENT
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_NAME IN ('ant_callback_logs', 'ant_transaction_logs', 'ant_status_history', 'ant_refunds');

-- 檢查索引是否建立成功
SELECT TABLE_NAME, INDEX_NAME
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_NAME = 'servers_log'
AND INDEX_NAME IN ('idx_servers_log_orderid', 'idx_servers_log_pay_cp', 'idx_servers_log_stats');

-- ===================================
-- ANT 支付狀態對應參考
-- ===================================
/*
ANT API 狀態對應：
1 (已建立) -> 本地狀態 1 (處理中)
2 (處理中) -> 本地狀態 1 (處理中)
3 (待繳費) -> 本地狀態 1 (處理中)
4 (已完成) -> 本地狀態 2 (支付成功)
5 (已取消) -> 本地狀態 -2 (支付取消)
6 (已退款) -> 本地狀態 -4 (已退款)
7 (金額不符合) -> 本地狀態 -1 (支付失敗)
8 (銀行不符合) -> 本地狀態 -1 (支付失敗)
*/

-- ===================================
-- 完成訊息
-- ===================================
SELECT 'ANT 支付系統資料庫修改執行完成！' AS message;