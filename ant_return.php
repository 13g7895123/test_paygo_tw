<?php
/**
 * ANT 支付完成返回頁面
 * 用戶完成ANT支付後的返回頁面
 */
include("myadm/include.php");
include_once('./pay_bank.php');
include_once('./ant_api_service.php');

$pdo = openpdo();

// 取得返回參數
$order_id = $_GET['order_id'] ?? $_POST['order_id'] ?? '';
$status = $_GET['status'] ?? $_POST['status'] ?? '';

if (empty($order_id)) {
    alert("訂單編號缺失", 0);
}

// 查詢訂單資訊
$query = $pdo->prepare("SELECT * FROM servers_log WHERE orderid = ?");
$query->execute(array($order_id));
$order_data = $query->fetch();

if (!$order_data) {
    alert("找不到訂單資訊", 0);
}

// 取得伺服器資訊
$server_query = $pdo->prepare("SELECT * FROM servers WHERE auton = ?");
$server_query->execute(array($order_data['foran']));
$server_data = $server_query->fetch();

if (!$server_data) {
    alert("找不到伺服器資訊", 0);
}

// 取得ANT設定
$payment_info = getSpecificBankPaymentInfo($pdo, $order_data['auton'], 'ant');

if ($payment_info && isset($payment_info['payment_config'])) {
    $ant_merchant_id = $payment_info['payment_config']['merchant_id'];
    $ant_hashkey = $payment_info['payment_config']['hashkey'];
    $ant_hashiv = $payment_info['payment_config']['hashiv'];
    $is_production = ($payment_info['server_info']['gstats_bank'] == 1);

    // 初始化ANT API服務並查詢最新狀態
    $ant_api = new ANTApiService($ant_merchant_id, $ant_hashkey, $ant_hashiv, $is_production);
    $status_result = $ant_api->queryPaymentStatus($order_id);
    
    if ($status_result['success']) {
        $latest_status = $status_result['status'] ?? $status;
    } else {
        $latest_status = $status;
    }
} else {
    $latest_status = $status;
}

// 根據狀態確定頁面內容
$is_success = false;
$status_message = '';
$status_color = '#dc3545'; // 預設紅色

switch (strtolower($latest_status)) {
    case 'success':
    case 'completed':
    case 'trade_success':
        $is_success = true;
        $status_message = '支付成功';
        $status_color = '#28a745'; // 綠色
        break;
        
    case 'failed':
    case 'error':
    case 'trade_failed':
        $status_message = '支付失敗';
        break;
        
    case 'cancelled':
    case 'trade_closed':
        $status_message = '支付已取消';
        break;
        
    case 'timeout':
        $status_message = '支付超時';
        break;
        
    case 'pending':
    case 'processing':
        $status_message = '支付處理中';
        $status_color = '#ffc107'; // 黃色
        break;
        
    default:
        $status_message = '支付狀態未知';
        break;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ANT 支付結果 - <?= htmlspecialchars($server_data['names']) ?></title>
    <style>
        body {
            font-family: 'Microsoft JhengHei', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }
        
        .status-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .success-icon { color: #28a745; }
        .error-icon { color: #dc3545; }
        .warning-icon { color: #ffc107; }
        
        .status-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            color: <?= $status_color ?>;
        }
        
        .order-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .info-label {
            font-weight: bold;
            color: #6c757d;
        }
        
        .info-value {
            color: #495057;
        }
        
        .action-buttons {
            margin-top: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 0 10px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .auto-refresh {
            font-size: 14px;
            color: #6c757d;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="status-icon">
            <?php if ($is_success): ?>
                <span class="success-icon">✓</span>
            <?php elseif ($latest_status === 'pending' || $latest_status === 'processing'): ?>
                <span class="warning-icon">⏳</span>
            <?php else: ?>
                <span class="error-icon">✗</span>
            <?php endif; ?>
        </div>
        
        <div class="status-title"><?= htmlspecialchars($status_message) ?></div>
        
        <div class="order-info">
            <div class="info-row">
                <span class="info-label">伺服器名稱:</span>
                <span class="info-value"><?= htmlspecialchars($server_data['names']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">訂單編號:</span>
                <span class="info-value"><?= htmlspecialchars($order_data['orderid']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">遊戲帳號:</span>
                <span class="info-value"><?= htmlspecialchars($order_data['gameid']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">支付金額:</span>
                <span class="info-value">NT$ <?= number_format($order_data['money']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">支付方式:</span>
                <span class="info-value">ANT 銀行轉帳</span>
            </div>
            <div class="info-row">
                <span class="info-label">處理時間:</span>
                <span class="info-value"><?= date('Y-m-d H:i:s') ?></span>
            </div>
        </div>
        
        <?php if ($is_success): ?>
            <p style="color: #28a745; font-weight: bold;">
                感謝您的支付！您的訂單已經處理完成。
            </p>
        <?php elseif ($latest_status === 'pending' || $latest_status === 'processing'): ?>
            <p style="color: #ffc107; font-weight: bold;">
                您的支付正在處理中，請稍候...
            </p>
            <div class="auto-refresh">
                頁面將自動重新整理以更新狀態
            </div>
        <?php else: ?>
            <p style="color: #dc3545; font-weight: bold;">
                支付處理遇到問題，請聯繫客服或重新嘗試。
            </p>
        <?php endif; ?>
        
        <div class="action-buttons">
            <a href="index.php" class="btn btn-primary">返回首頁</a>
            <?php if (!$is_success && $latest_status !== 'pending' && $latest_status !== 'processing'): ?>
                <a href="index.php" class="btn btn-secondary">重新支付</a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($latest_status === 'pending' || $latest_status === 'processing'): ?>
    <script>
        // 處理中狀態每10秒重新整理頁面
        setTimeout(function() {
            location.reload();
        }, 10000);
    </script>
    <?php endif; ?>
</body>
</html>