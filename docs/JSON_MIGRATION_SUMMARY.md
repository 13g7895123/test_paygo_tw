# 🔄 派獎記錄 JSON 格式遷移 - 完整方案實施總結

## 📋 問題描述

**原問題**：`/myadm/api/gift_api.php` 中的 `handle_get_gift_logs` 函式使用 JSON 格式儲存道具資料，但用戶環境的 MySQL 版本不支援 JSON 資料類型。

**解決方案**：採用方案一 - 建立分離式關聯資料表，完全取代 JSON 格式。

## 🗂️ 已建立的檔案清單

### 1. 資料庫遷移檔案
- 📄 `docs/sql/send_gift_migration.sql` - 資料庫結構遷移 SQL
- 📄 `myadm/migration/migrate_gift_logs.php` - 資料遷移腳本

### 2. 更新後的應用程式代碼
- 📄 `myadm/api/gift_api_updated.php` - 修改後的 API 函式

### 3. 部署工具和文件
- 📄 `scripts/deploy_json_migration.php` - 自動化部署腳本
- 📄 `docs/deployment/json_to_relational_deployment.md` - 詳細部署指南
- 📄 `docs/JSON_MIGRATION_SUMMARY.md` - 此總結文件

## 🔧 核心修改內容

### 新增的資料表結構

```sql
-- 道具明細表（取代 JSON 格式）
CREATE TABLE send_gift_log_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_id INT NOT NULL,                    -- 關聯到 send_gift_logs.id
    item_code VARCHAR(50) NOT NULL,         -- 道具編號
    item_name VARCHAR(200) DEFAULT NULL,    -- 道具名稱
    quantity INT NOT NULL DEFAULT 1,       -- 數量
    sort_order INT DEFAULT 0,              -- 排序
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_log_id (log_id),
    INDEX idx_item_code (item_code),
    FOREIGN KEY (log_id) REFERENCES send_gift_logs(id) ON DELETE CASCADE
);

-- 原表格新增欄位
ALTER TABLE send_gift_logs
    ADD COLUMN items_backup JSON DEFAULT NULL,      -- 原 JSON 備份
    ADD COLUMN items_summary TEXT DEFAULT NULL;     -- 道具摘要文字
```

### 修改的 PHP 函式

1. **`handle_get_gift_logs()`** - 從關聯表查詢道具明細
2. **`handle_get_gift_log_detail()`** - 支援新的資料結構
3. **`handle_get_gift_execution_log()`** - 生成相同格式的 SQL
4. **`handle_send_gift_updated()`** - 直接寫入關聯表，不再使用 JSON

## 🚀 快速執行指令

### 方法一：自動化部署（推薦）

```bash
# 檢查系統並模擬執行
php scripts/deploy_json_migration.php --dry-run

# 執行完整遷移
php scripts/deploy_json_migration.php --force

# 或者保守執行（會詢問確認）
php scripts/deploy_json_migration.php
```

### 方法二：手動逐步執行

```bash
# 1. 執行資料庫結構遷移
mysql -u [username] -p[password] [database] < docs/sql/send_gift_migration.sql

# 2. 執行資料遷移
php myadm/migration/migrate_gift_logs.php migrate

# 3. 手動更新 gift_api.php（參考 gift_api_updated.php）
```

### 方法三：網頁界面執行

訪問：`https://yourdomain.com/myadm/migration/migrate_gift_logs.php`

## 📊 遷移前後對比

### 遷移前（JSON 格式）
```sql
-- 資料儲存
items: '[{"itemCode":"ITEM001","itemName":"測試道具","quantity":10}]'

-- 查詢方式
SELECT items FROM send_gift_logs WHERE id = 1;
-- 需要 PHP 中使用 json_decode() 解析
```

### 遷移後（關聯表格式）
```sql
-- 資料儲存
send_gift_logs: items_summary = '測試道具 x10'
send_gift_log_items:
  - log_id=1, item_code='ITEM001', item_name='測試道具', quantity=10

-- 查詢方式
SELECT sgl.*, sgld.item_code, sgld.item_name, sgld.quantity
FROM send_gift_logs sgl
LEFT JOIN send_gift_log_items sgld ON sgl.id = sgld.log_id
WHERE sgl.id = 1;
```

## ✅ 向後兼容性

新的代碼包含向後兼容邏輯：

```php
// 優先從新表格查詢
$items = get_from_relational_table($log_id);

// 如果新表格無資料，回退到 JSON 解析
if (empty($items) && !empty($legacy_json)) {
    $items = json_decode($legacy_json, true);
}
```

## 🔍 驗證檢查清單

遷移完成後，請檢查以下項目：

- [ ] 資料庫中存在 `send_gift_log_items` 表
- [ ] `migration_status` 表顯示狀態為 'completed'
- [ ] 原有派獎記錄可正常查看
- [ ] 新派獎功能正常運作
- [ ] 道具明細顯示正確
- [ ] 執行記錄和 SQL 生成功能正常

### 驗證 SQL
```sql
-- 檢查遷移狀態
SELECT * FROM migration_status WHERE migration_name = 'json_to_relational_gift_logs';

-- 檢查資料完整性
SELECT
    (SELECT COUNT(*) FROM send_gift_logs WHERE items_backup IS NOT NULL) as original_count,
    (SELECT COUNT(*) FROM send_gift_logs WHERE items_summary IS NOT NULL) as migrated_count,
    (SELECT COUNT(*) FROM send_gift_log_items) as detail_count;

-- 測試關聯查詢
SELECT sgl.id, sgl.game_account, sgl.items_summary,
       GROUP_CONCAT(CONCAT(sgld.item_name, ' x', sgld.quantity)) as items_detail
FROM send_gift_logs sgl
LEFT JOIN send_gift_log_items sgld ON sgl.id = sgld.log_id
GROUP BY sgl.id
LIMIT 5;
```

## 🔧 手動代碼更新步驟

如果不使用自動化部署，需要手動更新 `myadm/api/gift_api.php`：

1. **備份原檔案**：
   ```bash
   cp myadm/api/gift_api.php myadm/api/gift_api_backup.php
   ```

2. **替換函式**：從 `gift_api_updated.php` 複製以下函式到 `gift_api.php`：
   - `handle_get_gift_logs()`
   - `handle_get_gift_log_detail()`
   - `handle_get_gift_execution_log()`
   - `handle_send_gift_updated()`
   - `get_gift_log_items()` （新增的輔助函式）

3. **修改 switch case**：
   ```php
   case 'send_gift':
       handle_send_gift_updated($pdo);  // 使用新函式
       break;
   ```

## 🗑️ 清理作業（可選）

當確認遷移完全成功且系統運行穩定後，可以執行清理：

```sql
-- 移除 JSON 備份欄位（不可逆！）
ALTER TABLE send_gift_logs DROP COLUMN items_backup;

-- 移除備份表（如有）
DROP TABLE send_gift_logs_backup_[timestamp];
```

## 🔄 回滾方案

如果遷移失敗，可以執行回滾：

```sql
-- 恢復原始結構
DROP TABLE send_gift_log_items;
DROP TABLE migration_status;

-- 恢復原始資料（如有備份表）
DROP TABLE send_gift_logs;
RENAME TABLE send_gift_logs_backup_[timestamp] TO send_gift_logs;
```

```bash
# 恢復原始 PHP 檔案
cp myadm/api/gift_api_backup.php myadm/api/gift_api.php
```

## 📞 技術支援

如遇到問題，請檢查：

1. **遷移日誌**：查看 `migrate_gift_logs.php` 的輸出
2. **資料庫錯誤**：檢查 MySQL 錯誤日誌
3. **PHP 錯誤**：檢查 PHP 錯誤日誌
4. **遷移狀態**：查詢 `migration_status` 表

保留這些資訊以便進一步診斷問題。

---

## 🎉 預期效益

遷移完成後，您將獲得：

- ✅ **兼容性**：支援所有 MySQL 5.6+ 版本
- ✅ **效能**：更快的查詢速度和更好的索引支援
- ✅ **維護性**：清晰的關聯式資料結構
- ✅ **擴展性**：更容易新增道具相關功能
- ✅ **穩定性**：移除對 JSON 功能的依賴

**遷移已完成準備，請按照上述步驟執行即可！** 🚀