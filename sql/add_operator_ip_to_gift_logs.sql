-- 為派獎記錄表增加操作者IP欄位
-- 此SQL用於記錄派獎操作者的IP地址，提供更完整的操作記錄追蹤

-- 檢查是否已存在operator_ip欄位，如果不存在則新增
ALTER TABLE `send_gift_logs`
ADD COLUMN IF NOT EXISTS `operator_ip` VARCHAR(45) NULL DEFAULT NULL COMMENT '操作者IP地址'
AFTER `operator_name`;

-- 為新增的欄位建立索引以提升查詢效能
CREATE INDEX IF NOT EXISTS `idx_operator_ip` ON `send_gift_logs` (`operator_ip`);

-- 顯示表格結構確認更改
DESCRIBE `send_gift_logs`;