-- 為派獎記錄表增加操作者IP欄位
-- 此SQL用於記錄派獎操作者的IP地址，提供更完整的操作記錄追蹤

-- 檢查是否已存在operator_ip欄位，如果不存在則新增
-- 使用標準MySQL語法，兼容所有版本
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'send_gift_logs'
    AND COLUMN_NAME = 'operator_ip'
);

-- 如果欄位不存在則新增
SET @sql = IF(@column_exists = 0,
    'ALTER TABLE `send_gift_logs` ADD COLUMN `operator_ip` VARCHAR(45) NULL DEFAULT NULL COMMENT ''操作者IP地址'' AFTER `operator_name`',
    'SELECT "Column operator_ip already exists" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 檢查是否已存在idx_operator_ip索引
SET @index_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'send_gift_logs'
    AND INDEX_NAME = 'idx_operator_ip'
);

-- 如果索引不存在則建立
SET @sql = IF(@index_exists = 0,
    'CREATE INDEX `idx_operator_ip` ON `send_gift_logs` (`operator_ip`)',
    'SELECT "Index idx_operator_ip already exists" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 顯示表格結構確認更改
DESCRIBE `send_gift_logs`;