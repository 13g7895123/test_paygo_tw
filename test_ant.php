<?php
/**
 * 簡易ANT測試頁面 - 檢查是否空白的問題
 */
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ANT API 測試驗證</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f0f0f0; }
        .container { max-width: 800px; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-size: 18px; font-weight: bold; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 6px; margin: 20px 0; }
        .test-links { margin-top: 30px; }
        .test-links a { display: inline-block; background: #007cba; color: white; padding: 12px 20px; margin: 5px; text-decoration: none; border-radius: 6px; }
        .test-links a:hover { background: #005a87; }
        .credentials { background: #fff3cd; padding: 15px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 ANT API 測試系統</h1>

        <div class="success">
            ✅ 頁面載入成功！空白問題已修復
        </div>

        <div class="info">
            <h3>📋 修復項目</h3>
            <ul>
                <li>移除了會導致空白頁面的JSON Content-Type header</li>
                <li>只有在返回JSON數據時才設置JSON header</li>
                <li>確保HTML頁面能正常顯示</li>
            </ul>
        </div>

        <div class="credentials">
            <h3>🔐 API測試憑證</h3>
            <p><strong>API網址:</strong> https://api.nubitya.com</p>
            <p><strong>API Token:</strong> dkTqv40XBDmvlfBayoMngA0BAlDAxCrkzIAAUdwYB6kkKZVLOit1R06PKcgkhglASS79c6yzaokrdoPP</p>
            <p><strong>Hash Key:</strong> lyAJwWnVAKNScXjE6t2rxUOAeesvIP9S</p>
            <p><strong>Hash IV:</strong> yhncs1WpMo60azxEczokzIlVVvVuW69p</p>
        </div>

        <div class="test-links">
            <h3>🧪 測試連結</h3>
            <a href="ant_order_test.php" target="_blank">ANT開單測試工具</a>
            <a href="ant_test_api.php" target="_blank">ANT連線測試工具</a>
            <a href="ant_order_test.php?action=create_order" target="_blank">API開單測試 (JSON)</a>
        </div>

        <div class="info">
            <h3>📖 使用說明</h3>
            <ol>
                <li><strong>ANT開單測試工具</strong> - 完整的網頁測試介面，可以測試開單和銀行驗證功能</li>
                <li><strong>ANT連線測試工具</strong> - 測試與API伺服器的基本連線</li>
                <li><strong>API開單測試</strong> - 直接返回JSON格式的測試結果</li>
            </ol>
        </div>

        <div class="info">
            <h3>🔧 技術說明</h3>
            <p>原本的問題是在PHP檔案開頭設置了 <code>Content-Type: application/json</code>，這會導致瀏覽器期待JSON內容而非HTML，造成頁面空白。現在已經修正為只有在實際返回JSON數據時才設置JSON header。</p>
        </div>
    </div>

    <script>
        // 檢查頁面是否正確載入
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ ANT測試頁面載入成功');
            console.log('📅 載入時間:', new Date().toLocaleString());
        });
    </script>
</body>
</html>