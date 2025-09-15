-- Fix server_items table column names
-- This changes game_name -> item_code and database_name -> item_name

-- First, check current table structure:
-- DESCRIBE server_items;

-- Rename the columns to match the new naming convention
ALTER TABLE server_items
CHANGE COLUMN game_name item_code VARCHAR(100) NOT NULL COMMENT '道具編號',
CHANGE COLUMN database_name item_name VARCHAR(100) COMMENT '道具名稱';

-- Verify the changes
-- DESCRIBE server_items;

-- Optional: Check existing data after the change
-- SELECT id, server_id, item_code, item_name, is_active FROM server_items LIMIT 5;