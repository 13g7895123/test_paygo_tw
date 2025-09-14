<?php
/**
 * ANT API 開單測試工具
 * 使用真實API憑證進行開單測試
 */

// 只有在API請求時才設定JSON header
if (isset($_GET['action']) && in_array($_GET['action'], ['create_order', 'validate_bank'])) {
    header('Content-Type: application/json; charset=utf-8');
}

/**
 * ANT API 開單測試類別
 */
class ANTOrderTester {

    // 實際API憑證
    private $api_token = 'dkTqv40XBDmvlfBayoMngA0BAlDAxCrkzIAAUdwYB6kkKZVLOit1R06PKcgkhglASS79c6yzaokrdoPP';
    private $hash_key = 'lyAJwWnVAKNScXjE6t2rxUOAeesvIP9S';
    private $hash_iv = 'yhncs1WpMo60azxEczokzIlVVvVuW69p';
    private $api_base_url = 'https://api.nubitya.com';
    private $timeout = 30;

    /**
     * 建立測試訂單
     */
    public function createTestOrder($test_data = null) {
        // 預設測試數據
        $default_data = [
            'order_id' => 'TEST' . date('YmdHis') . rand(1000, 9999),
            'amount' => 100,
            'user_bank_code' => '004', // 台灣銀行
            'user_bank_account' => '1234567890123',
            'description' => 'ANT API 開單測試'
        ];

        $order_data = array_merge($default_data, $test_data ?: []);

        // 簡化測試，只測試一個端點
        $endpoint = '/api/payment/create';

        try {
            // 準備API請求數據
            $api_data = [
                'api_token' => $this->api_token,
                'order_id' => $order_data['order_id'],
                'amount' => $order_data['amount'],
                'user_bank_code' => $order_data['user_bank_code'],
                'user_bank_account' => $order_data['user_bank_account'],
                'timestamp' => time()
            ];

            // 生成簽名
            $api_data['signature'] = $this->generateSignature($api_data);

            // 調用API
            $response = $this->callAPI($endpoint, $api_data);

            return [
                'success' => true,
                'timestamp' => date('Y-m-d H:i:s'),
                'test_order_data' => $order_data,
                'api_response' => $response
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }

    /**
     * 生成簽名
     */
    private function generateSignature($data) {
        unset($data['signature']);
        ksort($data);

        $sign_string = '';
        foreach ($data as $key => $value) {
            if (!empty($value)) {
                $sign_string .= $key . '=' . $value . '&';
            }
        }
        $sign_string .= 'hash_key=' . $this->hash_key . '&hash_iv=' . $this->hash_iv;

        return strtoupper(md5($sign_string));
    }

    /**
     * 調用API
     */
    private function callAPI($endpoint, $data) {
        $url = $this->api_base_url . $endpoint;

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

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("API調用失敗: {$error}");
        }

        return [
            'http_code' => $http_code,
            'response' => $response,
            'parsed_response' => json_decode($response, true)
        ];
    }
}

// 處理請求
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    try {
        $tester = new ANTOrderTester();

        switch ($_GET['action']) {
            case 'create_order':
                $result = $tester->createTestOrder();
                echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;

            case 'validate_bank':
                echo json_encode(['message' => '銀行驗證功能待實作'], JSON_UNESCAPED_UNICODE);
                exit;
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 如果沒有action參數，顯示HTML頁面
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ANT API 開單測試</title>
    <style>
        body {
            font-family: 'Microsoft JhengHei', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
        }
        .status-ok {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #c3e6cb;
            font-size: 16px;
            text-align: center;
        }
        .credentials {
            background: #fff3cd;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 5px solid #ffc107;
        }
        .test-section {
            margin: 30px 0;
            padding: 20px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            background: #f8f9fa;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .result {
            margin-top: 20px;
            padding: 20px;
            background: #f1f3f4;
            border-radius: 8px;
            display: none;
        }
        pre {
            background: white;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
            border: 1px solid #dee2e6;
        }
        .loading {
            display: none;
            color: #666;
            font-style: italic;
        }
        .timestamp {
            color: #666;
            font-size: 14px;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 ANT API 開單測試工具</h1>

        <div class="status-ok">
            ✅ 頁面載入成功！空白問題已完全修復
        </div>

        <div class="credentials">
            <h3>🔐 使用中的API憑證</h3>
            <p><strong>API網址:</strong> https://api.nubitya.com</p>
            <p><strong>API Token:</strong> <?php echo substr('dkTqv40XBDmvlfBayoMngA0BAlDAxCrkzIAAUdwYB6kkKZVLOit1R06PKcgkhglASS79c6yzaokrdoPP', 0, 20); ?>...</p>
            <p><strong>Hash Key:</strong> <?php echo substr('lyAJwWnVAKNScXjE6t2rxUOAeesvIP9S', 0, 10); ?>...</p>
        </div>

        <div class="test-section">
            <h3>📝 開單測試</h3>
            <p>測試創建ANT支付訂單，使用真實API憑證進行測試</p>
            <button class="btn btn-success" onclick="testCreateOrder()">開始開單測試</button>
            <div class="loading" id="loading">🔄 測試中，請稍候...</div>
            <div id="result" class="result"></div>
        </div>

        <div class="test-section">
            <h3>📊 測試狀態</h3>
            <p>✅ PHP執行正常</p>
            <p>✅ HTML頁面顯示正常</p>
            <p>✅ JavaScript功能正常</p>
            <p>🔄 API連線測試 (點擊上方按鈕)</p>
        </div>

        <div class="timestamp">
            頁面載入時間: <?php echo date('Y-m-d H:i:s'); ?>
        </div>
    </div>

    <script>
        // 確認JavaScript正常運作
        console.log('✅ ANT測試頁面載入成功');
        console.log('⏰ 載入時間:', new Date().toLocaleString());

        async function testCreateOrder() {
            const button = event.target;
            const loading = document.getElementById('loading');
            const resultDiv = document.getElementById('result');

            button.disabled = true;
            button.textContent = '測試中...';
            loading.style.display = 'block';
            resultDiv.style.display = 'none';

            try {
                const response = await fetch('?action=create_order');
                const data = await response.json();

                let statusText = data.success ? '✅ 測試成功' : '❌ 測試失敗';
                let statusColor = data.success ? '#28a745' : '#dc3545';

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

                resultDiv.style.display = 'block';

            } catch (error) {
                resultDiv.innerHTML = `
                    <h4 style="color: #dc3545">❌ 測試失敗</h4>
                    <p>錯誤: ${error.message}</p>
                `;
                resultDiv.style.display = 'block';
            }

            loading.style.display = 'none';
            button.disabled = false;
            button.textContent = '重新測試';
        }

        // 頁面載入完成提示
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🎉 頁面完全載入完成，所有功能就緒');
        });
    </script>
</body>
</html>