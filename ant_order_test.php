<?php
/**
 * ANT API 開單測試工具
 * 根據真實API文檔進行開單測試
 */

// 只有在API請求時才設定JSON header
if (isset($_GET['action']) && in_array($_GET['action'], ['create_order', 'query_status'])) {
    header('Content-Type: application/json; charset=utf-8');
}

/**
 * ANT API 開單測試類別
 */
class ANTOrderTester {

    // 實際API憑證
    private $username = 'antpay018';
    private $hash_key = 'lyAJwWnVAKNScXjE6t2rxUOAeesvIP9S';
    private $hash_iv = 'yhncs1WpMo60azxEczokzIlVVvVuW69p';
    private $api_base_url = 'https://api.nubitya.com';
    private $timeout = 30;

    /**
     * 解碼字符串中的Unicode字符 (如 \u4e2d\u6587)
     */
    private function decodeUnicodeString($str) {
        return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function($match) {
            $unicode = hexdec($match[1]);
            if ($unicode < 0x80) {
                return chr($unicode);
            } elseif ($unicode < 0x800) {
                return chr(0xC0 | ($unicode >> 6)) . chr(0x80 | ($unicode & 0x3F));
            } else {
                return chr(0xE0 | ($unicode >> 12)) . chr(0x80 | (($unicode >> 6) & 0x3F)) . chr(0x80 | ($unicode & 0x3F));
            }
        }, $str);
    }

    /**
     * 遞歸解碼數組中的Unicode字符
     */
    private function decodeUnicodeInArray($data) {
        if (is_string($data)) {
            return $this->decodeUnicodeString($data);
        } elseif (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $decoded_key = is_string($key) ? $this->decodeUnicodeString($key) : $key;
                $result[$decoded_key] = $this->decodeUnicodeInArray($value);
            }
            return $result;
        }
        return $data;
    }

    /**
     * 生成簽章 (根據ANT API文檔的簽章生成規則)
     */
    private function generateSignature($data) {
        // 移除sign欄位
        unset($data['sign']);

        // 按英文字母 A-Z 排序
        ksort($data);

        // 組合參數字串
        $params = [];
        foreach ($data as $key => $value) {
            if ($value !== '' && $value !== null) {
                $params[] = $key . '=' . $value;
            }
        }
        $param_string = implode('&', $params);

        // 加上 HashKey 和 HashIV
        $sign_string = 'HashKey=' . $this->hash_key . '&' . $param_string . '&HashIV=' . $this->hash_iv;

        // URL encode
        $encoded_string = urlencode($sign_string);

        // 轉小寫
        $lowercase_string = strtolower($encoded_string);

        // SHA256 加密
        $hash = hash('sha256', $lowercase_string);

        // 轉大寫
        return strtoupper($hash);
    }

    /**
     * 調用ANT API
     */
    private function callApi($endpoint, $data, $method = 'POST') {
        $url = $this->api_base_url . $endpoint;

        $ch = curl_init();

        if ($method === 'GET') {
            if (!empty($data)) {
                $query_string = http_build_query($data);
                $url .= '?' . $query_string;
            }
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        } else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: ANT-TEST-CLIENT/1.0'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 測試環境

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("API調用失敗: {$error}");
        }

        $result = [
            'http_code' => $http_code,
            'response' => $response,
            'parsed' => null
        ];

        if ($response) {
            // 先解碼Unicode字符
            $decoded_response = $this->decodeUnicodeString($response);
            $result['decoded_response'] = $decoded_response;

            $parsed = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $result['parsed'] = $parsed;
                // 也對解析後的數據進行Unicode解碼
                $result['decoded_parsed'] = $this->decodeUnicodeInArray($parsed);
            }
        }

        return $result;
    }

    /**
     * 建立測試訂單 (根據真實API文檔)
     */
    public function createTestOrder($test_data = null) {
        // 預設測試數據
        $default_data = [
            'partner_number' => 'TEST' . date('YmdHis') . rand(1000, 9999),
            'amount' => 100,
            'user_bank_code' => '004', // 台灣銀行
            'user_bank_account' => '1234567890123456',
            'item_name' => 'ANT API 開單測試',
            'trade_desc' => 'ANT API 開單測試 - ' . date('Y-m-d H:i:s'),
            'notify_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/ant_callback.php',
            'remark' => '測試訂單'
        ];

        $order_data = array_merge($default_data, $test_data ?: []);

        try {
            // 準備expected_banks參數 (JSON格式)
            $expected_banks = [
                [
                    'bank_code' => $order_data['user_bank_code'],
                    'bank_account' => $order_data['user_bank_account']
                ]
            ];

            // 準備API請求數據 (根據真實API文檔)
            $api_data = [
                'username' => $this->username,
                'partner_number' => $order_data['partner_number'],
                'payment_type_slug' => 'BANK-ACCOUNT-DEPOSIT',
                'amount' => (int)$order_data['amount'],
                'item_name' => $order_data['item_name'],
                'trade_desc' => $order_data['trade_desc'],
                'notify_url' => $order_data['notify_url'],
                'expected_banks' => json_encode($expected_banks),
                'remark' => $order_data['remark']
            ];

            // 生成簽章
            $api_data['sign'] = $this->generateSignature($api_data);

            // 記錄請求資料
            $this->logRequest('CREATE_ORDER', $api_data, $order_data);

            // 調用ANT API
            $result = $this->callApi('/api/partner/deposit-orders', $api_data, 'POST');

            // 記錄回應資料
            $this->logResponse('CREATE_ORDER', $result);

            return [
                'success' => true,
                'request_data' => $api_data,
                'http_code' => $result['http_code'],
                'response' => $result['response'],
                'decoded_response' => $result['decoded_response'] ?? null,
                'parsed_response' => $result['parsed'],
                'decoded_parsed' => $result['decoded_parsed'] ?? null,
                'test_order_data' => $order_data
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'request_data' => $api_data ?? [],
                'test_order_data' => $order_data
            ];
        }
    }

    /**
     * 查詢訂單狀態
     */
    public function queryOrderStatus($order_number) {
        try {
            // 準備查詢參數
            $query_data = [
                'username' => $this->username
            ];

            // 生成簽章
            $query_data['sign'] = $this->generateSignature($query_data);

            // 記錄請求資料
            $this->logRequest('QUERY_STATUS', $query_data, ['order_number' => $order_number]);

            // 調用ANT API (GET方法)
            $endpoint = '/api/partner/deposit-orders/' . urlencode($order_number);
            $result = $this->callApi($endpoint, $query_data, 'GET');

            // 記錄回應資料
            $this->logResponse('QUERY_STATUS', $result);

            return [
                'success' => true,
                'request_data' => $query_data,
                'http_code' => $result['http_code'],
                'response' => $result['response'],
                'decoded_response' => $result['decoded_response'] ?? null,
                'parsed_response' => $result['parsed'],
                'decoded_parsed' => $result['decoded_parsed'] ?? null,
                'order_number' => $order_number
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'order_number' => $order_number
            ];
        }
    }

    /**
     * 記錄請求日誌
     */
    private function logRequest($action, $api_data, $extra_data = []) {
        $log_data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => $action,
            'api_data' => $this->sanitizeLogData($api_data),
            'extra_data' => $extra_data
        ];

        error_log('[ANT-TEST-REQUEST] ' . json_encode($log_data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 記錄回應日誌
     */
    private function logResponse($action, $result) {
        $log_data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => $action,
            'http_code' => $result['http_code'],
            'response' => $result['response'],
            'parsed' => $result['parsed']
        ];

        error_log('[ANT-TEST-RESPONSE] ' . json_encode($log_data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 清理敏感資料
     */
    private function sanitizeLogData($data) {
        if (is_array($data)) {
            $sensitive_fields = ['sign', 'hash_key', 'hash_iv', 'expected_banks'];
            foreach ($sensitive_fields as $field) {
                if (isset($data[$field])) {
                    $data[$field] = '***HIDDEN***';
                }
            }
        }
        return $data;
    }

    /**
     * 驗證簽章
     */
    public function verifySignature($data, $received_sign) {
        $calculated_sign = $this->generateSignature($data);
        return $calculated_sign === $received_sign;
    }

    /**
     * 生成測試報告
     */
    public function generateTestReport($test_results) {
        $report = [
            'test_summary' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'total_tests' => count($test_results),
                'successful_tests' => 0,
                'failed_tests' => 0
            ],
            'test_details' => []
        ];

        foreach ($test_results as $test) {
            if ($test['success']) {
                $report['test_summary']['successful_tests']++;
            } else {
                $report['test_summary']['failed_tests']++;
            }

            $report['test_details'][] = $test;
        }

        return $report;
    }
}

// 處理API請求
if (isset($_GET['action'])) {
    $tester = new ANTOrderTester();

    switch ($_GET['action']) {
        case 'create_order':
            // 獲取測試參數
            $test_data = [];
            if (isset($_POST['amount'])) $test_data['amount'] = (int)$_POST['amount'];
            if (isset($_POST['bank_code'])) $test_data['user_bank_code'] = $_POST['bank_code'];
            if (isset($_POST['bank_account'])) $test_data['user_bank_account'] = $_POST['bank_account'];

            $result = $tester->createTestOrder($test_data);
            echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;

        case 'query_status':
            $order_number = $_GET['order_number'] ?? '';
            if (empty($order_number)) {
                echo json_encode(['success' => false, 'error' => '缺少訂單編號'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $result = $tester->queryOrderStatus($order_number);
            echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ANT API 開單測試工具</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        h2 { color: #666; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .form-group { margin: 10px 0; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input, textarea, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; margin: 5px; }
        button:hover { background: #0056b3; }
        .result { margin: 10px 0; padding: 10px; border-radius: 3px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        pre { background: #f8f9fa; border: 1px solid #e9ecef; padding: 10px; border-radius: 3px; overflow-x: auto; white-space: pre-wrap; }
        .test-params { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        @media (max-width: 768px) {
            .test-params { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>ANT API 開單測試工具</h1>

        <div class="info result">
            <h3>📋 測試說明</h3>
            <p>此工具使用真實的ANT API憑證進行開單測試，請確保已正確設定IP白名單。</p>
            <ul>
                <li><strong>API 端點:</strong> https://api.nubitya.com</li>
                <li><strong>支付方式:</strong> BANK-ACCOUNT-DEPOSIT (約定帳戶)</li>
                <li><strong>簽章方式:</strong> SHA256 (根據官方文檔實作)</li>
            </ul>
        </div>

        <!-- 建立訂單測試 -->
        <div class="section">
            <h2>🔧 建立測試訂單</h2>
            <div class="test-params">
                <div class="form-group">
                    <label for="test-amount">測試金額:</label>
                    <input type="number" id="test-amount" value="100" min="1">
                </div>
                <div class="form-group">
                    <label for="test-bank-code">銀行代號:</label>
                    <select id="test-bank-code">
                        <option value="004">004 - 台灣銀行</option>
                        <option value="005">005 - 台灣土地銀行</option>
                        <option value="006">006 - 合作金庫銀行</option>
                        <option value="007">007 - 第一商業銀行</option>
                        <option value="008">008 - 華南商業銀行</option>
                        <option value="009">009 - 彰化商業銀行</option>
                        <option value="011">011 - 上海商業儲蓄銀行</option>
                        <option value="012">012 - 台北富邦銀行</option>
                        <option value="812">812 - 台新銀行</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="test-bank-account">測試銀行帳號 (16位):</label>
                <input type="text" id="test-bank-account" value="1234567890123456" maxlength="16">
            </div>

            <button onclick="testCreateOrder()">🚀 建立測試訂單</button>
            <div id="create-result"></div>
        </div>

        <!-- 查詢訂單狀態 -->
        <div class="section">
            <h2>📊 查詢訂單狀態</h2>
            <div class="form-group">
                <label for="query-order-number">ANT訂單編號:</label>
                <input type="text" id="query-order-number" placeholder="請輸入從建立訂單回應中取得的ANT訂單編號">
            </div>

            <button onclick="testQueryStatus()">🔍 查詢狀態</button>
            <div id="query-result"></div>
        </div>

        <!-- 測試結果記錄 -->
        <div class="section">
            <h2>📝 測試記錄</h2>
            <div id="test-log"></div>
            <button onclick="clearLog()">🗑️ 清除記錄</button>
        </div>
    </div>

    <script>
        let testHistory = [];

        function addToLog(title, data, isSuccess = true) {
            const timestamp = new Date().toLocaleString('zh-TW');
            const logEntry = {
                timestamp: timestamp,
                title: title,
                data: data,
                isSuccess: isSuccess
            };

            testHistory.unshift(logEntry);
            updateLogDisplay();
        }

        function updateLogDisplay() {
            const logDiv = document.getElementById('test-log');
            if (testHistory.length === 0) {
                logDiv.innerHTML = '<p>暫無測試記錄</p>';
                return;
            }

            let html = '';
            testHistory.forEach((entry, index) => {
                const statusClass = entry.isSuccess ? 'success' : 'error';
                html += `
                    <div class="result ${statusClass}" style="margin-bottom: 10px;">
                        <h4>${entry.title} - ${entry.timestamp}</h4>
                        <pre>${JSON.stringify(entry.data, null, 2)}</pre>
                    </div>
                `;
            });

            logDiv.innerHTML = html;
        }

        function clearLog() {
            testHistory = [];
            updateLogDisplay();
        }

        async function testCreateOrder() {
            const amount = document.getElementById('test-amount').value;
            const bankCode = document.getElementById('test-bank-code').value;
            const bankAccount = document.getElementById('test-bank-account').value;
            const resultDiv = document.getElementById('create-result');

            resultDiv.innerHTML = '<div class="info result">⏳ 正在建立測試訂單...</div>';

            try {
                const formData = new FormData();
                formData.append('amount', amount);
                formData.append('bank_code', bankCode);
                formData.append('bank_account', bankAccount);

                const response = await fetch('?action=create_order', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    resultDiv.innerHTML = `
                        <div class="success result">
                            <h4>✅ 訂單建立成功</h4>
                            <p><strong>商戶訂單號:</strong> ${result.test_order_data.partner_number}</p>
                            <p><strong>HTTP狀態碼:</strong> ${result.http_code}</p>
                            ${result.parsed_response && result.parsed_response.content ?
                                `<p><strong>ANT訂單號:</strong> ${result.parsed_response.content.number || 'N/A'}</p>` : ''
                            }
                        </div>
                        <div class="info result">
                            <h4>📤 API請求詳情</h4>
                            <pre>${JSON.stringify(result.request_data, null, 2)}</pre>
                        </div>
                        <div class="info result">
                            <h4>📥 API回應詳情 (原始)</h4>
                            <pre>${result.response}</pre>
                        </div>
                        ${result.decoded_response ? `
                        <div class="info result">
                            <h4>🔤 API回應詳情 (Unicode解碼後)</h4>
                            <pre>${result.decoded_response}</pre>
                        </div>` : ''}
                        ${result.decoded_parsed ? `
                        <div class="info result">
                            <h4>📋 解析後資料 (Unicode解碼)</h4>
                            <pre>${JSON.stringify(result.decoded_parsed, null, 2)}</pre>
                        </div>` : ''}
                    `;

                    // 自動填入查詢欄位
                    if (result.parsed_response && result.parsed_response.content && result.parsed_response.content.number) {
                        document.getElementById('query-order-number').value = result.parsed_response.content.number;
                    }

                    addToLog('建立訂單測試', result, true);
                } else {
                    resultDiv.innerHTML = `
                        <div class="error result">
                            <h4>❌ 訂單建立失敗</h4>
                            <p><strong>錯誤:</strong> ${result.error}</p>
                        </div>
                        <div class="info result">
                            <h4>📤 請求詳情</h4>
                            <pre>${JSON.stringify(result.request_data, null, 2)}</pre>
                        </div>
                    `;

                    addToLog('建立訂單測試 (失敗)', result, false);
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="error result">
                        <h4>❌ 系統錯誤</h4>
                        <p>${error.message}</p>
                    </div>
                `;

                addToLog('建立訂單測試 (系統錯誤)', {error: error.message}, false);
            }
        }

        async function testQueryStatus() {
            const orderNumber = document.getElementById('query-order-number').value.trim();
            const resultDiv = document.getElementById('query-result');

            if (!orderNumber) {
                resultDiv.innerHTML = '<div class="error result">❌ 請輸入ANT訂單編號</div>';
                return;
            }

            resultDiv.innerHTML = '<div class="info result">⏳ 正在查詢訂單狀態...</div>';

            try {
                const response = await fetch(`?action=query_status&order_number=${encodeURIComponent(orderNumber)}`);
                const result = await response.json();

                if (result.success) {
                    const orderInfo = result.parsed_response && result.parsed_response.content ? result.parsed_response.content : {};
                    const statusMap = {
                        1: '已建立',
                        2: '處理中',
                        3: '待繳費',
                        4: '已完成',
                        5: '已取消',
                        6: '已退款',
                        7: '金額不符合',
                        8: '銀行不符合'
                    };

                    resultDiv.innerHTML = `
                        <div class="success result">
                            <h4>✅ 查詢成功</h4>
                            <p><strong>ANT訂單號:</strong> ${orderInfo.number || 'N/A'}</p>
                            <p><strong>商戶訂單號:</strong> ${orderInfo.partner_number || 'N/A'}</p>
                            <p><strong>訂單狀態:</strong> ${statusMap[orderInfo.status] || '未知'} (${orderInfo.status})</p>
                            <p><strong>訂單金額:</strong> ${orderInfo.amount || 'N/A'}</p>
                            <p><strong>HTTP狀態碼:</strong> ${result.http_code}</p>
                        </div>
                        <div class="info result">
                            <h4>📥 完整回應 (原始)</h4>
                            <pre>${result.response}</pre>
                        </div>
                        ${result.decoded_response ? `
                        <div class="info result">
                            <h4>🔤 完整回應 (Unicode解碼後)</h4>
                            <pre>${result.decoded_response}</pre>
                        </div>` : ''}
                        ${result.decoded_parsed ? `
                        <div class="info result">
                            <h4>📋 解析後資料 (Unicode解碼)</h4>
                            <pre>${JSON.stringify(result.decoded_parsed, null, 2)}</pre>
                        </div>` : ''}
                    `;

                    addToLog('查詢訂單狀態', result, true);
                } else {
                    resultDiv.innerHTML = `
                        <div class="error result">
                            <h4>❌ 查詢失敗</h4>
                            <p><strong>錯誤:</strong> ${result.error}</p>
                        </div>
                    `;

                    addToLog('查詢訂單狀態 (失敗)', result, false);
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="error result">
                        <h4>❌ 系統錯誤</h4>
                        <p>${error.message}</p>
                    </div>
                `;

                addToLog('查詢訂單狀態 (系統錯誤)', {error: error.message}, false);
            }
        }

        // 初始化
        updateLogDisplay();
    </script>
</body>
</html>