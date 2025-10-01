<?php
/**
 * Bank Funds Migration API
 * 從 servers 表匯入資料到 bank_funds 表
 */

// 啟動會話
if (!isset($_SESSION)) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://test.paygo.tw');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// 處理 OPTIONS 預檢請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include("../include.php");

// 設定執行時間和記憶體限制
set_time_limit(300); // 5分鐘
ini_set('memory_limit', '512M');

// 錯誤處理函數
function api_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// 成功響應函數
function api_success($data = null, $message = null) {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// 權限檢查函數
function check_permission() {
    // 只有管理員可以執行遷移
    if (empty($_SESSION["adminid"])) {
        api_error('Access denied: Only administrators can perform migration operations', 403);
    }
    return true;
}

// 取得請求方法和動作
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

try {
    $pdo = openpdo();

    // 檢查權限
    check_permission();

    switch ($action) {
        case 'preview':
            handle_preview_migration($pdo);
            break;

        case 'execute':
            handle_execute_migration($pdo);
            break;

        case 'test':
            handle_test_migration($pdo);
            break;

        default:
            api_error('Invalid action. Use action=preview, action=execute or action=test', 400);
    }

} catch (Exception $e) {
    error_log("Bank Funds Migration API Error: " . $e->getMessage());
    api_error('Internal server error: ' . $e->getMessage(), 500);
}

/**
 * 預覽資料遷移 - 顯示將要執行的操作
 */
function handle_preview_migration($pdo) {
    try {
        // 取得所有 servers 記錄
        $servers_query = $pdo->query("
            SELECT id, names,
                   MerchantID2, HashIV2, HashKey2,
                   gomypay_shop_id2, gomypay_key2,
                   smilepay_shop_id2, smilepay_key2,
                   szfupay_shop_id2, szfupay_key2
            FROM servers
        ");

        $servers = $servers_query->fetchAll(PDO::FETCH_ASSOC);

        $will_insert = 0;
        $already_exists = 0;
        $preview_details = [];

        foreach ($servers as $server) {
            $server_code = $server['id'];
            $server_name = $server['names'];
            $server_preview = [
                'server_code' => $server_code,
                'server_name' => $server_name,
                'actions' => []
            ];

            // 預覽 MerchantID2, HashIV2, HashKey2 (ecpay, newebpay, funpoint)
            if (!empty($server['MerchantID2']) && !empty($server['HashIV2']) && !empty($server['HashKey2'])) {
                $payment_types = ['ecpay', 'newebpay', 'funpoint'];

                foreach ($payment_types as $payment_type) {
                    // 檢查是否已存在
                    $check_query = $pdo->prepare("
                        SELECT id FROM bank_funds
                        WHERE server_code = :server_code
                        AND third_party_payment = :payment_type
                    ");
                    $check_query->execute([
                        ':server_code' => $server_code,
                        ':payment_type' => $payment_type
                    ]);

                    $exists = $check_query->fetch();

                    if ($exists) {
                        $already_exists++;
                        $server_preview['actions'][] = [
                            'payment_type' => $payment_type,
                            'action' => 'skip',
                            'reason' => 'already_exists',
                            'data' => [
                                'merchant_id' => $server['MerchantID2'],
                                'hashkey' => $server['HashKey2'],
                                'hashiv' => $server['HashIV2']
                            ]
                        ];
                    } else {
                        $will_insert++;
                        $server_preview['actions'][] = [
                            'payment_type' => $payment_type,
                            'action' => 'insert',
                            'data' => [
                                'server_code' => $server_code,
                                'third_party_payment' => $payment_type,
                                'merchant_id' => $server['MerchantID2'],
                                'hashkey' => $server['HashKey2'],
                                'hashiv' => $server['HashIV2']
                            ]
                        ];
                    }
                }
            }

            // 預覽 gomypay
            if (!empty($server['gomypay_shop_id2']) && !empty($server['gomypay_key2'])) {
                $check_query = $pdo->prepare("
                    SELECT id FROM bank_funds
                    WHERE server_code = :server_code
                    AND third_party_payment = 'gomypay'
                ");
                $check_query->execute([':server_code' => $server_code]);

                $exists = $check_query->fetch();

                if ($exists) {
                    $already_exists++;
                    $server_preview['actions'][] = [
                        'payment_type' => 'gomypay',
                        'action' => 'skip',
                        'reason' => 'already_exists',
                        'data' => [
                            'merchant_id' => $server['gomypay_shop_id2'],
                            'verify_key' => $server['gomypay_key2']
                        ]
                    ];
                } else {
                    $will_insert++;
                    $server_preview['actions'][] = [
                        'payment_type' => 'gomypay',
                        'action' => 'insert',
                        'data' => [
                            'server_code' => $server_code,
                            'third_party_payment' => 'gomypay',
                            'merchant_id' => $server['gomypay_shop_id2'],
                            'verify_key' => $server['gomypay_key2']
                        ]
                    ];
                }
            }

            // 預覽 smilepay
            if (!empty($server['smilepay_shop_id2']) && !empty($server['smilepay_key2'])) {
                $check_query = $pdo->prepare("
                    SELECT id FROM bank_funds
                    WHERE server_code = :server_code
                    AND third_party_payment = 'smilepay'
                ");
                $check_query->execute([':server_code' => $server_code]);

                $exists = $check_query->fetch();

                if ($exists) {
                    $already_exists++;
                    $server_preview['actions'][] = [
                        'payment_type' => 'smilepay',
                        'action' => 'skip',
                        'reason' => 'already_exists',
                        'data' => [
                            'merchant_id' => $server['smilepay_shop_id2'],
                            'verify_key' => $server['smilepay_key2']
                        ]
                    ];
                } else {
                    $will_insert++;
                    $server_preview['actions'][] = [
                        'payment_type' => 'smilepay',
                        'action' => 'insert',
                        'data' => [
                            'server_code' => $server_code,
                            'third_party_payment' => 'smilepay',
                            'merchant_id' => $server['smilepay_shop_id2'],
                            'verify_key' => $server['smilepay_key2']
                        ]
                    ];
                }
            }

            // 預覽 szfupay
            if (!empty($server['szfupay_shop_id2']) && !empty($server['szfupay_key2'])) {
                $check_query = $pdo->prepare("
                    SELECT id FROM bank_funds
                    WHERE server_code = :server_code
                    AND third_party_payment = 'szfupay'
                ");
                $check_query->execute([':server_code' => $server_code]);

                $exists = $check_query->fetch();

                if ($exists) {
                    $already_exists++;
                    $server_preview['actions'][] = [
                        'payment_type' => 'szfupay',
                        'action' => 'skip',
                        'reason' => 'already_exists',
                        'data' => [
                            'merchant_id' => $server['szfupay_shop_id2'],
                            'verify_key' => $server['szfupay_key2']
                        ]
                    ];
                } else {
                    $will_insert++;
                    $server_preview['actions'][] = [
                        'payment_type' => 'szfupay',
                        'action' => 'insert',
                        'data' => [
                            'server_code' => $server_code,
                            'third_party_payment' => 'szfupay',
                            'merchant_id' => $server['szfupay_shop_id2'],
                            'verify_key' => $server['szfupay_key2']
                        ]
                    ];
                }
            }

            if (!empty($server_preview['actions'])) {
                $preview_details[] = $server_preview;
            }
        }

        api_success([
            'summary' => [
                'total_servers' => count($servers),
                'will_insert' => $will_insert,
                'already_exists' => $already_exists,
                'total_operations' => $will_insert + $already_exists
            ],
            'details' => $preview_details
        ], "預覽完成：將插入 {$will_insert} 筆新記錄，跳過 {$already_exists} 筆已存在的記錄");

    } catch (Exception $e) {
        api_error('Preview migration failed: ' . $e->getMessage());
    }
}

/**
 * 執行資料遷移
 */
function handle_execute_migration($pdo) {
    try {
        // 開始交易
        $pdo->beginTransaction();

        // 取得所有 servers 記錄
        $servers_query = $pdo->query("
            SELECT id,
                   MerchantID2, HashIV2, HashKey2,
                   gomypay_shop_id2, gomypay_key2,
                   smilepay_shop_id2, smilepay_key2,
                   szfupay_shop_id2, szfupay_key2
            FROM servers
        ");

        $servers = $servers_query->fetchAll(PDO::FETCH_ASSOC);

        $inserted_count = 0;
        $skipped_count = 0;
        $details = [];

        foreach ($servers as $server) {
            $server_code = $server['id'];
            $server_details = [
                'server_code' => $server_code,
                'records' => []
            ];

            // 處理 MerchantID2, HashIV2, HashKey2 (ecpay, newebpay, funpoint)
            if (!empty($server['MerchantID2']) && !empty($server['HashIV2']) && !empty($server['HashKey2'])) {
                $payment_types = ['ecpay', 'newebpay', 'funpoint'];

                foreach ($payment_types as $payment_type) {
                    // 檢查是否已存在
                    $check_query = $pdo->prepare("
                        SELECT id FROM bank_funds
                        WHERE server_code = :server_code
                        AND third_party_payment = :payment_type
                    ");
                    $check_query->execute([
                        ':server_code' => $server_code,
                        ':payment_type' => $payment_type
                    ]);

                    if ($check_query->fetch()) {
                        $skipped_count++;
                        $server_details['records'][] = [
                            'payment_type' => $payment_type,
                            'status' => 'skipped',
                            'reason' => 'already_exists'
                        ];
                        continue;
                    }

                    // 插入新記錄
                    $insert_query = $pdo->prepare("
                        INSERT INTO bank_funds (
                            server_code, third_party_payment, merchant_id,
                            hashkey, hashiv, created_at, updated_at
                        ) VALUES (
                            :server_code, :payment_type, :merchant_id,
                            :hashkey, :hashiv, NOW(), NOW()
                        )
                    ");

                    $insert_query->execute([
                        ':server_code' => $server_code,
                        ':payment_type' => $payment_type,
                        ':merchant_id' => $server['MerchantID2'],
                        ':hashkey' => $server['HashKey2'],
                        ':hashiv' => $server['HashIV2']
                    ]);

                    $inserted_count++;
                    $server_details['records'][] = [
                        'payment_type' => $payment_type,
                        'status' => 'inserted',
                        'merchant_id' => $server['MerchantID2']
                    ];
                }
            }

            // 處理 gomypay
            if (!empty($server['gomypay_shop_id2']) && !empty($server['gomypay_key2'])) {
                $check_query = $pdo->prepare("
                    SELECT id FROM bank_funds
                    WHERE server_code = :server_code
                    AND third_party_payment = 'gomypay'
                ");
                $check_query->execute([':server_code' => $server_code]);

                if ($check_query->fetch()) {
                    $skipped_count++;
                    $server_details['records'][] = [
                        'payment_type' => 'gomypay',
                        'status' => 'skipped',
                        'reason' => 'already_exists'
                    ];
                } else {
                    $insert_query = $pdo->prepare("
                        INSERT INTO bank_funds (
                            server_code, third_party_payment, merchant_id,
                            verify_key, created_at, updated_at
                        ) VALUES (
                            :server_code, 'gomypay', :merchant_id,
                            :verify_key, NOW(), NOW()
                        )
                    ");

                    $insert_query->execute([
                        ':server_code' => $server_code,
                        ':merchant_id' => $server['gomypay_shop_id2'],
                        ':verify_key' => $server['gomypay_key2']
                    ]);

                    $inserted_count++;
                    $server_details['records'][] = [
                        'payment_type' => 'gomypay',
                        'status' => 'inserted',
                        'merchant_id' => $server['gomypay_shop_id2']
                    ];
                }
            }

            // 處理 smilepay
            if (!empty($server['smilepay_shop_id2']) && !empty($server['smilepay_key2'])) {
                $check_query = $pdo->prepare("
                    SELECT id FROM bank_funds
                    WHERE server_code = :server_code
                    AND third_party_payment = 'smilepay'
                ");
                $check_query->execute([':server_code' => $server_code]);

                if ($check_query->fetch()) {
                    $skipped_count++;
                    $server_details['records'][] = [
                        'payment_type' => 'smilepay',
                        'status' => 'skipped',
                        'reason' => 'already_exists'
                    ];
                } else {
                    $insert_query = $pdo->prepare("
                        INSERT INTO bank_funds (
                            server_code, third_party_payment, merchant_id,
                            verify_key, created_at, updated_at
                        ) VALUES (
                            :server_code, 'smilepay', :merchant_id,
                            :verify_key, NOW(), NOW()
                        )
                    ");

                    $insert_query->execute([
                        ':server_code' => $server_code,
                        ':merchant_id' => $server['smilepay_shop_id2'],
                        ':verify_key' => $server['smilepay_key2']
                    ]);

                    $inserted_count++;
                    $server_details['records'][] = [
                        'payment_type' => 'smilepay',
                        'status' => 'inserted',
                        'merchant_id' => $server['smilepay_shop_id2']
                    ];
                }
            }

            // 處理 szfupay
            if (!empty($server['szfupay_shop_id2']) && !empty($server['szfupay_key2'])) {
                $check_query = $pdo->prepare("
                    SELECT id FROM bank_funds
                    WHERE server_code = :server_code
                    AND third_party_payment = 'szfupay'
                ");
                $check_query->execute([':server_code' => $server_code]);

                if ($check_query->fetch()) {
                    $skipped_count++;
                    $server_details['records'][] = [
                        'payment_type' => 'szfupay',
                        'status' => 'skipped',
                        'reason' => 'already_exists'
                    ];
                } else {
                    $insert_query = $pdo->prepare("
                        INSERT INTO bank_funds (
                            server_code, third_party_payment, merchant_id,
                            verify_key, created_at, updated_at
                        ) VALUES (
                            :server_code, 'szfupay', :merchant_id,
                            :verify_key, NOW(), NOW()
                        )
                    ");

                    $insert_query->execute([
                        ':server_code' => $server_code,
                        ':merchant_id' => $server['szfupay_shop_id2'],
                        ':verify_key' => $server['szfupay_key2']
                    ]);

                    $inserted_count++;
                    $server_details['records'][] = [
                        'payment_type' => 'szfupay',
                        'status' => 'inserted',
                        'merchant_id' => $server['szfupay_shop_id2']
                    ];
                }
            }

            if (!empty($server_details['records'])) {
                $details[] = $server_details;
            }
        }

        // 提交交易
        $pdo->commit();

        api_success([
            'total_servers' => count($servers),
            'records_inserted' => $inserted_count,
            'records_skipped' => $skipped_count,
            'details' => $details
        ], "資料遷移完成，共插入 {$inserted_count} 筆記錄，跳過 {$skipped_count} 筆已存在的記錄");

    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollback();
        }
        api_error('Migration execution failed: ' . $e->getMessage());
    }
}

/**
 * 測試資料遷移 (比對資料)
 */
function handle_test_migration($pdo) {
    try {
        // 取得所有 servers 記錄
        $servers_query = $pdo->query("
            SELECT id,
                   MerchantID2, HashIV2, HashKey2,
                   gomypay_shop_id2, gomypay_key2,
                   smilepay_shop_id2, smilepay_key2,
                   szfupay_shop_id2, szfupay_key2
            FROM servers
        ");

        $servers = $servers_query->fetchAll(PDO::FETCH_ASSOC);

        $test_results = [];
        $total_expected = 0;
        $total_found = 0;
        $total_missing = 0;
        $total_mismatch = 0;

        foreach ($servers as $server) {
            $server_code = $server['id'];
            $server_test = [
                'server_code' => $server_code,
                'checks' => []
            ];

            // 檢查 MerchantID2, HashIV2, HashKey2 (ecpay, newebpay, funpoint)
            if (!empty($server['MerchantID2']) && !empty($server['HashIV2']) && !empty($server['HashKey2'])) {
                $payment_types = ['ecpay', 'newebpay', 'funpoint'];

                foreach ($payment_types as $payment_type) {
                    $total_expected++;

                    $check_query = $pdo->prepare("
                        SELECT merchant_id, hashkey, hashiv, verify_key
                        FROM bank_funds
                        WHERE server_code = :server_code
                        AND third_party_payment = :payment_type
                    ");
                    $check_query->execute([
                        ':server_code' => $server_code,
                        ':payment_type' => $payment_type
                    ]);

                    $result = $check_query->fetch(PDO::FETCH_ASSOC);

                    if (!$result) {
                        $total_missing++;
                        $server_test['checks'][] = [
                            'payment_type' => $payment_type,
                            'status' => 'missing',
                            'expected' => [
                                'merchant_id' => $server['MerchantID2'],
                                'hashkey' => $server['HashKey2'],
                                'hashiv' => $server['HashIV2']
                            ],
                            'found' => null
                        ];
                    } else {
                        $total_found++;
                        $is_match = (
                            $result['merchant_id'] === $server['MerchantID2'] &&
                            $result['hashkey'] === $server['HashKey2'] &&
                            $result['hashiv'] === $server['HashIV2']
                        );

                        if (!$is_match) {
                            $total_mismatch++;
                        }

                        $server_test['checks'][] = [
                            'payment_type' => $payment_type,
                            'status' => $is_match ? 'match' : 'mismatch',
                            'expected' => [
                                'merchant_id' => $server['MerchantID2'],
                                'hashkey' => $server['HashKey2'],
                                'hashiv' => $server['HashIV2']
                            ],
                            'found' => [
                                'merchant_id' => $result['merchant_id'],
                                'hashkey' => $result['hashkey'],
                                'hashiv' => $result['hashiv']
                            ]
                        ];
                    }
                }
            }

            // 檢查 gomypay
            if (!empty($server['gomypay_shop_id2']) && !empty($server['gomypay_key2'])) {
                $total_expected++;

                $check_query = $pdo->prepare("
                    SELECT merchant_id, verify_key
                    FROM bank_funds
                    WHERE server_code = :server_code
                    AND third_party_payment = 'gomypay'
                ");
                $check_query->execute([':server_code' => $server_code]);

                $result = $check_query->fetch(PDO::FETCH_ASSOC);

                if (!$result) {
                    $total_missing++;
                    $server_test['checks'][] = [
                        'payment_type' => 'gomypay',
                        'status' => 'missing',
                        'expected' => [
                            'merchant_id' => $server['gomypay_shop_id2'],
                            'verify_key' => $server['gomypay_key2']
                        ],
                        'found' => null
                    ];
                } else {
                    $total_found++;
                    $is_match = (
                        $result['merchant_id'] === $server['gomypay_shop_id2'] &&
                        $result['verify_key'] === $server['gomypay_key2']
                    );

                    if (!$is_match) {
                        $total_mismatch++;
                    }

                    $server_test['checks'][] = [
                        'payment_type' => 'gomypay',
                        'status' => $is_match ? 'match' : 'mismatch',
                        'expected' => [
                            'merchant_id' => $server['gomypay_shop_id2'],
                            'verify_key' => $server['gomypay_key2']
                        ],
                        'found' => [
                            'merchant_id' => $result['merchant_id'],
                            'verify_key' => $result['verify_key']
                        ]
                    ];
                }
            }

            // 檢查 smilepay
            if (!empty($server['smilepay_shop_id2']) && !empty($server['smilepay_key2'])) {
                $total_expected++;

                $check_query = $pdo->prepare("
                    SELECT merchant_id, verify_key
                    FROM bank_funds
                    WHERE server_code = :server_code
                    AND third_party_payment = 'smilepay'
                ");
                $check_query->execute([':server_code' => $server_code]);

                $result = $check_query->fetch(PDO::FETCH_ASSOC);

                if (!$result) {
                    $total_missing++;
                    $server_test['checks'][] = [
                        'payment_type' => 'smilepay',
                        'status' => 'missing',
                        'expected' => [
                            'merchant_id' => $server['smilepay_shop_id2'],
                            'verify_key' => $server['smilepay_key2']
                        ],
                        'found' => null
                    ];
                } else {
                    $total_found++;
                    $is_match = (
                        $result['merchant_id'] === $server['smilepay_shop_id2'] &&
                        $result['verify_key'] === $server['smilepay_key2']
                    );

                    if (!$is_match) {
                        $total_mismatch++;
                    }

                    $server_test['checks'][] = [
                        'payment_type' => 'smilepay',
                        'status' => $is_match ? 'match' : 'mismatch',
                        'expected' => [
                            'merchant_id' => $server['smilepay_shop_id2'],
                            'verify_key' => $server['smilepay_key2']
                        ],
                        'found' => [
                            'merchant_id' => $result['merchant_id'],
                            'verify_key' => $result['verify_key']
                        ]
                    ];
                }
            }

            // 檢查 szfupay
            if (!empty($server['szfupay_shop_id2']) && !empty($server['szfupay_key2'])) {
                $total_expected++;

                $check_query = $pdo->prepare("
                    SELECT merchant_id, verify_key
                    FROM bank_funds
                    WHERE server_code = :server_code
                    AND third_party_payment = 'szfupay'
                ");
                $check_query->execute([':server_code' => $server_code]);

                $result = $check_query->fetch(PDO::FETCH_ASSOC);

                if (!$result) {
                    $total_missing++;
                    $server_test['checks'][] = [
                        'payment_type' => 'szfupay',
                        'status' => 'missing',
                        'expected' => [
                            'merchant_id' => $server['szfupay_shop_id2'],
                            'verify_key' => $server['szfupay_key2']
                        ],
                        'found' => null
                    ];
                } else {
                    $total_found++;
                    $is_match = (
                        $result['merchant_id'] === $server['szfupay_shop_id2'] &&
                        $result['verify_key'] === $server['szfupay_key2']
                    );

                    if (!$is_match) {
                        $total_mismatch++;
                    }

                    $server_test['checks'][] = [
                        'payment_type' => 'szfupay',
                        'status' => $is_match ? 'match' : 'mismatch',
                        'expected' => [
                            'merchant_id' => $server['szfupay_shop_id2'],
                            'verify_key' => $server['szfupay_key2']
                        ],
                        'found' => [
                            'merchant_id' => $result['merchant_id'],
                            'verify_key' => $result['verify_key']
                        ]
                    ];
                }
            }

            if (!empty($server_test['checks'])) {
                $test_results[] = $server_test;
            }
        }

        $all_match = ($total_expected === $total_found && $total_mismatch === 0);

        api_success([
            'summary' => [
                'total_expected' => $total_expected,
                'total_found' => $total_found,
                'total_missing' => $total_missing,
                'total_mismatch' => $total_mismatch,
                'all_match' => $all_match,
                'test_passed' => $all_match
            ],
            'details' => $test_results
        ], $all_match ? '測試通過：所有資料已正確匯入' : '測試發現問題：有資料缺失或不一致');

    } catch (Exception $e) {
        api_error('Test migration failed: ' . $e->getMessage());
    }
}

?>
