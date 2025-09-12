-- 派獎功能相關資料表

-- 派獎設定資料表
-- 用於存儲伺服器的派獎設定資訊，包含資料表名稱、帳號欄位
CREATE TABLE send_gift_settings (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '流水號',
    server_id INT NOT NULL COMMENT '伺服器ID (對應servers表的id)',
    table_name VARCHAR(100) NOT NULL COMMENT '資料表名稱',
    account_field VARCHAR(100) NOT NULL COMMENT '帳號欄位名稱',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    
    INDEX idx_server_id (server_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='派獎設定主表';

-- 動態欄位設定資料表
-- 用於存儲每個伺服器的動態欄位名稱和對應資料
CREATE TABLE send_gift_fields (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '流水號',
    server_id INT NOT NULL COMMENT '伺服器ID (對應servers表的id)',
    field_name VARCHAR(100) NOT NULL COMMENT '欄位名稱',
    field_value VARCHAR(255) NOT NULL COMMENT '欄位資料/值',
    sort_order INT DEFAULT 0 COMMENT '排序順序',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    
    INDEX idx_server_id (server_id),
    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='派獎動態欄位設定表';

-- 派獎記錄資料表
-- 用於記錄所有派獎操作的詳細資訊
CREATE TABLE send_gift_logs (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '流水號',
    server_id INT NOT NULL COMMENT '伺服器ID',
    server_name VARCHAR(100) NOT NULL COMMENT '伺服器名稱',
    game_account VARCHAR(100) NOT NULL COMMENT '遊戲帳號',
    items JSON NOT NULL COMMENT '物品資訊(JSON格式)',
    total_items INT DEFAULT 0 COMMENT '物品總數量',
    status ENUM('pending', 'success', 'failed') DEFAULT 'pending' COMMENT '狀態：pending-等待中, success-成功, failed-失敗',
    error_message TEXT COMMENT '錯誤訊息',
    operator_id INT COMMENT '操作者ID',
    operator_name VARCHAR(100) COMMENT '操作者名稱',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    
    INDEX idx_server_id (server_id),
    INDEX idx_game_account (game_account),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='派獎操作記錄表';

-- 物品管理資料表
-- 用於存儲各伺服器可用的物品清單
CREATE TABLE server_items (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '流水號',
    server_id INT NOT NULL COMMENT '伺服器ID (對應servers表的id)',
    game_name VARCHAR(100) NOT NULL COMMENT '物品遊戲名稱',
    database_name VARCHAR(100) NOT NULL COMMENT '物品資料庫名稱',
    is_active TINYINT(1) DEFAULT 1 COMMENT '是否啟用：1-啟用, 0-停用',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    
    INDEX idx_server_id (server_id),
    INDEX idx_database_name (database_name),
    INDEX idx_is_active (is_active),
    UNIQUE KEY unique_server_database (server_id, database_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='伺服器物品管理表';

-- 查詢範例SQL

-- 1. 查詢特定伺服器的完整派獎設定
-- SELECT 
--     s.id as server_id,
--     s.forname as server_name,
--     sgs.table_name,
--     sgs.account_field,
--     sgf.field_name,
--     sgf.field_value,
--     sgf.sort_order
-- FROM servers s
-- LEFT JOIN send_gift_settings sgs ON s.id = sgs.server_id
-- LEFT JOIN send_gift_fields sgf ON s.id = sgf.server_id
-- WHERE s.id = ? 
-- ORDER BY sgf.sort_order;

-- 2. 查詢特定伺服器的物品清單
-- SELECT 
--     si.id,
--     si.game_name,
--     si.database_name,
--     si.is_active,
--     si.created_at
-- FROM server_items si
-- WHERE si.server_id = ? AND si.is_active = 1
-- ORDER BY si.game_name;

-- 3. 查詢派獎記錄
-- SELECT 
--     sgl.id,
--     sgl.server_name,
--     sgl.game_account,
--     sgl.items,
--     sgl.total_items,
--     sgl.status,
--     sgl.created_at
-- FROM send_gift_logs sgl
-- WHERE sgl.server_id = ?
-- ORDER BY sgl.created_at DESC
-- LIMIT 50;