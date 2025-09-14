<?php
include("myadm/include.php");
include_once('./pay_bank.php');
include_once('./ant_api_service.php');

// 基本驗證
if($_SESSION["foran"] == "") alert("伺服器資料錯誤-8000201。", 0);
if($_SESSION["serverid"] == "") alert("伺服器資料錯誤-8000202。", 0);
if($_SESSION["lastan"] == "") alert("伺服器資料錯誤-8000203。", 0);

$pdo = openpdo();

// 取得訂單資訊
$query = $pdo->prepare("SELECT * FROM servers_log where auton=?");
$query->execute(array($_SESSION["lastan"]));
if(!$datalist = $query->fetch()) alert("不明錯誤-8000207。", 0);
if($datalist["stats"] != 0) alert("金流狀態有誤-8000208。", 0);

$paytype = $datalist["paytype"];
$user_bank_code = $datalist["user_bank_code"];
$user_bank_account = $datalist["user_bank_account"];

// 取得伺服器設定
$sq = $pdo->prepare("SELECT * FROM servers where auton=?");
$sq->execute(array($_SESSION["foran"]));
if(!$sqd = $sq->fetch()) alert("不明錯誤-8000204。", 0);

$gstats_bank = $sqd["gstats_bank"];

if ($paytype == 2) {	// 銀行轉帳
    // 使用新的 bank_funds 資料表取得 ANT 銀行轉帳設定
    $payment_info = getSpecificBankPaymentInfo($pdo, $_SESSION["lastan"], 'ant');
    
    if ($payment_info && isset($payment_info['payment_config'])) {
        $ant_merchant_id = $payment_info['payment_config']['merchant_id'];
        $ant_hashkey = $payment_info['payment_config']['hashkey'];
        $ant_hashiv = $payment_info['payment_config']['hashiv'];
        
        // 確認使用者銀行資訊存在
        if (empty($user_bank_code) || empty($user_bank_account)) {
            alert("ANT支付需要提供銀行代號與帳號", 0);
        }
        
        try {
            // 初始化ANT API服務
            $ant_api = new ANTApiService($ant_merchant_id, $ant_hashkey, $ant_hashiv, ($gstats_bank == 1));
            
            // 1. 先驗證銀行帳戶
            $validation_result = $ant_api->validateBankAccount($user_bank_code, $user_bank_account);
            
            if (!$validation_result['success']) {
                // 銀行帳戶驗證失敗
                echo "<h1>ANT 銀行轉帳 - 帳戶驗證失敗</h1>";
                echo "<div style='color: red;'>";
                echo "<p>錯誤: " . htmlspecialchars($validation_result['error']) . "</p>";
                echo "<p>錯誤代碼: " . htmlspecialchars($validation_result['code']) . "</p>";
                echo "</div>";
                echo "<a href='index.php' style='margin-top: 20px; display: inline-block;'>返回重新輸入</a>";
                exit;
            }
            
            // 2. 創建支付請求
            $payment_data = [
                'order_id' => $datalist["orderid"],
                'amount' => $datalist["money"],
                'user_bank_code' => $user_bank_code,
                'user_bank_account' => $user_bank_account,
                'callback_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/ant_callback.php',
                'return_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/ant_return.php'
            ];
            
            $payment_result = $ant_api->createPayment($payment_data);
            
            if ($payment_result['success']) {
                // 支付請求創建成功
                
                // 更新訂單狀態為處理中
                $update_query = $pdo->prepare("UPDATE servers_log SET stats = 1 WHERE auton = ?");
                $update_query->execute(array($_SESSION["lastan"]));
                
                // 顯示支付處理頁面
                echo "<h1>ANT 銀行轉帳處理中</h1>";
                echo "<div style='padding: 20px; background: #f8f9fa; border-radius: 5px; margin: 20px 0;'>";
                echo "<h3>支付資訊</h3>";
                echo "<p><strong>訂單編號:</strong> " . htmlspecialchars($datalist["orderid"]) . "</p>";
                echo "<p><strong>支付金額:</strong> NT$ " . number_format($datalist["money"]) . "</p>";
                echo "<p><strong>銀行代號:</strong> " . htmlspecialchars($user_bank_code) . "</p>";
                echo "<p><strong>銀行帳號:</strong> " . str_repeat('*', strlen($user_bank_account) - 4) . substr($user_bank_account, -4) . "</p>";
                echo "<p><strong>處理狀態:</strong> <span style='color: green;'>處理中</span></p>";
                
                if (isset($payment_result['payment_url'])) {
                    echo "<p><strong>請點擊下方按鈕完成支付:</strong></p>";
                    echo "<a href='" . htmlspecialchars($payment_result['payment_url']) . "' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>前往ANT支付</a>";
                }
                
                echo "</div>";
                
                // 支付狀態查詢腳本
                echo "<script>";
                echo "var orderCheckInterval = setInterval(function() {";
                echo "  fetch('ant_status_check.php?order_id=" . urlencode($datalist["orderid"]) . "')";
                echo "    .then(response => response.json())";
                echo "    .then(data => {";
                echo "      if (data.status === 'completed' || data.status === 'failed') {";
                echo "        clearInterval(orderCheckInterval);";
                echo "        location.reload();";
                echo "      }";
                echo "    });";
                echo "}, 5000);"; // 每5秒檢查一次
                echo "</script>";
                
            } else {
                // 支付請求創建失敗
                echo "<h1>ANT 銀行轉帳 - 支付請求失敗</h1>";
                echo "<div style='color: red;'>";
                echo "<p>錯誤: " . htmlspecialchars($payment_result['error']) . "</p>";
                echo "<p>錯誤代碼: " . htmlspecialchars($payment_result['code']) . "</p>";
                echo "</div>";
                echo "<a href='index.php' style='margin-top: 20px; display: inline-block;'>返回重新嘗試</a>";
            }
            
        } catch (Exception $e) {
            // 系統錯誤
            error_log("ANT API Error: " . $e->getMessage());
            echo "<h1>ANT 銀行轉帳 - 系統錯誤</h1>";
            echo "<div style='color: red;'>";
            echo "<p>系統暫時無法處理您的請求，請稍後再試。</p>";
            echo "<p>錯誤訊息: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
            echo "<a href='index.php' style='margin-top: 20px; display: inline-block;'>返回首頁</a>";
        }
        
    } else {
        alert("ANT設定錯誤", 0);
    }
} else {
    alert("支付方式錯誤", 0);
}
?>