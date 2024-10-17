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

$loc = "maps.php";
if(isset($_REQUEST['return_url'])) {
	$loc = $_REQUEST['return_url'];
}

// user is already logged in
if(isset($_SESSION['user_id']) && validUserID($PDO, $_SESSION['user_id'])) {
	header("location: $loc"); exit;
}


if($TESTING) {

	$stmt = $PDO->prepare("SELECT id FROM \"User\" WHERE username = 'andreas'");
	$stmt->execute();
	$row = $stmt->fetch();
	$_SESSION['user_id'] = $row['id']; // log user in

}
else
if(isset($_POST['username'])
&& isset($_POST['email'])
&& isset($_POST['password'])) { // arriving from signup

	$username = sanitize($_POST['username']);
	$email = sanitize($_POST['email']);
	$password = sanitize($_POST['password']);

	if(isUsernameRegistered($PDO, $username)) { http_response_code(500); exit; }

	$user_id = registerUser($PDO, $username, $password, $email); // register user
	$_SESSION['user_id'] = $user_id; // log user in

}
else
if(isset($_POST['username'])
&& isset($_POST['password'])) { // arriving from signin

	$username = $_POST['username'];
	$password = $_POST['password'];

	$check = validSignIn($PDO, $username, $password);
	if($check == 1) {
		$stmt = $PDO->prepare("SELECT id FROM \"User\" WHERE username = ? AND password = ?");
		$stmt->execute([$username, $password]);
		$row = $stmt->fetch();
		$user_id = $row['id'];

		$stmt = $PDO->prepare("UPDATE \"User\" SET last_signin_date = NOW() WHERE id = ?");
		$stmt->execute([$user_id]);

		$_SESSION['user_id'] = $user_id; // log user in
	}
	elseif($check < 1) {
		header("Access-Control-Allow-Origin: *");
		header("location: index.php?return_url={$loc}&signin_failed=true&username={$username}");
		exit;
	}
	else{ http_response_code(500); exit; }

}
else
if(isset($_POST['username'])
&& isset($_POST['email'])) { // arriving from reset password

	$username = $_POST['username'];
	$email = $_POST['email'];

	if(!validUserEmail($PDO, $username, $email)) {
		http_response_code(401); exit;
	}

	$password = random_string(12);

	$stmt = $PDO->prepare("UPDATE \"User\" SET password = ? WHERE username = ? AND email = ?");
	$stmt->execute([hash("sha256", $password), $username, $email]);

	$subject = "GeoTales: password reset";
	$body = "A password-reset has been triggered on the user {$username} \n The new password is: {$password}";

	sendSESEmail($username, $email, $subject, $body);

	header("Access-Control-Allow-Origin: *");
	header("location: index.php?return_url={$loc}&password_reset=true");
	exit;

}

header("Access-Control-Allow-Origin: *");
header("location: $loc");

exit;
