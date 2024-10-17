<?php
/*******************************************************************************
* Copyright (C) Nordfjord EDB AS - All Rights Reserved                         *
*                                                                              *
* Unauthorized copying of this file, via any medium is strictly prohibited     *
* Proprietary and confidential                                                 *
* Written by Andreas Atakan <aca@geotales.io>, January 2022                    *
*******************************************************************************/

ini_set('display_errors', 'On'); ini_set('html_errors', 0); error_reporting(-1);

//session_set_cookie_params(['SameSite' => 'None', 'Secure' => true]);
session_start();

include "init.php";
include_once("helper.php");

$payload = file_get_contents("php://input");
$data = json_decode($payload, true);


if(isset($_SERVER['HTTP_STRIPE_SIGNATURE'])
&& isset($data['type'])) {

	$sig = explode(",", $_SERVER['HTTP_STRIPE_SIGNATURE']);
	$t = null; $v1 = null;
	foreach($sig as $v) {
		$r = explode("=", $v);
		if($r[0] == "t") { $t = $r[1]; }
		if($r[0] == "v1") { $v1 = $r[1]; }
	}

	$sha = hash_hmac("sha256", "{$t}.{$payload}", $CONFIG['stripe_webhooks_secret']);
	if($v1 != $sha) { http_response_code(401); exit; }

	if($data['type'] == "customer.subscription.created"
	|| $data['type'] == "customer.subscription.deleted") {

		$prod = $data['data']['object']['items']['data'][0]['price']['product'];
		$stripe_id = $data['data']['object']['customer'];
		$paid = $data['type'] == "customer.subscription.created" ? "1" : "0";

		if($prod == $CONFIG['stripe_product_id']) {
			$stmt = $PDO->prepare("UPDATE \"User\" SET paid = ? WHERE stripe_id = ?");
			$stmt->execute([$paid, $stripe_id]);
		}
		else{ http_response_code(500); exit; }

		http_response_code(200); exit;

	}
	else
	if($data['type'] == "customer.deleted") {

		$stripe_id = $data['data']['object']['id'];
		$stmt = $PDO->prepare("UPDATE \"User\" SET paid = false, stripe_id = null WHERE stripe_id = ?");
		$stmt->execute([$stripe_id]);

		http_response_code(200); exit;

	}
	else{ exit; }

}
else{ exit; }
