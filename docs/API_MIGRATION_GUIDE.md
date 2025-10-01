# 🌐 API 介面遷移指南

由於您無法直接在正式環境執行 PHP 檔案，我們為您準備了完整的 API 介面來執行 JSON 格式遷移。

## 📁 已建立的 API 檔案

### 1. 獨立遷移 API
- **檔案位置**：`myadm/api/migration_api.php`
- **用途**：專門的遷移管理 API
- **訪問方式**：`https://yourdomain.com/myadm/api/migration_api.php`

### 2. 網頁管理介面
- **檔案位置**：`myadm/migration/migration_manager.html`
- **用途**：完整的網頁操作介面
- **訪問方式**：`https://yourdomain.com/myadm/migration/migration_manager.html`

### 3. gift_api.php 擴展
- **檔案位置**：`myadm/api/gift_api_migration_extension.php`
- **用途**：整合到現有 gift_api.php 的遷移功能

## 🚀 推薦使用方式

### 方式一：網頁管理介面（最簡單）

**直接訪問網頁介面**：
```
https://yourdomain.com/myadm/migration/migration_manager.html
```

這個介面提供：
- ✅ 系統需求檢查
- ✅ 遷移狀態監控
- ✅ 一鍵完整遷移
- ✅ 步驟式遷移
- ✅ 即時進度顯示
- ✅ 操作日誌
- ✅ 回滾功能

### 方式二：API 呼叫

#### 2.1 使用獨立遷移 API

**基礎 URL**：`https://yourdomain.com/myadm/api/migration_api.php`

**API 端點列表**：

```javascript
// 1. 檢查系統需求
fetch('migration_api.php?action=check_requirements', {
    method: 'GET',
    credentials: 'include'
});

// 2. 檢查遷移狀態
fetch('migration_api.php?action=check_status', {
    method: 'GET',
    credentials: 'include'
});

// 3. 備份資料
fetch('migration_api.php', {
    method: 'POST',
    body: new FormData([['action', 'backup_data']]),
    credentials: 'include'
});

// 4. 建立資料表結構
fetch('migration_api.php', {
    method: 'POST',
    body: new FormData([['action', 'create_tables']]),
    credentials: 'include'
});

// 5. 執行資料遷移
fetch('migration_api.php', {
    method: 'POST',
    body: new FormData([['action', 'migrate_data']]),
    credentials: 'include'
});

// 6. 驗證遷移結果
fetch('migration_api.php', {
    method: 'POST',
    body: new FormData([['action', 'validate_migration']]),
    credentials: 'include'
});

// 7. 一鍵完整遷移
fetch('migration_api.php', {
    method: 'POST',
    body: new FormData([['action', 'full_migration']]),
    credentials: 'include'
});
```

#### 2.2 使用現有 gift_api.php（需先整合）

如果您想在現有的 `gift_api.php` 中使用遷移功能：

1. **整合擴展檔案**：
   將 `gift_api_migration_extension.php` 的內容整合到 `gift_api.php`

2. **API 呼叫**：
```javascript
// 基礎 URL：https://yourdomain.com/myadm/api/gift_api.php

// 檢查需求
fetch('gift_api.php?action=migration_check_requirements');

// 檢查狀態
fetch('gift_api.php?action=migration_check_status');

// 備份資料
fetch('gift_api.php', {
    method: 'POST',
    body: new FormData([['action', 'migration_backup_data']])
});

// 建立結構
fetch('gift_api.php', {
    method: 'POST',
    body: new FormData([['action', 'migration_create_structure']])
});

// 執行遷移
fetch('gift_api.php', {
    method: 'POST',
    body: new FormData([['action', 'migration_execute']])
});

// 驗證結果
fetch('gift_api.php', {
    method: 'POST',
    body: new FormData([['action', 'migration_validate']])
});

// 一鍵遷移
fetch('gift_api.php', {
    method: 'POST',
    body: new FormData([['action', 'migration_full']])
});
```

## 📋 完整遷移流程

### 步驟一：檢查系統需求

**請求**：
```javascript
const checkRequirements = async () => {
    const response = await fetch('migration_api.php?action=check_requirements', {
        credentials: 'include'
    });
    const result = await response.json();

    if (result.success && result.data.all_requirements_passed) {
        console.log('✅ 系統需求檢查通過');
        return true;
    } else {
        console.log('❌ 系統需求檢查失敗');
        return false;
    }
};
```

### 步驟二：檢查當前狀態

```javascript
const checkStatus = async () => {
    const response = await fetch('migration_api.php?action=check_status', {
        credentials: 'include'
    });
    const result = await response.json();

    if (result.data.migration_completed) {
        console.log('✅ 遷移已完成');
        return 'completed';
    } else {
        console.log('⏳ 遷移尚未完成');
        return 'pending';
    }
};
```

### 步驟三：執行一鍵遷移

```javascript
const fullMigration = async () => {
    try {
        const formData = new FormData();
        formData.append('action', 'full_migration');

        const response = await fetch('migration_api.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });

        const result = await response.json();

        if (result.success) {
            console.log('🎉 遷移完成！', result.message);
            return true;
        } else {
            console.error('❌ 遷移失敗：', result.message);
            return false;
        }
    } catch (error) {
        console.error('❌ API 呼叫失敗：', error);
        return false;
    }
};
```

## 🔧 API 響應格式

所有 API 都回傳統一的 JSON 格式：

```json
{
    "success": true|false,
    "message": "操作結果訊息",
    "data": {
        // 具體的資料內容
    },
    "timestamp": "2024-01-01T12:00:00+00:00"
}
```

### 成功響應範例

```json
{
    "success": true,
    "message": "Migration completed successfully",
    "data": {
        "records_processed": 150,
        "total_records": 150,
        "backup_table": "send_gift_logs_backup_20240101_120000"
    },
    "timestamp": "2024-01-01T12:00:00+00:00"
}
```

### 錯誤響應範例

```json
{
    "success": false,
    "message": "Access denied: Only administrators can perform migration operations",
    "timestamp": "2024-01-01T12:00:00+00:00"
}
```

## 🔐 權限要求

**重要**：所有遷移 API 都需要管理員權限

- 必須以管理員身份登入系統
- `$_SESSION["adminid"]` 必須存在且不為空
- 一般用戶無法執行遷移操作

## 🛡️ 安全考量

1. **權限檢查**：所有 API 都有嚴格的權限檢查
2. **CSRF 保護**：使用 credentials: 'include' 確保 Session 正確
3. **操作日誌**：所有操作都會記錄到系統日誌
4. **交易安全**：資料遷移使用資料庫交易，確保一致性

## 📊 監控和日誌

### 檢查遷移進度

```javascript
const checkProgress = async () => {
    const response = await fetch('migration_api.php?action=check_status');
    const result = await response.json();

    if (result.data.migration_status) {
        const status = result.data.migration_status;
        console.log(`狀態：${status.status}`);
        console.log(`處理記錄數：${status.records_processed}`);

        if (status.error_message) {
            console.error(`錯誤：${status.error_message}`);
        }
    }
};

// 定期檢查進度
const monitorProgress = () => {
    const interval = setInterval(async () => {
        const status = await checkProgress();

        if (status === 'completed' || status === 'failed') {
            clearInterval(interval);
        }
    }, 5000); // 每5秒檢查一次
};
```

## 🔄 回滾操作

如果遷移失敗或需要回滾：

```javascript
const rollback = async (backupTable) => {
    const formData = new FormData();
    formData.append('action', 'rollback');
    formData.append('backup_table', backupTable);

    const response = await fetch('migration_api.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    });

    const result = await response.json();

    if (result.success) {
        console.log('✅ 回滾完成');
    } else {
        console.error('❌ 回滾失敗：', result.message);
    }
};
```

## 📞 故障排除

### 常見問題

**Q: API 回傳 403 錯誤**
A: 確保已以管理員身份登入系統

**Q: API 回傳 500 錯誤**
A: 檢查伺服器錯誤日誌，可能是資料庫連線或權限問題

**Q: 遷移卡在 'running' 狀態**
A: 檢查是否有 PHP 執行時間限制，或資料庫鎖定問題

**Q: 前端無法呼叫 API**
A: 確保 CORS 設定正確，使用 `credentials: 'include'`

### 偵錯技巧

1. **檢查瀏覽器 Network 標籤**：查看 API 請求和響應
2. **檢查 Console**：查看 JavaScript 錯誤
3. **檢查伺服器日誌**：查看 PHP 錯誤日誌
4. **檢查資料庫**：查看 `migration_status` 表的狀態

## 🎯 總結

您現在有三種方式可以執行遷移：

1. **🌐 網頁介面**（推薦）：訪問 `migration_manager.html`
2. **🔌 獨立 API**：使用 `migration_api.php`
3. **🔧 整合 API**：整合到現有的 `gift_api.php`

建議先使用**網頁介面**進行遷移，它提供最佳的用戶體驗和即時反饋。如果需要自動化或整合到其他系統，再使用 API 介面。

**立即開始**：
```
https://yourdomain.com/myadm/migration/migration_manager.html
```

遷移成功後，您的系統將完全兼容所有 MySQL 版本，不再依賴 JSON 資料類型！ 🎉