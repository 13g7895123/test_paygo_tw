# 派獎記錄 JSON 格式遷移部署指南

此文件詳細說明如何將派獎記錄系統從 JSON 格式遷移到關聯式資料表格式，以解決 MySQL 版本兼容性問題。

## 📋 遷移概述

**問題**：現有系統使用 JSON 資料類型儲存道具資訊，但某些 MySQL 環境不支援 JSON 格式。

**解決方案**：將 JSON 格式改為分離的關聯式資料表，提供更好的兼容性和查詢效能。

## 🔧 準備工作

### 1. 檢查系統需求
- MySQL 5.6+ （不需要 JSON 支援）
- PHP 7.0+
- 充足的磁碟空間（約為現有資料的 1.5 倍）

### 2. 備份現有資料
```sql
-- 備份原始資料表
CREATE TABLE send_gift_logs_backup AS SELECT * FROM send_gift_logs;

-- 驗證備份
SELECT COUNT(*) FROM send_gift_logs_backup;
```

### 3. 停止相關服務（建議）
停止所有可能影響派獎記錄的服務，避免在遷移過程中產生新資料。

## 📝 部署步驟

### 第一步：建立新的資料表結構

```bash
# 執行資料庫結構遷移
mysql -u [username] -p[password] [database_name] < docs/sql/send_gift_migration.sql
```

或透過 phpMyAdmin 等工具執行 `docs/sql/send_gift_migration.sql`

執行後會建立：
- `send_gift_log_items` 表（道具明細表）
- `migration_status` 表（遷移狀態追蹤）
- `send_gift_logs` 表增加 `items_backup` 和 `items_summary` 欄位

### 第二步：執行資料遷移

#### 選項 A：網頁界面執行（推薦）
1. 訪問：`https://yourdomain.com/myadm/migration/migrate_gift_logs.php`
2. 點擊「開始遷移」按鈕
3. 等待遷移完成（會顯示即時進度）

#### 選項 B：命令行執行
```bash
cd /path/to/your/project/myadm/migration/
php migrate_gift_logs.php migrate
```

#### 遷移過程說明
遷移腳本會：
1. 檢查現有資料完整性
2. 分批處理 JSON 資料（每批 100 筆）
3. 將道具資料插入新的明細表
4. 生成易讀的道具摘要文字
5. 驗證遷移結果
6. 更新遷移狀態

### 第三步：更新應用程式代碼

#### 1. 備份現有 PHP 檔案
```bash
cp myadm/api/gift_api.php myadm/api/gift_api_original_backup.php
```

#### 2. 更新 gift_api.php
從 `gift_api_updated.php` 複製以下函式到 `gift_api.php`：
- `handle_get_gift_logs()`
- `handle_get_gift_log_detail()`
- `handle_get_gift_execution_log()`
- `handle_send_gift_updated()` （替換原有的 `handle_send_gift()`）

#### 3. 在 gift_api.php 中修改 switch case
```php
// 在 switch case 中替換
case 'send_gift':
    handle_send_gift_updated($pdo);  // 使用新的函式
    break;
```

### 第四步：驗證遷移結果

#### 1. 資料完整性檢查
```sql
-- 檢查遷移記錄數
SELECT
    (SELECT COUNT(*) FROM send_gift_logs WHERE items_backup IS NOT NULL) as original_records,
    (SELECT COUNT(*) FROM send_gift_logs WHERE items_summary IS NOT NULL) as migrated_records,
    (SELECT COUNT(*) FROM send_gift_log_items) as detail_records;

-- 檢查遷移狀態
SELECT * FROM migration_status WHERE migration_name = 'json_to_relational_gift_logs';
```

#### 2. 功能測試
1. 測試派獎記錄查詢功能
2. 測試新派獎功能
3. 檢查派獎記錄詳情頁面
4. 驗證執行記錄和 SQL 生成功能

#### 3. 效能測試
```sql
-- 測試查詢效能
EXPLAIN SELECT sgl.*, GROUP_CONCAT(CONCAT(sgld.item_name, ' x', sgld.quantity)) as items_preview
FROM send_gift_logs sgl
LEFT JOIN send_gift_log_items sgld ON sgl.id = sgld.log_id
WHERE sgl.server_id = 'TEST_SERVER'
GROUP BY sgl.id
ORDER BY sgl.created_at DESC
LIMIT 20;
```

## 🔄 回滾計劃

如果遷移失敗或發現問題，可以執行回滾：

### 1. 恢復原始資料表結構
```sql
-- 回滾資料表結構（注意：這會清除新結構）
DROP TABLE IF EXISTS send_gift_log_items;
DROP TABLE IF EXISTS migration_status;

-- 恢復原始資料
DROP TABLE send_gift_logs;
CREATE TABLE send_gift_logs AS SELECT * FROM send_gift_logs_backup;

-- 重新建立索引（如需要）
ALTER TABLE send_gift_logs ADD PRIMARY KEY (id);
```

### 2. 恢復原始 PHP 代碼
```bash
cp myadm/api/gift_api_original_backup.php myadm/api/gift_api.php
```

## 🧹 清理作業（遷移成功後執行）

當確認遷移完全成功且系統運行穩定後，可以清理不需要的資料：

### 1. 移除備份欄位
```sql
-- 移除 JSON 備份欄位（不可逆操作！）
ALTER TABLE send_gift_logs DROP COLUMN items_backup;
```

### 2. 移除舊的備份表
```sql
-- 清除備份表（請確保不再需要）
DROP TABLE send_gift_logs_backup;
```

### 3. 清理暫存檔案
```bash
rm myadm/api/gift_api_original_backup.php
rm myadm/api/gift_api_updated.php
```

## 📊 遷移後的效益

### 1. 兼容性改善
- ✅ 支援所有 MySQL 5.6+ 版本
- ✅ 移除對 JSON 資料類型的依賴
- ✅ 提升跨平台兼容性

### 2. 效能優化
- ✅ 支援道具欄位索引查詢
- ✅ 更有效的分頁查詢
- ✅ 減少 JSON 解析開銷

### 3. 維護性提升
- ✅ 清晰的關聯式資料結構
- ✅ 支援複雜的 SQL 查詢
- ✅ 更好的資料完整性約束

## ⚠️ 注意事項

1. **資料一致性**：遷移過程中避免新增派獎記錄
2. **備份重要性**：務必在開始前完整備份資料庫
3. **測試環境**：建議先在測試環境完整測試遷移流程
4. **監控觀察**：遷移後持續觀察系統穩定性
5. **向後兼容**：新代碼包含向後兼容邏輯，可以處理未遷移的舊資料

## 🆘 故障排除

### 常見問題

**Q: 遷移過程中出現 "表格不存在" 錯誤**
A: 確保已正確執行 `send_gift_migration.sql`

**Q: JSON 解析失敗**
A: 檢查 `items_backup` 欄位中是否有無效的 JSON 資料

**Q: 遷移後查詢變慢**
A: 檢查是否正確建立了索引，考慮增加額外索引

**Q: 前端顯示格式錯誤**
A: 確認新的 API 回傳格式與前端期望的格式一致

### 聯繫支援
如遇到其他問題，請保留：
- 遷移日誌
- 錯誤訊息
- `migration_status` 表的內容
- 系統環境資訊

---
**遷移完成後，請保留此文件以備將來參考。**