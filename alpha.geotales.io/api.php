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

if(!isset($_REQUEST['op'])) {
	http_response_code(422); exit;
}
$op = $_REQUEST['op'];


if($op == "analytics") {

	$user_id = null;
	if(isset($_SESSION['user_id']) && validUserID($PDO, $_SESSION['user_id'])) { $user_id = $_SESSION['user_id']; } // Is logged in

	$stmt = $PDO->prepare("INSERT INTO \"Analytics\" (user_id, location, ip, agent) VALUES (?, ?, ?, ?)");
	$stmt->execute([$user_id, $_SERVER['HTTP_REFERER'], $_SERVER['REMOTE_ADDR'], $_POST['agent'] ?? $_SERVER['HTTP_USER_AGENT']]);

	echo json_encode(array("status" => "success"));
	exit;

}
else
if($op == "map_read") {

	if(!isset($_GET['id'])
	|| !isset($_GET['password'])) {
		http_response_code(422); exit;
	}
	$id = $_GET['id'];
	$password = $_GET['password'];

	$user_can_write = false;
	if(isset($_SESSION['user_id']) && validUserID($PDO, $_SESSION['user_id'])) {
		$user_can_write = userMapCanWrite($PDO, $_SESSION['user_id'], $id);
	}
	if(mapHasPw($PDO, $id)
	&& !$user_can_write
	&& !mapCheckPw($PDO, $id, $password)) { http_response_code(401); exit; }

	$stmt = $PDO->prepare("SELECT data FROM \"Map\" WHERE id = ?");
	$stmt->execute([$id]);
	$row = $stmt->fetch();

	echo json_encode(array(
		"data" => $row['data']
	));
	exit;

}
else
if($op == "map_view") {

	if(!isset($_POST['id'])) {
		http_response_code(422); exit;
	}
	$id = $_POST['id'];

	$user_id = null;
	if(isset($_SESSION['user_id']) && validUserID($PDO, $_SESSION['user_id'])) {
		$user_id = $_SESSION['user_id'];
	}

	$stmt = $PDO->prepare("INSERT INTO \"View\" (user_id, map_id) VALUES (?, ?)");
	$stmt->execute([$user_id, $id]);

	echo json_encode(array("status" => "success"));
	exit;

}
else
if($op == "user_is_username_unique") {

	if(!isset($_GET['username'])) {
		http_response_code(422); exit;
	}
	$username = $_GET['username'];

	$stmt = $PDO->prepare("SELECT COUNT(id) AS c FROM \"User\" WHERE username = ?");
	$stmt->execute([$username]);
	$row = $stmt->fetch();

	echo json_encode(array(
		"isUnique" => $row['c'] == 0
	));
	exit;

}
else
if($op == "user_username_email_correct") {

	if(!isset($_GET['username'])
	|| !isset($_GET['email'])) {
		http_response_code(422); exit;
	}
	$username = $_GET['username'];
	$email = $_GET['email'];

	echo json_encode(array(
		"isValid" => validUserEmail($PDO, $username, $email)
	));
	exit;

}


// Not logged in
if(!isset($_SESSION['user_id']) || !validUserID($PDO, $_SESSION['user_id'])) {
	http_response_code(401); exit;
}
$user_id = $_SESSION['user_id'];
$paid = getUserPaid($PDO, $user_id);
$map_count_ok = userMapWithinLimit($PDO, $user_id);



if($op == "map_get") {

	if(!isset($_GET['id'])) {
		http_response_code(422); exit;
	}
	$id = $_GET['id'];

	$stmt = $PDO->prepare("SELECT title, description, thumbnail, published_date IS NOT NULL AS published FROM \"Map\" WHERE id = ?");
	$stmt->execute([$id]);
	$row = $stmt->fetch();

	echo json_encode(array(
		"title" => $row['title'],
		"description" => $row['description'],
		"thumbnail" => $row['thumbnail'],
		"published" => $row['published']
	));
	exit;

}
else
if($op == "map_like") {

	if(!isset($_POST['id'])) {
		http_response_code(422); exit;
	}
	$id = $_POST['id'];

	$stmt = $PDO->prepare("INSERT INTO \"Reaction\" (user_id, map_id, type) VALUES (?, ?, 'like')");
	$stmt->execute([$user_id, $id]);

	echo json_encode(array("status" => "success"));
	exit;

}
else
if($op == "map_unlike") {

	if(!isset($_POST['id'])) {
		http_response_code(422); exit;
	}
	$id = $_POST['id'];

	$stmt = $PDO->prepare("DELETE FROM \"Reaction\" WHERE type = 'like' AND user_id = ? AND map_id = ?");
	$stmt->execute([$user_id, $id]);

	echo json_encode(array("status" => "success"));
	exit;

}
else
if($op == "map_flag") {

	if(!isset($_POST['id'])) {
		http_response_code(422); exit;
	}
	$id = $_POST['id'];

	$stmt = $PDO->prepare("INSERT INTO \"Flag\" (user_id, map_id, type) VALUES (?, ?, 'flag')");
	$stmt->execute([$user_id, $id]);

	echo json_encode(array("status" => "success"));
	exit;

}
else
if($op == "map_unflag") {

	if(!isset($_POST['id'])) {
		http_response_code(422); exit;
	}
	$id = $_POST['id'];

	$stmt = $PDO->prepare("DELETE FROM \"Flag\" WHERE user_id = ? AND map_id = ?");
	$stmt->execute([$user_id, $id]);

	echo json_encode(array("status" => "success"));
	exit;

}
else
if($op == "map_comment") {

	if(!isset($_POST['id'])
	|| !isset($_POST['content'])
	|| sane_is_null($_POST['content'])) {
		http_response_code(422); exit;
	}
	$id = $_POST['id'];
	$content = sanitize($_POST['content']);
	$ref = $_POST['ref'] ?? null;

	$stmt = $PDO->prepare("INSERT INTO \"Comment\" (user_id, map_id, ref, content) VALUES (?, ?, ?, ?)");
	$stmt->execute([$user_id, $id, $ref, $content]);

	echo json_encode(array("status" => "success"));
	exit;

}
else
if($op == "map_write") {

	if(!isset($_POST['id'])
	|| !isset($_POST['data'])) {
		http_response_code(422); exit;
	}
	$id = $_POST['id'];
	$data = $_POST['data'];
	if(!userMapCanWrite($PDO, $user_id, $id)) { http_response_code(401); exit; }

	$stmt = $PDO->prepare("UPDATE \"Map\" SET data = ? WHERE id = ?");
	$stmt->execute([$data, $id]);

	if(isset($_POST['thumbnail'])
	&& !hasMapThumbnail($PDO, $id)) {
		$stmt = $PDO->prepare("UPDATE \"Map\" SET thumbnail = ? WHERE id = ?");
		$stmt->execute([$_POST['thumbnail'], $id]);
	}

	echo json_encode(array("status" => "success"));
	exit;

}
else
if($op == "map_republish") {

	if(!isset($_POST['id'])) {
		http_response_code(422); exit;
	}
	$id = $_POST['id'];
	if(!userMapCanWrite($PDO, $user_id, $id)) { http_response_code(401); exit; }

	$published = mapRepublish($PDO, $id);

	echo json_encode(array(
		"published" => $published
	));
	exit;

}
else
if($op == "map_password_remove") {

	if(!isset($_POST['id'])) {
		http_response_code(422); exit;
	}
	$id = $_POST['id'];
	if(!userMapCanWrite($PDO, $user_id, $id)) { http_response_code(401); exit; }

	$stmt = $PDO->prepare("UPDATE \"Map\" SET password = NULL WHERE id = ?");
	$stmt->execute([$id]);

	echo json_encode(array("status" => "success"));
	exit;

}
else
if($op == "upload_create") {

	if(!isset($_POST['type'])) {
		http_response_code(422); exit;
	}
	$type = sanitize($_POST['type']);
	$res = uploadCreate($PDO, $user_id, $type, $_FILES["image"]["tmp_name"], $_FILES["image"]["name"]);

	if(!$res) { http_response_code(500); exit; }

	echo $res;
	exit;

}
else
if($op == "upload_get") {

	$stmt = $PDO->prepare("SELECT U.ref, UU.type FROM \"User_Upload\" AS UU INNER JOIN \"Upload\" AS U ON UU.upload_id = U.id WHERE UU.user_id = ?");
	$stmt->execute([$user_id]);
	$rows = $stmt->fetchAll();

	echo json_encode($rows);
	exit;

}
else
if($op == "upload_delete") {

	if(!isset($_POST['id'])) {
		http_response_code(422); exit;
	}
	$id = $_POST['id'];

	$stmt = $PDO->prepare("DELETE FROM \"User_Upload\" WHERE user_id = ? AND upload_id = ?");
	$stmt->execute([$user_id, $id]);

	echo json_encode(array("status" => "success"));
	exit;

}
else
if($op == "payment_create_checkout_session") {

	$checkout = paymentCreateCheckout($PDO, $user_id);
	if(!$checkout) { http_response_code(500); exit; }

	echo json_encode(array(
		"url" => $checkout
	));
	exit;

}
else
if($op == "payment_create_portal_session") {

	$portal = paymentCreatePortal($PDO, $user_id);
	if(!$portal) { http_response_code(500); exit; }

	echo json_encode(array(
		"url" => $portal
	));
	exit;

}
else{
	http_response_code(501); exit;
}
