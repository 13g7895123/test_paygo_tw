<?php
/**
 * ANT API 測試工具
 * 用於測試 https://api.nubitya.com 的連線狀況
 */

header('Content-Type: application/json; charset=utf-8');

// 允許跨域請求 (僅測試用途)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

/**
 * ANT API 測試類別
 */
class ANTApiTester {

    private $api_base_url = 'https://api.nubitya.com';
    private $timeout = 10;

    // 實際API憑證 (來自第85點)
    private $api_token = 'dkTqv40XBDmvlfBayoMngA0BAlDAxCrkzIAAUdwYB6kkKZVLOit1R06PKcgkhglASS79c6yzaokrdoPP';
    private $hash_key = 'lyAJwWnVAKNScXjE6t2rxUOAeesvIP9S';
    private $hash_iv = 'yhncs1WpMo60azxEczokzIlVVvVuW69p';

    /**
     * 測試 API 連線狀況
     */
    public function testConnection() {
        $results = [];

        // 1. 測試基本連線
        $results['basic_connection'] = $this->testBasicConnection();

        // 2. 測試 API 端點回應
        $results['api_response'] = $this->testApiResponse();

        // 3. 測試網路延遲
        $results['latency'] = $this->testLatency();

        return [
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'api_url' => $this->api_base_url,
            'tests' => $results,
            'summary' => $this->generateSummary($results)
        ];
    }

    /**
     * 測試基本連線
     */
    private function testBasicConnection() {
        $start_time = microtime(true);

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->api_base_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true); // 只取得 header
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 測試環境暫時關閉 SSL 驗證
            curl_setopt($ch, CURLOPT_USERAGENT, 'ANT-API-Tester/1.0');

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            $connection_time = curl_getinfo($ch, CURLINFO_CONNECT_TIME);
            $total_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);

            curl_close($ch);

            $end_time = microtime(true);
            $execution_time = round(($end_time - $start_time) * 1000, 2); // 毫秒

            if ($error) {
                return [
                    'status' => 'failed',
                    'error' => $error,
                    'execution_time_ms' => $execution_time
                ];
            }

            return [
                'status' => 'success',
                'http_code' => $http_code,
                'connection_time' => round($connection_time * 1000, 2),
                'total_time' => round($total_time * 1000, 2),
                'execution_time_ms' => $execution_time
            ];

        } catch (Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'execution_time_ms' => round((microtime(true) - $start_time) * 1000, 2)
            ];
        }
    }

    /**
     * 測試 API 回應
     */
    private function testApiResponse() {
        $start_time = microtime(true);

        try {
            // 測試一個簡單的端點 (通常 API 會有 health check 或 ping 端點)
            $test_endpoints = [
                '/ping',
                '/health',
                '/status',
                '/' // 根目錄
            ];

            $results = [];

            foreach ($test_endpoints as $endpoint) {
                $url = $this->api_base_url . $endpoint;

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_USERAGENT, 'ANT-API-Tester/1.0');

                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                $error = curl_error($ch);

                curl_close($ch);

                $results[$endpoint] = [
                    'http_code' => $http_code,
                    'content_type' => $content_type,
                    'response_length' => strlen($response),
                    'error' => $error ?: null,
                    'has_json_response' => $this->isValidJson($response)
                ];

                // 如果找到有效回應，可以提前結束
                if ($http_code === 200) {
                    break;
                }
            }

            $end_time = microtime(true);
            $execution_time = round(($end_time - $start_time) * 1000, 2);

            return [
                'status' => 'completed',
                'endpoints_tested' => $results,
                'execution_time_ms' => $execution_time
            ];

        } catch (Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'execution_time_ms' => round((microtime(true) - $start_time) * 1000, 2)
            ];
        }
    }

    /**
     * 測試網路延遲
     */
    private function testLatency() {
        $attempts = 3;
        $latencies = [];

        for ($i = 0; $i < $attempts; $i++) {
            $start_time = microtime(true);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->api_base_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if (!$error) {
                $end_time = microtime(true);
                $latencies[] = round(($end_time - $start_time) * 1000, 2);
            }

            // 間隔100毫秒
            usleep(100000);
        }

        if (empty($latencies)) {
            return [
                'status' => 'failed',
                'error' => '無法測量延遲'
            ];
        }

        return [
            'status' => 'success',
            'attempts' => $attempts,
            'successful_attempts' => count($latencies),
            'min_latency_ms' => min($latencies),
            'max_latency_ms' => max($latencies),
            'avg_latency_ms' => round(array_sum($latencies) / count($latencies), 2),
            'all_latencies' => $latencies
        ];
    }

    /**
     * 檢查是否為有效的JSON
     */
    private function isValidJson($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * 生成測試摘要
     */
    private function generateSummary($results) {
        $summary = [
            'overall_status' => 'unknown',
            'connection_available' => false,
            'api_responsive' => false,
            'average_latency_ms' => 0,
            'recommendations' => []
        ];

        // 檢查基本連線
        if ($results['basic_connection']['status'] === 'success') {
            $summary['connection_available'] = true;

            if ($results['basic_connection']['http_code'] >= 200 && $results['basic_connection']['http_code'] < 400) {
                $summary['api_responsive'] = true;
            }
        }

        // 檢查延遲
        if ($results['latency']['status'] === 'success') {
            $summary['average_latency_ms'] = $results['latency']['avg_latency_ms'];

            if ($results['latency']['avg_latency_ms'] > 2000) {
                $summary['recommendations'][] = '網路延遲較高，建議檢查網路連線';
            }
        }

        // 整體狀態判斷
        if ($summary['connection_available'] && $summary['api_responsive']) {
            $summary['overall_status'] = 'healthy';
        } elseif ($summary['connection_available']) {
            $summary['overall_status'] = 'connection_ok_api_issue';
            $summary['recommendations'][] = 'API 連線正常但回應異常，請檢查 API 端點';
        } else {
            $summary['overall_status'] = 'connection_failed';
            $summary['recommendations'][] = 'API 連線失敗，請檢查網址或網路狀況';
        }

        return $summary;
    }

    /**
     * 測試開單功能 (第85-86點要求)
     */
    public function testCreateOrder() {
        try {
            // 生成測試訂單資料
            $order_data = [
                'order_id' => 'TEST' . date('YmdHis') . rand(1000, 9999),
                'amount' => 100,
                'user_bank_code' => '004', // 台灣銀行
                'user_bank_account' => '1234567890123',
                'description' => 'ANT API 開單測試',
                'callback_url' => 'https://test.paygo.tw/ant_callback.php',
                'return_url' => 'https://test.paygo.tw/ant_return.php'
            ];

            // 準備API請求數據
            $api_data = [
                'api_token' => $this->api_token,
                'order_id' => $order_data['order_id'],
                'amount' => $order_data['amount'],
                'user_bank_code' => $order_data['user_bank_code'],
                'user_bank_account' => $order_data['user_bank_account'],
                'description' => $order_data['description'],
                'callback_url' => $order_data['callback_url'],
                'return_url' => $order_data['return_url'],
                'timestamp' => time()
            ];

            // 生成簽名
            $api_data['signature'] = $this->generateOrderSignature($api_data);

            // 測試多個可能的API端點
            $endpoints = [
                '/api/payment/create',
                '/payment/create',
                '/create',
                '/order/create'
            ];

            $results = [];
            foreach ($endpoints as $endpoint) {
                $result = $this->callOrderAPI($endpoint, $api_data);
                $results[$endpoint] = $result;

                // 如果找到成功的端點，記錄它
                if ($result['success']) {
                    break;
                }
            }

            return [
                'success' => true,
                'timestamp' => date('Y-m-d H:i:s'),
                'test_order_data' => $order_data,
                'api_tests' => $results,
                'api_credentials_used' => [
                    'api_token' => substr($this->api_token, 0, 20) . '...',
                    'hash_key' => substr($this->hash_key, 0, 10) . '...',
                    'hash_iv' => substr($this->hash_iv, 0, 10) . '...'
                ]
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
     * 生成開單API簽名
     */
    private function generateOrderSignature($data) {
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
     * 調用開單API
     */
    private function callOrderAPI($endpoint, $data) {
        $start_time = microtime(true);

        try {
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

            $end_time = microtime(true);
            $execution_time = round(($end_time - $start_time) * 1000, 2);

            if ($error) {
                return [
                    'success' => false,
                    'endpoint' => $endpoint,
                    'error' => $error,
                    'execution_time_ms' => $execution_time
                ];
            }

            return [
                'success' => ($http_code >= 200 && $http_code < 300),
                'endpoint' => $endpoint,
                'http_code' => $http_code,
                'response' => substr($response, 0, 500), // 限制回應長度
                'parsed_response' => json_decode($response, true),
                'execution_time_ms' => $execution_time
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'execution_time_ms' => round((microtime(true) - $start_time) * 1000, 2)
            ];
        }
    }
}

// 處理請求
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // 顯示測試頁面
        if (isset($_GET['test'])) {
            $tester = new ANTApiTester();

            if ($_GET['test'] === 'run') {
                // 執行連線測試
                $result = $tester->testConnection();
                echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } elseif ($_GET['test'] === 'order') {
                // 執行開單測試 (第85-86點要求)
                $result = $tester->testCreateOrder();
                echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
        } else {
            // 顯示測試介面
            ?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ANT API 連線測試</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .container { max-width: 800px; }
        .test-button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .test-button:hover { background: #005a87; }
        .result-box { margin-top: 20px; padding: 20px; background: #f5f5f5; border-radius: 4px; }
        .status-healthy { color: #28a745; }
        .status-warning { color: #ffc107; }
        .status-error { color: #dc3545; }
        pre { background: white; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>ANT API 完整測試工具</h1>
        <p>測試目標: <strong>https://api.nubitya.com</strong></p>

        <div style="margin: 20px 0; padding: 15px; background: #fff3cd; border-radius: 6px; border-left: 4px solid #ffc107;">
            <h3>🔐 使用真實API憑證 (第85點)</h3>
            <p><strong>API Token:</strong> dkTqv40XBDmvl...</p>
            <p><strong>Hash Key:</strong> lyAJwWnVAK...</p>
            <p><strong>Hash IV:</strong> yhncs1WpMo...</p>
        </div>

        <button class="test-button" onclick="runTest()">連線測試</button>
        <button class="test-button" onclick="runOrderTest()" style="background: #28a745;">開單測試 (第85-86點)</button>

        <div id="result" class="result-box" style="display: none;">
            <h3>測試結果</h3>
            <div id="result-content"></div>
        </div>
    </div>

    <script>
        async function runTest() {
            const button = document.querySelector('.test-button');
            const resultDiv = document.getElementById('result');
            const contentDiv = document.getElementById('result-content');

            button.disabled = true;
            button.textContent = '測試中...';

            try {
                const response = await fetch('?test=run');
                const data = await response.json();

                let statusClass = 'status-error';
                if (data.summary.overall_status === 'healthy') {
                    statusClass = 'status-healthy';
                } else if (data.summary.overall_status === 'connection_ok_api_issue') {
                    statusClass = 'status-warning';
                }

                contentDiv.innerHTML = `
                    <div class="${statusClass}">
                        <h4>整體狀態: ${data.summary.overall_status}</h4>
                    </div>
                    <p><strong>連線狀態:</strong> ${data.summary.connection_available ? '正常' : '失敗'}</p>
                    <p><strong>API 回應:</strong> ${data.summary.api_responsive ? '正常' : '異常'}</p>
                    <p><strong>平均延遲:</strong> ${data.summary.average_latency_ms} ms</p>
                    ${data.summary.recommendations.length > 0 ? `<p><strong>建議:</strong> ${data.summary.recommendations.join(', ')}</p>` : ''}
                    <details>
                        <summary>詳細測試結果</summary>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </details>
                `;

                resultDiv.style.display = 'block';

            } catch (error) {
                contentDiv.innerHTML = `<div class="status-error">測試失敗: ${error.message}</div>`;
                resultDiv.style.display = 'block';
            }

            button.disabled = false;
            button.textContent = '重新測試';
        }

        // 第85-86點：開單測試功能
        async function runOrderTest() {
            const button = event.target;
            const resultDiv = document.getElementById('result');
            const contentDiv = document.getElementById('result-content');

            button.disabled = true;
            button.textContent = '開單測試中...';

            try {
                const response = await fetch('?test=order');
                const data = await response.json();

                let statusClass = data.success ? 'status-healthy' : 'status-error';
                let statusIcon = data.success ? '✅' : '❌';

                contentDiv.innerHTML = `
                    <div class="${statusClass}">
                        <h4>${statusIcon} 開單測試結果</h4>
                    </div>
                    <p><strong>測試時間:</strong> ${data.timestamp}</p>
                    ${data.test_order_data ? `
                        <div style="background: #e7f3ff; padding: 10px; border-radius: 5px; margin: 10px 0;">
                            <h5>📝 測試訂單資料</h5>
                            <p><strong>訂單號:</strong> ${data.test_order_data.order_id}</p>
                            <p><strong>金額:</strong> ${data.test_order_data.amount} 元</p>
                            <p><strong>銀行代號:</strong> ${data.test_order_data.user_bank_code} (台灣銀行)</p>
                            <p><strong>銀行帳號:</strong> ${data.test_order_data.user_bank_account}</p>
                        </div>
                    ` : ''}
                    ${data.api_credentials_used ? `
                        <div style="background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0;">
                            <h5>🔐 使用的API憑證</h5>
                            <p><strong>API Token:</strong> ${data.api_credentials_used.api_token}</p>
                            <p><strong>Hash Key:</strong> ${data.api_credentials_used.hash_key}</p>
                            <p><strong>Hash IV:</strong> ${data.api_credentials_used.hash_iv}</p>
                        </div>
                    ` : ''}
                    ${data.error ? `<p style="color: #dc3545;"><strong>錯誤:</strong> ${data.error}</p>` : ''}
                    ${data.api_tests ? `
                        <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;">
                            <h5>🔧 API端點測試結果</h5>
                            ${Object.entries(data.api_tests).map(([endpoint, result]) => `
                                <p><strong>${endpoint}:</strong>
                                ${result.success ? '✅ 成功' : '❌ 失敗'}
                                (${result.http_code || 'N/A'})
                                ${result.execution_time_ms || 0}ms</p>
                            `).join('')}
                        </div>
                    ` : ''}
                    <details>
                        <summary>完整測試結果</summary>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </details>
                `;

                resultDiv.style.display = 'block';

            } catch (error) {
                contentDiv.innerHTML = `
                    <div class="status-error">❌ 開單測試失敗</div>
                    <p>錯誤: ${error.message}</p>
                `;
                resultDiv.style.display = 'block';
            }

            button.disabled = false;
            button.textContent = '重新開單測試';
        }
    </script>
</body>
</html>
            <?php
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 處理 POST 請求 (API 格式)
        $tester = new ANTApiTester();
        $result = $tester->testConnection();
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}
?>