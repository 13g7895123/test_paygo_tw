<?php
include("myadm/include.php");

	if($_SESSION["foran"] == "") alert("伺服器資料錯誤-8000201。", 0);
	if($_SESSION["serverid"] == "") alert("伺服器資料錯誤-8000202。", 0);
	if($_SESSION["lastan"] == "") alert("伺服器資料錯誤-8000203。", 0);
	$serverid = $_SESSION["serverid"];
	//read
	$pdo = openpdo(); 	

	$query = $pdo->prepare("SELECT * FROM servers_log where auton=?");
	$query->execute(array($_SESSION["lastan"]));
	if(!$datalist = $query->fetch()) alert("不明錯誤-8000207。", 0);
	if($datalist["stats"] != 0) alert("金流狀態有誤-8000208。", 0);
	$paytype = $datalist["paytype"];

	$sq = $pdo->prepare("SELECT * FROM servers where auton=?");
	$sq->execute(array($_SESSION["foran"]));
	if(!$sqd = $sq->fetch()) alert("不明錯誤-8000204。", 0);

	$gstats = $sqd["gstats"];
	$gstats2 = $sqd["gstats2"];

	if ($paytype == 5) {
		if ($gstats == 1) {
			$tokenurl = "https://api.pchomepay.com.tw/v1/token";
			$paymenturl = "https://api.pchomepay.com.tw/v1/payment";
		} else {
			$tokenurl = "https://sandbox-api.pchomepay.com.tw/v1/token";    
			$paymenturl = "https://sandbox-api.pchomepay.com.tw/v1/payment";
		}
		$app_id = $sqd["pchome_app_id"];
		$secret_code = $sqd["pchome_secret_code"];	
	} else {
		if ($gstats2 == 1) {
			$tokenurl = "https://api.pchomepay.com.tw/v1/token";
			$paymenturl = "https://api.pchomepay.com.tw/v1/payment";
		} else {				
			$tokenurl = "https://sandbox-api.pchomepay.com.tw/v1/token";    
			$paymenturl = "https://sandbox-api.pchomepay.com.tw/v1/payment";
		}
		$app_id = $sqd["pchome_app_id2"];
		$secret_code = $sqd["pchome_secret_code2"];	
	}

    $forname = $sqd["names"];

	$sq2 = $pdo->prepare("update servers_log set forname=? where auton=?");
    $sq2->execute(array($forname, $_SESSION["lastan"]));
    
    if($app_id == "" || $secret_code == "") alert("金流錯誤-8000206。", 0);
    $query    = $pdo->prepare("SELECT * FROM servers_log where auton=?");
    $query->execute(array($_SESSION["lastan"]));
    if(!$datalist = $query->fetch()) alert("不明錯誤-8000207。", 0);
    if($datalist["stats"] != 0) alert("金流狀態有誤-8000208。", 0);
    	
    $money = $datalist["money"];
	$ItemName = random_products($_SESSION["serverid"]);
    $pt = $datalist["paytype"];
    $stats = $datalist["stats"];
    $tradeno = $datalist["orderid"];
    $nowtime = date("Y/m/d H:i:s");
    $accpwd = base64_encode($app_id.":".$secret_code);

	$headers = array('Content-Type:application/json','Authorization:Basic '.$accpwd);	
	$result = curl($tokenurl, "", 1, 1, $headers);	
	
	$token = json_decode($result);
	if(json_last_error() != JSON_ERROR_NONE) alert("金流狀態有誤-8000209。", 0);
	if(!isset($token->token)) alert("金流狀態有誤-8000211。", 0);

	$pay_headers = array(
		'Content-Type:application/json',
		'pcpay-token:'.$token->token
	);
	$requestPayLoad='{
		"order_id":"'.$tradeno.'",
		"pay_type":["CARD"],
		"amount":'.$money.',		
		"return_url": "'.$weburl.$serverid.'",
		"fail_return_url": "'.$weburl.$serverid.'",		
		"notify_url": "'.$weburl.'pchome_r.php",
		"card_installment":"1",
		"items":[{"name":"'.$ItemName.'","url": "'.$weburl.$serverid.'"}]		
	}';	
	
	//print_r($requestPayLoad);exit;
	$response = curl($paymenturl, $requestPayLoad, 1, 1, $pay_headers);
	$responsej = json_decode($response);

	if(json_last_error() == JSON_ERROR_NONE) {
		$payment_url = $responsej->payment_url;		
		header('Location: '.$payment_url);
	} else {
		alert("金流錯誤-8000210。", 0);
	}
	exit;
?>	