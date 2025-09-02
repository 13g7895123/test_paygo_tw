<?php

/**
 * 希望專用
 * 處理用戶點數更新功能
 * @param PDO $gamepdo 遊戲資料庫連接
 * @param PDO $pdo 主資料庫連接
 * @param string $gameid 遊戲用戶ID
 * @param int $bmoney 要增加的點數
 * @param string $MerchantTradeNo 訂單號
 * @param string $endstr 成功時返回的字符串
 * @return bool 成功返回true，失敗會直接die
 */
function updateUserPoint($gamepdo, $pdo, $gameid, $bmoney, $MerchantTradeNo, $endstr) {
    $userq = $gamepdo->prepare("SELECT point FROM users WHERE id = ?");
    $userq->execute(array($gameid));

    if($userdata = $userq->fetch()) {
        // 有資料則取出現有point，加總後存回
        $current_point = $userdata['point'];
        $new_point = $current_point + $bmoney;
        
        $updateq = $gamepdo->prepare("UPDATE users SET point = ? WHERE id = ?");
        if(!$updateq->execute(array($new_point, $gameid))) {
            $qud = $pdo->prepare("update servers_log set errmsg='更新用戶點數時發生錯誤' where orderid=?");
            $qud->execute(array($MerchantTradeNo));
            die("0");
        }

        echo $endstr;
        exit; 
    } else {
        // 無資料則記錄錯誤
        $qud = $pdo->prepare("update servers_log set errmsg='找不到對應的遊戲用戶' where orderid=?");
        $qud->execute(array($MerchantTradeNo));
        die("0");
    }
}

?>