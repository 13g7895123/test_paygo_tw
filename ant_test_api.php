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
}

// 處理請求
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // 顯示測試頁面
        if (isset($_GET['test']) && $_GET['test'] === 'run') {
            // 執行測試
            $tester = new ANTApiTester();
            $result = $tester->testConnection();
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
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
        <h1>ANT API 連線測試工具</h1>
        <p>測試目標: <strong>https://api.nubitya.com</strong></p>

        <button class="test-button" onclick="runTest()">開始測試</button>

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