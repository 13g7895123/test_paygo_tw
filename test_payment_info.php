<!DOCTYPE html>
<html>
<head>
    <title>測試 getSpecificBankPaymentInfo 函數</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 200px;
        }
        .btn {
            background: #007cba;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn:hover { background: #005a87; }
        .result {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            white-space: pre-wrap;
            font-family: monospace;
            max-height: 600px;
            overflow-y: auto;
        }
        .error { background: #ffebee; border-color: #f44336; color: #c62828; }
        .success { background: #e8f5e8; border-color: #4caf50; color: #2e7d32; }
        .info { background: #e3f2fd; border-color: #2196f3; color: #1565c0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>測試 getSpecificBankPaymentInfo 函數</h1>

        <p>此工具用於測試 <code>$payment_info = getSpecificBankPaymentInfo($pdo, $order_data['auton'], 'ant');</code> 這一行程式碼的執行結果。</p>

        <form id="testForm" onsubmit="testFunction(event)">
            <div class="form-group">
                <label for="auton">Auton (servers_log 的 ID):</label>
                <input type="number" id="auton" name="auton" required>
                <small>請輸入 servers_log 表中的 auton 值</small>
            </div>

            <div class="form-group">
                <label for="payment_type">支付類型:</label>
                <select id="payment_type" name="payment_type">
                    <option value="ant" selected>ant</option>
                    <option value="ecpay">ecpay</option>
                    <option value="ebpay">ebpay</option>
                    <option value="smilepay">smilepay</option>
                    <option value="gomypay">gomypay</option>
                    <option value="szfu">szfu</option>
                </select>
            </div>

            <button type="submit" class="btn">測試函數</button>
        </form>

        <div id="result" class="result" style="display: none;"></div>

        <div id="loading" style="display: none; color: #666; margin-top: 20px;">
            <p>正在測試函數...</p>
        </div>
    </div>

    <script>
        function testFunction(event) {
            event.preventDefault();

            const auton = document.getElementById('auton').value;
            const paymentType = document.getElementById('payment_type').value;
            const resultDiv = document.getElementById('result');
            const loadingDiv = document.getElementById('loading');

            // 顯示載入狀態
            loadingDiv.style.display = 'block';
            resultDiv.style.display = 'none';

            // 發送 AJAX 請求
            const url = `test_payment_info_api.php?auton=${auton}&payment_type=${paymentType}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    loadingDiv.style.display = 'none';
                    resultDiv.style.display = 'block';

                    if (data.success) {
                        resultDiv.className = 'result success';
                        resultDiv.textContent = JSON.stringify(data, null, 2);
                    } else {
                        resultDiv.className = 'result error';
                        resultDiv.textContent = JSON.stringify(data, null, 2);
                    }
                })
                .catch(error => {
                    loadingDiv.style.display = 'none';
                    resultDiv.style.display = 'block';
                    resultDiv.className = 'result error';
                    resultDiv.textContent = '請求失敗：' + error.message;
                });
        }
    </script>
</body>
</html>