<?php
/**
 * ANT 支付結果回調處理端點
 * 接收ANT服務的支付結果通知
 */
include("myadm/include.php");
include_once('./pay_bank.php');
include_once('./ant_api_service.php');

// 記錄所有接收到的資料
$raw_input = file_get_contents('php://input');
$callback_data = json_decode($raw_input, true);

// 如果是POST form data
if (empty($callback_data)) {
    $callback_data = $_POST;
}

// 記錄回調日誌
error_log('[ANT-CALLBACK] Received: ' . json_encode($callback_data, JSON_UNESCAPED_UNICODE));

// 立即記錄回調資訊到專用表（不論後續處理是否成功）
$pdo_for_log = openpdo();
if ($pdo_for_log) {
    try {
        $initial_log_query = $pdo_for_log->prepare("
            INSERT INTO ant_callback_logs
            (order_id, callback_data, callback_time, status_before, status_after)
            VALUES (?, ?, NOW(), NULL, NULL)
        ");
        
        $partner_number_for_log = $callback_data['partner_number'] ?? $callback_data['order_id'] ?? $callback_data['orderid'] ?? 'unknown';
        
        $initial_log_query->execute([
            $partner_number_for_log,
            json_encode($callback_data, JSON_UNESCAPED_UNICODE)
        ]);
        
        $callback_log_id = $pdo_for_log->lastInsertId();
        error_log("[ANT-CALLBACK] 初始回調記錄已建立，ID: {$callback_log_id}");
    } catch (Exception $log_e) {
        error_log("[ANT-CALLBACK-LOG-ERROR] 無法記錄初始回調資料: " . $log_e->getMessage());
    }
}

try {
    // 檢查必要參數
    if (empty($callback_data)) {
        throw new Exception("沒有收到回調資料");
    }
    
    // 根據ANT API文檔，可能使用不同的欄位名稱
    $partner_number = $callback_data['partner_number'] ?? $callback_data['order_id'] ?? $callback_data['orderid'] ?? '';
    $ant_order_number = $callback_data['number'] ?? $callback_data['order_number'] ?? '';

    if (empty($partner_number)) {
        throw new Exception("回調資料中缺少訂單編號");
    }
    
    $pdo = openpdo();
    
    // 查詢本地訂單資訊
    $query = $pdo->prepare("SELECT sl.*, s.gstats_bank FROM servers_log sl
                           LEFT JOIN servers s ON sl.foran = s.auton
                           WHERE sl.orderid = ?");
    $query->execute(array($partner_number));
    $order_data = $query->fetch();
    
    if (!$order_data) {
        throw new Exception("找不到訂單資訊: " . $partner_number);
    }

    // 檢查是否為ANT支付
    if ($order_data['pay_cp'] !== 'ant') {
        throw new Exception("非ANT支付訂單: " . $partner_number);
    }

    // 檢查是否為模擬付款
    $is_mock_payment = (isset($_POST['mockpay']) && $_POST['mockpay'] == 1) ||
                       (isset($callback_data['mockpay']) && $callback_data['mockpay'] == 1);

    if ($is_mock_payment) {
        // 模擬付款使用預設測試參數
        $ant_username = 'antpay018';
        $ant_hashkey = 'lyAJwWnVAKNScXjE6t2rxUOAeesvIP9S';
        $ant_hashiv = 'yhncs1WpMo60azxEczokzIlVVvVuW69p';
        error_log("[ANT-CALLBACK] 模擬付款使用預設測試參數");
    } else {
        // 正常付款從資料庫取得ANT設定
        $payment_info = getSpecificBankPaymentInfo($pdo, $order_data['auton'], 'ant');

        if (!$payment_info || !isset($payment_info['payment_config'])) {
            throw new Exception("ANT設定錯誤");
        }

        // 使用資料庫的參數，所有參數必須從資料庫取得
        $ant_username = $payment_info['payment_config']['username'] ?? null;
        $ant_hashkey = $payment_info['payment_config']['hashkey'] ?? null;
        $ant_hashiv = $payment_info['payment_config']['hashiv'] ?? null;

        // 檢查必要參數是否存在
        if (!$ant_username) {
            throw new Exception("ANT設定錯誤：缺少username參數");
        }
        if (!$ant_hashkey) {
            throw new Exception("ANT設定錯誤：缺少hashkey參數");
        }
        if (!$ant_hashiv) {
            throw new Exception("ANT設定錯誤：缺少hashiv參數");
        }
    }
    $is_production = ($order_data['gstats_bank'] == 1);

    // 解析ANT回調資料格式
    $payment_status_code = $callback_data['status'] ?? 0;
    $payment_amount = $callback_data['amount'] ?? 0;
    $paid_amount = $callback_data['pay_amount'] ?? $payment_amount;

    // Debug: 記錄接收到的關鍵參數
    error_log("[ANT-CALLBACK-DEBUG] 接收參數: status={$payment_status_code}, amount={$payment_amount}, partner_number={$partner_number}");

    // 驗證金額
    if ($payment_amount != $order_data['money']) {
        error_log("[ANT-CALLBACK] 金額不符: 預期 {$order_data['money']}, 實際 {$payment_amount}");
    }

    // 根據ANT狀態代碼更新本地訂單狀態
    $new_status = $order_data['stats']; // 預設保持原狀態
    $status_description = '';

    switch ($payment_status_code) {
        case 4: // ANT: 已完成
            $new_status = 1; // 本地: 付款完成
            $status_description = '支付成功';
            break;

        case 5: // ANT: 已取消
        case 7: // ANT: 金額不符合
        case 8: // ANT: 銀行不符合
            $new_status = 2; // 本地: 付款失敗
            $status_description = '支付失敗';
            break;

        case 6: // ANT: 已退款
            $new_status = -4; // 本地: 已退款
            $status_description = '已退款';
            break;

        case 1: // ANT: 已建立
        case 2: // ANT: 處理中
        case 3: // ANT: 待繳費
        default:
            $new_status = 0; // 本地: 等待付款
            $status_description = '等待付款';
            break;
    }

    // Debug: 記錄狀態映射結果
    error_log("[ANT-CALLBACK-DEBUG] 狀態映射: ANT status={$payment_status_code} => 本地 status={$new_status} ({$status_description})");

    // 記錄回調前的狀態
    $status_before = $order_data['stats'];

    // 根據狀態設定回傳訊息
    if ($new_status == 1) {
        if ($payment_status_code == 4) {
            $rtn_msg = '交易成功';
        } else {
            $rtn_msg = $is_mock_payment ? '模擬付款成功' : '支付成功';
        }
    } else if ($new_status == 2) {
        $rtn_msg = $is_mock_payment ? '模擬付款失敗' : '支付失敗';
    } else if ($new_status == 0) {
        $rtn_msg = '等待付款';
    } else {
        $rtn_msg = $status_description;
    }

    // 開始資料庫交易
    $pdo->beginTransaction();

    try {
        // 更新訂單狀態和訊息 (更完整的欄位更新，參考ebpay_r.php)
        $payment_date = $callback_data['paid_at'];
        $payment_type_charge_fee = 0; // ANT通常不收手續費
        $rtn_code = ($new_status == 1) ? 1 : 0;

        $update_query = $pdo->prepare("UPDATE servers_log SET stats = ?, RtnMsg = ?, paytimes = ?, hmoney = ?, rmoney = ?, rCheckMacValue = ?, RtnCode = ? WHERE auton = ?");
        $update_result = $update_query->execute([
            $new_status,
            $rtn_msg,
            $payment_date,
            $payment_type_charge_fee,
            $paid_amount,
            $callback_data['sign'] ?? '',
            $rtn_code,
            $order_data['auton']
        ]);

        if (!$update_result) {
            throw new Exception("訂單狀態更新失敗");
        }

        // Debug: 記錄更新成功
        error_log("[ANT-CALLBACK-DEBUG] 訂單狀態更新成功: {$order_data['auton']} 從 {$status_before} 更新為 {$new_status}");

        // 如果支付成功，處理遊戲內業務邏輯 (參考ebpay_r.php的完整流程)
        if ($new_status == 1) {
            // 查詢伺服器設定
            $server_query = $pdo->prepare("SELECT * FROM servers WHERE auton = ?");
            $server_query->execute([$order_data['foran']]);
            $server_data = $server_query->fetch();

            if (!$server_data) {
                throw new Exception("找不到伺服器設定資料");
            }

            // 連接遊戲伺服器資料庫
            $game_pdo = opengamepdo(
                $server_data["db_ip"],
                $server_data["db_port"],
                $server_data["db_name"],
                $server_data["db_user"],
                $server_data["db_pass"]
            );

            if (!$game_pdo) {
                throw new Exception("無法連接遊戲伺服器資料庫");
            }

            $money = $order_data["money"];
            $bmoney = $order_data["bmoney"];
            $gameid = $order_data["gameid"];
            $paytype = $order_data["paytype"];
            $pid = $server_data["db_pid"];
            $bonusid = $server_data["db_bonusid"];
            $bonusrate = $server_data["db_bonusrate"];

            // 處理 ezpay 特殊邏輯
            if ($server_data["paytable"] == "ezpay") {
                $game_query = $game_pdo->prepare("INSERT INTO ezpay (amount, payname, state) VALUES (?, ?, 1)");
                if (!$game_query->execute([$bmoney, $gameid])) {
                    throw new Exception("存入贊助幣時發生錯誤 (ezpay)");
                }
            } else {
                // 處理贊助金
                $card = ($paytype == 5) ? 1 : 0;
                $game_query = $game_pdo->prepare("INSERT INTO shop_user (p_id, p_name, count, account, r_count, card) VALUES (?, ?, ?, ?, ?, ?)");
                if (!$game_query->execute([$pid, '贊助幣', $bmoney, $gameid, $money, $card])) {
                    throw new Exception("存入贊助幣時發生錯誤");
                }

                // 處理紅利幣
                if (!empty($bonusid) && $bonusrate > 0) {
                    $bonusmoney = $money * ($bonusrate / 100);
                    $bonus_query = $game_pdo->prepare("INSERT INTO shop_user (p_id, p_name, count, account, r_count) VALUES (?, ?, ?, ?, ?)");
                    if (!$bonus_query->execute([$bonusid, '紅利幣', $bonusmoney, $gameid, $money])) {
                        throw new Exception("存入紅利幣時發生錯誤");
                    }
                }

                // 處理滿額贈禮 (直接實作，參考ebpay_r.php)
                $gift1 = 0;
                $gift_check = $pdo->prepare("SELECT * FROM servers_gift WHERE foran = ? AND types = 1 AND pid = 'stat'");
                $gift_check->execute([$order_data['foran']]);
                if ($gift_data = $gift_check->fetch()) {
                    if ($gift_data["sizes"] == 1) $gift1 = 1;
                }

                if ($gift1 == 1) {
                    $gift_query = $pdo->prepare("SELECT * FROM servers_gift WHERE foran = ? AND types = 1 AND NOT pid = 'stat'");
                    $gift_query->execute([$order_data['foran']]);
                    if ($gifts = $gift_query->fetchAll()) {
                        foreach ($gifts as $gift) {
                            $m1 = $gift["m1"];
                            $m2 = $gift["m2"];
                            $gift_pid = $gift["pid"];
                            $sizes = $gift["sizes"];
                            if ($money >= $m1 && $money <= $m2 && $sizes > 0) {
                                $gift_add = $game_pdo->prepare("INSERT INTO shop_user (p_id, p_name, count, account) VALUES (?, ?, ?, ?)");
                                $gift_add->execute([$gift_pid, '滿額贈禮', $sizes, $gameid]);
                            }
                        }
                    }
                }

                // 處理首購禮
                $gift2 = 0;
                $first_check = $pdo->prepare("SELECT * FROM servers_gift WHERE foran = ? AND types = 2 AND pid = 'stat'");
                $first_check->execute([$order_data['foran']]);
                if ($first_data = $first_check->fetch()) {
                    if ($first_data["sizes"] == 1) $gift2 = 1;
                }

                if ($gift2 == 1) {
                    $first_purchase_check = $game_pdo->prepare("SELECT COUNT(*) FROM shop_user WHERE account = ? AND p_name = '贊助幣'");
                    $first_purchase_check->execute([$gameid]);
                    if ($first_purchase_check->fetchColumn() == 1) {
                        $first_gifts = $pdo->prepare("SELECT * FROM servers_gift WHERE foran = ? AND types = 2 AND NOT pid = 'stat'");
                        $first_gifts->execute([$order_data['foran']]);
                        if ($first_gift_list = $first_gifts->fetchAll()) {
                            foreach ($first_gift_list as $first_gift) {
                                $m1 = $first_gift["m1"];
                                $m2 = $first_gift["m2"];
                                $first_pid = $first_gift["pid"];
                                $sizes = $first_gift["sizes"];
                                if ($money >= $m1 && $money <= $m2 && $sizes > 0) {
                                    $first_add = $game_pdo->prepare("INSERT INTO shop_user (p_id, p_name, count, account) VALUES (?, ?, ?, ?)");
                                    $first_add->execute([$first_pid, 'first', $sizes, $gameid]);
                                }
                            }
                        }
                    }
                }

                // 處理累積儲值
                $gift3 = 0;
                $acc_check = $pdo->prepare("SELECT * FROM servers_gift WHERE foran = ? AND types = 3 AND pid = 'stat'");
                $acc_check->execute([$order_data['foran']]);
                if ($acc_data = $acc_check->fetch()) {
                    if ($acc_data["sizes"] == 1) $gift3 = 1;
                }

                if ($gift3 == 1) {
                    $total_query = $game_pdo->prepare("SELECT SUM(r_count) FROM shop_user WHERE account = ? AND p_name = '贊助幣'");
                    $total_query->execute([$gameid]);
                    $total_pay = $total_query->fetchColumn();
                    if ($total_pay > 0) {
                        $acc_gifts = $pdo->prepare("SELECT * FROM servers_gift WHERE foran = ? AND types = 3 AND NOT pid = 'stat'");
                        $acc_gifts->execute([$order_data['foran']]);
                        if ($acc_list = $acc_gifts->fetchAll()) {
                            foreach ($acc_list as $acc_gift) {
                                $m1 = $acc_gift["m1"];
                                $acc_pid = $acc_gift["pid"];
                                $sizes = $acc_gift["sizes"];
                                if ($total_pay >= $m1 && $sizes > 0) {
                                    $acc_exist = $game_pdo->prepare("SELECT COUNT(*) FROM shop_user WHERE account = ? AND p_name = '累積儲值' AND r_count = ?");
                                    $acc_exist->execute([$gameid, $m1]);
                                    if (!$acc_exist->fetchColumn()) {
                                        $acc_add = $game_pdo->prepare("INSERT INTO shop_user (p_id, p_name, count, account, r_count) VALUES (?, ?, ?, ?, ?)");
                                        $acc_add->execute([$acc_pid, '累積儲值', $sizes, $gameid, $m1]);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            error_log("[ANT-CALLBACK] 遊戲內業務處理完成: 訂單 {$partner_number}, 用戶 {$gameid}, 金額 {$money}");
        }

        // 更新之前建立的回調記錄，補充處理結果資訊
        if (isset($callback_log_id)) {
            $update_log_query = $pdo->prepare("
                UPDATE ant_callback_logs 
                SET servers_log_id = ?, status_before = ?, status_after = ?,
                    callback_data = ?
                WHERE id = ?
            ");
            
            $detailed_callback_data = json_encode([
                'partner_number' => $partner_number,
                'ant_order_number' => $ant_order_number,
                'status_code' => $payment_status_code,
                'amount' => $payment_amount,
                'paid_amount' => $paid_amount,
                'raw_data' => $callback_data
            ], JSON_UNESCAPED_UNICODE);
            
            $update_log_result = $update_log_query->execute([
                $order_data['auton'],
                $status_before,
                $new_status,
                $detailed_callback_data,
                $callback_log_id
            ]);

            if (!$update_log_result) {
                error_log("[ANT-CALLBACK-LOG-ERROR] 回調記錄更新失敗");
            }
        }

        // 更新ANT訂單編號到本地資料庫
        // if ($ant_order_number && $ant_order_number !== $order_data['third_party_order_id']) {
        //     $update_ant_order = $pdo->prepare("UPDATE servers_log SET third_party_order_id = ? WHERE auton = ?");
        //     $update_ant_order->execute([$ant_order_number, $order_data['auton']]);
        // }

        // 提交交易
        $pdo->commit();

        // 記錄成功處理日誌
        error_log("[ANT-CALLBACK] 訂單 {$partner_number} (商店) / {$ant_order_number} (ANT) 狀態更新成功: {$status_description} (status: {$new_status})");

        // 回應ANT服務 (根據文檔要求返回正確格式)
        echo "OK"; // ANT API文檔要求的回應格式

    } catch (Exception $inner_e) {
        // 回滾交易
        $pdo->rollBack();
        throw $inner_e; // 重新拋出異常，讓外層catch處理
    }
    
} catch (Exception $e) {
    // 回滾交易（如果還在進行中）
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // 錯誤處理
    error_log("[ANT-CALLBACK-ERROR] " . $e->getMessage() . " | Data: " . json_encode($callback_data, JSON_UNESCAPED_UNICODE));

    // 檢查是否為模擬付款
    $is_mock_payment = (isset($_POST['mockpay']) && $_POST['mockpay'] == 1) ||
                       (isset($callback_data['mockpay']) && $callback_data['mockpay'] == 1);

    // 只有在非模擬付款且找到訂單的情況下才標記為失敗
    if (!$is_mock_payment && isset($order_data) && $order_data) {
        // 只有在實際支付流程中發生錯誤時才更新狀態
        // 模擬付款的錯誤不應該影響訂單狀態
        try {
            $pdo->prepare("UPDATE servers_log SET stats = 2, RtnMsg = ? WHERE auton = ?")
                ->execute(['Callback處理失敗: ' . $e->getMessage(), $order_data['auton']]);
            error_log("[ANT-CALLBACK-ERROR] 已將訂單 {$order_data['auton']} 標記為失敗");
        } catch (Exception $updateError) {
            error_log("[ANT-CALLBACK-ERROR] 無法更新訂單狀態: " . $updateError->getMessage());
        }
    } else if ($is_mock_payment) {
        error_log("[ANT-CALLBACK-ERROR] 模擬付款錯誤，不更新訂單狀態: " . $e->getMessage());
    }

    // 回應錯誤 (根據ANT要求的格式)
    http_response_code(400);
    echo "ERROR: " . $e->getMessage();
}
?>