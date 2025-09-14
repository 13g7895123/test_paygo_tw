-- Point 95: 將特店編號改為使用者名稱的資料庫更新腳本
-- 執行日期: 2024年
-- 修改範圍: server_add 頁面和相關資料表
-- 注意：執行前請備份資料庫

-- ===================================
-- 第一部分：修改 bank_funds 表，新增 username 欄位
-- ===================================

-- 1. 為 bank_funds 表新增 username 欄位，專門給 ANT 支付使用
ALTER TABLE bank_funds
ADD COLUMN username VARCHAR(100) NULL COMMENT 'ANT支付專用使用者名稱' AFTER merchant_id;

-- ===================================
-- 第二部分：修改 servers_log 表，新增 username 欄位
-- ===================================

-- 2. 為 servers_log 表新增 username 欄位，用於訂單建立時記錄
ALTER TABLE servers_log
ADD COLUMN username VARCHAR(100) NULL COMMENT '使用者名稱（ANT支付用）' AFTER user_bank_account;

-- ===================================
-- 第三部分：新增索引以提升查詢效能
-- ===================================

-- 3. 為新增的 username 欄位建立索引
CREATE INDEX idx_bank_funds_username ON bank_funds(username);
CREATE INDEX idx_servers_log_username ON servers_log(username);

-- ===================================
-- 執行完成檢查
-- ===================================

-- 檢查 bank_funds 表是否成功新增 username 欄位
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'bank_funds'
AND COLUMN_NAME = 'username';

-- 檢查 servers_log 表是否成功新增 username 欄位
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'servers_log'
AND COLUMN_NAME = 'username';

-- 檢查索引是否建立成功
SELECT TABLE_NAME, INDEX_NAME, COLUMN_NAME
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_NAME IN ('bank_funds', 'servers_log')
AND INDEX_NAME IN ('idx_bank_funds_username', 'idx_servers_log_username');

-- ===================================
-- 使用說明
-- ===================================
/*
新增的欄位說明：

1. bank_funds.username
   - 專門用於 ANT 支付服務
   - 儲存 ANT API 所需的使用者名稱
   - 可為 NULL（其他金流服務不使用此欄位）

2. servers_log.username
   - 用於訂單建立時記錄使用者名稱
   - 當支付類型為銀行轉帳且使用 ANT 服務時會填入
   - 可為 NULL（非 ANT 支付的訂單不使用此欄位）

3. 索引說明：
   - idx_bank_funds_username: 提升根據 username 查詢金流設定的效能
   - idx_servers_log_username: 提升根據 username 查詢訂單的效能
*/

-- ===================================
-- 完成訊息
-- ===================================
SELECT 'Point 95 使用者名稱欄位新增完成！' AS message;