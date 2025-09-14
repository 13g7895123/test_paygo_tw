<?php
/**
 * ANT 代收代付服務 API 類別
 * 實作ANT銀行轉帳相關的API功能
 */
class ANTApiService {

    private $username;
    private $hash_key;
    private $hash_iv;
    private $is_production;
    private $api_base_url;

    /**
     * 建構子
     */
    public function __construct($username = null, $hash_key = null, $hash_iv = null, $is_production = false) {
        // 使用提供的真實憑證
        $this->username = $username ?: 'antpay018';
        $this->hash_key = $hash_key ?: 'lyAJwWnVAKNScXjE6t2rxUOAeesvIP9S';
        $this->hash_iv = $hash_iv ?: 'yhncs1WpMo60azxEczokzIlVVvVuW69p';
        $this->is_production = $is_production;

        // 設定API基礎URL
        $this->api_base_url = 'https://api.nubitya.com';
    }
    
    /**
     * 1. 支付請求創建 (Payment Creation)
     * 創建ANT支付請求，初始化轉帳流程
     */
    public function createPayment($order_data) {
        try {
            // 驗證必要參數 - 修正為與ant_order_test.php一致的參數名稱
            $required_fields = ['partner_number', 'amount', 'user_bank_code', 'user_bank_account'];
            foreach ($required_fields as $field) {
                if (empty($order_data[$field])) {
                    throw new Exception("缺少必要參數: {$field}");
                }
            }

            // 驗證銀行代號格式
            if (!$this->validateBankCode($order_data['user_bank_code'])) {
                throw new Exception("銀行代號格式錯誤");
            }

            // 準備expected_banks參數 (JSON格式)
            $expected_banks = [
                [
                    'bank_code' => $order_data['user_bank_code'],
                    'bank_account' => $order_data['user_bank_account']
                ]
            ];

            // 準備API請求參數 (根據真實API文檔) - 修正參數名稱以與ant_order_test.php一致
            $api_data = [
                'username' => $this->username,
                'partner_number' => $order_data['partner_number'], // 直接使用partner_number
                'payment_type_slug' => 'BANK-ACCOUNT-DEPOSIT',
                'amount' => (int)$order_data['amount'],
                'item_name' => $order_data['item_name'] ?? '線上支付',
                'trade_desc' => $order_data['trade_desc'] ?? '線上支付',
                'notify_url' => $order_data['notify_url'] ?? '', // 修正為notify_url
                'expected_banks' => json_encode($expected_banks),
                'remark' => $order_data['remark'] ?? ''
            ];

            // 生成簽名
            $api_data['sign'] = $this->generateSignature($api_data);

            // 調用ANT API
            $response = $this->callApi('/api/partner/deposit-orders', $api_data, 'POST');

            // 記錄API調用日誌
            $this->logApiCall('createPayment', $api_data, $response);

            // 處理回應
            if (isset($response['result']) && $response['result'] === 'success') {
                return [
                    'success' => true,
                    'order_number' => $response['content']['number'] ?? '',
                    'partner_number' => $response['content']['partner_number'] ?? '',
                    'amount' => $response['content']['amount'] ?? 0,
                    'status' => $response['content']['status'] ?? 1,
                    'payment_info' => $response['content']['payment_info'] ?? [],
                    'message' => $response['message'] ?? '建立成功'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['message'] ?? '建立訂單失敗',
                    'code' => 'PAYMENT_CREATE_ERROR'
                ];
            }

        } catch (Exception $e) {
            $this->logError('createPayment', $e->getMessage(), $order_data);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'PAYMENT_CREATE_ERROR'
            ];
        }
    }
    
    /**
     * 2. 支付狀態查詢 (Payment Status Query)
     * 查詢ANT支付交易的當前狀態
     */
    public function queryPaymentStatus($order_number) {
        try {
            if (empty($order_number)) {
                throw new Exception("訂單編號不能為空");
            }

            // 準備API請求參數
            $api_data = [
                'username' => $this->username
            ];

            // 生成簽名
            $api_data['sign'] = $this->generateSignature($api_data);

            // 調用ANT API (使用GET方法查詢單筆訂單)
            $endpoint = '/api/partner/deposit-orders/' . urlencode($order_number);
            $response = $this->callApi($endpoint, $api_data, 'GET');

            // 記錄API調用日誌
            $this->logApiCall('queryPaymentStatus', $api_data, $response);

            // 處理回應
            if (isset($response['result']) && $response['result'] === 'success') {
                return [
                    'success' => true,
                    'order_info' => $response['content'] ?? [],
                    'message' => $response['message'] ?? '查詢成功'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['message'] ?? '查詢失敗',
                    'code' => 'STATUS_QUERY_ERROR'
                ];
            }

        } catch (Exception $e) {
            $this->logError('queryPaymentStatus', $e->getMessage(), compact('order_number'));
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'STATUS_QUERY_ERROR'
            ];
        }
    }
    
    /**
     * 驗證銀行代號格式 (3位數字)
     */
    private function validateBankCode($bank_code) {
        return preg_match('/^\d{3}$/', $bank_code);
    }
    
    /**
     * 生成API簽名 (根據ANT API文檔的簽章生成規則)
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
            // GET請求，將資料附加到URL
            if (!empty($data)) {
                $query_string = http_build_query($data);
                $url .= '?' . $query_string;
            }
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'User-Agent: ANT-API-Client/1.0'
            ]);
        } else {
            // POST請求
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'User-Agent: ANT-API-Client/1.0'
            ]);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->is_production);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("API調用失敗: {$error}");
        }

        if ($http_code !== 200) {
            throw new Exception("API回應錯誤: HTTP {$http_code} - Response: {$response}");
        }

        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("API回應格式錯誤: " . json_last_error_msg() . " - Response: {$response}");
        }

        return $result;
    }
    
    /**
     * 記錄API調用日誌
     */
    private function logApiCall($method, $request, $response) {
        $log_data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $method,
            'username' => $this->username,
            'request' => $this->sanitizeLogData($request),
            'response' => $this->sanitizeLogData($response)
        ];
        
        error_log('[ANT-API] ' . json_encode($log_data, JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * 記錄錯誤日誌
     */
    private function logError($method, $error, $data = []) {
        $log_data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $method,
            'username' => $this->username,
            'error' => $error,
            'data' => $this->sanitizeLogData($data)
        ];
        
        error_log('[ANT-API-ERROR] ' . json_encode($log_data, JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * 清理日誌資料 (移除敏感資訊)
     */
    private function sanitizeLogData($data) {
        if (is_array($data)) {
            // 隱藏敏感欄位
            $sensitive_fields = ['sign', 'hash_key', 'hash_iv', 'user_bank_account', 'expected_banks'];
            foreach ($sensitive_fields as $field) {
                if (isset($data[$field])) {
                    $data[$field] = '***HIDDEN***';
                }
            }
        }
        
        return $data;
    }
}
?>