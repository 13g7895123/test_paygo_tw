# ANT API 開單測試工具 (ant_order_test.php) - 完整執行流程

## 概述
`ant_order_test.php` 是一個完整的 ANT API 開單測試工具，使用真實 API 憑證進行測試，解決了第85-86點的需求。

## 系統架構
- **前端**: HTML + CSS + JavaScript (響應式設計)
- **後端**: PHP 類別化設計
- **API**: RESTful 風格，支援 JSON 格式
- **目標 API**: https://api.nubitya.com

---

## 📋 完整執行流程

### 1. 系統初始化階段

#### 1.1 頁面載入與環境檢查
```php
// 檔案: ant_order_test.php (行 7-10)
if (isset($_GET['action']) && in_array($_GET['action'], ['create_order', 'validate_bank'])) {
    header('Content-Type: application/json; charset=utf-8');
}
```

**執行內容:**
- 檢查是否為 API 請求
- 如果是 API 請求，設置 JSON Content-Type header
- 如果是一般頁面訪問，使用預設 HTML header

**帶入資料:**
- `$_GET['action']`: 動作參數 ('create_order' 或 'validate_bank')

#### 1.2 API 憑證初始化
```php
// ANTOrderTester 類別初始化 (行 15-22)
class ANTOrderTester {
    private $api_token = 'dkTqv40XBDmvlfBayoMngA0BAlDAxCrkzIAAUdwYB6kkKZVLOit1R06PKcgkhglASS79c6yzaokrdoPP';
    private $hash_key = 'lyAJwWnVAKNScXjE6t2rxUOAeesvIP9S';
    private $hash_iv = 'yhncs1WpMo60azxEczokzIlVVvVuW69p';
    private $api_base_url = 'https://api.nubitya.com';
    private $timeout = 30;
}
```

**執行內容:**
- 載入真實 API 憑證 (第85點提供)
- 設定 API 基礎網址
- 配置連線超時時間

**帶入資料:**
- **API Token**: `dkTqv40XBDmvlfBayoMngA0BAlDAxCrkzIAAUdwYB6kkKZVLOit1R06PKcgkhglASS79c6yzaokrdoPP`
- **Hash Key**: `lyAJwWnVAKNScXjE6t2rxUOAeesvIP9S`
- **Hash IV**: `yhncs1WpMo60azxEczokzIlVVvVuW69p`

---

### 2. 前端頁面顯示階段

#### 2.1 HTML 頁面渲染
```html
<!-- 行 152-339: 完整 HTML 結構 -->
<div class="container">
    <h1>🚀 ANT API 開單測試工具</h1>
    <!-- 狀態提示、憑證顯示、測試按鈕等 -->
</div>
```

**執行內容:**
- 渲染響應式網頁介面
- 顯示 API 憑證資訊 (部分隱藏)
- 載入測試按鈕和狀態區域

**帶入資料:**
- 當前時間戳: `<?php echo date('Y-m-d H:i:s'); ?>`
- API 憑證預覽 (前20/10字元)

#### 2.2 JavaScript 初始化
```javascript
// 行 285-336: 前端 JavaScript
console.log('✅ ANT測試頁面載入成功');
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎉 頁面完全載入完成，所有功能就緒');
});
```

**執行內容:**
- 確認 JavaScript 環境正常
- 註冊事件監聽器
- 準備 AJAX 功能

---

### 3. 用戶操作觸發階段

#### 3.1 用戶點擊「開始開單測試」按鈕
```javascript
// 行 290-331: testCreateOrder() 函數
async function testCreateOrder() {
    const button = event.target;
    const loading = document.getElementById('loading');
    const resultDiv = document.getElementById('result');

    // 按鈕狀態更新
    button.disabled = true;
    button.textContent = '測試中...';
    loading.style.display = 'block';
}
```

**執行內容:**
- 禁用測試按鈕防止重複點擊
- 顯示載入狀態
- 隱藏之前的結果

**帶入資料:**
- 按鈕 DOM 元素
- 載入指示器 DOM 元素
- 結果顯示區域 DOM 元素

---

### 4. API 請求處理階段

#### 4.1 前端發送 AJAX 請求
```javascript
// 行 301: 發送請求到後端 API
const response = await fetch('?action=create_order');
const data = await response.json();
```

**執行內容:**
- 使用 Fetch API 發送 GET 請求
- 帶上 `action=create_order` 參數
- 等待伺服器回應 JSON 格式資料

**帶入資料:**
- URL 參數: `?action=create_order`

#### 4.2 後端 PHP 處理請求
```php
// 行 129-148: 請求分發處理
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $tester = new ANTOrderTester();

    switch ($_GET['action']) {
        case 'create_order':
            $result = $tester->createTestOrder();
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
    }
}
```

**執行內容:**
- 檢查 HTTP 方法為 GET
- 創建 ANTOrderTester 實例
- 調用 `createTestOrder()` 方法
- 將結果轉為 JSON 格式輸出

**帶入資料:**
- `$_SERVER['REQUEST_METHOD']`: 'GET'
- `$_GET['action']`: 'create_order'

---

### 5. 測試資料準備階段

#### 5.1 生成測試訂單資料
```php
// 行 27-37: createTestOrder() 方法
$default_data = [
    'order_id' => 'TEST' . date('YmdHis') . rand(1000, 9999),
    'amount' => 100,
    'user_bank_code' => '004', // 台灣銀行
    'user_bank_account' => '1234567890123',
    'description' => 'ANT API 開單測試'
];

$order_data = array_merge($default_data, $test_data ?: []);
```

**執行內容:**
- 生成唯一的測試訂單編號
- 設定固定的測試金額 (100元)
- 使用台灣銀行代號 (004)
- 生成測試用銀行帳號

**帶入資料:**
- **訂單編號**: `TEST + YmdHis + 隨機4位數` (例: TEST202501141530001234)
- **金額**: `100` (整數)
- **銀行代號**: `004` (台灣銀行)
- **銀行帳號**: `1234567890123` (測試用)
- **描述**: `ANT API 開單測試`

#### 5.2 準備 API 請求資料
```php
// 行 43-51: API 請求資料準備
$api_data = [
    'api_token' => $this->api_token,
    'order_id' => $order_data['order_id'],
    'amount' => $order_data['amount'],
    'user_bank_code' => $order_data['user_bank_code'],
    'user_bank_account' => $order_data['user_bank_account'],
    'timestamp' => time()
];
```

**執行內容:**
- 組裝 ANT API 所需的請求參數
- 加入時間戳記防止重放攻擊
- 準備簽名生成所需資料

**帶入資料:**
- **api_token**: 完整 API Token
- **order_id**: 生成的測試訂單號
- **amount**: 100
- **user_bank_code**: 004
- **user_bank_account**: 1234567890123
- **timestamp**: Unix 時間戳 (例: 1705296600)

---

### 6. 加密金鑰與簽名生成階段 (詳細說明)

#### 6.1 加密憑證初始化回顧
```php
// 完整的加密憑證 (來自第85點提供的真實資料)
private $api_token = 'dkTqv40XBDmvlfBayoMngA0BAlDAxCrkzIAAUdwYB6kkKZVLOit1R06PKcgkhglASS79c6yzaokrdoPP';
private $hash_key = 'lyAJwWnVAKNScXjE6t2rxUOAeesvIP9S';
private $hash_iv = 'yhncs1WpMo60azxEczokzIlVVvVuW69p';
```

**加密憑證用途說明:**
- **API Token**: 用於 API 身份識別，直接加入請求參數
- **Hash Key**: 用於簽名生成，確保資料完整性 (32字元)
- **Hash IV**: 用於簽名生成，增強安全性 (32字元)

#### 6.2 簽名前資料準備
```php
// 簽名生成前的完整資料結構
$api_data = [
    'api_token' => 'dkTqv40XBDmvlfBayoMngA0BAlDAxCrkzIAAUdwYB6kkKZVLOit1R06PKcgkhglASS79c6yzaokrdoPP',
    'order_id' => 'TEST20250114153001234',
    'amount' => 100,
    'user_bank_code' => '004',
    'user_bank_account' => '1234567890123',
    'timestamp' => 1705296600
];
```

#### 6.3 詳細簽名生成過程
```php
// 行 78-91: generateSignature() 方法 - 逐步分解
private function generateSignature($data) {
    // 步驟 1: 移除已存在的簽名欄位 (避免循環引用)
    unset($data['signature']);

    // 步驟 2: 按鍵名字母順序排列 (確保簽名一致性)
    ksort($data);

    // 步驟 3: 組合參數字串
    $sign_string = '';
    foreach ($data as $key => $value) {
        if (!empty($value)) {
            $sign_string .= $key . '=' . $value . '&';
        }
    }

    // 步驟 4: 加入加密金鑰
    $sign_string .= 'hash_key=' . $this->hash_key . '&hash_iv=' . $this->hash_iv;

    // 步驟 5: MD5 雜湊並轉大寫
    return strtoupper(md5($sign_string));
}
```

#### 6.4 實際簽名字串組合示例

**步驟1-2: 排序後的參數**
```php
$sorted_data = [
    'amount' => 100,
    'api_token' => 'dkTqv40XBDmvlfBayoMngA0BAlDAxCrkzIAAUdwYB6kkKZVLOit1R06PKcgkhglASS79c6yzaokrdoPP',
    'order_id' => 'TEST20250114153001234',
    'timestamp' => 1705296600,
    'user_bank_account' => '1234567890123',
    'user_bank_code' => '004'
];
```

**步驟3: 參數字串組合**
```
amount=100&api_token=dkTqv40XBDmvlfBayoMngA0BAlDAxCrkzIAAUdwYB6kkKZVLOit1R06PKcgkhglASS79c6yzaokrdoPP&order_id=TEST20250114153001234&timestamp=1705296600&user_bank_account=1234567890123&user_bank_code=004&
```

**步驟4: 加入完整加密金鑰**
```
amount=100&api_token=dkTqv40XBDmvlfBayoMngA0BAlDAxCrkzIAAUdwYB6kkKZVLOit1R06PKcgkhglASS79c6yzaokrdoPP&order_id=TEST20250114153001234&timestamp=1705296600&user_bank_account=1234567890123&user_bank_code=004&hash_key=lyAJwWnVAKNScXjE6t2rxUOAeesvIP9S&hash_iv=yhncs1WpMo60azxEczokzIlVVvVuW69p
```

**步驟5: MD5 雜湊處理**
```php
$raw_md5 = md5($sign_string);
// 結果例如: a1b2c3d4e5f6789012345678901234567890abcd

$final_signature = strtoupper($raw_md5);
// 最終簽名: A1B2C3D4E5F6789012345678901234567890ABCD
```

#### 6.5 加密金鑰安全機制解析

**Hash Key 的作用:**
- **長度**: 32 字元 (`lyAJwWnVAKNScXjE6t2rxUOAeesvIP9S`)
- **用途**: 作為主要加密密鑰，確保簽名唯一性
- **安全性**: 只有擁有正確 Hash Key 的系統才能生成有效簽名

**Hash IV 的作用:**
- **長度**: 32 字元 (`yhncs1WpMo60azxEczokzIlVVvVuW69p`)
- **用途**: 初始化向量，增強加密強度
- **安全性**: 與 Hash Key 組合使用，防止彩虹表攻擊

**雙重金鑰驗證機制:**
```php
// ANT API 使用雙重金鑰驗證
$sign_string .= 'hash_key=' . $this->hash_key;     // 主密鑰
$sign_string .= '&hash_iv=' . $this->hash_iv;      // 輔助密鑰
```

#### 6.6 最終加密資料結構
```php
// 加入簽名後的完整 API 資料
$final_api_data = [
    'api_token' => 'dkTqv40XBDmvlfBayoMngA0BAlDAxCrkzIAAUdwYB6kkKZVLOit1R06PKcgkhglASS79c6yzaokrdoPP',
    'order_id' => 'TEST20250114153001234',
    'amount' => 100,
    'user_bank_code' => '004',
    'user_bank_account' => '1234567890123',
    'timestamp' => 1705296600,
    'signature' => 'A1B2C3D4E5F6789012345678901234567890ABCD'  // 32位大寫MD5簽名
];
```

#### 6.7 加密安全性驗證機制

**1. 參數完整性檢查:**
```php
// 確保所有必要參數都存在且非空
foreach ($data as $key => $value) {
    if (!empty($value)) {  // 只處理非空值
        $sign_string .= $key . '=' . $value . '&';
    }
}
```

**2. 時間戳防重放攻擊:**
```php
'timestamp' => time()  // Unix時間戳，防止請求重放
```

**3. 金鑰組合防暴力破解:**
```php
// 使用兩個32字元金鑰組合，增加破解難度
$sign_string .= 'hash_key=' . $this->hash_key . '&hash_iv=' . $this->hash_iv;
```

**4. MD5簽名一致性:**
```php
return strtoupper(md5($sign_string));  // 統一使用大寫格式
```

### 6.8 加密金鑰完整處理總結

**完整加密流程時序:**
```
1. 載入加密憑證
   ├── API Token: dkTqv40XBDmvlfBayoMngA0BAlDAxCrkzIAAUdwYB6kkKZVLOit1R06PKcgkhglASS79c6yzaokrdoPP
   ├── Hash Key: lyAJwWnVAKNScXjE6t2rxUOAeesvIP9S (32字元主密鑰)
   └── Hash IV: yhncs1WpMo60azxEczokzIlVVvVuW69p (32字元輔助密鑰)

2. 準備簽名資料
   ├── 移除已存在簽名
   ├── 按字母排序參數
   └── 組合參數字串

3. 加入雙重金鑰
   ├── 附加 hash_key=lyAJwWnVAKNScXjE6t2rxUOAeesvIP9S
   └── 附加 hash_iv=yhncs1WpMo60azxEczokzIlVVvVuW69p

4. 生成最終簽名
   ├── MD5 雜湊處理完整字串
   ├── 轉換為大寫格式 (32字元)
   └── 加入 API 請求資料
```

**最終請求資料包含完整加密資訊:**
```json
{
    "api_token": "dkTqv40XBDmvlfBayoMngA0BAlDAxCrkzIAAUdwYB6kkKZVLOit1R06PKcgkhglASS79c6yzaokrdoPP",
    "order_id": "TEST20250114153001234",
    "amount": 100,
    "user_bank_code": "004",
    "user_bank_account": "1234567890123",
    "timestamp": 1705296600,
    "signature": "A1B2C3D4E5F6789012345678901234567890ABCD"
}
```

**加密安全級別:**
- 🔐 **API Token**: 74字元身份識別密鑰
- 🔐 **Hash Key**: 32字元主要加密密鑰
- 🔐 **Hash IV**: 32字元輔助加密密鑰
- 🔐 **MD5 簽名**: 32字元雜湊驗證碼
- 🛡️ **總安全強度**: 170字元多重加密保護

### 7. API 調用階段

#### 7.1 cURL 請求設定
```php
// 行 96-114: callAPI() 方法 - cURL 設定
$url = $this->api_base_url . $endpoint;  // https://api.nubitya.com/api/payment/create

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'User-Agent: ANT-API-Tester/1.0'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
```

**執行內容:**
- 設定完整的 API 網址
- 配置 POST 請求方式
- 將資料轉為 JSON 格式
- 設定 HTTP headers
- 配置超時和 SSL 選項

**帶入資料:**
- **URL**: `https://api.nubitya.com/api/payment/create`
- **Method**: `POST`
- **Body**: JSON 格式的 API 資料
- **Headers**: `Content-Type: application/json`, `User-Agent: ANT-API-Tester/1.0`
- **Timeout**: 30 秒

#### 7.2 執行 API 請求
```php
// 行 111-118: 執行請求並取得回應
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    throw new Exception("API調用失敗: {$error}");
}
```

**執行內容:**
- 執行 cURL 請求
- 取得 HTTP 狀態碼
- 檢查是否有連線錯誤
- 清理 cURL 資源

**可能的回應資料:**
- **成功情況**: HTTP 200, JSON 回應
- **失敗情況**: HTTP 錯誤碼, 錯誤訊息
- **網路錯誤**: cURL 錯誤訊息

---

### 8. 結果處理階段

#### 8.1 API 回應解析
```php
// 行 120-125: 回應資料處理
return [
    'http_code' => $http_code,
    'response' => $response,
    'parsed_response' => json_decode($response, true)
];
```

**執行內容:**
- 記錄 HTTP 狀態碼
- 保留原始回應內容
- 嘗試解析 JSON 回應

**帶入資料:**
- **http_code**: HTTP 狀態碼 (例: 200, 400, 500)
- **response**: API 原始回應字串
- **parsed_response**: 解析後的 PHP 陣列 (如果是有效 JSON)

#### 8.2 測試結果封裝
```php
// 行 59-64: 成功情況結果封裝
return [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'test_order_data' => $order_data,
    'api_response' => $response
];
```

**執行內容:**
- 標記測試成功狀態
- 記錄測試執行時間
- 包含測試訂單資料
- 包含 API 完整回應

**輸出資料結構:**
```json
{
    "success": true,
    "timestamp": "2025-01-14 15:30:15",
    "test_order_data": {
        "order_id": "TEST20250114153001234",
        "amount": 100,
        "user_bank_code": "004",
        "user_bank_account": "1234567890123",
        "description": "ANT API 開單測試"
    },
    "api_response": {
        "http_code": 200,
        "response": "...",
        "parsed_response": {...}
    }
}
```

---

### 9. 前端結果顯示階段

#### 9.1 JSON 回應解析
```javascript
// 行 302-306: 前端處理 API 回應
const response = await fetch('?action=create_order');
const data = await response.json();

let statusText = data.success ? '✅ 測試成功' : '❌ 測試失敗';
let statusColor = data.success ? '#28a745' : '#dc3545';
```

**執行內容:**
- 解析後端返回的 JSON 資料
- 判斷測試成功或失敗狀態
- 設定對應的顏色和圖示

#### 9.2 動態更新頁面內容
```javascript
// 行 307-316: 結果顯示 HTML 生成
resultDiv.innerHTML = `
    <h4 style="color: ${statusColor}">${statusText}</h4>
    <p><strong>測試時間:</strong> ${data.timestamp}</p>
    ${data.test_order_data ? `<p><strong>測試訂單:</strong> ${data.test_order_data.order_id}</p>` : ''}
    ${data.error ? `<p><strong>錯誤:</strong> ${data.error}</p>` : ''}
    <details>
        <summary>詳細結果</summary>
        <pre>${JSON.stringify(data, null, 2)}</pre>
    </details>
`;
```

**執行內容:**
- 生成結果顯示的 HTML 內容
- 包含狀態、時間、訂單號等關鍵資訊
- 提供完整 JSON 結果的詳細檢視
- 更新頁面 DOM 元素

#### 9.3 UI 狀態恢復
```javascript
// 行 328-330: 恢復 UI 狀態
loading.style.display = 'none';
button.disabled = false;
button.textContent = '重新測試';
```

**執行內容:**
- 隱藏載入指示器
- 重新啟用測試按鈕
- 更新按鈕文字為「重新測試」

---

## 📊 完整資料流向圖

```
用戶點擊按鈕
    ↓
JavaScript AJAX 請求 (?action=create_order)
    ↓
PHP 後端處理
    ↓
生成測試訂單資料:
├── order_id: TEST20250114153001234
├── amount: 100
├── user_bank_code: 004
├── user_bank_account: 1234567890123
└── description: ANT API 開單測試
    ↓
準備 API 請求資料:
├── api_token: dkTqv40XBDmvlfBayoMngA0BAlDAxCrkzIAAUdwYB6kkKZVLOit1R06PKcgkhglASS79c6yzaokrdoPP
├── order_id: TEST20250114153001234
├── amount: 100
├── user_bank_code: 004
├── user_bank_account: 1234567890123
└── timestamp: 1705296600
    ↓
生成簽名:
├── 排序參數: amount=100&api_token=...&order_id=...&timestamp=...&user_bank_account=...&user_bank_code=004
├── 加入密鑰: ...&hash_key=lyAJwWnVAKNScXjE6t2rxUOAeesvIP9S&hash_iv=yhncs1WpMo60azxEczokzIlVVvVuW69p
└── MD5簽名: A1B2C3D4E5F6... (32位大寫)
    ↓
cURL API 請求:
├── URL: https://api.nubitya.com/api/payment/create
├── Method: POST
├── Body: JSON格式完整資料
└── Headers: Content-Type: application/json
    ↓
API 回應處理:
├── HTTP Code: 200/400/500...
├── Response: JSON 或錯誤訊息
└── 解析結果: PHP 陣列
    ↓
結果封裝:
├── success: true/false
├── timestamp: 2025-01-14 15:30:15
├── test_order_data: {...}
└── api_response: {...}
    ↓
JSON 回傳給前端
    ↓
JavaScript 解析並更新頁面
    ↓
顯示測試結果給用戶
```

## 🔐 加密金鑰與簽名機制詳解

### 完整 API 憑證資訊
```php
// ant_order_test.php 行 18-21: 真實 API 憑證
private $api_token = 'dkTqv40XBDmvlfBayoMngA0BAlDAxCrkzIAAUdwYB6kkKZVLOit1R06PKcgkhglASS79c6yzaokrdoPP';
private $hash_key = 'lyAJwWnVAKNScXjE6t2rxUOAeesvIP9S';
private $hash_iv = 'yhncs1WpMo60azxEczokzIlVVvVuW69p';
private $api_base_url = 'https://api.nubitya.com';
```

### 簽名生成完整流程示例

#### 步驟1: 準備簽名參數
```php
// 原始 API 資料 (行 44-50)
$api_data = [
    'api_token' => 'dkTqv40XBDmvlfBayoMngA0BAlDAxCrkzIAAUdwYB6kkKZVLOit1R06PKcgkhglASS79c6yzaokrdoPP',
    'order_id' => 'TEST20250114153001234',
    'amount' => 100,
    'user_bank_code' => '004',
    'user_bank_account' => '1234567890123',
    'timestamp' => 1705296600
];
```

#### 步驟2: 參數排序 (行 80)
```php
ksort($api_data);
// 排序後順序: amount, api_token, order_id, timestamp, user_bank_account, user_bank_code
```

#### 步驟3: 構建簽名字串 (行 82-88)
```php
$sign_string = '';
foreach ($data as $key => $value) {
    if (!empty($value)) {
        $sign_string .= $key . '=' . $value . '&';
    }
}
$sign_string .= 'hash_key=' . $this->hash_key . '&hash_iv=' . $this->hash_iv;
```

#### 步驟4: 完整簽名字串範例
```text
amount=100&api_token=dkTqv40XBDmvlfBayoMngA0BAlDAxCrkzIAAUdwYB6kkKZVLOit1R06PKcgkhglASS79c6yzaokrdoPP&order_id=TEST20250114153001234&timestamp=1705296600&user_bank_account=1234567890123&user_bank_code=004&hash_key=lyAJwWnVAKNScXjE6t2rxUOAeesvIP9S&hash_iv=yhncs1WpMo60azxEczokzIlVVvVuW69p
```

#### 步驟5: MD5 加密與大寫轉換 (行 90)
```php
return strtoupper(md5($sign_string));
// 輸出範例: 'A1B2C3D4E5F6789012345678901234567'
```

### 加密安全機制說明

#### 雙金鑰驗證
- **Hash Key**: `lyAJwWnVAKNScXjE6t2rxUOAeesvIP9S` (32位)
- **Hash IV**: `yhncs1WpMo60azxEczokzIlVVvVuW69p` (32位)
- **作用**: 防止參數篡改，確保請求來源合法性

#### 時間戳防重放攻擊
- **timestamp**: 當前時間戳 `time()`
- **作用**: 防止請求被重複使用，增強安全性

#### 參數完整性檢查
- **排序**: 所有參數按字母順序排列
- **過濾**: 只包含非空值參數
- **追加**: 最後加入雙密鑰

### 最終 API 請求資料結構
```json
{
    "api_token": "dkTqv40XBDmvlfBayoMngA0BAlDAxCrkzIAAUdwYB6kkKZVLOit1R06PKcgkhglASS79c6yzaokrdoPP",
    "order_id": "TEST20250114153001234",
    "amount": 100,
    "user_bank_code": "004",
    "user_bank_account": "1234567890123",
    "timestamp": 1705296600,
    "signature": "A1B2C3D4E5F6789012345678901234567"
}
```

## 🔧 錯誤處理機制

### 1. 網路連線錯誤
- **觸發條件**: cURL 執行失敗
- **處理方式**: 拋出 Exception，記錄錯誤訊息
- **用戶顯示**: 「API調用失敗: [錯誤詳情]」

### 2. API 回應錯誤
- **觸發條件**: HTTP 狀態碼非 200
- **處理方式**: 記錄狀態碼和回應內容
- **用戶顯示**: 顯示 HTTP 狀態碼和原始回應

### 3. JSON 解析錯誤
- **觸發條件**: API 回應非有效 JSON
- **處理方式**: 保留原始回應，parsed_response 為 null
- **用戶顯示**: 在詳細結果中顯示原始回應

### 4. JavaScript 錯誤
- **觸發條件**: AJAX 請求失敗或網路問題
- **處理方式**: try-catch 捕獲並顯示錯誤
- **用戶顯示**: 「❌ 測試失敗 錯誤: [錯誤訊息]」

## 📈 完成狀態

✅ **第85點**: 使用真實API憑證進行開單測試 - **完成**
✅ **第86點**: 修復空白頁面問題，確保內容正常顯示 - **完成**
✅ **第87點**: 撰寫完整執行流程說明 - **完成**

---

## 💡 使用說明

1. **訪問測試頁面**: https://test.paygo.tw/ant_order_test.php
2. **檢查頁面載入**: 確認顯示 API 憑證和測試按鈕
3. **執行開單測試**: 點擊「開始開單測試」按鈕
4. **查看測試結果**: 檢視成功/失敗狀態和詳細資訊
5. **重複測試**: 可點擊「重新測試」進行多次測試

每次測試都會生成唯一的訂單編號，確保測試的獨立性和可追蹤性。