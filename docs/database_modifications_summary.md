# ANT 支付系統資料庫修改記錄
# ANT Payment System Database Modifications

本文件記錄 Points 88-90 中實際修改的資料庫結構變更。

## 📊 實際修改項目

### 1. ANT 支付系統相關修改

#### 1.1 現有表格欄位修改

**servers_log 表**
- 新增欄位：
  - `user_bank_code` VARCHAR(20) NULL COMMENT 'ANT使用者銀行代號'
  - `user_bank_account` VARCHAR(50) NULL COMMENT 'ANT使用者銀行帳號'
  - `third_party_order_id` VARCHAR(50) NULL COMMENT 'ANT系統訂單編號'

- 新增索引：
  ```sql
  CREATE INDEX idx_servers_log_orderid ON servers_log(orderid);
  CREATE INDEX idx_servers_log_pay_cp ON servers_log(pay_cp);
  CREATE INDEX idx_servers_log_stats ON servers_log(stats);
  ```

#### 1.2 新建表格

**ant_callback_logs** - ANT回調記錄表
```sql
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
```

**ant_transaction_logs** - ANT交易API調用日誌
```sql
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
```

**ant_status_history** - ANT支付狀態變更歷史
```sql
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
```

**ant_refunds** - ANT退款記錄表
```sql
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
```

## 🔧 執行優先順序

### 第一階段：基礎 ANT 支付功能
1. 執行 `docs/sql/ant_database_updates.sql` 中的基礎修改
2. 更新 `servers_log` 表新增 ANT 相關欄位
3. 建立必要的索引以提升查詢效能

### 第二階段：ANT 日誌記錄功能
1. 建立 `ant_callback_logs` 表
2. 建立 `ant_transaction_logs` 表
3. 建立 `ant_status_history` 表
4. 建立 `ant_refunds` 表（可選）

### 第三階段：測試與驗證
1. 測試 ANT API 整合功能
2. 確認回調記錄功能運作正常
3. 驗證狀態更新和日誌記錄

## ⚠️ 注意事項

1. **備份資料庫**：執行任何修改前請務必備份現有資料庫
2. **測試環境**：建議先在測試環境執行所有修改
3. **外鍵約束**：ANT 相關表格都有外鍵關聯到 servers_log 表
4. **索引效能**：新增索引後需要重新分析查詢效能
5. **API 測試**：使用 `ant_order_test.php` 測試 ANT API 功能

## 📝 ANT 支付狀態對應

| ANT 狀態碼 | ANT 說明 | 本地狀態 | 本地說明 |
|-----------|----------|---------|----------|
| 1 | 已建立 | 1 | 處理中 |
| 2 | 處理中 | 1 | 處理中 |
| 3 | 待繳費 | 1 | 處理中 |
| 4 | 已完成 | 2 | 支付成功 |
| 5 | 已取消 | -2 | 支付取消 |
| 6 | 已退款 | -4 | 已退款 |
| 7 | 金額不符合 | -1 | 支付失敗 |
| 8 | 銀行不符合 | -1 | 支付失敗 |

## 🎯 相關檔案

### 資料庫 SQL 檔案
- `docs/sql/ant_database_updates.sql` - ANT 資料庫更新腳本

### API 整合檔案
- `ant_api_service.php` - ANT API 服務類別
- `ant_next.php` - ANT 支付流程
- `ant_status_check.php` - ANT 狀態查詢
- `ant_callback.php` - ANT 回調處理
- `ant_order_test.php` - ANT 測試工具

### 文檔檔案
- `docs/ant-api-analysis.md` - ANT API 分析報告
- `docs/ant_order_test_workflow.md` - ANT 測試工具執行流程

---

*本文件記錄 Points 88-90 中實際修改的 ANT 支付系統資料庫結構變更。派獎功能和動態欄位功能未在此次修改中包含。*