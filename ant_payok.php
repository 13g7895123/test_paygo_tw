<?php

include("myadm/include.php");
include_once('./web_class.php');

//print_r($_REQUEST);

$MerchantTradeNo = $_POST["MerchantTradeNo"];

if(!$MerchantTradeNo) alert("資料錯誤-8000301。", 0);

	//read
	$pdo = openpdo();
    $sq    = $pdo->prepare("SELECT * FROM servers_log where orderid=?");
    $sq->execute(array($MerchantTradeNo));
    if(!$sqd = $sq->fetch()) alert("不明錯誤-8000302。", 0);
    $user_IP = get_real_ip();
    $custombg = "assets/images/particles_bg.jpg";
    $pagebgpdo= $pdo->prepare("SELECT custombg FROM servers where auton=?");
    $pagebgpdo->execute(array($sqd["foran"]));

    if($pagebg = $pagebgpdo->fetch()) {
        if (isset($pagebg["custombg"]) && !empty($pagebg["custombg"])) {
            $custombg = "assets/images/custombg/".$pagebg["custombg"];
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Game Sponsor</title>
    <meta name="description" content="Game Sponsor" />
    <meta name="Author" content="<?=$weburl?>" />

    <!-- mobile settings -->
    <meta name="viewport" content="width=device-width, maximum-scale=1, initial-scale=1, user-scalable=0" />

    <!--[if IE]><meta http-equiv='X-UA-Compatible' content='IE=edge,chrome=1'><![endif]-->

    <!-- CORE CSS -->
    <link href="/assets/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />

    <!-- THEME CSS -->
    <link href="/assets/css/essentials.css" rel="stylesheet" type="text/css" />
    <link href="/assets/css/layout.css?v=1.1" rel="stylesheet" type="text/css" />

    <!-- PAGE LEVEL SCRIPTS -->
    <link href="/assets/css/color_scheme/green.css" rel="stylesheet" type="text/css" id="color_scheme" />
</head>

<body>
    <!-- wrapper -->
    <div id="wrapper">
        <!-- SLIDER -->
        <section id="slider" class="fullheight" style="background:url('<?=$custombg?>')">
            <span class="overlay dark-2"><!-- dark overlay [0 to 9 opacity] --></span>
            <canvas id="canvas-particle" data-rgb="156,217,249"><!-- CANVAS PARTICLES --></canvas>
            <div class="display-table">
                <div class="display-table-cell vertical-align-middle">
                    <div class="container text-center">
                        <h2>Game Sponsor</h2>
                        <h1 class="nomargin wow fadeInUp" data-wow-delay="0.4s">
                            <!--

                                TEXT ROTATOR

                                data-animation="fade|flip|flipCube|flipUp|spin"

                            -->
                            <span class="rotate" data-animation="fade" data-speed="1500">
                                自助贊助中心, 快速金流, 安全隱私
                            </span>
                        </h1>
                        <hr>
                        <div class="main-form">
                            <div class="col-md-12 col-xs-12 main-title pb-20">遊戲伺服器：【<?=$sqd["forname"]?>】<?=$dbstat?></div>
                            <div class="col-md-12 col-xs-12 pb-20">
                            <p>請至對應金流繳費，並確保您的代碼抄寫正確<br>
                <p>繳費教學：<a href="https://www.opay.tw/Service/pay_way_cvcde" target="_blank">超商代碼繳費</a>｜<a href="https://www.opay.tw/Service/pay_way_webatm" target="_blank">ATM虛擬帳號轉帳</a>｜<a href="https://www.opay.tw/Service/pay_way_cvpay" target="_blank">7-11 ibon代碼</a></p>
                            </div>
                            <div class="col-md-12 col-xs-12 pb-20">
                                <h2>
                                <?

		$PaymentInfo = $_POST["PaymentInfo"] ?? '';
		$ANTOrderNo = $_POST["ANTOrderNo"] ?? '';
		$payment_info_data = null;
		if ($PaymentInfo) {
		    $payment_info_data = json_decode($PaymentInfo, true);
		}

		if($sqd["paytype"] == 2 && $sqd["pay_cp"] == 'ant') {
			echo "<div style='font-size:26px;color:white;'>繳費金額：".$sqd["money"]."</div>";

			// ANT 銀行轉帳資訊顯示 - 使用與ebpay_payok相同的樣式
			if ($payment_info_data && is_array($payment_info_data)) {
				// 如果有API回傳的銀行資訊
				$BankCode = $payment_info_data['bank_code'] ?? '';
				$vAccount = $payment_info_data['bank_account'] ?? '';

				// 獲取繳費期限 - 預設3天後到期
				$ExpireDate = $sqd["ExpireDate"] ?? '';
				if (empty($ExpireDate)) {
					$ExpireDate = date('Y-m-d H:i:s', strtotime('+1 days'));
					// 更新資料庫中的期限
					if (!empty($BankCode) && !empty($vAccount)) {
						$sq2 = $pdo->prepare("update servers_log set PaymentNo=?, ExpireDate=? where orderid=?");
						$sq2->execute(array($BankCode."-".$vAccount, $ExpireDate, $MerchantTradeNo));
					}
				}

				// 使用標準的payment_inf_render函數顯示銀行轉帳資訊
				if ($BankCode && $vAccount) {
					echo web::payment_inf_render(0, $BankCode, $vAccount, $ExpireDate);
				} else {
					echo "<div style='color:white; margin-top:20px;'>";
					echo "</div>";
				}
			} else {
				// 顯示基本資訊或等待狀態
				echo "<div style='color:white; margin-top:20px;'>";
				echo "<p>ANT 約定帳戶轉帳</p>";
				if (!empty($sqd["user_bank_code"])) {
					echo "<p>您的銀行代號：" . htmlspecialchars($sqd["user_bank_code"]) . "</p>";
				}
				if (!empty($sqd["user_bank_account"])) {
					$masked_account = str_repeat('*', strlen($sqd["user_bank_account"]) - 4) . substr($sqd["user_bank_account"], -4);
					echo "<p>您的銀行帳號：" . htmlspecialchars($masked_account) . "</p>";
				}
				// 顯示期限資訊
				$ExpireDate = $sqd["ExpireDate"] ?? '';
				if (empty($ExpireDate)) {
					$ExpireDate = date('Y-m-d H:i:s', strtotime('+3 days'));
				}
				echo "<div style='font-size:26px;color:white; margin-top:10px;'>請在繳費期限 ".$ExpireDate." 前完成轉帳</div>";
				echo "</div>";
			}

		} else {
			echo "<div style='color: red;'>不支援的付款方式</div>";
		}

                                ?>
                                </h2>
                            </div>

                            <div class="col-md-12 col-xs-12 pb-20">

                            <a class="btn btn-default" href="<?=$weburl.$sqd["serverid"]?>" target="_self">回首頁</a>

                            </div>

                            <div class="col-md-12 col-xs-12 pb-20" style="color:white">所有繳費資料包含IP電磁紀錄皆已留存，如有惡意人士利用此繳費平台進行第三方詐騙，請受害者立即與我們客服聯繫提供資料報警處理，請注意您的贊助皆為個人自願性，繳費後將無法做退費的動作，我們會將該筆費用維持伺服器運行與開發研究，並捐出部分款項給慈善機構，如贊助金流系統故障請聯絡客服人員！</div>

                          <div class="col-md-12 col-xs-12 pb-20" style="color:white">您的 IP 位置：<?=$user_IP?></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- /SLIDER -->

        <!-- FOOTER -->

        <footer id="footer">



                <div class="row">

                  <div class="col-md-3"></div>

                    <div class="col-md-6 text-center">

                        &copy Game Sponsor

                    </div>

                    <div class="col-md-3"></div>



                </div>




        </footer>

        <!-- /FOOTER -->
    </div>
    <!-- /wrapper -->

    <!-- SCROLL TO TOP -->

    <a href="#" id="toTop"></a>




    <!-- PRELOADER -->

    <div id="preloader">

        <div class="inner">

            <span class="loader"></span>

        </div>

    </div><!-- /PRELOADER -->




    <!-- JAVASCRIPT FILES -->

    <script type="text/javascript">var plugin_path = 'assets/plugins/';</script>

    <script type="text/javascript" src="/assets/plugins/jquery/jquery-2.2.3.min.js"></script>



    <script type="text/javascript" src="/assets/js/scripts.js"></script>

    <!-- PARTICLE EFFECT -->

    <script type="text/javascript" src="/assets/plugins/canvas.particles.js"></script>
        <? include_once('./integration.php');?>
</body>
</html>