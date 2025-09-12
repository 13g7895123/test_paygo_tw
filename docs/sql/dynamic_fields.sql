-- 動態欄位功能的資料表結構
-- Dynamic Fields Functionality Database Schema

-- 建立伺服器動態設定資料表
CREATE TABLE `server_dynamic_fields` (
    `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '流水號',
    `server_id` INT NOT NULL COMMENT '伺服器ID，關聯servers表',
    `table_name` VARCHAR(255) DEFAULT NULL COMMENT '資料表名稱',
    `account_field` VARCHAR(255) DEFAULT NULL COMMENT '帳號欄位名稱',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    INDEX `idx_server_id` (`server_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='伺服器動態設定主表';

-- 建立動態欄位詳細資料表
CREATE TABLE `server_dynamic_field_details` (
    `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '流水號',
    `server_id` INT NOT NULL COMMENT '伺服器ID，關聯servers表',
    `field_name` VARCHAR(255) NOT NULL COMMENT '欄位名稱',
    `field_value` TEXT DEFAULT NULL COMMENT '欄位資料',
    `sort_order` INT DEFAULT 0 COMMENT '排序順序',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    INDEX `idx_server_id` (`server_id`),
    INDEX `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='伺服器動態欄位詳細資料表';

-- 新增欄位到servers表（如果不存在）
ALTER TABLE `servers` 
ADD COLUMN `table_name` VARCHAR(255) DEFAULT NULL COMMENT '資料表名稱',
ADD COLUMN `account_field` VARCHAR(255) DEFAULT NULL COMMENT '帳號欄位名稱';

-- 查詢特定伺服器的動態設定
-- Query dynamic settings for specific server
SELECT 
    s.id as server_id,
    s.names as server_name,
    s.table_name,
    s.account_field,
    GROUP_CONCAT(
        CONCAT(sdf.field_name, ':', sdf.field_value) 
        ORDER BY sdf.sort_order 
        SEPARATOR '|'
    ) as dynamic_fields
FROM servers s
LEFT JOIN server_dynamic_field_details sdf ON s.id = sdf.server_id
WHERE s.id = ? -- 替換為特定的server_id
GROUP BY s.id;

-- 插入動態欄位資料的範例
-- Example: Insert dynamic field data
INSERT INTO server_dynamic_field_details (server_id, field_name, field_value, sort_order) 
VALUES 
(?, ?, ?, 1), -- server_id, field_name, field_value, sort_order
(?, ?, ?, 2);

-- 更新伺服器的基本動態設定
-- Update server basic dynamic settings  
UPDATE servers 
SET table_name = ?, account_field = ?, updated_at = NOW()
WHERE id = ?;

-- 刪除伺服器的所有動態欄位（用於重新設定）
-- Delete all dynamic fields for a server (for reset)
DELETE FROM server_dynamic_field_details WHERE server_id = ?;

-- 綜合查詢 - 取得伺服器完整的動態設定資訊
-- Comprehensive query - Get complete dynamic settings for server
SELECT 
    s.id,
    s.names,
    s.table_name,
    s.account_field,
    sdf.field_name,
    sdf.field_value,
    sdf.sort_order
FROM servers s
LEFT JOIN server_dynamic_field_details sdf ON s.id = sdf.server_id
WHERE s.id = ?
ORDER BY sdf.sort_order ASC;