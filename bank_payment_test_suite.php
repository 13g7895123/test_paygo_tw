<?php
/**
 * 銀行支付功能完整測試套件
 * 支援所有第三方金流服務商的銀行支付測試
 * 包含：ecpay, ebpay, gomypay, smilepay, funpoint, szfu, ant
 */

// 防止任何不期望的輸出
ob_start();

// 引用各家金流服務
try {
    require_once 'ant_api_service.php';
} catch (Error $e) {
    if (isset($_GET['action'])) {
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();
        echo json_encode(['error' => 'Failed to load payment services: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
    throw $e;
}

// 只有在API請求時才設定JSON header並清理輸出緩衝區
if (isset($_GET['action']) && in_array($_GET['action'], [
    'create_order', 'query_status', 'test_callback', 'test_refund',
    'batch_query', 'run_all_tests', 'test_scenarios', 'test_provider',
    'list_providers', 'test_all_providers'
])) {
    ob_clean(); // 清理任何之前的輸出
    header('Content-Type: application/json; charset=utf-8');
}

/**
 * 多金流銀行支付測試套件類別
 */
class BankPaymentTestSuite {

    private $payment_services = [];
    private $test_results = [];
    private $test_count = 0;
    private $passed_count = 0;
    private $failed_count = 0;
    private $supported_providers = [];

    public function __construct() {
        $this->initializeProviders();
    }

    /**
     * 初始化所有支援的金流服務商
     */
    private function initializeProviders() {
        // ANT 金流服務
        $this->payment_services['ant'] = new ANTApiService(
            'antpay018', // username
            'lyAJwWnVAKNScXjE6t2rxUOAeesvIP9S', // hash_key
            'yhncs1WpMo60azxEczokzIlVVvVuW69p', // hash_iv
            false // is_production
        );

        // 定義支援的金流服務商
        $this->supported_providers = [
            'ant' => [
                'name' => 'ANT支付',
                'fields' => ['username', 'hashkey', 'hashiv'],
                'test_credentials' => [
                    'username' => 'antpay018',
                    'hashkey' => 'lyAJwWnVAKNScXjE6t2rxUOAeesvIP9S',
                    'hashiv' => 'yhncs1WpMo60azxEczokzIlVVvVuW69p'
                ],
                'active' => true
            ],
            'ecpay' => [
                'name' => '綠界金流',
                'fields' => ['merchant_id', 'hashkey', 'hashiv'],
                'test_credentials' => [
                    'merchant_id' => 'Test_MerchantID_ECPAY',
                    'hashkey' => 'Test_HashKey_ECPAY',
                    'hashiv' => 'Test_HashIV_ECPAY'
                ],
                'active' => false // 需要實際API服務
            ],
            'ebpay' => [
                'name' => '藍新金流',
                'fields' => ['merchant_id', 'hashkey', 'hashiv'],
                'test_credentials' => [
                    'merchant_id' => 'Test_MerchantID_EBPAY',
                    'hashkey' => 'Test_HashKey_EBPAY',
                    'hashiv' => 'Test_HashIV_EBPAY'
                ],
                'active' => false // 需要實際API服務
            ],
            'gomypay' => [
                'name' => '萬事達金流',
                'fields' => ['merchant_id', 'verify_key'],
                'test_credentials' => [
                    'merchant_id' => 'Test_ShopID_GOMYPAY',
                    'verify_key' => 'Test_Key_GOMYPAY'
                ],
                'active' => false // 需要實際API服務
            ],
            'smilepay' => [
                'name' => '速買配金流',
                'fields' => ['merchant_id', 'verify_key'],
                'test_credentials' => [
                    'merchant_id' => 'Test_ShopID_SMILEPAY',
                    'verify_key' => 'Test_Key_SMILEPAY'
                ],
                'active' => false // 需要實際API服務
            ],
            'funpoint' => [
                'name' => '歐買尬金流',
                'fields' => ['merchant_id', 'hashkey', 'hashiv'],
                'test_credentials' => [
                    'merchant_id' => 'Test_MerchantID_FUNPOINT',
                    'hashkey' => 'Test_HashKey_FUNPOINT',
                    'hashiv' => 'Test_HashIV_FUNPOINT'
                ],
                'active' => false // 需要實際API服務
            ],
            'szfu' => [
                'name' => '數支付金流',
                'fields' => ['merchant_id', 'verify_key'],
                'test_credentials' => [
                    'merchant_id' => 'Test_ShopID_SZFU',
                    'verify_key' => 'Test_Key_SZFU'
                ],
                'active' => false // 需要實際API服務
            ]
        ];
    }

    /**
     * 取得所有支援的金流服務商清單
     */
    public function getSupportedProviders() {
        return $this->supported_providers;
    }

    /**
     * 測試特定金流服務商
     */
    public function testProvider($provider_code, $test_type = 'all') {
        if (!isset($this->supported_providers[$provider_code])) {
            return [
                'success' => false,
                'error' => "不支援的金流服務商: {$provider_code}",
                'code' => 'UNSUPPORTED_PROVIDER'
            ];
        }

        $provider = $this->supported_providers[$provider_code];
        $results = [];

        // 檢查是否為活動狀態
        if (!$provider['active']) {
            return [
                'success' => false,
                'provider_name' => $provider['name'],
                'error' => "金流服務商 {$provider['name']} 目前不可用，需要實際的API服務",
                'code' => 'PROVIDER_NOT_ACTIVE',
                'test_credentials' => $provider['test_credentials'],
                'required_fields' => $provider['fields']
            ];
        }

        $this->resetTestStats();

        try {
            switch ($test_type) {
                case 'create_order':
                    $results = $this->testCreateOrderForProvider($provider_code);
                    break;
                case 'query_status':
                    $results = $this->testQueryStatusForProvider($provider_code);
                    break;
                case 'callback':
                    $results = $this->testCallbackForProvider($provider_code);
                    break;
                case 'all':
                default:
                    $results = $this->runAllTestsForProvider($provider_code);
                    break;
            }

            return [
                'success' => true,
                'provider_name' => $provider['name'],
                'provider_code' => $provider_code,
                'test_type' => $test_type,
                'results' => $results,
                'summary' => [
                    'total_tests' => $this->test_count,
                    'passed' => $this->passed_count,
                    'failed' => $this->failed_count,
                    'success_rate' => $this->test_count > 0 ? round(($this->passed_count / $this->test_count) * 100, 2) : 0
                ]
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'provider_name' => $provider['name'],
                'error' => $e->getMessage(),
                'code' => 'PROVIDER_TEST_ERROR'
            ];
        }
    }

    /**
     * 測試所有活動的金流服務商
     */
    public function testAllProviders() {
        $results = [];
        $overall_stats = [
            'total_providers' => 0,
            'active_providers' => 0,
            'inactive_providers' => 0,
            'successful_providers' => 0,
            'failed_providers' => 0
        ];

        foreach ($this->supported_providers as $provider_code => $provider) {
            $overall_stats['total_providers']++;

            if ($provider['active']) {
                $overall_stats['active_providers']++;
                $provider_result = $this->testProvider($provider_code, 'all');

                if ($provider_result['success']) {
                    $overall_stats['successful_providers']++;
                } else {
                    $overall_stats['failed_providers']++;
                }

                $results[$provider_code] = $provider_result;
            } else {
                $overall_stats['inactive_providers']++;
                $results[$provider_code] = [
                    'success' => false,
                    'provider_name' => $provider['name'],
                    'status' => 'inactive',
                    'message' => '服務商目前不可用，需要實際的API憑證',
                    'required_fields' => $provider['fields'],
                    'test_credentials' => $provider['test_credentials']
                ];
            }
        }

        return [
            'success' => true,
            'overall_stats' => $overall_stats,
            'provider_results' => $results,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * 執行所有測試
     */
    public function runAllTests() {
        $this->resetTestStats();

        // 基本功能測試
        $this->testCreateOrder();
        $this->testQueryStatus();
        $this->testCallback();

        // 錯誤情境測試
        $this->testErrorScenarios();

        // 邊界條件測試
        $this->testBoundaryConditions();

        // 不同銀行測試
        $this->testDifferentBanks();

        // 性能測試
        $this->testPerformance();

        return $this->generateTestReport();
    }

    /**
     * 測試建立訂單
     */
    public function testCreateOrder($custom_data = null) {
        $test_cases = [
            '正常訂單' => [
                'amount' => 100,
                'bank_code' => '004',
                'bank_account' => '1234567890123456'
            ],
            '大金額訂單' => [
                'amount' => 50000,
                'bank_code' => '007',
                'bank_account' => '9876543210987654'
            ],
            '小金額訂單' => [
                'amount' => 1,
                'bank_code' => '812',
                'bank_account' => '1111222233334444'
            ]
        ];

        $results = [];

        foreach ($test_cases as $case_name => $test_data) {
            $data = $custom_data ?: $test_data;
            $start_time = microtime(true);

            try {
                // 準備訂單資料
                $order_data = [
                    'order_id' => 'TEST_' . date('YmdHis') . '_' . rand(1000, 9999),
                    'amount' => (int)$data['amount'],
                    'user_bank_code' => $data['bank_code'],
                    'user_bank_account' => $data['bank_account'],
                    'item_name' => 'Bank Payment Test - ' . $case_name,
                    'trade_desc' => 'Test transaction for ' . $case_name,
                    'notify_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/ant_callback.php',
                    'remark' => 'Automated test case: ' . $case_name
                ];

                // 調用ANT API
                $result = $this->payment_services['ant']->createPayment($order_data);

                $execution_time = (microtime(true) - $start_time) * 1000;

                $test_result = [
                    'case_name' => $case_name,
                    'success' => $result !== false,
                    'execution_time' => round($execution_time, 2),
                    'result' => $result,
                    'test_data' => $order_data
                ];

                $results[] = $test_result;
                $this->recordTestResult($test_result);

            } catch (Exception $e) {
                $test_result = [
                    'case_name' => $case_name,
                    'success' => false,
                    'error' => $e->getMessage(),
                    'execution_time' => round((microtime(true) - $start_time) * 1000, 2)
                ];

                $results[] = $test_result;
                $this->recordTestResult($test_result);
            }
        }

        return $results;
    }

    /**
     * 測試查詢訂單狀態
     */
    public function testQueryStatus() {
        $test_orders = [
            'TEST20241201001',
            'TEST20241201002',
            'NONEXISTENT_ORDER'
        ];

        $results = [];

        foreach ($test_orders as $order_id) {
            $start_time = microtime(true);

            try {
                $result = $this->payment_services['ant']->queryPaymentStatus($order_id);
                $execution_time = (microtime(true) - $start_time) * 1000;

                $test_result = [
                    'case_name' => "查詢訂單: $order_id",
                    'success' => $result !== false,
                    'execution_time' => round($execution_time, 2),
                    'result' => $result
                ];

                $results[] = $test_result;
                $this->recordTestResult($test_result);

            } catch (Exception $e) {
                $test_result = [
                    'case_name' => "查詢訂單: $order_id",
                    'success' => false,
                    'error' => $e->getMessage(),
                    'execution_time' => round((microtime(true) - $start_time) * 1000, 2)
                ];

                $results[] = $test_result;
                $this->recordTestResult($test_result);
            }
        }

        return $results;
    }

    /**
     * 測試回調處理
     */
    public function testCallback() {
        $callback_scenarios = [
            '成功支付回調' => [
                'order_id' => 'TEST_CALLBACK_SUCCESS',
                'status' => 4,
                'amount' => 100,
                'partner_number' => 'TEST123'
            ],
            '失敗支付回調' => [
                'order_id' => 'TEST_CALLBACK_FAIL',
                'status' => 7,
                'amount' => 100,
                'partner_number' => 'TEST456'
            ],
            '取消支付回調' => [
                'order_id' => 'TEST_CALLBACK_CANCEL',
                'status' => 5,
                'amount' => 100,
                'partner_number' => 'TEST789'
            ]
        ];

        $results = [];

        foreach ($callback_scenarios as $scenario_name => $callback_data) {
            $start_time = microtime(true);

            try {
                // 模擬回調處理
                $result = $this->simulateCallbackProcessing($callback_data);
                $execution_time = (microtime(true) - $start_time) * 1000;

                $test_result = [
                    'case_name' => $scenario_name,
                    'success' => $result,
                    'execution_time' => round($execution_time, 2),
                    'callback_data' => $callback_data
                ];

                $results[] = $test_result;
                $this->recordTestResult($test_result);

            } catch (Exception $e) {
                $test_result = [
                    'case_name' => $scenario_name,
                    'success' => false,
                    'error' => $e->getMessage(),
                    'execution_time' => round((microtime(true) - $start_time) * 1000, 2)
                ];

                $results[] = $test_result;
                $this->recordTestResult($test_result);
            }
        }

        return $results;
    }

    /**
     * 錯誤情境測試
     */
    public function testErrorScenarios() {
        $error_cases = [
            '無效金額' => [
                'amount' => -100,
                'bank_code' => '004',
                'bank_account' => '1234567890123456'
            ],
            '無效銀行代碼' => [
                'amount' => 100,
                'bank_code' => '999',
                'bank_account' => '1234567890123456'
            ],
            '無效帳號格式' => [
                'amount' => 100,
                'bank_code' => '004',
                'bank_account' => '123'
            ],
            '空值測試' => [
                'amount' => null,
                'bank_code' => null,
                'bank_account' => null
            ]
        ];

        $results = [];

        foreach ($error_cases as $case_name => $test_data) {
            $start_time = microtime(true);

            try {
                $order_data = [
                    'order_id' => 'ERROR_TEST_' . date('YmdHis') . '_' . rand(1000, 9999),
                    'amount' => $test_data['amount'],
                    'user_bank_code' => $test_data['bank_code'],
                    'user_bank_account' => $test_data['bank_account'],
                    'item_name' => 'Error Test - ' . $case_name,
                    'trade_desc' => 'Error scenario test',
                    'notify_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/ant_callback.php'
                ];

                $result = $this->payment_services['ant']->createPayment($order_data);

                // 錯誤情境應該返回false或拋出異常
                $test_success = ($result === false);
                $execution_time = (microtime(true) - $start_time) * 1000;

                $test_result = [
                    'case_name' => $case_name,
                    'success' => $test_success,
                    'execution_time' => round($execution_time, 2),
                    'result' => $result,
                    'expected' => '應該失敗'
                ];

                $results[] = $test_result;
                $this->recordTestResult($test_result);

            } catch (Exception $e) {
                // 拋出異常是預期的
                $test_result = [
                    'case_name' => $case_name,
                    'success' => true,
                    'execution_time' => round((microtime(true) - $start_time) * 1000, 2),
                    'error' => $e->getMessage(),
                    'expected' => '預期錯誤'
                ];

                $results[] = $test_result;
                $this->recordTestResult($test_result);
            }
        }

        return $results;
    }

    /**
     * 邊界條件測試
     */
    public function testBoundaryConditions() {
        $boundary_cases = [
            '最小金額' => ['amount' => 1],
            '最大金額' => ['amount' => 999999],
            '最短帳號' => ['bank_account' => '1234567890123456'],
            '最長帳號' => ['bank_account' => '1234567890123456789012345678901234567890']
        ];

        $results = [];

        foreach ($boundary_cases as $case_name => $test_data) {
            $start_time = microtime(true);

            try {
                $order_data = [
                    'order_id' => 'BOUNDARY_' . date('YmdHis') . '_' . rand(1000, 9999),
                    'amount' => $test_data['amount'] ?? 100,
                    'user_bank_code' => '004',
                    'user_bank_account' => $test_data['bank_account'] ?? '1234567890123456',
                    'item_name' => 'Boundary Test - ' . $case_name,
                    'trade_desc' => 'Boundary condition test',
                    'notify_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/ant_callback.php'
                ];

                $result = $this->payment_services['ant']->createPayment($order_data);
                $execution_time = (microtime(true) - $start_time) * 1000;

                $test_result = [
                    'case_name' => $case_name,
                    'success' => $result !== false,
                    'execution_time' => round($execution_time, 2),
                    'result' => $result
                ];

                $results[] = $test_result;
                $this->recordTestResult($test_result);

            } catch (Exception $e) {
                $test_result = [
                    'case_name' => $case_name,
                    'success' => false,
                    'error' => $e->getMessage(),
                    'execution_time' => round((microtime(true) - $start_time) * 1000, 2)
                ];

                $results[] = $test_result;
                $this->recordTestResult($test_result);
            }
        }

        return $results;
    }

    /**
     * 不同銀行測試
     */
    public function testDifferentBanks() {
        $banks = [
            '004' => '台灣銀行',
            '005' => '台灣土地銀行',
            '006' => '合作金庫銀行',
            '007' => '第一商業銀行',
            '008' => '華南商業銀行',
            '009' => '彰化商業銀行',
            '011' => '上海商業儲蓄銀行',
            '012' => '台北富邦銀行',
            '812' => '台新銀行'
        ];

        $results = [];

        foreach ($banks as $bank_code => $bank_name) {
            $start_time = microtime(true);

            try {
                $order_data = [
                    'order_id' => 'BANK_' . $bank_code . '_' . date('YmdHis'),
                    'amount' => 100,
                    'user_bank_code' => $bank_code,
                    'user_bank_account' => '1234567890123456',
                    'item_name' => $bank_name . ' 測試',
                    'trade_desc' => $bank_name . ' 銀行轉帳測試',
                    'notify_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/ant_callback.php'
                ];

                $result = $this->payment_services['ant']->createPayment($order_data);
                $execution_time = (microtime(true) - $start_time) * 1000;

                $test_result = [
                    'case_name' => $bank_name . "($bank_code)",
                    'success' => $result !== false,
                    'execution_time' => round($execution_time, 2),
                    'result' => $result
                ];

                $results[] = $test_result;
                $this->recordTestResult($test_result);

            } catch (Exception $e) {
                $test_result = [
                    'case_name' => $bank_name . "($bank_code)",
                    'success' => false,
                    'error' => $e->getMessage(),
                    'execution_time' => round((microtime(true) - $start_time) * 1000, 2)
                ];

                $results[] = $test_result;
                $this->recordTestResult($test_result);
            }
        }

        return $results;
    }

    /**
     * 性能測試
     */
    public function testPerformance() {
        $results = [];
        $concurrent_requests = 5;
        $start_time = microtime(true);

        // 模擬併發請求
        for ($i = 0; $i < $concurrent_requests; $i++) {
            $order_data = [
                'order_id' => 'PERF_' . date('YmdHis') . '_' . $i,
                'amount' => 100,
                'user_bank_code' => '004',
                'user_bank_account' => '1234567890123456',
                'item_name' => '性能測試 #' . $i,
                'trade_desc' => '性能測試請求',
                'notify_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/ant_callback.php'
            ];

            try {
                $request_start = microtime(true);
                $result = $this->payment_services['ant']->createPayment($order_data);
                $request_time = (microtime(true) - $request_start) * 1000;

                $test_result = [
                    'case_name' => "性能測試 #$i",
                    'success' => $result !== false,
                    'execution_time' => round($request_time, 2),
                    'result' => $result
                ];

                $results[] = $test_result;
                $this->recordTestResult($test_result);

            } catch (Exception $e) {
                $test_result = [
                    'case_name' => "性能測試 #$i",
                    'success' => false,
                    'error' => $e->getMessage(),
                    'execution_time' => round((microtime(true) - $start_time) * 1000, 2)
                ];

                $results[] = $test_result;
                $this->recordTestResult($test_result);
            }
        }

        $total_time = (microtime(true) - $start_time) * 1000;

        // 添加性能統計
        $performance_summary = [
            'case_name' => '性能統計',
            'success' => true,
            'total_requests' => $concurrent_requests,
            'total_time' => round($total_time, 2),
            'average_time' => round($total_time / $concurrent_requests, 2),
            'requests_per_second' => round($concurrent_requests / ($total_time / 1000), 2)
        ];

        $results[] = $performance_summary;

        return $results;
    }

    /**
     * 模擬回調處理
     */
    private function simulateCallbackProcessing($callback_data) {
        // 模擬回調處理邏輯
        if (!isset($callback_data['order_id']) || !isset($callback_data['status'])) {
            return false;
        }

        // 記錄回調處理
        error_log("Callback processed: " . json_encode($callback_data));

        return true;
    }

    /**
     * 重置測試統計
     */
    private function resetTestStats() {
        $this->test_results = [];
        $this->test_count = 0;
        $this->passed_count = 0;
        $this->failed_count = 0;
    }

    /**
     * 記錄測試結果
     */
    private function recordTestResult($result) {
        $this->test_results[] = $result;
        $this->test_count++;

        if ($result['success']) {
            $this->passed_count++;
        } else {
            $this->failed_count++;
        }
    }

    /**
     * 生成測試報告
     */
    private function generateTestReport() {
        return [
            'summary' => [
                'total_tests' => $this->test_count,
                'passed' => $this->passed_count,
                'failed' => $this->failed_count,
                'success_rate' => $this->test_count > 0 ? round(($this->passed_count / $this->test_count) * 100, 2) : 0,
                'timestamp' => date('Y-m-d H:i:s')
            ],
            'results' => $this->test_results
        ];
    }
}

// 處理API請求
if (isset($_GET['action'])) {
    try {
        $tester = new BankPaymentTestSuite();
    } catch (Exception $e) {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Failed to initialize test suite: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }

    switch ($_GET['action']) {
        case 'run_all_tests':
            try {
                ob_clean();
                $result = $tester->runAllTests();
                echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } catch (Exception $e) {
                ob_clean();
                echo json_encode(['error' => 'Run all tests failed: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            exit;

        case 'create_order':
            try {
                ob_clean();
                $test_data = null;
                if (isset($_POST['amount'], $_POST['bank_code'], $_POST['bank_account'])) {
                    $test_data = [
                        'amount' => (int)$_POST['amount'],
                        'bank_code' => $_POST['bank_code'],
                        'bank_account' => $_POST['bank_account']
                    ];
                }
                $result = $tester->testCreateOrder($test_data);
                echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } catch (Exception $e) {
                ob_clean();
                echo json_encode(['error' => 'Create order test failed: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            exit;

        case 'query_status':
            try {
                ob_clean();
                $result = $tester->testQueryStatus();
                echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } catch (Exception $e) {
                ob_clean();
                echo json_encode(['error' => 'Query status test failed: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            exit;

        case 'test_callback':
            try {
                ob_clean();
                $result = $tester->testCallback();
                echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } catch (Exception $e) {
                ob_clean();
                echo json_encode(['error' => 'Callback test failed: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            exit;

        case 'test_scenarios':
            try {
                $scenario = $_GET['scenario'] ?? 'error';
                $provider = $_GET['provider'] ?? 'ant';

                switch ($scenario) {
                    case 'error':
                        $result = $tester->testErrorScenariosForProvider($provider);
                        break;
                    case 'boundary':
                        $result = $tester->testBoundaryConditionsForProvider($provider);
                        break;
                    case 'banks':
                        $result = $tester->testDifferentBanksForProvider($provider);
                        break;
                    case 'performance':
                        $result = $tester->testPerformanceForProvider($provider);
                        break;
                    default:
                        $result = ['error' => 'Unknown scenario'];
                }
                ob_clean();
                echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } catch (Exception $e) {
                ob_clean();
                echo json_encode(['error' => 'Scenario test failed: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            exit;

        case 'list_providers':
            try {
                ob_clean();
                $result = $tester->getSupportedProviders();
                echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } catch (Exception $e) {
                ob_clean();
                echo json_encode(['error' => 'List providers failed: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            exit;

        case 'test_provider':
            try {
                $provider_code = $_GET['provider'] ?? 'ant';
                $test_type = $_GET['test_type'] ?? 'all';
                ob_clean();
                $result = $tester->testProvider($provider_code, $test_type);
                echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } catch (Exception $e) {
                ob_clean();
                echo json_encode(['error' => 'Provider test failed: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            exit;

        case 'test_all_providers':
            try {
                ob_clean();
                $result = $tester->testAllProviders();
                echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } catch (Exception $e) {
                ob_clean();
                echo json_encode(['error' => 'Test all providers failed: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            exit;

        default:
            ob_clean();
            echo json_encode(['error' => 'Invalid action'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
    }
}

// 如果不是API請求，清理輸出緩衝區並顯示HTML頁面
ob_end_clean();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>銀行支付功能測試套件</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #333; text-align: center; margin-bottom: 30px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #fafafa; }
        .test-section h3 { color: #666; margin-top: 0; }
        .test-buttons { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 20px 0; }
        .test-btn { background: #007bff; color: white; border: none; padding: 15px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; transition: all 0.3s; }
        .test-btn:hover { background: #0056b3; transform: translateY(-1px); }
        .test-btn.danger { background: #dc3545; }
        .test-btn.danger:hover { background: #c82333; }
        .test-btn.success { background: #28a745; }
        .test-btn.success:hover { background: #218838; }
        .test-btn.warning { background: #ffc107; color: #212529; }
        .test-btn.warning:hover { background: #e0a800; }
        .results { margin: 20px 0; }
        .result-item { margin: 10px 0; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff; }
        .result-item.success { background: #d4edda; border-left-color: #28a745; }
        .result-item.error { background: #f8d7da; border-left-color: #dc3545; }
        .result-item.warning { background: #fff3cd; border-left-color: #ffc107; }
        .loading { display: none; text-align: center; padding: 20px; color: #666; }
        .loading.show { display: block; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; text-align: center; border: 1px solid #ddd; }
        .stat-number { font-size: 2em; font-weight: bold; color: #007bff; }
        .stat-label { color: #666; margin-top: 5px; }
        pre { background: #f8f9fa; border: 1px solid #e9ecef; padding: 15px; border-radius: 5px; overflow-x: auto; white-space: pre-wrap; max-height: 400px; overflow-y: auto; }
        .custom-test { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .custom-test input, .custom-test select { padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏦 銀行支付功能測試套件</h1>

        <div class="stats" id="test-stats" style="display: none;">
            <div class="stat-card">
                <div class="stat-number" id="total-tests">0</div>
                <div class="stat-label">總測試數</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="passed-tests" style="color: #28a745;">0</div>
                <div class="stat-label">通過測試</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="failed-tests" style="color: #dc3545;">0</div>
                <div class="stat-label">失敗測試</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="success-rate">0%</div>
                <div class="stat-label">成功率</div>
            </div>
        </div>

        <div class="test-section">
            <h3>🏦 金流服務商測試</h3>
            <div class="test-buttons">
                <button class="test-btn success" onclick="testAllProviders()">
                    🔥 測試所有金流服務商
                </button>
                <button class="test-btn" onclick="showProviderList()">
                    📝 查看支援的金流服務商
                </button>
                <div class="provider-selector" style="margin-top: 15px;">
                    <select id="provider-select" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-right: 10px;">
                        <option value="ant">ANT支付</option>
                        <option value="ecpay">綠界金流</option>
                        <option value="ebpay">藍新金流</option>
                        <option value="gomypay">萬事達金流</option>
                        <option value="smilepay">速買配金流</option>
                        <option value="funpoint">歐買尬金流</option>
                        <option value="szfu">數支付金流</option>
                    </select>
                    <button class="test-btn" onclick="testSelectedProvider()">
                        🎯 測試選擇的金流服務商
                    </button>
                </div>
            </div>
        </div>

        <div class="test-section">
            <h3>🚀 快速測試 (ANT金流)</h3>
            <div class="test-buttons">
                <button class="test-btn success" onclick="runAllTests()">
                    🔥 執行所有測試
                </button>
                <button class="test-btn" onclick="runSingleTest('create_order')">
                    📝 建立訂單測試
                </button>
                <button class="test-btn" onclick="runSingleTest('query_status')">
                    🔍 查詢狀態測試
                </button>
                <button class="test-btn" onclick="runSingleTest('test_callback')">
                    📞 回調處理測試
                </button>
            </div>
        </div>

        <div class="test-section">
            <h3>🧪 情境測試</h3>
            <div class="test-buttons">
                <button class="test-btn danger" onclick="runScenarioTest('error')">
                    ❌ 錯誤情境測試
                </button>
                <button class="test-btn warning" onclick="runScenarioTest('boundary')">
                    📏 邊界條件測試
                </button>
                <button class="test-btn" onclick="runScenarioTest('banks')">
                    🏦 不同銀行測試
                </button>
                <button class="test-btn" onclick="runScenarioTest('performance')">
                    ⚡ 性能測試
                </button>
            </div>
        </div>

        <div class="test-section">
            <h3>🎯 自訂測試</h3>
            <div class="custom-test">
                <input type="number" id="custom-amount" placeholder="金額" value="100" min="1">
                <select id="custom-bank">
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
                <input type="text" id="custom-account" placeholder="銀行帳號" value="1234567890123456" maxlength="20">
                <button class="test-btn" onclick="runCustomTest()">
                    🎯 執行自訂測試
                </button>
            </div>
        </div>

        <div class="loading" id="loading">
            <div>⏳ 正在執行測試，請稍候...</div>
        </div>

        <div class="results" id="results">
            <div class="result-item" style="background: #e7f3ff; border-left-color: #007bff;">
                <strong>📋 測試說明</strong>
                <p>此測試套件包含銀行支付功能的完整測試，包括正常流程、錯誤處理、邊界條件等各種情境。點擊上方按鈕開始測試。</p>
                <ul>
                    <li><strong>建立訂單測試：</strong>測試各種金額和銀行的訂單建立</li>
                    <li><strong>查詢狀態測試：</strong>測試訂單狀態查詢功能</li>
                    <li><strong>回調處理測試：</strong>測試支付完成後的回調處理</li>
                    <li><strong>錯誤情境測試：</strong>測試各種錯誤輸入的處理</li>
                    <li><strong>性能測試：</strong>測試API響應速度和併發處理能力</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        let testHistory = [];

        function showLoading() {
            document.getElementById('loading').classList.add('show');
            document.getElementById('results').style.display = 'none';
        }

        function hideLoading() {
            document.getElementById('loading').classList.remove('show');
            document.getElementById('results').style.display = 'block';
        }

        function updateStats(summary) {
            const statsDiv = document.getElementById('test-stats');
            if (summary) {
                document.getElementById('total-tests').textContent = summary.total_tests || 0;
                document.getElementById('passed-tests').textContent = summary.passed || 0;
                document.getElementById('failed-tests').textContent = summary.failed || 0;
                document.getElementById('success-rate').textContent = (summary.success_rate || 0) + '%';
                statsDiv.style.display = 'grid';
            }
        }

        function displayResults(results, title = '測試結果') {
            const resultsDiv = document.getElementById('results');
            let html = `<h3>${title}</h3>`;

            if (results.summary) {
                updateStats(results.summary);
                results = results.results;
            }

            if (Array.isArray(results)) {
                results.forEach((result, index) => {
                    const statusClass = result.success ? 'success' : 'error';
                    const statusIcon = result.success ? '✅' : '❌';
                    const timeInfo = result.execution_time ? ` (${result.execution_time}ms)` : '';

                    html += `
                        <div class="result-item ${statusClass}">
                            <strong>${statusIcon} ${result.case_name}${timeInfo}</strong>
                            ${result.error ? `<p><strong>錯誤：</strong>${result.error}</p>` : ''}
                            ${result.expected ? `<p><strong>預期：</strong>${result.expected}</p>` : ''}
                            ${result.result ? `<details><summary>詳細結果</summary><pre>${JSON.stringify(result.result, null, 2)}</pre></details>` : ''}
                        </div>
                    `;
                });
            } else {
                html += `<div class="result-item error"><strong>❌ 測試執行失敗</strong><pre>${JSON.stringify(results, null, 2)}</pre></div>`;
            }

            resultsDiv.innerHTML = html;
        }

        async function runAllTests() {
            showLoading();

            try {
                const response = await fetch('?action=run_all_tests');
                const results = await response.json();
                displayResults(results, '🔥 完整測試報告');
                testHistory.unshift({
                    type: '完整測試',
                    timestamp: new Date().toLocaleString(),
                    results: results
                });
            } catch (error) {
                displayResults({error: error.message}, '測試執行錯誤');
            }

            hideLoading();
        }

        async function runSingleTest(testType) {
            showLoading();

            try {
                const response = await fetch(`?action=${testType}`, {
                    method: 'POST'
                });
                const results = await response.json();
                displayResults(results, getTestTitle(testType));
                testHistory.unshift({
                    type: getTestTitle(testType),
                    timestamp: new Date().toLocaleString(),
                    results: results
                });
            } catch (error) {
                displayResults({error: error.message}, '測試執行錯誤');
            }

            hideLoading();
        }

        async function runScenarioTest(scenario) {
            showLoading();

            try {
                const response = await fetch(`?action=test_scenarios&scenario=${scenario}`);
                const results = await response.json();
                displayResults(results, getScenarioTitle(scenario));
                testHistory.unshift({
                    type: getScenarioTitle(scenario),
                    timestamp: new Date().toLocaleString(),
                    results: results
                });
            } catch (error) {
                displayResults({error: error.message}, '測試執行錯誤');
            }

            hideLoading();
        }

        async function runCustomTest() {
            const amount = document.getElementById('custom-amount').value;
            const bankCode = document.getElementById('custom-bank').value;
            const bankAccount = document.getElementById('custom-account').value;

            if (!amount || !bankCode || !bankAccount) {
                alert('請填寫完整的測試參數');
                return;
            }

            showLoading();

            try {
                const formData = new FormData();
                formData.append('amount', amount);
                formData.append('bank_code', bankCode);
                formData.append('bank_account', bankAccount);

                const response = await fetch('?action=create_order', {
                    method: 'POST',
                    body: formData
                });
                const results = await response.json();
                displayResults(results, '🎯 自訂測試結果');
                testHistory.unshift({
                    type: '自訂測試',
                    timestamp: new Date().toLocaleString(),
                    results: results
                });
            } catch (error) {
                displayResults({error: error.message}, '測試執行錯誤');
            }

            hideLoading();
        }

        function getTestTitle(testType) {
            const titles = {
                'create_order': '📝 建立訂單測試結果',
                'query_status': '🔍 查詢狀態測試結果',
                'test_callback': '📞 回調處理測試結果'
            };
            return titles[testType] || '測試結果';
        }

        function getScenarioTitle(scenario) {
            const titles = {
                'error': '❌ 錯誤情境測試結果',
                'boundary': '📏 邊界條件測試結果',
                'banks': '🏦 不同銀行測試結果',
                'performance': '⚡ 性能測試結果'
            };
            return titles[scenario] || '情境測試結果';
        }

        // 新增的多金流測試函數
        async function testAllProviders() {
            showLoading();

            try {
                const response = await fetch('?action=test_all_providers');
                const results = await response.json();
                displayProviderResults(results, '🏦 所有金流服務商測試結果');
                testHistory.unshift({
                    type: '所有金流服務商測試',
                    timestamp: new Date().toLocaleString(),
                    results: results
                });
            } catch (error) {
                displayResults({error: error.message}, '測試執行錯誤');
            }

            hideLoading();
        }

        async function showProviderList() {
            showLoading();

            try {
                const response = await fetch('?action=list_providers');
                const providers = await response.json();
                displayProviderList(providers);
            } catch (error) {
                displayResults({error: error.message}, '取得金流服務商清單錯誤');
            }

            hideLoading();
        }

        async function testSelectedProvider() {
            const provider = document.getElementById('provider-select').value;
            if (!provider) {
                alert('請選擇金流服務商');
                return;
            }

            showLoading();

            try {
                const response = await fetch(`?action=test_provider&provider=${provider}&test_type=all`);
                const results = await response.json();
                displayProviderTestResult(results, `🎯 ${results.provider_name || provider} 測試結果`);
                testHistory.unshift({
                    type: `單一金流服務商測試 - ${results.provider_name || provider}`,
                    timestamp: new Date().toLocaleString(),
                    results: results
                });
            } catch (error) {
                displayResults({error: error.message}, '測試執行錯誤');
            }

            hideLoading();
        }

        function displayProviderResults(results, title) {
            const resultsDiv = document.getElementById('results');
            let html = `<h3>${title}</h3>`;

            if (results.overall_stats) {
                html += `
                    <div class="result-item" style="background: #e7f3ff; border-left-color: #007bff;">
                        <strong>📊 整體統計</strong>
                        <p>🏦 總金流服務商數: ${results.overall_stats.total_providers}</p>
                        <p>✅ 活動服務商: ${results.overall_stats.active_providers}</p>
                        <p>❌ 非活動服務商: ${results.overall_stats.inactive_providers}</p>
                        <p>✓ 測試成功: ${results.overall_stats.successful_providers}</p>
                        <p>✗ 測試失敗: ${results.overall_stats.failed_providers}</p>
                    </div>
                `;
            }

            if (results.provider_results) {
                for (const [providerCode, providerResult] of Object.entries(results.provider_results)) {
                    const statusClass = providerResult.success ? 'success' : (providerResult.status === 'inactive' ? 'warning' : 'error');
                    const statusIcon = providerResult.success ? '✅' : (providerResult.status === 'inactive' ? '⚠️' : '❌');

                    html += `
                        <div class="result-item ${statusClass}">
                            <strong>${statusIcon} ${providerResult.provider_name} (${providerCode})</strong>
                            ${providerResult.message ? `<p>${providerResult.message}</p>` : ''}
                            ${providerResult.status === 'inactive' ? `
                                <p><strong>需要的欄位:</strong> ${providerResult.required_fields.join(', ')}</p>
                                <details><summary>測試憑證</summary><pre>${JSON.stringify(providerResult.test_credentials, null, 2)}</pre></details>
                            ` : ''}
                            ${providerResult.summary ? `
                                <p><strong>測試統計:</strong> 總測試 ${providerResult.summary.total_tests}, 通過 ${providerResult.summary.passed}, 失敗 ${providerResult.summary.failed}, 成功率 ${providerResult.summary.success_rate}%</p>
                            ` : ''}
                        </div>
                    `;
                }
            }

            resultsDiv.innerHTML = html;
        }

        function displayProviderTestResult(result, title) {
            const resultsDiv = document.getElementById('results');
            let html = `<h3>${title}</h3>`;

            if (!result.success) {
                html += `
                    <div class="result-item error">
                        <strong>❌ 測試失敗</strong>
                        <p><strong>錯誤:</strong> ${result.error}</p>
                        ${result.required_fields ? `<p><strong>需要的欄位:</strong> ${result.required_fields.join(', ')}</p>` : ''}
                        ${result.test_credentials ? `<details><summary>測試憑證</summary><pre>${JSON.stringify(result.test_credentials, null, 2)}</pre></details>` : ''}
                    </div>
                `;
            } else {
                if (result.summary) {
                    updateStats(result.summary);
                }

                // 顯示測試結果
                if (result.results && Array.isArray(result.results)) {
                    result.results.forEach((testResult, index) => {
                        const statusClass = testResult.success ? 'success' : 'error';
                        const statusIcon = testResult.success ? '✅' : '❌';
                        const timeInfo = testResult.execution_time ? ` (${testResult.execution_time}ms)` : '';
                        const simulatedBadge = testResult.simulated ? ' 🔸模擬' : '';

                        html += `
                            <div class="result-item ${statusClass}">
                                <strong>${statusIcon} ${testResult.case_name}${timeInfo}${simulatedBadge}</strong>
                                ${testResult.error ? `<p><strong>錯誤:</strong>${testResult.error}</p>` : ''}
                                ${testResult.result ? `<details><summary>詳細結果</summary><pre>${JSON.stringify(testResult.result, null, 2)}</pre></details>` : ''}
                            </div>
                        `;
                    });
                }
            }

            resultsDiv.innerHTML = html;
        }

        function displayProviderList(providers) {
            const resultsDiv = document.getElementById('results');
            let html = `<h3>🏦 支援的金流服務商清單</h3>`;

            for (const [code, provider] of Object.entries(providers)) {
                const statusClass = provider.active ? 'success' : 'warning';
                const statusIcon = provider.active ? '✅' : '⚠️';
                const statusText = provider.active ? '活動' : '非活動';

                html += `
                    <div class="result-item ${statusClass}">
                        <strong>${statusIcon} ${provider.name} (${code}) - ${statusText}</strong>
                        <p><strong>需要欄位:</strong> ${provider.fields.join(', ')}</p>
                        ${!provider.active ? '<p><strong>說明:</strong> 需要實際的API憑證才可進行測試</p>' : ''}
                        <details><summary>測試憑證範例</summary><pre>${JSON.stringify(provider.test_credentials, null, 2)}</pre></details>
                    </div>
                `;
            }

            resultsDiv.innerHTML = html;
        }

        // 頁面載入完成
        document.addEventListener('DOMContentLoaded', function() {
            console.log('多金流銀行支付測試套件已就緒');

            // 初始化金流服務商清單
            showProviderList();
        });
    </script>
</body>
</html>