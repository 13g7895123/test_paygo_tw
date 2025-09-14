<?php
include("myadm/include.php");
include_once('./pay_bank.php');
include_once('./ant_api_service.php');

// 錯誤處理函數
function showErrorAndGoBack($message) {
    echo '<html><head><meta charset="utf-8"><title>ANT 支付錯誤</title></head><body>';
    echo '<script>';
    echo 'alert("' . $message . '");';
    echo 'history.back();';
    echo '</script>';
    echo '</body></html>';
    exit;
}

// 基本驗證
if($_SESSION["foran"] == "") showErrorAndGoBack("伺服器資料錯誤-8000201\\n\\n請重新進入系統。");
if($_SESSION["serverid"] == "") showErrorAndGoBack("伺服器資料錯誤-8000202\\n\\n請重新進入系統。");
if($_SESSION["lastan"] == "") showErrorAndGoBack("伺服器資料錯誤-8000203\\n\\n請重新進入系統。");

$pdo = openpdo();

// 取得訂單資訊
$query = $pdo->prepare("SELECT * FROM servers_log where auton=?");
$query->execute(array($_SESSION["lastan"]));
if(!$datalist = $query->fetch()) showErrorAndGoBack("不明錯誤-8000207\\n\\n找不到訂單資料，請重新下訂。");
if($datalist["stats"] != 0) showErrorAndGoBack("金流狀態有誤-8000208\\n\\n訂單狀態異常，請重新下訂。");

$paytype = $datalist["paytype"];
$user_bank_code = $datalist["user_bank_code"];
$user_bank_account = $datalist["user_bank_account"];

// 取得伺服器設定
$sq = $pdo->prepare("SELECT * FROM servers where auton=?");
$sq->execute(array($_SESSION["foran"]));
if(!$sqd = $sq->fetch()) showErrorAndGoBack("不明錯誤-8000204\\n\\n找不到伺服器設定，請聯絡管理員。");

$gstats_bank = $sqd["gstats_bank"];

if ($paytype == 2) {	// 銀行轉帳
    // 使用新的 bank_funds 資料表取得 ANT 銀行轉帳設定
    $payment_info = getSpecificBankPaymentInfo($pdo, $_SESSION["lastan"], 'ant');

    if ($payment_info && isset($payment_info['payment_config'])) {
        // 確認使用者銀行資訊存在
        if (empty($user_bank_code) || empty($user_bank_account)) {
            showErrorAndGoBack("ANT支付需要提供銀行代號與帳號\\n\\n請返回填寫完整的銀行資訊。");
        }

        try {
            // 使用資料庫的參數 (依據點109要求)
            $ant_username = $payment_info['payment_config']['merchant_id'] ?? 'antpay018';
            $ant_hash_key = $payment_info['payment_config']['hashkey'] ?? 'lyAJwWnVAKNScXjE6t2rxUOAeesvIP9S';
            $ant_hash_iv = $payment_info['payment_config']['hashiv'] ?? 'yhncs1WpMo60azxEczokzIlVVvVuW69p';
            $ant_api = new ANTApiService($ant_username, $ant_hash_key, $ant_hash_iv, ($gstats_bank == 1));
            
            // 創建支付請求 - 使用與ant_order_test.php一致的數據格式
            $payment_data = [
                'partner_number' => $datalist["orderid"], // 使用partner_number而不是order_id
                'amount' => (int)$datalist["money"],
                'user_bank_code' => $user_bank_code,
                'user_bank_account' => $user_bank_account,
                'item_name' => '線上支付',
                'trade_desc' => '線上支付 - ' . date('Y-m-d H:i:s'),
                'notify_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/ant_callback.php', // 使用notify_url而不是callback_url
                'remark' => '線上支付訂單'
            ];
            
            $payment_result = $ant_api->createPayment($payment_data);
            
            if ($payment_result['success']) {
                // 支付請求創建成功
                // 注意：不更新狀態，保持為 0（等待付款）直到收到轉帳回調

                // 儲存ANT訂單編號（如果有的話）
                if (isset($payment_result['order_number']) && !empty($payment_result['order_number'])) {
                    $ant_order_update = $pdo->prepare("UPDATE servers_log SET third_party_order_id = ? WHERE auton = ?");
                    $ant_order_update->execute(array($payment_result['order_number'], $_SESSION["lastan"]));
                }

                // 跳轉到ANT支付結果頁面 (參照funpoint_payok.php模式)
                echo '<form method="post" action="ant_payok.php" id="antPaymentForm">';
                echo '<input type="hidden" name="MerchantTradeNo" value="' . htmlspecialchars($datalist["orderid"]) . '">';
                if (isset($payment_result['payment_info']) && !empty($payment_result['payment_info'])) {
                    // 如果API回傳有銀行資訊，傳遞給payok頁面
                    echo '<input type="hidden" name="PaymentInfo" value="' . htmlspecialchars(json_encode($payment_result['payment_info'])) . '">';
                }
                if (isset($payment_result['order_number'])) {
                    echo '<input type="hidden" name="ANTOrderNo" value="' . htmlspecialchars($payment_result['order_number']) . '">';
                }
                echo '</form>';
                echo '<script>document.getElementById("antPaymentForm").submit();</script>';

            } else {
                // 支付請求創建失敗
                $error_message = "ANT 銀行轉帳 - 支付請求失敗\\n\\n錯誤訊息: " . addslashes($payment_result['error']);
                if (isset($payment_result['code'])) {
                    $error_message .= "\\n錯誤代碼: " . addslashes($payment_result['code']);
                }
                showErrorAndGoBack($error_message);
            }
            
        } catch (Exception $e) {
            // 系統錯誤
            error_log("ANT API Error: " . $e->getMessage());
            $error_message = "ANT 銀行轉帳 - 系統錯誤\\n\\n系統暫時無法處理您的請求，請稍後再試。\\n\\n錯誤訊息: " . addslashes($e->getMessage());
            showErrorAndGoBack($error_message);
        }
        
    } else {
        showErrorAndGoBack("ANT設定錯誤\\n\\n請檢查ANT金流設定是否正確。");
    }
} else {
    showErrorAndGoBack("支付方式錯誤\\n\\n請選擇正確的支付方式。");
}
?>