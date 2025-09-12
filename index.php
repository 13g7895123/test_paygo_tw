<?php

include("myadm/include.php");

if ($_REQUEST["st"] == "readbi" && $_REQUEST["v"] != "") {
	if ($_SESSION["foran"] == "") die("伺服器資料錯誤-8000201。");
	if ($_SESSION["serverid"] == "") die("伺服器資料錯誤-8000202。");
	$v = $_REQUEST["v"];

	$pdo = openpdo();
	$query    = $pdo->prepare("SELECT * FROM servers_bi where stats=1 and foran=? order by money2 asc");
	$query->execute(array($_SESSION["foran"]));

	if (!$datalist = $query->fetchAll()) die($v);
	$bb = 0;

	foreach ($datalist as $datainfo) {
		$m1 = $datainfo["money1"];
		$m2 = $datainfo["money2"];
		$bi = $datainfo["bi"];
		if ($v >= $m1 && $v <= $m2) $bb = $bi;
	}

	if ($bb == 0) $bb = 1;
	$bb = $bb * $v;

	echo $bb;
	die();
}

if ($_REQUEST["st"] == "send") {
	// 檢查是否為AJAX請求
	$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

	if ($_SESSION["foran"] == "") {
		if ($is_ajax) {
			echo json_encode(['status' => 'error', 'message' => '伺服器資料錯誤-8000201。']);
			die();
		} else {
			alert("伺服器資料錯誤-8000201。", 0);
		}
	}
	if ($_SESSION["serverid"] == "") {
		if ($is_ajax) {
			echo json_encode(['status' => 'error', 'message' => '伺服器資料錯誤-8000202。']);
			die();
		} else {
			alert("伺服器資料錯誤-8000202。", 0);
		}
	}
	$gid = $_REQUEST["gid"];
	$money = $_REQUEST["money"];
	$pt = $_REQUEST["pt"];
	$psn = $_REQUEST["psn"];
	$is_bank = $_REQUEST["is_bank"];

	if ($gid == "") {
		if ($is_ajax) {
			echo json_encode(['status' => 'error', 'message' => '請輸入遊戲帳號。']);
			die();
		} else {
			alert("請輸入遊戲帳號。", 0);
		}
	}
	//if($cid == "") alert("請輸入角色名稱。", 0);
	if ($money == "") {
		if ($is_ajax) {
			echo json_encode(['status' => 'error', 'message' => '請輸入繳款金額。']);
			die();
		} else {
			alert("請輸入繳款金額。", 0);
		}
	}
	$money = intval($money);
	if (!is_numeric($money)) {
		if ($is_ajax) {
			echo json_encode(['status' => 'error', 'message' => '繳款金額只能輸入數字。']);
			die();
		} else {
			alert("繳款金額只能輸入數字。", 0);
		}
	}

	if ($pt == "") {
		if ($is_ajax) {
			echo json_encode(['status' => 'error', 'message' => '請選擇繳款方式。']);
			die();
		} else {
			alert("請選擇繳款方式。", 0);
		}
	}
	if ($psn == "") {
		if ($is_ajax) {
			echo json_encode(['status' => 'error', 'message' => '請輸入驗證碼。']);
			die();
		} else {
			alert("請輸入驗證碼。", 0);
		}
	}

	if ($psn != $_SESSION['excellence_fun_code']) {
		if ($is_ajax) {
			echo json_encode(['status' => 'error', 'message' => '驗證碼錯誤。']);
			die();
		} else {
			alert("驗證碼錯誤。", 0);
		}
	}

	// check database config
	$pdo = openpdo();
	$dbqq = $pdo->prepare("SELECT * FROM servers where auton=?");
	$dbqq->execute(array($_SESSION["foran"]));

	if (!$datalist = $dbqq->fetch()) {
		if ($is_ajax) {
			echo json_encode(['status' => 'error', 'message' => '伺服器尚未就緒。']);
			die();
		} else {
			alert("伺服器尚未就緒。", 0);
		}
	} else {
		if (!$datalist["db_ip"] || !$datalist["db_port"] || !$datalist["db_name"] || !$datalist["db_user"] || !$datalist["db_pass"]) {
			if ($is_ajax) {
				echo json_encode(['status' => 'error', 'message' => '伺服器尚未就緒 - 資料庫未設定完成。']);
				die();
			} else {
				alert("伺服器尚未就緒 - 資料庫未設定完成。", 0);
			}
		}
	}

	// 定義變數，後面的datalist會被其他查詢結果蓋掉
	$pay_cp = $datalist["pay_cp"];
	$pay_cp2 = $datalist["pay_cp2"];
	$pay_bank = $datalist["pay_bank"];
	$forname = $datalist["names"];
	// check game id

	if ($datalist['game'] == 0) {
		$table_name = 'accounts';
		$column_name = 'login';
	} else if ($datalist['game'] == 1) {
		$table_name = 'login';
		$column_name = 'userid';
	}

	// 有選擇遊戲的時候要判斷帳號是否存在
	if ($datalist['game'] == 0 or $datalist['game'] == 1) {
		// 判斷是否為希望
		if ($datalist['paytable'] == 'hope') {
			$gamepdo = opengamepdo($datalist["db_ip"], $datalist["db_port"], $datalist["db_name"], $datalist["db_user"], $datalist["db_pass"]);
			$gamequery = $gamepdo->prepare("select * from users where LOWER(id)=?");
			$gamequery->execute(array(strtolower($gid)));

			if (!$gamequery->fetch()) {
				if ($is_ajax) {
					echo json_encode(['status' => 'error', 'message' => '遊戲內無此帳號，請確認您的遊戲帳號3。']);
					die();
				} else {
					alert("遊戲內無此帳號，請確認您的遊戲帳號4。", 0);
				}
			}
		} else {	// 其他遊戲到各自資料庫取資料確認
			$gamepdo = opengamepdo($datalist["db_ip"], $datalist["db_port"], $datalist["db_name"], $datalist["db_user"], $datalist["db_pass"]);
			$gameq = $gamepdo->prepare("select * from $table_name where LOWER($column_name)=?");
			$gameq->execute(array(strtolower($gid)));
			if (!$gameq->fetch()) {
				if ($is_ajax) {
					echo json_encode(['status' => 'error', 'message' => '遊戲內無此帳號，請確認您的遊戲帳號。']);
					die();
				} else {
					alert("遊戲內無此帳號，請確認您的遊戲帳號。", 0);
				}
			}
		}
	}

	// get ip
	$user_IP = get_real_ip();

	// make order id
	$orderid = date("ymdHis");
	$orderid .= strtoupper(substr(uniqid(rand()), 0, 3));

	/* 訂單編號加上網域判斷 */
	$orderid .= '05';	/* paygo 為 01，ezpay 為 02 */

	//算比值
	$bb = 0;
	$qq = $pdo->prepare("SELECT * FROM servers_bi where stats=1 and foran=? order by money2 asc");
	$qq->execute(array($_SESSION["foran"]));
	if ($datalist = $qq->fetchAll()) {
		foreach ($datalist as $datainfo) {
			$m1 = $datainfo["money1"];
			$m2 = $datainfo["money2"];
			$bi = $datainfo["bi"];
			if ($money >= $m1 && $money <= $m2) $bb = $bi;
		}
	}

	if ($pt == 5) { // 信用卡金流
		$pay_cp_check = $pay_cp;
	} else {
		$pay_cp_check = $pay_cp2;
	}

	// 銀行轉帳判定
	if ($pt == 2) {
		$pay_cp_check = $pay_bank;
	}

	if ($bb == 0) $bb = 1;
	$bmoney = $bb * $money;

	// 寫入 servers_log
	$input = array(':foran' => $_SESSION["foran"], ':forname' => $forname, ':serverid' => $_SESSION["serverid"], ':gameid' => $gid, ':money' => $money, ':bmoney' => $bmoney, ':paytype' => $pt, ':bi' => $bb, ':userip' => $user_IP, ':orderid' => $orderid, ':pay_cp' => $pay_cp_check, 'is_bank' => $is_bank);
	$query = $pdo->prepare("INSERT INTO servers_log (foran, forname, serverid, gameid, money, bmoney, paytype, bi, userip, orderid, pay_cp, is_bank) VALUES(:foran, :forname, :serverid,:gameid,:money,:bmoney,:paytype,:bi,:userip,:orderid, :pay_cp, :is_bank)");
	$query->execute($input);

	$result = $pdo->lastInsertId();

	$_SESSION["lastan"] = $result;
	if (!empty($shareid = _s("shareid"))) {
		$shq = $pdo->prepare("UPDATE servers_log SET shareid=? where auton=?");
		$shq->execute(array($shareid, $result));
	}
	// 根據請求類型處理重定向
	if ($is_ajax) {
		// AJAX請求：返回重定向URL
		$redirect_url = '';
		switch ($pay_cp_check) {
			case "pchome":
				$redirect_url = 'pchome_next.php';
				break;
			case "ebpay":
				$redirect_url = 'ebpay_next.php';
				break;
			case "gomypay":
				$redirect_url = 'gomypay_next.php';
				break;
			case "smilepay":
				$redirect_url = 'smilepay_next.php';
				break;
			case "funpoint":
				$redirect_url = 'funpoint_next.php';
				break;
			case "szfu":
				$redirect_url = 'szfu_next.php';
				break;
			default:
				$redirect_url = 'next.php';
				break;
		}
		echo json_encode(['status' => 'success', 'redirect' => $redirect_url]);
		die();
	} else {
		// 傳統表單提交：直接重定向
		switch ($pay_cp_check) {
			case "pchome":
				header('Location: pchome_next.php');
				break;
			case "ebpay":
				header('Location: ebpay_next.php');
				break;
			case "gomypay":
				header('Location: gomypay_next.php');
				break;
			case "smilepay":
				header('Location: smilepay_next.php');
				break;
			case "funpoint":
				header('Location: funpoint_next.php');
				break;
			case "szfu":
				header('Location: szfu_next.php');
				break;
			default:
				header('Location: next.php');
				break;
		}
		die();
	}
}

$id = $_REQUEST["id"];

if ($id == "") die("server id error.");

$pdo = openpdo();
$query = $pdo->prepare("SELECT * FROM servers where id=?");
$query->execute(array($id));
if (!$datalist = $query->fetch()) die("server id error.");

if ($datalist["stats"] == 0) die("server stop.");
$_SESSION["foran"] = $datalist["auton"];
$_SESSION["serverid"] = $datalist["id"];

if (!$datalist["db_ip"] || !$datalist["db_port"] || !$datalist["db_name"] || !$datalist["db_user"] || !$datalist["db_pass"]) $dbstat = "<small style='color:#999'>資料庫尚未就緒</small>";
else $dbstat = "";

$base_money = $datalist["base_money"];
if (!$base_money) $base_money = 100;

$user_ip = get_real_ip();

if (!empty($ss = _r("s"))) {
	$shq = $pdo->prepare("SELECT * FROM shareuser where uid=? limit 1");
	$shq->execute(array($ss));
	if ($shqi = $shq->fetch()) $_SESSION["shareid"] = $shqi["uid"];
}

$custombg = $datalist["custombg"];

if (empty($custombg)) $custombg = "assets/images/particles_bg.jpg";
else $custombg = "assets/images/custombg/" . $custombg;

// 指紋檢查控制變數
$enable_fingerprint_check = false;

?>

<!DOCTYPE html>

<html lang="en">

<head>
	<meta charset="utf-8" />
	<title>Game Sponsor</title>
	<meta name="description" content="Game Sponsor" />
	<meta name="Author" content="<?= $weburl ?>" />

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

	<!-- SweetAlert2 CSS -->
	<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

	<!-- SweetAlert2 JS -->
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

	<!-- 自定義 SweetAlert2 樣式 -->
	<style>
		.swal-wide {
			border-radius: 15px !important;
			box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3) !important;
		}

		.swal-title-large {
			font-size: 1.5rem !important;
			font-weight: bold !important;
			color: #333 !important;
		}

		.swal-content-large {
			font-size: 1.1rem !important;
			line-height: 1.6 !important;
			color: #666 !important;
		}

		.swal2-popup {
			font-size: 1rem !important;
		}

		.swal2-icon {
			margin: 1rem auto !important;
		}

		.swal2-timer-progress-bar {
			background: #ff6b6b !important;
		}
	</style>
</head>



<body>
	<!-- wrapper -->
	<div id="wrapper">
		<!-- SLIDER -->
		<section id="slider" class="fullheight" style="background:url('<?= $custombg ?>')">
			<span class="overlay dark-2"><!-- dark overlay [0 to 9 opacity] --></span>
			<canvas id="canvas-particle" data-rgb="156, 217, 249">CANVAS PARTICLES</canvas>
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
							<form method="post" action="index.php">
								<div class="col-md-12 col-xs-12 main-title padding-bottom-20 bold">遊戲伺服器：【<?= $datalist["names"] ?>】<?= $dbstat ?></div>
								<?php
								if (true) {
									$query2 = $pdo->prepare("SELECT * FROM servers where gp=? order by des desc");
									$query2->execute(array($gp));
									if ($gsarr = $query2->fetchALL()) {
										echo '<div class="col-md-12 col-xs-12 main-title padding-bottom-20">';
										echo '<select id="server" class="form-control" onchange="this.options[this.selectedIndex].value && (window.location = this.options[this.selectedIndex].value);">';
										foreach ($gsarr as $gs) {
											if ($gs["id"] == $id) $gssel = " selected";
											else $gssel = "";
											echo '<option value="/' . $gs["id"] . '"' . $gssel . '>' . $gs["names"] . '</option>';
										}
										echo '</select>';
										echo '</div>';
									}
								}
								?>

								<div class="col-md-12 col-xs-12 padding-bottom-20">
									<input type="text" class="form-control" name="gid" id="gid" placeholder="請輸入遊戲帳號" autocomplete="off" required>
								</div>

								<!--<div class="col-md-12 col-xs-12 padding-bottom-20">
								<input type="text" class="form-control" name="cid" id="cid" placeholder="請輸入角色名稱" required>
								</div>-->

								<div class="col-md-12 col-xs-12 padding-bottom-20">
									<select name="pt" id="pt" class="form-control" required>
										<option value="">請選取繳款方式</option>
										<?php
										if ($datalist["pay_cp2"] == "gomypay" || $datalist["pay_cp2"] == "szfu") {
											echo '<option value="30">超商代碼(上限20000)全家</option>
												<option value="31">超商代碼(上限20000)OK</option>
												<option value="32">超商代碼(上限20000)萊爾富</option>
												<option value="33">超商代碼(上限20000)7-11</option>';
										} elseif ($datalist["pay_cp2"] == "smilepay") {
											echo '<option value="30">超商代碼(上限20000)全家</option>
												<option value="31">超商代碼(上限20000)7-11</option>
												<option value="32">超商代碼(上限20000)萊爾富</option>';
										} else if ($datalist["pay_cp2"] != "no"){
											// 限制不顯示的超商
											$noShop = array('破界仙境', '浪流連天堂', '最終之戰', '時空裂痕', '初樂天堂','希望');
											if (!in_array($datalist["names"], $noShop)) {
												echo '<option value="3">超商代碼(7-11/OK/全家/萊爾富)</option>';
											}
										}
										?>
										<?php
										if ($datalist["pay_bank"] != "no") {
											echo '<option value="2">銀行轉帳</option>';
										}
										?>
										<?php
										if ($datalist["pay_cp"] != "no") {
											echo '<option value="5">信用卡</option>';
										}
										?>
										<!--option value="6">網路ATM(僅支援IE瀏覽器)</option-->
										<!--<option value="5">信用卡</option>-->
									</select>
								</div>

								<div id='form_hidden' style="opacity: 1; transition: opacity 0.5s ease-in-out; display: none;">
									<div class="col-md-12 col-xs-12 padding-bottom-20">
										<? if ($datalist['pay_type_show'] == 0) { ?>
											<input type="number" class="form-control" name="money" id="money" min="<?= $base_money ?>" placeholder="請輸入繳款金額" required>
										<? } else {
											$server_id = $datalist['auton'];    // server ID
											$sscp_query = $pdo->prepare("SELECT * FROM servers_show_customize_price WHERE foran=? ORDER BY money");
											$sscp_query->execute(array($server_id));
											if ($sscp = $sscp_query->fetchALL()) {
												$option_html = '<option value="0">請選擇金額</option>';
												foreach ($sscp as $skey => $sval) {
													$option_html .= "<option value='" . $sval['money'] . "'>" . $sval['money'] . "</option>";
												}
											}
										?>
											<select name="money" id="sel_money" class="form-control" required>
												<?= $option_html; ?>
											</select>
										<? } ?>
									</div>

									<? if ($datalist['use_virtual_ratio'] == 0) { ?>
										<div class="col-md-12 col-xs-12 padding-bottom-20">
											<div class="col-md-10 col-xs-9 pl-0"><input type="text" class="form-control" name="money2" id="money2" placeholder="幣值換算" readonly></div>
											<div class="col-md-2 col-xs-3 pl-0"><button id="read_bi_btn" type="button" class="btn btn-primary btn-md" style="color:#fff !important;">點我換算</button></div>
										</div>
									<? } ?>

									<div class="col-md-12 col-xs-12 padding-bottom-20">
										<div class="col-md-6 col-xs-6 pl-0">
											<input type="text" class="form-control" name="psn" id="psn" placeholder="驗證碼" autocomplete="off" required>
										</div>
										<div class="col-md-6 col-xs-6">
											<a href="#r" onclick="reload_psn($(this))"><img id="index_psn_img" src="psn.php"></a>
										</div>
									</div>
									<div class="col-md-12 col-xs-12 padding-bottom-20">
										<input type="hidden" name="st" value="send">
										<button type="button" id="submit_btn" class="btn btn-default">確定儲值</button>
									</div>
								</div>
							</form>
							<div class="col-md-12 col-xs-12 pb-20" style="color:white">　
								<br><br><br><br><br><br>
								<br>所有繳費資料包含IP電磁紀錄皆已留存，如有惡意人士利用此繳費平台進行第三方詐騙，請受害者立即與我們客服聯繫提供資料報警處理，請注意您的贊助皆為個人自願性，繳費後將無法做退費的動作，我們會將該筆費用維持伺服器運行與開發研究，並捐出部分款項給慈善機構，如贊助金流系統故障請聯絡客服人員！
							</div>
							<div class="col-md-12 col-xs-12 padding-bottom-20" style="color:white">您的 IP 位置：<?= $user_ip ?></div>
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
	<script type="text/javascript">
		var plugin_path = 'assets/plugins/';
	</script>
	<script type="text/javascript" src="/assets/plugins/jquery/jquery-2.2.3.min.js"></script>
	<script type="text/javascript" src="/assets/js/scripts.js"></script>

	<!-- PARTICLE EFFECT -->
	<script type="text/javascript" src="/assets/plugins/canvas.particles.js"></script>

	<script>
		<?php if ($enable_fingerprint_check) { ?>
			// Initialize the agent on page load.
			const fpPromise = import('https://fpjscdn.net/v3/pnBTxtSQ7Wxmz51hybho')
				.then(FingerprintJS => FingerprintJS.load())

			// Get the visitorId when you need it.
			let visitorId = '';
			fpPromise
				.then(fp => fp.get())
				.then(result => {
					visitorId = result.visitorId
					console.log(visitorId)
				})
		<?php } else { ?>
			// 指紋檢查已停用
			let visitorId = 'disabled';
		<?php } ?>

		$(function async () {
			// alert('<?= $forname ?>')
			$('#money').on('input', function() {
				const payType = $('#pt').val();
				const store = [3, 30, 31, 32, 33];
				let max_money = 0;

				if (payType == 2) {
					max_money = <?= $datalist['max_bank'] != null && $datalist['max_bank'] != 0 ? $datalist['max_bank'] : 0 ?>;
				} else if (store.includes(parseInt(payType))) {
					max_money = <?= $datalist['max_store'] != 'null' && $datalist['max_store'] != 0 ? $datalist['max_store'] : 0 ?>;
				} else if (payType == 5) {
					max_money = <?= $datalist['max_credit'] != null && $datalist['max_credit'] != 0 ? $datalist['max_credit'] : 0 ?>;
				}

				if (max_money == 0) {
					return;
				}

				let inp_money = $(this).val();
				if (inp_money > max_money) {
					$('#money').val(max_money);
					alert('您選擇的繳款金額上限為' + max_money + '元');
					return false;
				}
			});

			// Initially hide the form elements below
			$('#form_hidden').hide();

			// Function to check if conditions are met
			function checkConditions() {
				const selectVal = $("#pt").val();
				const inputVal = $("#gid").val().trim();

				// Check if select has valid value (not 0) and input is not empty
				if (selectVal != "0" && selectVal != "" && inputVal !== "") {
					// Show elements with fade animation
					$('#form_hidden').slideDown(800);

					return;
				}

				$('#form_hidden').slideUp(400);
			}

			// Monitor both select and input for changes
			$("#pt, #gid").on('change keyup', function() {
				checkConditions();
			});

			// 支付方式改變，如果皆不為預設，則清空繳款金額
			$("#pt").on('change', function() {
				const selectVal = $("#pt").val();
				const inputVal = $("#money").val();

				if (selectVal != '' && inputVal != '') {
					$("#money").val('');
					$('#money2').val('');
				}
			});

			// 驗證碼輸入框按Enter鍵送出
			$("#psn").on("keypress", function(e) {
				if (e.which == 13) {  // Enter鍵的鍵碼是13
					e.preventDefault();
					$("#submit_btn").click();
				}
			});

			// 表單提交處理
			$("#submit_btn").on("click", async function() {
				try {
					<?php if ($enable_fingerprint_check) { ?>
						// 等待指紋檢查完成
						const result = await checkFingerprint();

						if (result.code == '99') {
							alert(result.msg);
							return;
						}

						// 檢查指紋檢查是否成功
						if (!result.continue) {
							alert(result.msg);
							return;
						}
					<?php } ?>

					// 收集表單資料
					var formData = {
						st: "send",
						gid: $("#gid").val(),
						money: <?php if ($datalist['pay_type_show'] == 0) { ?>$("#money").val() <?php } else { ?>$("#sel_money").val() <?php } ?>,
						pt: $("#pt").val(),
						psn: $("#psn").val(),
						is_bank: ($("#pt").val() == 2) ? 1 : 0,
					};

					// 發送AJAX請求
					$.ajax({
						url: "index.php",
						type: "POST",
						data: formData,
						dataType: "json",
						beforeSend: function(xhr) {
							xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
							$("#submit_btn").prop("disabled", true).text("處理中...");
						},
						success: function(response) {
							if (response.status === 'success') {
								// 重定向到支付頁面
								window.location.href = response.redirect;
							} else if (response.status === 'error') {
								alert(response.message);
								$("#submit_btn").prop("disabled", false).text("確定儲值");
							}
						},
						error: function(xhr, status, error) {
							alert("系統錯誤，請稍後再試。");
							$("#submit_btn").prop("disabled", false).text("確定儲值");
						}
					});
				} catch (error) {
					<?php if ($enable_fingerprint_check) { ?>
						console.error('指紋檢查錯誤:', error);
						Swal.fire({
							icon: 'error',
							title: '系統錯誤',
							text: '指紋驗證過程中發生錯誤，請稍後再試',
							allowOutsideClick: false,
							timer: 3000,
							timerProgressBar: true,
							showConfirmButton: false,
							width: '400px',
							padding: '2rem',
							background: '#fff',
							backdrop: 'rgba(0,0,0,0.4)',
							customClass: {
								popup: 'swal-wide',
								title: 'swal-title-large',
								content: 'swal-content-large'
							}
						});
					<?php } ?>
				}
			});

			<?php if ($enable_fingerprint_check === true) { ?>
				// 確認指紋
				async function checkFingerprint() {
					try {
						// 等待 visitorId 準備就緒
						if (!visitorId) {
							const fp = await fpPromise;
							const result = await fp.get();
							visitorId = result.visitorId;
						}

						console.log('test');
						console.log($('#server').val());

						// 建立要傳送的資料物件
						const requestData = {
							server: $('#server').length ? $('#server').val().replace('/', '') : '<?= $id ?>',
							account: $('#gid').val(),
							fingerprint: visitorId
						};

						// 設定 fetch 請求選項
						const fetchOptions = {
							method: 'POST',
							headers: {
								'Content-Type': 'application/json',
								'X-Requested-With': 'XMLHttpRequest'
							},
							body: JSON.stringify(requestData) // 將資料轉為 JSON 字串
						};

						// 發送 fetch 請求並等待回應
						const response = await fetch('https://backend.pcgame.tw/api/fingerprint/check', fetchOptions);

						if (!response.ok) {
							throw new Error('網路回應不正確');
						}

						const data = await response.json();

						if (data.success === true) {
							// 成功取得資料
							// console.log('指紋驗證回應資料:', data);
							return data;
						} else {
							// 處理業務邏輯錯誤
							throw new Error(data.message || '資料處理失敗');
						}
					} catch (error) {
						// 處理所有錯誤情況
						console.error('指紋檢查錯誤:', error.message);
						return {
							success: false,
							continue: false,
							message: error.message || '指紋驗證失敗'
						};
					}
				}
			<?php } ?>

			$("#read_bi_btn").on("click", function() {
				var $oi = $("#money2");

				let inp_money
				<?php if ($datalist['pay_type_show'] == 0) { ?>
					inp_money = $("#money").val()
				<?php } else { ?>
					if ($("#sel_money :selected").val() != 0) {
						inp_money = $("#sel_money :selected").val();
					} else {
						alert('請選擇金額')
					}
				<?php } ?>

				if (!inp_money) {
					$oi.val("請先輸入繳款金額。");
					return false;
				}

				if (!$.isNumeric(inp_money)) {
					$oi.val("繳款金額只能是數字。");
					return false;
				}

				if (inp_money < <?= $base_money ?>) {
					$oi.val("繳款金額必須大於 <?= $base_money ?>。");
					return false;
				}

				$.ajax({
					url: "index.php",
					data: {
						st: "readbi",
						v: inp_money
					},
					dataType: "html"
				}).done(function(msg) {
					$oi.val(msg);
				});
			});
		});
	</script>
</body>

</html>

<script type="text/javascript">
	function reload_psn($th) {
		var $d = new Date();
		var $img = $th.find("img");
		$img.attr("src", $img.attr("src") + "?" + $d.getTime());
	}
</script>