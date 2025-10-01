-- ===============================================
-- 派獎記錄 JSON 格式遷移到關聯式資料表
-- 此腳本會將 JSON 格式的道具資料遷移到分離的資料表
-- ===============================================

-- 1. 建立新的道具明細表
CREATE TABLE send_gift_log_items (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '流水號',
    log_id INT NOT NULL COMMENT '派獎記錄ID',
    item_code VARCHAR(50) NOT NULL COMMENT '道具編號',
    item_name VARCHAR(200) DEFAULT NULL COMMENT '道具名稱',
    quantity INT NOT NULL DEFAULT 1 COMMENT '數量',
    sort_order INT DEFAULT 0 COMMENT '排序順序',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',

    -- 索引
    INDEX idx_log_id (log_id),
    INDEX idx_item_code (item_code),
    INDEX idx_log_item (log_id, item_code),

    -- 外鍵約束
    FOREIGN KEY (log_id) REFERENCES send_gift_logs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='派獎記錄道具明細表';

-- 2. 修改原有資料表 - 備份JSON並新增摘要欄位
ALTER TABLE send_gift_logs
    ADD COLUMN items_backup JSON DEFAULT NULL COMMENT 'JSON備份(遷移後可刪除)' AFTER items,
    ADD COLUMN items_summary TEXT DEFAULT NULL COMMENT '道具摘要文字' AFTER items_backup;

-- 先將現有的 items 資料備份到 items_backup
UPDATE send_gift_logs SET items_backup = items WHERE items IS NOT NULL;

-- 3. 建立遷移狀態追蹤表（可選）
CREATE TABLE migration_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration_name VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending',
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    error_message TEXT,
    records_processed INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='遷移狀態追蹤表';

-- 插入遷移記錄
INSERT INTO migration_status (migration_name, status)
VALUES ('json_to_relational_gift_logs', 'pending');