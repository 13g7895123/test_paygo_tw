<?php
include("myadm/include.php");
require_once('hope.php');
$endstr = '1|OK';
$MerchantID = $_REQUEST["MerchantID"];
$MerchantTradeNo = $_REQUEST["MerchantTradeNo"];
$RtnCode = $_REQUEST["RtnCode"];
$RtnMsg = $_REQUEST["RtnMsg"];
$rCheckMacValue = $_REQUEST["CheckMacValue"];
$TradeAmt = $_REQUEST["TradeAmt"];
$PaymentDate = $_REQUEST["PaymentDate"];
$PaymentTypeChargeFee = $_REQUEST["PaymentTypeChargeFee"];
$pdo = openpdo();

// 設定 error_log 寫入到指定檔案 (移到最前面)
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug_r.log');
error_log("========== r.php START ==========");
error_log("MerchantTradeNo: $MerchantTradeNo, RtnCode: $RtnCode");

try {
    $pdo->beginTransaction();
    error_log("Transaction started");
    
    $query    = $pdo->prepare("SELECT * FROM servers_log where orderid=? for update");
    $query->execute(array($MerchantTradeNo));
    if (!$datalist = $query->fetch()) {
        error_log("ERROR: No data found for orderid: $MerchantTradeNo - dying with 0");
        die("0");
    }
    error_log("BEFORE UPDATE - stats:" . $datalist["stats"] . " | hmoney:" . $datalist["hmoney"] . " | paytimes:" . $datalist["paytimes"] . " | rmoney:" . $datalist["rmoney"] . " | rCheckMacValue:" . $datalist["rCheckMacValue"] . " | RtnCode:" . $datalist["RtnCode"] . " | RtnMsg:" . $datalist["RtnMsg"]);

    $foran = $datalist["foran"];
    if ($datalist["stats"] != 0  && $_POST["mockpay"] != 1) {
        error_log("ERROR: stats != 0 and mockpay != 1 - dying with 0");
        die("0");
    }
    
    if ($RtnCode == 1) {
        $rstat = ($RtnMsg == '模擬付款成功') ? 3 : 1;   // 模擬付款成功狀態碼改為3
    } else {
        $rstat = 2;
    }
    error_log("rstat set to: $rstat");
    
    // 直接執行SQL - 不用 prepared statement
    $sql = "UPDATE servers_log SET 
            stats = " . intval($rstat) . ",
            hmoney = " . floatval($PaymentTypeChargeFee) . ",
            paytimes = '" . addslashes($PaymentDate) . "',
            rmoney = " . floatval($TradeAmt) . ",
            rCheckMacValue = '" . addslashes($rCheckMacValue) . "',
            RtnCode = " . intval($RtnCode) . ",
            RtnMsg = '" . addslashes($RtnMsg) . "'
            WHERE orderid = '" . addslashes($MerchantTradeNo) . "'";
    
    error_log("SQL: " . $sql);
    $rr = $pdo->exec($sql);
    error_log("UPDATE result: " . $rr . " rows affected");
    
    // 立即 commit 讓更新生效
    $pdo->commit();
    error_log("Transaction COMMITTED - update should be permanent now");
    
    // 重新開始交易給後面的程式碼使用
    $pdo->beginTransaction();
    
    // 檢查更新後的資料 - 顯示所有被更新的欄位
    $check_after = $pdo->prepare("SELECT stats, hmoney, paytimes, rmoney, rCheckMacValue, RtnCode, RtnMsg FROM servers_log WHERE orderid = ?");
    $check_after->execute(array($MerchantTradeNo));
    $data_after = $check_after->fetch(PDO::FETCH_ASSOC);
    error_log("AFTER UPDATE - stats:" . $data_after['stats'] . " | hmoney:" . $data_after['hmoney'] . " | paytimes:" . $data_after['paytimes'] . " | rmoney:" . $data_after['rmoney'] . " | rCheckMacValue:" . $data_after['rCheckMacValue'] . " | RtnCode:" . $data_after['RtnCode'] . " | RtnMsg:" . $data_after['RtnMsg']);
    // print_r("AFTER UPDATE - stats:" . $data_after['stats'] . " | hmoney:" . $data_after['hmoney'] . " | paytimes:" . $data_after['paytimes'] . " | rmoney:" . $data_after['rmoney'] . " | rCheckMacValue:" . $data_after['rCheckMacValue'] . " | RtnCode:" . $data_after['RtnCode'] . " | RtnMsg:" . $data_after['RtnMsg']); die();
    
    $rstat = ($rstat == 3) ? 1 : $rstat;
    error_log("Final rstat: $rstat, condition: " . (($rr >= 1 && $rstat == 1) ? 'TRUE' : 'FALSE'));

    if ($rr >= 1 && $rstat == 1) {  // 改為 >= 1 因為可能影響多行
        $qquerylog = $pdo->prepare("SELECT * FROM servers_log where orderid=?");
        $qquerylog->execute(array($MerchantTradeNo));
        $ddlog = $qquerylog->fetch();
        $money = $ddlog["money"];
        $bmoney = $ddlog["bmoney"];
        $gameid = $ddlog["gameid"];
        $paytype = $ddlog["paytype"];
  
        $qquery = $pdo->prepare("SELECT * FROM servers where auton=?");
        $qquery->execute(array($foran));
        if (!$dd = $qquery->fetch()) {
            $qud = $pdo->prepare("update servers_log set errmsg='找尋伺服器資料庫時發生錯誤' where orderid=?");
            $qud->execute(array($MerchantTradeNo));
            die("0");
        }
        $ip = $dd["db_ip"];
        $port = $dd["db_port"];
        $dbname = $dd["db_name"];
        $user = $dd["db_user"];
        $pass = $dd["db_pass"];
        $pid = $dd["db_pid"];

        $bonusid = $dd["db_bonusid"];
        $bonusrate = $dd["db_bonusrate"];        

        $gamepdo = opengamepdo($ip, $port, $dbname, $user, $pass);

        if($dd["paytable"] == "ezpay") {
            // ezpay 處理贊助金
            $gamei = array(':amount' => $bmoney,':payname' => $gameid);
            $gameq   = $gamepdo->prepare("INSERT INTO ezpay (amount, payname, state) VALUES(:amount,:payname, 1)");
            if(!$rr = $gameq->execute($gamei)) {
                $qud = $pdo->prepare("update servers_log set errmsg='存入贊助幣時發生錯誤' where orderid=?");
                $qud->execute(array($MerchantTradeNo));
                die("0");
            }
            $pdo->commit();
            echo $endstr;
            exit;            
        }

        // 查詢遊戲資料表user，取得現有point並加總後存回
        if($dd["paytable"] == "hope") {
            updateUserPoint($gamepdo, $pdo, $gameid, $bmoney, $MerchantTradeNo, $endstr);
        }

        //處理贊助金
        if ($paytype == 5) {
            $card = 1;
        } else {
            $card = 0;
        }
        $gamei = array(':p_id' => $pid,':p_name' => '贊助幣',':count' => $bmoney,':account' => $gameid,':r_count' => $money, ':card' => $card);
        $gameq   = $gamepdo->prepare("INSERT INTO shop_user (p_id,p_name, count, account,r_count, card) VALUES(:p_id,:p_name,:count,:account,:r_count, :card)");
        if (!$gameq->execute($gamei)) {
            $qud = $pdo->prepare("update servers_log set errmsg='存入贊助幣時發生錯誤' where orderid=?");
            $qud->execute(array($MerchantTradeNo));
            die("0");
        }

        // 處理紅利幣
        if (!empty($bonusid) && $bonusrate > 0) {
            $bonusmoney = $money * ($bonusrate / 100);
            $gamei = array(':p_id' => $bonusid,':p_name' => '紅利幣',':count' => $bonusmoney,':account' => $gameid,':r_count' => $money);
            $gameq   = $gamepdo->prepare("INSERT INTO shop_user (p_id,p_name, count, account,r_count) VALUES(:p_id,:p_name,:count,:account,:r_count)");
            if (!$gameq->execute($gamei)) {
                $qud = $pdo->prepare("update servers_log set errmsg='存入紅利幣時發生錯誤' where orderid=?");
                $qud->execute(array($MerchantTradeNo));
                die("0");
            }
        }
  

        //處理滿額贈禮
        //是否開啟
        $gift1 = 0;
        $qquerylog1 = $pdo->prepare("SELECT * FROM servers_gift where foran=? and types=1 and pid='stat'");
        $qquerylog1->execute(array($foran));
        if ($ddlog1 = $qquerylog1->fetch()) {
            if ($ddlog1["sizes"] == 1) {
                $gift1 = 1;
            }
        }
  
        if ($gift1 == 1) {  //如果有開啟才動作
            // 抓所有金額
            $qquerylog1 = $pdo->prepare("SELECT * FROM servers_gift where foran=? and types=1 and not pid='stat' for update");
            $qquerylog1->execute(array($foran));
            if ($ddlog1 = $qquerylog1->fetchALL()) {
                foreach ($ddlog1 as $ddl1) {
                    $m1 = $ddl1["m1"];
                    $m2 = $ddl1["m2"];
                    $pid = $ddl1["pid"];
                    $sizes = $ddl1["sizes"];
                    if ($money >= $m1 && $money <= $m2 && $sizes > 0) {
                        $gamepdo_add = $gamepdo->prepare("INSERT INTO shop_user (p_id,p_name, count, account) VALUES(?,?,?,?)");
                        $gamepdo_add->execute(array($pid, '滿額贈禮', $sizes, $gameid));
                    }
                }
            }
        }

        //處理首購禮
        //是否開啟
        $gift2 = 0;
        $qquerylog2 = $pdo->prepare("SELECT * FROM servers_gift where foran=? and types=2 and pid='stat'");
        $qquerylog2->execute(array($foran));
        if ($ddlog2 = $qquerylog2->fetch()) {
            if ($ddlog2["sizes"] == 1) {
                $gift2 = 1;
            }
        }
  
        if ($gift2 == 1) {  //如果有開啟才動作
            // 確認是不是首購
            $gamepdo_query = $gamepdo->prepare("select count(*) from shop_user where account=? and p_name='贊助幣'");
            $gamepdo_query->execute(array($gameid));
    
            if ($gamepdo_query->fetchColumn() == 1) { // 是首購才動作
                $qquerylog2 = $pdo->prepare("SELECT * FROM servers_gift where foran=? and types=2 and not pid='stat' for update");
                $qquerylog2->execute(array($foran));
                if ($ddlog2 = $qquerylog2->fetchALL()) {
                    foreach ($ddlog2 as $ddl2) {
                        $m1 = $ddl2["m1"];
                        $m2 = $ddl2["m2"];
                        $pid = $ddl2["pid"];
                        $sizes = $ddl2["sizes"];
                        if ($money >= $m1 && $money <= $m2 && $sizes > 0) {
                            $gamepdo_add = $gamepdo->prepare("INSERT INTO shop_user (p_id,p_name, count, account) VALUES(?,?,?,?)");
                            $gamepdo_add->execute(array($pid, '首購禮', $sizes, $gameid));
                        }
                    }
                }
            }
        }
  
        //處理活動首購禮
        //是否開啟
        $gift4 = 0;
        $gift4time = 0;
        $qquerylog4 = $pdo->prepare("SELECT * FROM servers_gift where foran=? and types=4 and pid='stat'");
        $qquerylog4->execute(array($foran));
        if ($ddlog4 = $qquerylog4->fetch()) {
            if ($ddlog4["sizes"] == 1) {
                $gift4 = 1;
            }
        }
  
        if ($gift4 == 1) {  //如果有開啟才動作
            // 確認是不是在活動時間內
            $qquerylog4t = $pdo->prepare("SELECT * FROM servers_gift where foran=? and types=4 and pid in ('time1', 'time2')");
            $qquerylog4t->execute(array($foran));
            if ($ddlog4tt = $qquerylog4t->fetchAll()) {
                foreach ($ddlog4tt as $ddlog4t) {
                    if ($ddlog4t["pid"] == "time1") {
                        $time41 = $ddlog4t["dd"];
                    }
                    if ($ddlog4t["pid"] == "time2") {
                        $time42 = $ddlog4t["dd"];
                    }
                }
            }
            if (strtotime($time41) !== false && strtotime($time42) !== false) {
                if (time() >= strtotime($time41) && time() <= strtotime($time42)) {
                    $gift4time = 1;
                }
            }

            // 活動時間開關
            if ($gift4time === 1) {
                // 確認是不是首購
                $gamepdo_query = $gamepdo->prepare("select count(*) from shop_user where account=? and p_name='贊助幣' and create_time between '$time41' and '$time42'");
                $gamepdo_query->execute(array($gameid));
    
                if ($gamepdo_query->fetchColumn() == 1) { // 是首購才動作
                    $qquerylog4 = $pdo->prepare("SELECT * FROM servers_gift where foran=? and types=4 and not pid='stat' for update");
                    $qquerylog4->execute(array($foran));
                    if ($ddlog4 = $qquerylog4->fetchALL()) {
                        foreach ($ddlog4 as $ddl4) {
                            $m1 = $ddl4["m1"];
                            $m2 = $ddl4["m2"];
                            $pid = $ddl4["pid"];
                            $sizes = $ddl4["sizes"];
                            if ($money >= $m1 && $money <= $m2 && $sizes > 0) {
                                $gamepdo_add = $gamepdo->prepare("INSERT INTO shop_user (p_id,p_name, count, account) VALUES(?,?,?,?)");
                                $gamepdo_add->execute(array($pid, '活動首購禮', $sizes, $gameid));
                            }
                        }
                    }
                }
            }
        }

        //處理累積儲值
        //是否開啟
        $gift3 = 0;
        $qquerylog3 = $pdo->prepare("SELECT * FROM servers_gift where foran=? and types=3 and pid='stat'");
        $qquerylog3->execute(array($foran));
        if ($ddlog3 = $qquerylog3->fetch()) {
            if ($ddlog3["sizes"] == 1) {
                $gift3 = 1;
            }
        }
  
        if ($gift3 == 1) {  //如果有開啟才動作
            // 統計累積儲值金額
            $gamepdo_query = $gamepdo->prepare("select sum(r_count) from shop_user where account=? and p_name='贊助幣'");
            $gamepdo_query->execute(array($gameid));
            $total_pay = $gamepdo_query->fetchColumn();
            if ($total_pay > 0) { // 儲值金額大於0才動作
                //所有累積儲值金額
                $qquerylog3 = $pdo->prepare("SELECT * FROM servers_gift where foran=? and types=3 and not pid='stat' for update");
                $qquerylog3->execute(array($foran));
                if ($ddlog3 = $qquerylog3->fetchALL()) {
                    foreach ($ddlog3 as $ddl3) {
                        $m1 = $ddl3["m1"];
                        $pid = $ddl3["pid"];
                        $sizes = $ddl3["sizes"];
                        if ($total_pay >= $m1 && $sizes > 0) {
                            $gamepdo_qq = $gamepdo->prepare("select count(*) from shop_user where account=? and p_name='累積儲值' and r_count=?");
                            $gamepdo_qq->execute(array($gameid, $m1));
                            if (!$gamepdo_qq->fetchColumn()) {
                                $gamepdo_add = $gamepdo->prepare("INSERT INTO shop_user (p_id,p_name, count, account, r_count) VALUES(?,?,?,?,?)");
                                $gamepdo_add->execute(array($pid, '累積儲值', $sizes, $gameid, $m1));
                            }
                        }
                    }
                }
            }
        }
    }
    if ($rr) {
        $pdo->commit();
    }
} catch (Exception $e) {
    $pdo->rollBack();
    //file_put_contents('log.txt', 'aa:'.$e->getMessage()."\n");
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
echo $endstr;
exit;
