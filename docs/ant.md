# ANT 銀行轉帳服務實作建議

## 需求分析

基於第77點的需求，要在系統中加入ANT銀行轉帳服務。ANT服務與其他第三方金流的差異在於：
- **特殊要求**：轉帳時需要提供轉帳者的銀行代號與銀行帳號
- **使用情境**：當伺服器選用ANT且玩家選擇銀行轉帳時，需額外收集使用者銀行資訊

**修正說明（第78點）**：
原先第77點提到收集"銀行代號與密碼"，經第78點修正為"銀行代號與帳號"。

## 技術架構分析

### 現有系統架構
1. **伺服器設定**: `server_add.php` 已有ANT選項設定
2. **金流處理**: 使用 `bank_funds` 資料表存儲ANT商店設定
3. **付款流程**: `index.php` → `*_next.php` → 第三方金流
4. **資料記錄**: `servers_log` 表記錄交易資訊

### ANT特有需求
- 前端需動態顯示額外輸入欄位
- 後端需處理及驗證銀行資訊
- 需建立ANT專用的處理檔案
- 資料庫需擴展以儲存使用者銀行資訊

## 實作步驟

### 1. 資料庫結構調整

**修改 servers_log 表：**
```sql
ALTER TABLE servers_log ADD COLUMN user_bank_code VARCHAR(20) NULL COMMENT '使用者銀行代號';
ALTER TABLE servers_log ADD COLUMN user_bank_account VARCHAR(50) NULL COMMENT '使用者銀行帳號';
```

**考量點（第79點修正）：**
- `user_bank_code`: 儲存銀行代號（如：700為中華郵政），預設NULL
- `user_bank_account`: 儲存使用者銀行帳號，預設NULL
- 欄位設為可NULL，因為只有ANT支付方式才會使用這些欄位
- 欄位長度根據台灣銀行系統標準設計

### 2. 前端表單修改 (index.php)

**新增ANT專用欄位區塊：**
```html
<!-- ANT 銀行轉帳專用欄位 -->
<div id="ant_bank_fields" style="display: none;">
    <div class="col-md-12 col-xs-12 padding-bottom-20">
        <input type="text" class="form-control" name="user_bank_code" id="user_bank_code" 
               placeholder="請輸入銀行代號（如：700）" maxlength="20">
    </div>
    <div class="col-md-12 col-xs-12 padding-bottom-20">
        <input type="text" class="form-control" name="user_bank_account" id="user_bank_account" 
               placeholder="請輸入銀行帳號" maxlength="50">
    </div>
</div>
```

**JavaScript動態控制邏輯：**
```javascript
// 監聽支付方式變更
$("#pt").on('change', function() {
    const payType = $(this).val();
    const serverPayBank = '<?= $datalist["pay_bank"] ?>'; // 從PHP取得伺服器設定
    
    // 當選擇銀行轉帳(2)且伺服器使用ANT時顯示額外欄位
    if (payType == '2' && serverPayBank == 'ant') {
        $('#ant_bank_fields').slideDown();
    } else {
        $('#ant_bank_fields').slideUp();
        // 清空欄位值
        $('#user_bank_code, #user_bank_account').val('');
    }
});
```

**表單驗證增強：**
```javascript
// 在表單提交前驗證ANT專用欄位
function validateAntFields() {
    const payType = $("#pt").val();
    const serverPayBank = '<?= $datalist["pay_bank"] ?>';
    
    if (payType == '2' && serverPayBank == 'ant') {
        const bankCode = $("#user_bank_code").val().trim();
        const bankAccount = $("#user_bank_account").val().trim();
        
        if (!bankCode) {
            alert('請輸入銀行代號');
            $("#user_bank_code").focus();
            return false;
        }
        
        if (!bankAccount) {
            alert('請輸入銀行帳號');
            $("#user_bank_account").focus();
            return false;
        }
        
        // 銀行代號格式驗證（3位數字）
        if (!/^\d{3}$/.test(bankCode)) {
            alert('銀行代號格式錯誤，請輸入3位數字');
            $("#user_bank_code").focus();
            return false;
        }
    }
    
    return true;
}
```

### 3. 後端處理邏輯修改 (index.php)

**修改表單提交處理：**
```php
// 收集ANT專用欄位資料（第79點修正：預設NULL）
$user_bank_code = null;
$user_bank_account = null;

if ($pt == 2 && $pay_bank == 'ant') {
    $user_bank_code = trim(_r("user_bank_code"));
    $user_bank_account = trim(_r("user_bank_account"));
    
    // 驗證必填欄位
    if (empty($user_bank_code) || empty($user_bank_account)) {
        alert("ANT銀行轉帳需要提供銀行代號與帳號", 0);
    }
    
    // 銀行代號格式驗證
    if (!preg_match('/^\d{3}$/', $user_bank_code)) {
        alert("銀行代號格式錯誤", 0);
    }
} else {
    // 非ANT支付方式時，欄位保持NULL
    $user_bank_code = null;
    $user_bank_account = null;
}

// 修改INSERT SQL語句
$input = array(
    ':foran' => $_SESSION["foran"], 
    ':forname' => $forname, 
    ':serverid' => $_SESSION["serverid"], 
    ':gameid' => $gid, 
    ':money' => $money, 
    ':bmoney' => $bmoney, 
    ':paytype' => $pt, 
    ':bi' => $bb, 
    ':userip' => $user_IP, 
    ':orderid' => $orderid, 
    ':pay_cp' => $pay_cp_check, 
    ':is_bank' => $is_bank,
    ':user_bank_code' => $user_bank_code,
    ':user_bank_account' => $user_bank_account
);

$query = $pdo->prepare("INSERT INTO servers_log (foran, forname, serverid, gameid, money, bmoney, paytype, bi, userip, orderid, pay_cp, is_bank, user_bank_code, user_bank_account) VALUES(:foran, :forname, :serverid, :gameid, :money, :bmoney, :paytype, :bi, :userip, :orderid, :pay_cp, :is_bank, :user_bank_code, :user_bank_account)");
```

### 4. 建立ANT處理檔案 (ant_next.php)

**檔案結構參考其他 *_next.php：**
```php
<?php
include("myadm/include.php");
include_once('./pay_bank.php');

// 基本驗證
if($_SESSION["foran"] == "") alert("伺服器資料錯誤-8000201。", 0);
if($_SESSION["serverid"] == "") alert("伺服器資料錯誤-8000202。", 0);
if($_SESSION["lastan"] == "") alert("伺服器資料錯誤-8000203。", 0);

$pdo = openpdo();

// 取得訂單資訊
$query = $pdo->prepare("SELECT * FROM servers_log where auton=?");
$query->execute(array($_SESSION["lastan"]));
if(!$datalist = $query->fetch()) alert("不明錯誤-8000207。", 0);
if($datalist["stats"] != 0) alert("金流狀態有誤-8000208。", 0);

// 取得ANT設定
$payment_info = getSpecificBankPaymentInfo($pdo, $_SESSION["lastan"], 'ant');

if ($payment_info && isset($payment_info['payment_config'])) {
    $ant_shop_id = $payment_info['payment_config']['merchant_id'];
    $ant_key = $payment_info['payment_config']['verify_key'];
} else {
    alert("ANT設定錯誤", 0);
}

// 取得使用者銀行資訊
$user_bank_code = $datalist["user_bank_code"];
$user_bank_account = $datalist["user_bank_account"];

// ANT API處理邏輯
// ... 根據ANT API文檔實作轉帳邏輯
?>
```

### 5. 更新重定向邏輯 (index.php)

**在switch case中新增ANT處理：**
```php
switch ($pay_cp_check) {
    case "ant":
        $redirect_url = 'ant_next.php';
        break;
    // ... 其他case
}
```

## 安全性考量

### 1. 資料保護
- **銀行帳號加密**: 雖然不是密碼，但仍屬敏感資訊，建議使用可逆加密
- **傳輸安全**: 確保使用HTTPS傳輸
- **日誌安全**: 避免在錯誤日誌中記錄完整銀行帳號

### 2. 輸入驗證
- **格式驗證**: 銀行代號必須為3位數字
- **長度限制**: 銀行帳號合理長度限制
- **特殊字元過濾**: 防止SQL注入

### 3. 存取控制
- **權限檢查**: 確認使用者有權限進行此操作
- **頻率限制**: 防止惡意大量嘗試

## 測試建議

### 1. 功能測試
- 測試不同伺服器設定下的欄位顯示邏輯
- 驗證表單提交和資料儲存
- 確認ANT與其他金流的切換正常

### 2. 安全測試
- 輸入驗證測試
- SQL注入防護測試
- XSS防護測試

### 3. 使用者體驗測試
- 欄位顯示/隱藏動畫效果
- 錯誤提示清晰度
- 表單操作流暢性

## 實作檢查清單

- [ ] 資料庫新增欄位
- [ ] 前端表單新增ANT欄位區塊
- [ ] JavaScript動態顯示邏輯
- [ ] 表單驗證邏輯
- [ ] 後端資料處理邏輯
- [ ] 建立ant_next.php檔案
- [ ] 更新重定向邏輯
- [ ] 安全性檢查
- [ ] 功能測試
- [ ] 使用者體驗測試

## 備註

此實作建議基於第77點需求，並經過以下修正：
- **第78點修正**：將收集資訊從"銀行代號與密碼"調整為"銀行代號與帳號"
- **第79點修正**：將資料庫欄位設為可NULL，因為只有ANT支付方式才會使用這些欄位

實際實作時需要：

1. 確認ANT API的具體要求和格式
2. 配合現有系統的編碼風格
3. 進行充分的測試確保功能正常
4. 注意資料安全和使用者隱私保護
5. 確保非ANT支付方式時，銀行資訊欄位保持NULL狀態

實作完成後應該要能讓使用ANT服務的伺服器，在玩家選擇銀行轉帳時正確收集和處理使用者的銀行資訊，而其他支付方式則不受影響。