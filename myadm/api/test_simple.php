<?php
// 簡單測試檔案，不依賴 include.php
session_start();

header('Content-Type: application/json; charset=utf-8');

$response = [
    'success' => true,
    'data' => [
        'session_adminid' => isset($_SESSION["adminid"]) ? $_SESSION["adminid"] : null,
        'session_shareid' => isset($_SESSION["shareid"]) ? $_SESSION["shareid"] : null,
        'session_id' => session_id(),
        'all_sessions' => $_SESSION,
        'timestamp' => date('c')
    ],
    'message' => 'Simple test working'
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>