<?php
/**
 * 銀行支付功能測試導覽頁面
 */
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>銀行支付功能測試中心</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { text-align: center; color: white; margin-bottom: 40px; }
        .header h1 { font-size: 2.5em; margin: 0; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
        .header p { font-size: 1.2em; margin-top: 10px; opacity: 0.9; }
        .test-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; }
        .test-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .test-card:hover { transform: translateY(-5px); box-shadow: 0 12px 40px rgba(0,0,0,0.15); }
        .test-card h3 { color: #333; margin-top: 0; font-size: 1.4em; }
        .test-card p { color: #666; line-height: 1.6; margin: 15px 0; }
        .test-card .features { list-style: none; padding: 0; margin: 20px 0; }
        .test-card .features li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            color: #555;
            font-size: 0.95em;
        }
        .test-card .features li:last-child { border-bottom: none; }
        .test-card .features li:before { content: "✓"; color: #28a745; font-weight: bold; margin-right: 10px; }
        .test-btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: bold;
            text-align: center;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }
        .test-btn:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: scale(1.02);
        }
        .status-section {
            background: rgba(255,255,255,0.95);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .status-item:last-child { border-bottom: none; }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-ready { background: #cce5ff; color: #004085; }
        .icon { font-size: 2em; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏦 銀行支付功能測試中心</h1>
            <p>完整的ANT銀行支付API測試套件 - 涵蓋所有測試情境</p>
        </div>

        <div class="status-section">
            <h3>🚀 系統狀態</h3>
            <div class="status-item">
                <span>ANT API服務</span>
                <span class="status-badge status-active">已就緒</span>
            </div>
            <div class="status-item">
                <span>測試環境</span>
                <span class="status-badge status-active">正常</span>
            </div>
            <div class="status-item">
                <span>API端點</span>
                <span class="status-badge status-active">https://api.nubitya.com</span>
            </div>
            <div class="status-item">
                <span>Username</span>
                <span class="status-badge status-ready">antpay018</span>
            </div>
        </div>

        <div class="test-cards">
            <div class="test-card">
                <div class="icon">🧪</div>
                <h3>完整測試套件</h3>
                <p>包含所有銀行支付功能的綜合測試，支援多種測試情境和自動化測試流程。</p>
                <ul class="features">
                    <li>建立訂單測試</li>
                    <li>查詢狀態測試</li>
                    <li>回調處理測試</li>
                    <li>錯誤情境測試</li>
                    <li>性能測試</li>
                    <li>不同銀行測試</li>
                    <li>邊界條件測試</li>
                    <li>自訂參數測試</li>
                </ul>
                <a href="bank_payment_test_suite.php" class="test-btn">開始完整測試</a>
            </div>

            <div class="test-card">
                <div class="icon">🎯</div>
                <h3>ANT API測試工具</h3>
                <p>專門針對ANT API的測試工具，支援Unicode解碼和詳細的API請求/回應分析。</p>
                <ul class="features">
                    <li>真實API測試</li>
                    <li>Unicode字符解碼</li>
                    <li>詳細請求分析</li>
                    <li>即時狀態查詢</li>
                    <li>測試記錄保存</li>
                    <li>多銀行代號支援</li>
                    <li>互動式測試介面</li>
                    <li>測試結果匯出</li>
                </ul>
                <a href="ant_order_test.php" class="test-btn">ANT API測試</a>
            </div>

            <div class="test-card">
                <div class="icon">📊</div>
                <h3>API狀態檢查</h3>
                <p>檢查ANT API的連線狀態和服務可用性，確認所有配置正確。</p>
                <ul class="features">
                    <li>連線狀態檢查</li>
                    <li>憑證驗證</li>
                    <li>API可用性測試</li>
                    <li>配置檢查</li>
                    <li>網絡連通性測試</li>
                    <li>錯誤診斷</li>
                    <li>系統資訊顯示</li>
                    <li>健康狀態報告</li>
                </ul>
                <a href="ant_status.php" class="test-btn">狀態檢查</a>
            </div>

            <div class="test-card">
                <div class="icon">🔧</div>
                <h3>簡易測試工具</h3>
                <p>輕量級的測試介面，適合快速驗證基本功能和進行簡單的API測試。</p>
                <ul class="features">
                    <li>基礎功能測試</li>
                    <li>快速驗證</li>
                    <li>簡潔介面</li>
                    <li>即時結果</li>
                    <li>輕量級設計</li>
                    <li>一鍵測試</li>
                    <li>基本配置檢查</li>
                    <li>問題快速定位</li>
                </ul>
                <a href="test_ant.php" class="test-btn">簡易測試</a>
            </div>
        </div>

        <div style="text-align: center; margin-top: 40px; color: white; opacity: 0.8;">
            <p>💡 選擇適合的測試工具來驗證銀行支付功能</p>
            <p style="font-size: 0.9em;">建議先使用「完整測試套件」進行全面測試，再使用其他工具進行特定測試</p>
        </div>
    </div>

    <script>
        // 簡單的頁面載入動畫
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.test-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'opacity 0.5s, transform 0.5s';

                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 150);
            });
        });
    </script>
</body>
</html>