<?php
/**
 * ANT 代收代付服務 API 類別
 * 實作ANT銀行轉帳相關的API功能
 */
class ANTApiService {
    
    private $merchant_id;
    private $ant_key;
    private $hash_iv;
    private $is_production;
    private $api_base_url;
    
    /**
     * 建構子
     */
    public function __construct($merchant_id = null, $ant_key = null, $is_production = false) {
        // 使用提供的真實憑證
        $this->merchant_id = $merchant_id ?: 'dkTqv40XBDmvlfBayoMngA0BAlDAxCrkzIAAUdwYB6kkKZVLOit1R06PKcgkhglASS79c6yzaokrdoPP';
        $this->ant_key = $ant_key ?: 'lyAJwWnVAKNScXjE6t2rxUOAeesvIP9S';
        $this->hash_iv = 'yhncs1WpMo60azxEczokzIlVVvVuW69p'; // 新增hash_iv屬性
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
            // 驗證必要參數
            $required_fields = ['order_id', 'amount', 'user_bank_code', 'user_bank_account'];
            foreach ($required_fields as $field) {
                if (empty($order_data[$field])) {
                    throw new Exception("缺少必要參數: {$field}");
                }
            }
            
            // 驗證銀行代號格式
            if (!$this->validateBankCode($order_data['user_bank_code'])) {
                throw new Exception("銀行代號格式錯誤");
            }
            
            // 準備API請求參數
            $api_data = [
                'merchant_id' => $this->merchant_id,
                'order_id' => $order_data['order_id'],
                'amount' => $order_data['amount'],
                'user_bank_code' => $order_data['user_bank_code'],
                'user_bank_account' => $order_data['user_bank_account'],
                'callback_url' => $order_data['callback_url'] ?? '',
                'return_url' => $order_data['return_url'] ?? '',
                'timestamp' => time()
            ];
            
            // 生成簽名
            $api_data['signature'] = $this->generateSignature($api_data);
            
            // 調用ANT API
            $response = $this->callApi('/payment/create', $api_data);
            
            // 記錄API調用日誌
            $this->logApiCall('createPayment', $api_data, $response);
            
            return $response;
            
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
     * 2. 銀行帳戶驗證 (Bank Account Validation)
     * 驗證使用者提供的銀行代號和帳號是否有效
     */
    public function validateBankAccount($bank_code, $account_number) {
        try {
            // 基本格式驗證
            if (!$this->validateBankCode($bank_code)) {
                return [
                    'success' => false,
                    'error' => '銀行代號格式錯誤',
                    'code' => 'INVALID_BANK_CODE'
                ];
            }
            
            if (empty($account_number)) {
                return [
                    'success' => false,
                    'error' => '銀行帳號不能為空',
                    'code' => 'INVALID_ACCOUNT_NUMBER'
                ];
            }
            
            // 準備API請求參數
            $api_data = [
                'merchant_id' => $this->merchant_id,
                'bank_code' => $bank_code,
                'account_number' => $account_number,
                'timestamp' => time()
            ];
            
            // 生成簽名
            $api_data['signature'] = $this->generateSignature($api_data);
            
            // 調用ANT API
            $response = $this->callApi('/account/validate', $api_data);
            
            // 記錄API調用日誌
            $this->logApiCall('validateBankAccount', $api_data, $response);
            
            return $response;
            
        } catch (Exception $e) {
            $this->logError('validateBankAccount', $e->getMessage(), compact('bank_code', 'account_number'));
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'VALIDATION_ERROR'
            ];
        }
    }
    
    /**
     * 3. 支付狀態查詢 (Payment Status Query)
     * 查詢ANT支付交易的當前狀態
     */
    public function queryPaymentStatus($order_id) {
        try {
            if (empty($order_id)) {
                throw new Exception("訂單編號不能為空");
            }
            
            // 準備API請求參數
            $api_data = [
                'merchant_id' => $this->merchant_id,
                'order_id' => $order_id,
                'timestamp' => time()
            ];
            
            // 生成簽名
            $api_data['signature'] = $this->generateSignature($api_data);
            
            // 調用ANT API
            $response = $this->callApi('/payment/status', $api_data);
            
            // 記錄API調用日誌
            $this->logApiCall('queryPaymentStatus', $api_data, $response);
            
            return $response;
            
        } catch (Exception $e) {
            $this->logError('queryPaymentStatus', $e->getMessage(), compact('order_id'));
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'STATUS_QUERY_ERROR'
            ];
        }
    }
    
    /**
     * 4. 支付結果通知處理 (Payment Notification Handler)
     * 處理ANT服務的支付結果回調通知
     */
    public function handleNotification($notification_data) {
        try {
            // 驗證通知簽名
            if (!$this->verifyNotificationSignature($notification_data)) {
                throw new Exception("通知簽名驗證失敗");
            }
            
            // 處理通知內容
            $order_id = $notification_data['order_id'] ?? '';
            $status = $notification_data['status'] ?? '';
            $amount = $notification_data['amount'] ?? 0;
            
            if (empty($order_id)) {
                throw new Exception("通知中缺少訂單編號");
            }
            
            // 記錄通知日誌
            $this->logApiCall('handleNotification', $notification_data, ['processed' => true]);
            
            return [
                'success' => true,
                'order_id' => $order_id,
                'status' => $status,
                'amount' => $amount,
                'message' => '通知處理成功'
            ];
            
        } catch (Exception $e) {
            $this->logError('handleNotification', $e->getMessage(), $notification_data);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'NOTIFICATION_ERROR'
            ];
        }
    }
    
    /**
     * 5. 退款請求 (Refund Request)
     * 處理ANT支付的退款申請
     */
    public function createRefund($refund_data) {
        try {
            // 驗證必要參數
            $required_fields = ['original_order_id', 'refund_amount'];
            foreach ($required_fields as $field) {
                if (empty($refund_data[$field])) {
                    throw new Exception("缺少必要參數: {$field}");
                }
            }
            
            // 準備API請求參數
            $api_data = [
                'merchant_id' => $this->merchant_id,
                'original_order_id' => $refund_data['original_order_id'],
                'refund_amount' => $refund_data['refund_amount'],
                'refund_reason' => $refund_data['refund_reason'] ?? '客戶申請退款',
                'timestamp' => time()
            ];
            
            // 生成簽名
            $api_data['signature'] = $this->generateSignature($api_data);
            
            // 調用ANT API
            $response = $this->callApi('/refund/create', $api_data);
            
            // 記錄API調用日誌
            $this->logApiCall('createRefund', $api_data, $response);
            
            return $response;
            
        } catch (Exception $e) {
            $this->logError('createRefund', $e->getMessage(), $refund_data);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'REFUND_ERROR'
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
     * 生成API簽名
     */
    private function generateSignature($data) {
        // 移除signature欄位
        unset($data['signature']);

        // 按鍵名排序
        ksort($data);

        // 組合簽名字串
        $sign_string = '';
        foreach ($data as $key => $value) {
            if (!empty($value)) {
                $sign_string .= $key . '=' . $value . '&';
            }
        }

        // 加入密鑰和IV
        $sign_string .= 'hash_key=' . $this->ant_key . '&hash_iv=' . $this->hash_iv;

        // 生成MD5簽名
        return strtoupper(md5($sign_string));
    }
    
    /**
     * 驗證通知簽名
     */
    private function verifyNotificationSignature($data) {
        $received_signature = $data['signature'] ?? '';
        $calculated_signature = $this->generateSignature($data);
        
        return $received_signature === $calculated_signature;
    }
    
    /**
     * 調用ANT API
     */
    private function callApi($endpoint, $data) {
        $url = $this->api_base_url . $endpoint;
        
        // 使用cURL發送請求
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: ANT-API-Client/1.0'
        ]);
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
            throw new Exception("API回應錯誤: HTTP {$http_code}");
        }
        
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("API回應格式錯誤");
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
            'merchant_id' => $this->merchant_id,
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
            'merchant_id' => $this->merchant_id,
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
            $sensitive_fields = ['signature', 'hash_key', 'hash_iv', 'user_bank_account'];
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