<?php
/**
 * 動態欄位測試運行器
 * Dynamic Fields Test Runner
 */

echo "=== 動態欄位功能完整測試套件 ===\n";
echo "Dynamic Fields Complete Test Suite\n\n";

$testFiles = [
    'API測試' => 'test_dynamic_fields_api.php',
    '整合測試' => 'test_dynamic_fields_integration.php'
];

$results = [];

foreach ($testFiles as $testName => $testFile) {
    echo "\n" . str_repeat('=', 50) . "\n";
    echo "執行 {$testName} ({$testFile})\n";
    echo str_repeat('=', 50) . "\n";
    
    $startTime = microtime(true);
    
    if (file_exists($testFile)) {
        // 捕獲輸出
        ob_start();
        include $testFile;
        $output = ob_get_clean();
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        echo $output;
        
        // 分析結果
        $passCount = substr_count($output, '✅');
        $failCount = substr_count($output, '❌');
        $totalCount = $passCount + $failCount;
        
        $results[$testName] = [
            'file' => $testFile,
            'duration' => $duration,
            'total' => $totalCount,
            'passed' => $passCount,
            'failed' => $failCount,
            'success_rate' => $totalCount > 0 ? round(($passCount / $totalCount) * 100, 1) : 0
        ];
        
        echo "\n📊 {$testName} 執行完成 - 耗時: {$duration}秒\n";
        
    } else {
        echo "❌ 測試文件不存在: {$testFile}\n";
        $results[$testName] = [
            'file' => $testFile,
            'duration' => 0,
            'total' => 0,
            'passed' => 0,
            'failed' => 1,
            'success_rate' => 0,
            'error' => '測試文件不存在'
        ];
    }
}

// 總結報告
echo "\n" . str_repeat('=', 60) . "\n";
echo "📈 測試執行總結報告\n";
echo str_repeat('=', 60) . "\n";

$grandTotal = 0;
$grandPassed = 0;
$grandFailed = 0;
$totalDuration = 0;

foreach ($results as $testName => $result) {
    echo sprintf("%-15s | %3d/%3d | %5.1f%% | %5.2fs | %s\n",
        $testName,
        $result['passed'],
        $result['total'],
        $result['success_rate'],
        $result['duration'],
        isset($result['error']) ? $result['error'] : '正常'
    );
    
    $grandTotal += $result['total'];
    $grandPassed += $result['passed'];
    $grandFailed += $result['failed'];
    $totalDuration += $result['duration'];
}

echo str_repeat('-', 60) . "\n";
$overallRate = $grandTotal > 0 ? round(($grandPassed / $grandTotal) * 100, 1) : 0;

echo sprintf("%-15s | %3d/%3d | %5.1f%% | %5.2fs | 總計\n",
    '總計',
    $grandPassed,
    $grandTotal,
    $overallRate,
    $totalDuration
);

// 最終評分
echo "\n🎯 最終評分:\n";
if ($overallRate >= 95) {
    echo "🏆 卓越 (≥95%) - 所有功能運作完美！\n";
} elseif ($overallRate >= 90) {
    echo "🥇 優秀 (≥90%) - 功能表現非常好！\n";
} elseif ($overallRate >= 80) {
    echo "🥈 良好 (≥80%) - 功能基本正常，有小幅改進空間。\n";
} elseif ($overallRate >= 70) {
    echo "🥉 及格 (≥70%) - 功能可用，但需要進一步改進。\n";
} else {
    echo "❌ 不及格 (<70%) - 功能存在嚴重問題，需要修復。\n";
}

// 建議
echo "\n💡 建議:\n";
if ($overallRate < 100) {
    echo "• 查看失敗的測試項目並進行修復\n";
}
if ($totalDuration > 10) {
    echo "• 考慮優化API回應時間\n";
}
if ($grandFailed > 0) {
    echo "• 詳細檢查錯誤處理機制\n";
}

echo "• 前端測試需要手動在瀏覽器中打開 test_dynamic_fields_frontend.html\n";
echo "• 確保資料庫結構已正確建立（參考 docs/sql/dynamic_fields.sql）\n";

echo "\n=== 測試運行完成 ===\n";

// 生成測試報告文件
$reportContent = "# 動態欄位功能測試報告\n\n";
$reportContent .= "執行時間: " . date('Y-m-d H:i:s') . "\n\n";
$reportContent .= "## 測試結果摘要\n\n";
$reportContent .= sprintf("- 總測試數: %d\n", $grandTotal);
$reportContent .= sprintf("- 通過測試: %d\n", $grandPassed);
$reportContent .= sprintf("- 失敗測試: %d\n", $grandFailed);
$reportContent .= sprintf("- 成功率: %.1f%%\n", $overallRate);
$reportContent .= sprintf("- 總耗時: %.2f秒\n\n", $totalDuration);

$reportContent .= "## 詳細結果\n\n";
foreach ($results as $testName => $result) {
    $reportContent .= sprintf("### %s\n", $testName);
    $reportContent .= sprintf("- 文件: %s\n", $result['file']);
    $reportContent .= sprintf("- 通過率: %.1f%% (%d/%d)\n", $result['success_rate'], $result['passed'], $result['total']);
    $reportContent .= sprintf("- 執行時間: %.2f秒\n", $result['duration']);
    if (isset($result['error'])) {
        $reportContent .= sprintf("- 錯誤: %s\n", $result['error']);
    }
    $reportContent .= "\n";
}

file_put_contents('test_report.md', $reportContent);
echo "\n📄 詳細測試報告已儲存至: test_report.md\n";
?>