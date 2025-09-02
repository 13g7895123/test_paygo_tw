<?php
include("myadm/include.php");
if($_REQUEST["st"] == "send") {
	if($_SESSION["foran"] == "") alert("伺服器資料錯誤-8000201。", 0);
	if($_SESSION["serverid"] == "") alert("伺服器資料錯誤-8000202。", 0);
	
	if(empty($Buyer_Name = _r("Buyer_Name"))) alert("請輸入姓名。", 0);
	if(empty($Buyer_Telm = _r("Buyer_Telm"))) alert("請輸入手機號碼。", 0);
	if(empty($Buyer_Mail = _r("Buyer_Mail"))) alert("請輸入Email。", 0);

	if(!filter_var($Buyer_Mail, FILTER_VALIDATE_EMAIL)) {
		alert("Email 格式錯誤。", 0);
	}
	$_SESSION["Buyer_Name"] = $Buyer_Name;
	$_SESSION["Buyer_Telm"] = $Buyer_Telm;
	$_SESSION["Buyer_Mail"] = $Buyer_Mail;

    header('Location: gomypay_next.php');
		
	die();
}

    if(empty($serverid = _s("serverid"))) alert("伺服器資料錯誤-8000202。", 0);

	$pdo = openpdo(); 	
    $query    = $pdo->prepare("SELECT * FROM servers where id=?");
    $query->execute(array($serverid));
    if(!$datalist = $query->fetch()) die("server id error.");
    
    $custombg = $datalist["custombg"];
	if(empty($custombg)) $custombg = "assets/images/particles_bg.jpg";
	else $custombg = "assets/images/custombg/".$custombg;
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
									請填寫付款資訊
								</span>
							</h1>
							<hr>
							<div class="main-form">
							<form method="post" action="">																
								<div class="col-md-12 col-xs-12 padding-bottom-20">								
								<input type="text" class="form-control" name="Buyer_Name" id="Buyer_Name" placeholder="請輸入姓名" required>
								</div>
								<div class="col-md-12 col-xs-12 padding-bottom-20">								
								<input type="tel" name="Buyer_Telm" id="Buyer_Telm" pattern="^[09]{2}[0-9]{8}$" minlength="10" maxlength="10" title="請輸入 09 開頭的十位數手機號碼" placeholder="請輸入手機號碼" class="form-control" required="">
								</div>
								<div class="col-md-12 col-xs-12 padding-bottom-20">								
								<input type="email" class="form-control" name="Buyer_Mail" id="Buyer_Mail" placeholder="請輸入 Email" required>
								</div>
								
								<div class="col-md-12 col-xs-12 padding-bottom-20">
								<input type="hidden" name="st" value="send">
								<input type="submit" class="btn btn-default" value="確定送出">
								</div>
								
							</form>

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
	</body>
</html>

<script type="text/javascript">
$(function() {

 $("#read_bi_btn").on("click", function() {
 var $oi = $("#money2");
 
 if(!$("#money").val()) {
 $oi.val("請先輸入繳款金額。");
 return false;	
 }
 
 if(!$.isNumeric($("#money").val())) {
 $oi.val("繳款金額只能是數字。");
 return false;	 	
 }
 
 if($("#money").val() < <?=$base_money?>) {
 $oi.val("繳款金額必須大於 <?=$base_money?>。");
 return false;	 	
 }
 
$.ajax({
  url: "index.php",
  data: { st: "readbi", v:$("#money").val() },
  dataType: "html"
}).done(function(msg) {
  $oi.val(msg);
});

});

});
function reload_psn($th) {
	var $d = new Date();
	var $img = $th.find("img");	
	$img.attr("src", $img.attr("src")+"?"+$d.getTime());
}
</script>