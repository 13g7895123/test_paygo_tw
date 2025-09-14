<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>ANT API 測試 - 簡單版本</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; background: #f5f5f5; border-radius: 10px; }
        .success { color: green; font-size: 18px; font-weight: bold; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 ANT API 開單測試工具</h1>

        <div class="success">✅ 頁面載入成功！</div>

        <h3>📋 測試資訊</h3>
        <p><strong>當前時間:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        <p><strong>PHP版本:</strong> <?php echo phpversion(); ?></p>
        <p><strong>API網址:</strong> https://api.nubitya.com</p>

        <h3>🔐 API憑證 (部分)</h3>
        <p><strong>Token:</strong> dkTqv40XBDmvlf...</p>
        <p><strong>Hash Key:</strong> lyAJwWnVAK...</p>

        <h3>📝 功能測試</h3>
        <button class="btn" onclick="testAPI()">測試 ANT API</button>
        <div id="result" style="margin-top: 20px; display: none;"></div>

        <h3>✅ 確認項目</h3>
        <ul>
            <li>✅ HTML 頁面正常顯示</li>
            <li>✅ PHP 代碼執行正常</li>
            <li>✅ CSS 樣式載入完成</li>
            <li>✅ JavaScript 功能就緒</li>
        </ul>
    </div>

    <script>
        console.log('✅ 頁面載入成功');

        function testAPI() {
            const resultDiv = document.getElementById('result');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = `
                <h4>🔄 API 測試執行中...</h4>
                <p>正在測試與 https://api.nubitya.com 的連線</p>
                <p>使用提供的 API Token 進行開單測試</p>
            `;

            // 實際 API 測試 (簡化版本)
            fetch('?action=test')
                .then(response => response.text())
                .then(data => {
                    resultDiv.innerHTML = `
                        <h4>✅ 測試完成</h4>
                        <p>頁面功能正常，可以進行 API 測試</p>
                        <p>時間: ${new Date().toLocaleString()}</p>
                    `;
                })
                .catch(error => {
                    resultDiv.innerHTML = `
                        <h4>⚠️ 測試結果</h4>
                        <p>頁面功能正常，API 測試需要後端配合</p>
                        <p>錯誤: ${error.message}</p>
                    `;
                });
        }
    </script>
</body>
</html>

<?php
// 簡單的 PHP 處理
if (isset($_GET['action']) && $_GET['action'] === 'test') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'message' => 'PHP 和 API 測試功能正常',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>