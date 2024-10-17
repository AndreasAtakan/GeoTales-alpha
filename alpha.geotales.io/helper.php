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

include "init.php";



//
function sanitize($str) {
	return htmlspecialchars($str);
}

//
function sane_is_null($v) {
	return is_null($v) || $v == "";
}

//
function random_string($length = 10) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}



//
function validUserID($PDO, $id) {
	$stmt = $PDO->prepare("SELECT COUNT(id) AS c FROM \"User\" WHERE id = ?");
	$stmt->execute([$id]);
	$row = $stmt->fetch();
	return $row['c'] == 1;
}

//
function validSignIn($PDO, $username, $password) {
	$stmt = $PDO->prepare("SELECT COUNT(id) AS c FROM \"User\" WHERE username = ? AND password = ?");
	$stmt->execute([$username, $password]);
	$row = $stmt->fetch();
	return $row['c'] <= 1 ? $row['c'] : false;
}

//
function validUserEmail($PDO, $username, $email) {
	$stmt = $PDO->prepare("SELECT COUNT(id) = 1 AS c FROM \"User\" WHERE username = ? AND email = ?");
	$stmt->execute([$username, $email]);
	$row = $stmt->fetch();
	return $row['c'] ?? false;
}

//
function registerUser($PDO, $username, $password, $email) {
	$stmt = $PDO->prepare("INSERT INTO \"User\" (username, password, email) VALUES (?, ?, ?) RETURNING id");
	$stmt->execute([$username, $password, $email]);
	$row = $stmt->fetch();
	return $row['id'];
}

//
function updateUser($PDO, $user_id, $username, $email, $photo, $password) {
	if(sane_is_null($username)
	&& sane_is_null($email)
	&& sane_is_null($photo)
	&& sane_is_null($password)) { return false; }

	if(!sane_is_null($username)) {
		if(isUsernameRegistered($PDO, $username)
		&& $username != getUsername($PDO, $user_id)) { return false; }

		$stmt = $PDO->prepare("UPDATE \"User\" SET username = ? WHERE id = ?");
		$stmt->execute([$username, $user_id]);
	}
	if(!sane_is_null($email)) {
		$stmt = $PDO->prepare("UPDATE \"User\" SET email = ? WHERE id = ?");
		$stmt->execute([$email, $user_id]);
	}
	if(!sane_is_null($photo)) {
		$stmt = $PDO->prepare("UPDATE \"User\" SET photo = ? WHERE id = ?");
		$stmt->execute([$photo, $user_id]);
	}
	if(!sane_is_null($password)) {
		$pw = $password; mb_substr($pw, 0, 64);
		$stmt = $PDO->prepare("UPDATE \"User\" SET password = ? WHERE id = ?");
		$stmt->execute([$pw, $user_id]);
	}

	return true;
}



//
function getUsername($PDO, $id) {
	$stmt = $PDO->prepare("SELECT username FROM \"User\" WHERE id = ?");
	$stmt->execute([$id]);
	$row = $stmt->fetch();
	return $row['username'];
}

//
function getUserEmail($PDO, $id) {
	$stmt = $PDO->prepare("SELECT email FROM \"User\" WHERE id = ?");
	$stmt->execute([$id]);
	$row = $stmt->fetch();
	return $row['email'];
}

//
function getUserPhoto($PDO, $id) {
	$stmt = $PDO->prepare("SELECT photo FROM \"User\" WHERE id = ?");
	$stmt->execute([$id]);
	$row = $stmt->fetch();
	$photo = sane_is_null($row['photo']) ? "assets/user-circle-solid.svg" : $row['photo'];
	return $photo;
}

//
function getUserPaid($PDO, $id) {
	$stmt = $PDO->prepare("SELECT paid FROM \"User\" WHERE id = ?");
	$stmt->execute([$id]);
	$row = $stmt->fetch();
	return $row['paid'] ?? false;
}

//
function getUserStripeID($PDO, $id) {
	$stmt = $PDO->prepare("SELECT stripe_id FROM \"User\" WHERE id = ?");
	$stmt->execute([$id]);
	$row = $stmt->fetch();
	return $row['stripe_id'] ?? false;
}

//
function isUsernameRegistered($PDO, $username) {
	$stmt = $PDO->prepare("SELECT COUNT(id) AS c FROM \"User\" WHERE username = ?");
	$stmt->execute([$username]);
	$row = $stmt->fetch();
	return $row['c'] >= 1;
}



//
function mapCreate($PDO, $user_id, $title, $description, $thumbnail, $password) {
	if(!getUserPaid($PDO, $user_id)
	&& !userMapWithinLimit($PDO, $user_id)) { return false; }

	$stmt = $PDO->prepare("INSERT INTO \"Map\" (title, description) VALUES (?, ?) RETURNING id");
	$stmt->execute([$title, $description]);
	$id = $stmt->fetchColumn();

	$stmt = $PDO->prepare("INSERT INTO \"User_Map\" (user_id, map_id, status) VALUES (?, ?, ?)");
	$stmt->execute([$user_id, $id, "owner"]);

	if(!sane_is_null($thumbnail)) {
		$stmt = $PDO->prepare("UPDATE \"Map\" SET thumbnail = ? WHERE id = ?");
		$stmt->execute([$thumbnail, $id]);
	}

	if(!sane_is_null($password)) {
		$pw = $password;
		mb_substr($pw, 0, 64);
		$stmt = $PDO->prepare("UPDATE \"Map\" SET password = ? WHERE id = ?");
		$stmt->execute([$pw, $id]);
	}

	return $id;
}

//
function mapUpdate($PDO, $map_id, $title, $description, $thumbnail, $password) {
	if(sane_is_null($title)
	&& sane_is_null($description)
	&& sane_is_null($thumbnail)
	&& sane_is_null($password)) { return false; }

	if(!sane_is_null($title)) {
		$stmt = $PDO->prepare("UPDATE \"Map\" SET title = ? WHERE id = ?");
		$stmt->execute([$title, $map_id]);
	}
	if(!sane_is_null($description)) {
		$stmt = $PDO->prepare("UPDATE \"Map\" SET description = ? WHERE id = ?");
		$stmt->execute([$description, $map_id]);
	}
	if(!sane_is_null($thumbnail)) {
		$stmt = $PDO->prepare("UPDATE \"Map\" SET thumbnail = ? WHERE id = ?");
		$stmt->execute([$thumbnail, $map_id]);
	}
	if(!sane_is_null($password)) {
		$pw = $password;
		mb_substr($pw, 0, 64);
		$stmt = $PDO->prepare("UPDATE \"Map\" SET password = ? WHERE id = ?");
		$stmt->execute([$pw, $map_id]);
	}

	return true;
}

//
function mapDelete($PDO, $map_id) {
	$stmt = $PDO->prepare("DELETE FROM \"Map\" WHERE id = ?");
	$stmt->execute([$map_id]);
	return true;
}

//
function mapClone($PDO, $user_id, $map_id, $password) {
	if(!getUserPaid($PDO, $user_id)
	&& !userMapWithinLimit($PDO, $user_id)) { return false; }

	if(mapHasPw($PDO, $map_id)
	&& !userMapCanWrite($PDO, $user_id, $map_id)
	&& !mapCheckPw($PDO, $map_id, $password)) { return false; }

	$stmt = $PDO->prepare("INSERT INTO \"Map\" (title, description, thumbnail, data) SELECT CONCAT('Copy of ', title) AS title, description, thumbnail, data FROM \"Map\" WHERE id = ? RETURNING id");
	$stmt->execute([$map_id]);
	$id = $stmt->fetchColumn();

	$stmt = $PDO->prepare("INSERT INTO \"User_Map\" (user_id, map_id, status) VALUES (?, ?, ?)");
	$stmt->execute([$user_id, $id, "owner"]);

	return $id;
}

//
function mapRepublish($PDO, $map_id) {
	$stmt = $PDO->prepare("SELECT published_date IS NOT NULL AS published FROM \"Map\" WHERE id = ?");
	$stmt->execute([$map_id]);
	$row = $stmt->fetch();
	$published = $row['published'] ?? false;
	$published = $published ? "NULL" : "NOW()";

	$stmt = $PDO->prepare("UPDATE \"Map\" SET published_date = {$published} WHERE id = ?");
	$stmt->execute([$map_id]);

	return !$published;
}

//
function mapHasPw($PDO, $map_id) {
	$stmt = $PDO->prepare("SELECT password IS NOT NULL AS pw FROM \"Map\" WHERE id = ?");
	$stmt->execute([$map_id]);
	$row = $stmt->fetch();
	return $row['pw'] ?? false;
}

//
function mapCheckPw($PDO, $map_id, $password) {
	$stmt = $PDO->prepare("SELECT password IS NOT NULL AS pw, password FROM \"Map\" WHERE id = ?");
	$stmt->execute([$map_id]);
	$row = $stmt->fetch();
	$has_pw = $row['pw'] ?? false;
	return $has_pw && $row['password'] == $password;
}

//
function mapGetThumbnail($PDO, $map_id) {
	$stmt = $PDO->prepare("SELECT thumbnail FROM \"Map\" WHERE id = ?");
	$stmt->execute([$map_id]);
	$row = $stmt->fetch();
	return $row['thumbnail'];
}

//
function mapHasThumbnail($PDO, $map_id) {
	return sane_is_null( mapGetThumbnail($PDO, $map_id) );
}



//
function userMapWithinLimit($PDO, $user_id) {
	$stmt = $PDO->prepare("SELECT COUNT(id) <= 5 AS is_within FROM \"User_Map\" WHERE user_id = ? AND status = 'owner'");
	$stmt->execute([$user_id]);
	$row = $stmt->fetch();
	return $row['is_within'] ?? false;
}

//
function userMapCanWrite($PDO, $user_id, $map_id) {
	$stmt = $PDO->prepare("SELECT status IN ('owner', 'editor') AS st FROM \"User_Map\" WHERE user_id = ? AND map_id = ?");
	$stmt->execute([$user_id, $map_id]);
	$row = $stmt->fetch();
	return $row['st'] ?? false;
}

//
function userMapHasLiked($PDO, $user_id, $map_id) {
	$stmt = $PDO->prepare("SELECT COUNT(id) >= 1 AS c FROM \"Reaction\" WHERE type = 'like' AND user_id = ? AND map_id = ?");
	$stmt->execute([$user_id, $map_id]);
	$row = $stmt->fetch();
	return $row['c'] ?? false;
}

//
function userMapHasFlagged($PDO, $user_id, $map_id) {
	$stmt = $PDO->prepare("SELECT COUNT(id) >= 1 AS c FROM \"Flag\" WHERE user_id = ? AND map_id = ?");
	$stmt->execute([$user_id, $map_id]);
	$row = $stmt->fetch();
	return $row['c'] ?? false;
}



//
function getAllLikes($PDO, $map_ids) {
	$ids = "";
	if(count($map_ids) > 0) {
		foreach($map_ids as $id) { $ids .= "'{$id}',"; }
		$ids = mb_substr($ids, 0, -1);
	}else{ $ids = "null"; }

	$likes = array();
	$stmt = $PDO->prepare("SELECT map_id, COUNT(id) AS c FROM \"Reaction\" WHERE map_id IN ({$ids}) AND type = 'like' GROUP BY map_id");
	$stmt->execute();
	$rows = $stmt->fetchAll();
	foreach($rows as $row) { $likes[ $row['map_id'] ] = $row['c']; }
	return $likes;
}

//
function getAllViews($PDO, $map_ids) {
	$ids = "";
	if(count($map_ids) > 0) {
		foreach($map_ids as $id) { $ids .= "'{$id}',"; }
		$ids = mb_substr($ids, 0, -1);
	}else{ $ids = "null"; }

	$views = array();
	$stmt = $PDO->prepare("SELECT map_id, COUNT(id) AS c FROM \"View\" WHERE map_id IN ({$ids}) GROUP BY map_id");
	$stmt->execute();
	$rows = $stmt->fetchAll();
	foreach($rows as $row) { $views[ $row['map_id'] ] = $row['c']; }
	return $views;
}

//
function getAllFlags($PDO, $map_ids) {
	$ids = "";
	if(count($map_ids) > 0) {
		foreach($map_ids as $id) { $ids .= "'{$id}',"; }
		$ids = mb_substr($ids, 0, -1);
	}else{ $ids = "null"; }

	$flags = array();
	$stmt = $PDO->prepare("SELECT map_id, COUNT(id) AS c FROM \"Flag\" WHERE map_id IN ({$ids}) AND type = 'flag' GROUP BY map_id");
	$stmt->execute();
	$rows = $stmt->fetchAll();
	foreach($rows as $row) { $flags[ $row['map_id'] ] = $row['c']; }
	return $flags;
}

//
function getLikes($PDO, $map_id) {
	$stmt = $PDO->prepare("SELECT COUNT(id) AS c FROM \"Reaction\" WHERE type = 'like' AND map_id = ?");
	$stmt->execute([$map_id]);
	$row = $stmt->fetch();
	return $row['c'];
}

//
function getViews($PDO, $map_id) {
	$stmt = $PDO->prepare("SELECT COUNT(id) AS c FROM \"View\" WHERE map_id = ?");
	$stmt->execute([$map_id]);
	$row = $stmt->fetch();
	return $row['c'];
}

//
function getFlags($PDO, $map_id) {
	$stmt = $PDO->prepare("SELECT COUNT(id) AS c FROM \"Flag\" WHERE map_id = ?");
	$stmt->execute([$map_id]);
	$row = $stmt->fetch();
	return $row['c'];
}

//
function getAllComments($PDO, $map_id) {
	$stmt = $PDO->prepare("
		SELECT
			U.username,
			U.photo AS user_photo,
			C.content,
			C.created_date
		FROM
			\"Comment\" AS C INNER JOIN
			\"User\" AS U
				ON C.user_id = U.id
		WHERE
			C.map_id = ?
	");
	$stmt->execute([$map_id]);
	$rows = $stmt->fetchAll();
	return $rows;
}



//
function paymentCreateCheckout($PDO, $user_id) {
	global $CONFIG;

	$paid = getUserPaid($PDO, $user_id);
	$stripe_id = getUserStripeID($PDO, $user_id);

	if($paid) { return false; }
	if(!$stripe_id) {
		$username = getUsername($PDO, $user_id);
		$email = getUserEmail($PDO, $user_id);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/customers");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array( "Content-Type: application/x-www-form-urlencoded" ));
		curl_setopt($ch, CURLOPT_USERPWD, $CONFIG['stripe_secret_key']);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "name={$username}&email={$email}");
		$res = curl_exec($ch);
		curl_close($ch);
		$res = json_decode($res, true);

		$stripe_id = $res['id'];
		$stmt = $PDO->prepare("UPDATE \"User\" SET stripe_id = ? WHERE id = ?");
		$stmt->execute([$stripe_id, $user_id]);
	}

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/checkout/sessions");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array( "Content-Type: application/x-www-form-urlencoded" ));
	curl_setopt($ch, CURLOPT_USERPWD, $CONFIG['stripe_secret_key']);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "success_url={$CONFIG['host']}/profile.php&
										  cancel_url={$CONFIG['host']}/profile.php&
										  mode=subscription&
										  customer={$stripe_id}&
										  line_items[0][price]={$CONFIG['stripe_price_id']}&
										  line_items[0][quantity]=1");
	$res = curl_exec($ch);
	curl_close($ch);
	$res = json_decode($res, true);

	return $res['url'];
}

//
function paymentCreatePortal($PDO, $user_id) {
	global $CONFIG;

	$paid = getUserPaid($PDO, $user_id);
	$stripe_id = getUserStripeID($PDO, $user_id);

	if(!$paid || !$stripe_id) { return false; }

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/billing_portal/sessions");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array( "Content-Type: application/x-www-form-urlencoded" ));
	curl_setopt($ch, CURLOPT_USERPWD, $CONFIG['stripe_secret_key']);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "customer={$stripe_id}&
										  return_url={$CONFIG['host']}/profile.php");
	$res = curl_exec($ch);
	curl_close($ch);
	$res = json_decode($res, true);

	return $res['url'];
}



//
function uploadCreate($PDO, $user_id, $type, $path, $name) {
	global $CONFIG;

	if(sane_is_null($path)
	|| sane_is_null($name)) { return null; }

	if(getimagesize($path) === false
	|| filesize($path) > 50000000) { return null; }

	$res = uploadToS3($path, $name);
	if(!$res) { return null; }

	$ref = "https://{$CONFIG['aws_bucket_name']}.s3.{$CONFIG['aws_region']}.amazonaws.com/{$res}";

	$stmt = $PDO->prepare("INSERT INTO \"Upload\" (ref) VALUES (?) RETURNING id");
	$stmt->execute([$ref]);
	$id = $stmt->fetchColumn();

	$stmt = $PDO->prepare("INSERT INTO \"User_Upload\" (user_id, upload_id, type) VALUES (?, ?, ?)");
	$stmt->execute([$user_id, $id, $type]);

	return $ref;
}



//
function getS3Hostname() {
	global $CONFIG;
	return $CONFIG['aws_bucket_name'] . ".s3.amazonaws.com";
}

function getS3Headers($file_path, $file_name) {
	global $CONFIG;

	// AWS API keys
	$aws_access_key_id = $CONFIG['aws_access_key_id'];
	$aws_secret_access_key = $CONFIG['aws_secret_access_key'];

	// AWS region and Host Name
	$aws_region = $CONFIG['aws_region'];
	$host_name = getS3Hostname();

	// Server path where content is present
	$content = file_get_contents($file_path);

	// AWS file permissions
	$content_acl = "public-read";

	// MIME type of file. Very important to set if you later plan to load the file from a S3 url in the browser (images, for example)
	$content_type = mime_content_type($file_path);
	// Name of content on S3
	$content_title = uniqid() . "." . strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

	// Service name for S3
	$aws_service_name = "s3";

	// UTC timestamp and date
	$timestamp = gmdate("Ymd\THis\Z");
	$date = gmdate("Ymd");

	// HTTP request headers as key & value
	$request_headers = array();
	$request_headers['Content-Type'] = $content_type;
	$request_headers['Date'] = $timestamp;
	$request_headers['Host'] = $host_name;
	$request_headers['x-amz-acl'] = $content_acl;
	$request_headers['x-amz-content-sha256'] = hash("sha256", $content);
	// Sort it in ascending order
	ksort($request_headers);

	// Canonical headers
	$canonical_headers = [];
	foreach($request_headers as $key => $value) {
		$canonical_headers[] = strtolower($key) . ":" . $value;
	}
	$canonical_headers = implode("\n", $canonical_headers);

	// Signed headers
	$signed_headers = [];
	foreach($request_headers as $key => $value) {
		$signed_headers[] = strtolower($key);
	}
	$signed_headers = implode(";", $signed_headers);

	// Cannonical request 
	$canonical_request = [];
	$canonical_request[] = "PUT";
	$canonical_request[] = "/" . $content_title;
	$canonical_request[] = "";
	$canonical_request[] = $canonical_headers;
	$canonical_request[] = "";
	$canonical_request[] = $signed_headers;
	$canonical_request[] = hash("sha256", $content);
	$canonical_request = implode("\n", $canonical_request);
	$hashed_canonical_request = hash("sha256", $canonical_request);

	// AWS Scope
	$scope = [];
	$scope[] = $date;
	$scope[] = $aws_region;
	$scope[] = $aws_service_name;
	$scope[] = "aws4_request";

	// String to sign
	$string_to_sign = [];
	$string_to_sign[] = "AWS4-HMAC-SHA256"; 
	$string_to_sign[] = $timestamp; 
	$string_to_sign[] = implode("/", $scope);
	$string_to_sign[] = $hashed_canonical_request;
	$string_to_sign = implode("\n", $string_to_sign);

	// Signing key
	$kSecret = "AWS4" . $aws_secret_access_key;
	$kDate = hash_hmac("sha256", $date, $kSecret, true);
	$kRegion = hash_hmac("sha256", $aws_region, $kDate, true);
	$kService = hash_hmac("sha256", $aws_service_name, $kRegion, true);
	$kSigning = hash_hmac("sha256", "aws4_request", $kService, true);

	// Signature
	$signature = hash_hmac("sha256", $string_to_sign, $kSigning);

	// Authorization
	$authorization = [
		"Credential=" . $aws_access_key_id . "/" . implode("/", $scope),
		"SignedHeaders=" . $signed_headers,
		"Signature=" . $signature
	];
	$authorization = "AWS4-HMAC-SHA256" . " " . implode(",", $authorization);

	// Curl headers
	$curl_headers = [ "Authorization: " . $authorization ];
	foreach($request_headers as $key => $value) {
		$curl_headers[] = $key . ": " . $value;
	}

	return array(
		"curl_headers" => $curl_headers,
		"content_title" => $content_title
	);
}

function uploadToS3($file_path, $file_name) {
	$host_name = getS3Hostname();
	$headers = getS3Headers($file_path, $file_name);

	$curl_headers = $headers['curl_headers'];

	$content = file_get_contents($file_path);
	$content_title = $headers['content_title'];

	$url = "https://" . $host_name . "/" . $content_title;
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
	//curl_setopt($ch, CURLOPT_FAILONERROR, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
	$res = curl_exec($ch);
	//if(curl_errno($ch)) { echo curl_error($ch); }
	curl_close($ch);

	return $res ? $content_title : false;
}



class SimpleEmailServiceRequest {
	private $ses, $verb, $parameters = array();
	protected $curl_handler = null;
	protected $response;
	public static $curlOptions = array();

	public function __construct(SimpleEmailService $ses = null, $verb = 'GET') {
		$this->ses = $ses;
		$this->verb = $verb;
		$this->response = (object) array('body' => '', 'code' => 0, 'error' => false);
	}

	public function setSES(SimpleEmailService $ses) {
		$this->ses = $ses;
		return $this;
	}

	public function setVerb($verb) {
		$this->verb = $verb;
		return $this;
	}

	public function setParameter($key, $value, $replace = true) {
		if(!$replace && isset($this->parameters[$key])) {
			$temp = (array)($this->parameters[$key]);
			$temp[] = $value;
			$this->parameters[$key] = $temp;
		} else {
			$this->parameters[$key] = $value;
		}
		return $this;
	}

	public function getParametersEncoded() {
		$params = array();

		foreach ($this->parameters as $var => $value) {
			if(is_array($value)) {
				foreach($value as $v) {
					$params[] = $var.'='.$this->__customUrlEncode($v);
				}
			} else {
				$params[] = $var.'='.$this->__customUrlEncode($value);
			}
		}

		sort($params, SORT_STRING);
		return $params;
	}

	public function clearParameters() {
		$this->parameters = array();
		return $this;
	}

	protected function getCurlHandler() {
		if(!empty($this->curl_handler)) { return $this->curl_handler; }

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERAGENT, 'SimpleEmailService/php');

		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, ($this->ses->verifyHost() ? 2 : 0));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, ($this->ses->verifyPeer() ? 1 : 0));
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
		curl_setopt($curl, CURLOPT_WRITEFUNCTION, array(&$this, '__responseWriteCallback'));
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

		foreach(self::$curlOptions as $option => $value) {
			curl_setopt($curl, $option, $value);
		}

		$this->curl_handler = $curl;

		return $this->curl_handler;
	}

	public function getResponse() {
		$url = 'https://'.$this->ses->getHost().'/';
		ksort($this->parameters);
		$query = http_build_query($this->parameters, '', '&', PHP_QUERY_RFC1738);
		$headers = $this->getHeaders($query);

		$curl_handler = $this->getCurlHandler();
		curl_setopt($curl_handler, CURLOPT_CUSTOMREQUEST, $this->verb);

		// Request types
		switch ($this->verb) {
			case 'GET':
			case 'DELETE':
				$url .= '?'.$query;
				break;

			case 'POST':
				curl_setopt($curl_handler, CURLOPT_POSTFIELDS, $query);
				$headers[] = 'Content-Type: application/x-www-form-urlencoded';
				break;
		}
		curl_setopt($curl_handler, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl_handler, CURLOPT_URL, $url);


		// Execute, grab errors
		if (curl_exec($curl_handler)) {
			$this->response->code = curl_getinfo($curl_handler, CURLINFO_HTTP_CODE);
		} else {
			$this->response->error = array(
				'curl' => true,
				'code' => curl_errno($curl_handler),
				'message' => curl_error($curl_handler),
			);
		}

		// cleanup for reusing the current instance for multiple requests
		curl_setopt($curl_handler, CURLOPT_POSTFIELDS, '');
		$this->parameters = array();

		// Parse body into XML
		if ($this->response->error === false && !empty($this->response->body)) {
			$this->response->body = simplexml_load_string($this->response->body);

			// Grab SES errors
			if (!in_array($this->response->code, array(200, 201, 202, 204))
				&& isset($this->response->body->Error)) {
				$error = $this->response->body->Error;
				$output = array();
				$output['curl'] = false;
				$output['Error'] = array();
				$output['Error']['Type'] = (string)$error->Type;
				$output['Error']['Code'] = (string)$error->Code;
				$output['Error']['Message'] = (string)$error->Message;
				$output['RequestId'] = (string)$this->response->body->RequestId;

				$this->response->error = $output;
				unset($this->response->body);
			}
		}

		$response = $this->response;
		$this->response = (object) array('body' => '', 'code' => 0, 'error' => false);

		return $response;
	}

	protected function getHeaders($query) {
		$headers = array();

		if ($this->ses->getRequestSignatureVersion() == SimpleEmailService::REQUEST_SIGNATURE_V4) {
			$date = (new DateTime('now', new DateTimeZone('UTC')))->format('Ymd\THis\Z');
			$headers[] = 'X-Amz-Date: ' . $date;
			$headers[] = 'Host: ' . $this->ses->getHost();
			$headers[] = 'Authorization: ' . $this->__getAuthHeaderV4($date, $query);
		} else {
			// must be in format 'Sun, 06 Nov 1994 08:49:37 GMT'
			$date = gmdate('D, d M Y H:i:s e');
			$auth = 'AWS3-HTTPS AWSAccessKeyId='.$this->ses->getAccessKey();
			$auth .= ',Algorithm=HmacSHA256,Signature='.$this->__getSignature($date);

			$headers[] = 'Date: ' . $date;
			$headers[] = 'Host: ' . $this->ses->getHost();
			$headers[] = 'X-Amzn-Authorization: ' . $auth;
		}

		return $headers;
	}

	public function __destruct() {
		if (!empty($this->curl_handler)) {
			@curl_close($this->curl_handler);
		}
	}

	private function __responseWriteCallback($curl, $data) {
		if (!isset($this->response->body)) {
			$this->response->body = $data;
		} else {
			$this->response->body .= $data;
		}
		return strlen($data);
	}

	private function __customUrlEncode($var) {
		return str_replace('%7E', '~', rawurlencode($var));
	}

	private function __getSignature($string) {
		return base64_encode(hash_hmac('sha256', $string, $this->ses->getSecretKey(), true));
	}

	private function __getSigningKey($key, $dateStamp, $regionName, $serviceName, $algo) {
		$kDate = hash_hmac($algo, $dateStamp, 'AWS4' . $key, true);
		$kRegion = hash_hmac($algo, $regionName, $kDate, true);
		$kService = hash_hmac($algo, $serviceName, $kRegion, true);
		return hash_hmac($algo,'aws4_request', $kService, true);
	}

	private function __getAuthHeaderV4($amz_datetime, $query) {
		$amz_date = substr($amz_datetime, 0, 8);
		$algo = 'sha256';
		$aws_algo = 'AWS4-HMAC-' . strtoupper($algo);

		$host_parts = explode('.', $this->ses->getHost());
		$service = $host_parts[0];
		$region = $host_parts[1];

		$canonical_uri = '/';
		if($this->verb === 'POST') {
			$canonical_querystring = '';
			$payload_data = $query;
		} else {
			$canonical_querystring = $query;
			$payload_data = '';
		}

		// ************* TASK 1: CREATE A CANONICAL REQUEST *************
		$canonical_headers_list = [
			'host:' . $this->ses->getHost(),
			'x-amz-date:' . $amz_datetime
		];

		$canonical_headers = implode("\n", $canonical_headers_list) . "\n";
		$signed_headers = 'host;x-amz-date';
		$payload_hash = hash($algo, $payload_data, false);

		$canonical_request = implode("\n", array(
			$this->verb,
			$canonical_uri,
			$canonical_querystring,
			$canonical_headers,
			$signed_headers,
			$payload_hash
		));

		// ************* TASK 2: CREATE THE STRING TO SIGN*************
		$credential_scope = $amz_date. '/' . $region . '/' . $service . '/' . 'aws4_request';
		$string_to_sign = implode("\n", array(
			$aws_algo,
			$amz_datetime,
			$credential_scope,
			hash($algo, $canonical_request, false)
		));

		// ************* TASK 3: CALCULATE THE SIGNATURE *************
		// Create the signing key using the function defined above.
		$signing_key = $this->__getSigningKey($this->ses->getSecretKey(), $amz_date, $region, $service, $algo);

		// Sign the string_to_sign using the signing_key
		$signature = hash_hmac($algo, $string_to_sign, $signing_key, false);

		// ************* TASK 4: ADD SIGNING INFORMATION TO THE REQUEST *************
		return $aws_algo . ' ' . implode(', ', array(
			'Credential=' . $this->ses->getAccessKey() . '/' . $credential_scope,
			'SignedHeaders=' . $signed_headers ,
			'Signature=' . $signature
		));
	}
}

final class SimpleEmailServiceMessage {
	public $to, $cc, $bcc, $replyto, $recipientsCharset;
	public $from, $returnpath;
	public $subject, $messagetext, $messagehtml;
	public $subjectCharset, $messageTextCharset, $messageHtmlCharset;
	public $attachments, $customHeaders, $configuration_set, $message_tags;
	public $is_clean, $raw_message;

	public function __construct() {
		$this->to = array();
		$this->cc = array();
		$this->bcc = array();
		$this->replyto = array();
		$this->recipientsCharset = 'UTF-8';

		$this->from = null;
		$this->returnpath = null;

		$this->subject = null;
		$this->messagetext = null;
		$this->messagehtml = null;

		$this->subjectCharset = 'UTF-8';
		$this->messageTextCharset = 'UTF-8';
		$this->messageHtmlCharset = 'UTF-8';

		$this->attachments = array();
		$this->customHeaders = array();
		$this->configuration_set = null;
		$this->message_tags = array();

		$this->is_clean = true;
		$this->raw_message = null;
	}

	public function addTo($to) {
		if (!is_array($to)) {
			$this->to[] = $to;
		} else {
			$this->to = array_unique(array_merge($this->to, $to));
		}

		$this->is_clean = false;

		return $this;
	}

	public function setTo($to) {
		$this->to = (array) $to;

		$this->is_clean = false;

		return $this;
	}

	public function clearTo() {
		$this->to = array();

		$this->is_clean = false;

		return $this;
	}

	public function addCC($cc) {
		if (!is_array($cc)) {
			$this->cc[] = $cc;
		} else {
			$this->cc = array_merge($this->cc, $cc);
		}

		$this->is_clean = false;

		return $this;
	}

	public function clearCC() {
		$this->cc = array();

		$this->is_clean = false;

		return $this;
	}

	public function addBCC($bcc) {
		if (!is_array($bcc)) {
			$this->bcc[] = $bcc;
		} else {
			$this->bcc = array_merge($this->bcc, $bcc);
		}

		$this->is_clean = false;

		return $this;
	}

	public function clearBCC() {
		$this->bcc = array();

		$this->is_clean = false;

		return $this;
	}

	public function addReplyTo($replyto) {
		if (!is_array($replyto)) {
			$this->replyto[] = $replyto;
		} else {
			$this->replyto = array_merge($this->replyto, $replyto);
		}

		$this->is_clean = false;

		return $this;
	}

	public function clearReplyTo() {
		$this->replyto = array();

		$this->is_clean = false;

		return $this;
	}

	public function clearRecipients() {
		$this->clearTo();
		$this->clearCC();
		$this->clearBCC();
		$this->clearReplyTo();

		$this->is_clean = false;

		return $this;
	}

	public function setFrom($from) {
		$this->from = $from;

		$this->is_clean = false;

		return $this;
	}

	public function setReturnPath($returnpath) {
		$this->returnpath = $returnpath;

		$this->is_clean = false;

		return $this;
	}

	public function setRecipientsCharset($charset) {
		$this->recipientsCharset = $charset;

		$this->is_clean = false;

		return $this;
	}

	public function setSubject($subject) {
		$this->subject = $subject;

		$this->is_clean = false;

		return $this;
	}

	public function setSubjectCharset($charset) {
		$this->subjectCharset = $charset;

		$this->is_clean = false;

		return $this;
	}

	public function setMessageFromString($text, $html = null) {
		$this->messagetext = $text;
		$this->messagehtml = $html;

		$this->is_clean = false;

		return $this;
	}

	public function setMessageFromFile($textfile, $htmlfile = null) {
		if (file_exists($textfile) && is_file($textfile) && is_readable($textfile)) {
			$this->messagetext = file_get_contents($textfile);
		} else {
			$this->messagetext = null;
		}
		if (file_exists($htmlfile) && is_file($htmlfile) && is_readable($htmlfile)) {
			$this->messagehtml = file_get_contents($htmlfile);
		} else {
			$this->messagehtml = null;
		}

		$this->is_clean = false;

		return $this;
	}

	public function setMessageFromURL($texturl, $htmlurl = null) {
		if ($texturl !== null) {
			$this->messagetext = file_get_contents($texturl);
		} else {
			$this->messagetext = null;
		}
		if ($htmlurl !== null) {
			$this->messagehtml = file_get_contents($htmlurl);
		} else {
			$this->messagehtml = null;
		}

		$this->is_clean = false;

		return $this;
	}

	public function setMessageCharset($textCharset, $htmlCharset = null) {
		$this->messageTextCharset = $textCharset;
		$this->messageHtmlCharset = $htmlCharset;

		$this->is_clean = false;

		return $this;
	}

	public function setConfigurationSet($configuration_set = null) {
		$this->configuration_set = $configuration_set;

		$this->is_clean = false;

		return $this;
	}

	public function getMessageTags() {
		return $this->message_tags;
	}

	public function getMessageTag($key) {
		return isset($this->message_tags[$key]) ? $this->message_tags[$key] : null;
	}

	public function setMessageTag($key, $value) {
		$this->message_tags[$key] = $value;

		$this->is_clean = false;

		return $this;
	}

	public function removeMessageTag($key) {
		unset($this->message_tags[$key]);

		$this->is_clean = false;

		return $this;
	}

	public function setMessageTags($message_tags = array()) {
		$this->message_tags = array_merge($this->message_tags, $message_tags);

		$this->is_clean = false;

		return $this;
	}

	public function removeMessageTags() {
		$this->message_tags = array();

		$this->is_clean = false;

		return $this;
	}

	public function addCustomHeader($header) {
		$this->customHeaders[] = $header;

		$this->is_clean = false;

		return $this;
	}

	public function addAttachmentFromData($name, $data, $mimeType = 'application/octet-stream', $contentId = null, $attachmentType = 'attachment') {
		$this->attachments[$name] = array(
			'name' => $name,
			'mimeType' => $mimeType,
			'data' => $data,
			'contentId' => $contentId,
			'attachmentType' => ($attachmentType == 'inline' ? 'inline; filename="' . $name . '"' : $attachmentType),
		);

		$this->is_clean = false;

		return $this;
	}

	public function addAttachmentFromFile($name, $path, $mimeType = 'application/octet-stream', $contentId = null, $attachmentType = 'attachment') {
		if (file_exists($path) && is_file($path) && is_readable($path)) {
			$this->addAttachmentFromData($name, file_get_contents($path), $mimeType, $contentId, $attachmentType);
			return true;
		}

		$this->is_clean = false;

		return false;
	}

	public function addAttachmentFromUrl($name, $url, $mimeType = 'application/octet-stream', $contentId = null, $attachmentType = 'attachment') {
		$data = file_get_contents($url);
		if ($data !== false) {
			$this->addAttachmentFromData($name, $data, $mimeType, $contentId, $attachmentType);
			return true;
		}

		$this->is_clean = false;

		return false;
	}

	public function hasInlineAttachments() {
		foreach ($this->attachments as $attachment) {
			if ($attachment['attachmentType'] != 'attachment') {
				return true;
			}

		}
		return false;
	}

	public function getRawMessage($encode = true) {
		if ($this->is_clean && !is_null($this->raw_message) && $encode) {
			return $this->raw_message;
		}

		$this->is_clean = true;

		$boundary = uniqid(rand(), true);
		$raw_message = count($this->customHeaders) > 0 ? join("\n", $this->customHeaders) . "\n" : '';

		if (!empty($this->message_tags)) {
			$message_tags = array();
			foreach ($this->message_tags as $key => $value) {
				$message_tags[] = "{$key}={$value}";
			}

			$raw_message .= 'X-SES-MESSAGE-TAGS: ' . join(', ', $message_tags) . "\n";
		}

		if (!is_null($this->configuration_set)) {
			$raw_message .= 'X-SES-CONFIGURATION-SET: ' . $this->configuration_set . "\n";
		}

		$raw_message .= count($this->to) > 0 ? 'To: ' . $this->encodeRecipients($this->to) . "\n" : '';
		$raw_message .= 'From: ' . $this->encodeRecipients($this->from) . "\n";
		if (!empty($this->replyto)) {
			$raw_message .= 'Reply-To: ' . $this->encodeRecipients($this->replyto) . "\n";
		}

		if (!empty($this->cc)) {
			$raw_message .= 'CC: ' . $this->encodeRecipients($this->cc) . "\n";
		}
		if (!empty($this->bcc)) {
			$raw_message .= 'BCC: ' . $this->encodeRecipients($this->bcc) . "\n";
		}

		if ($this->subject != null && strlen($this->subject) > 0) {
			$raw_message .= 'Subject: =?' . $this->subjectCharset . '?B?' . base64_encode($this->subject) . "?=\n";
		}

		$raw_message .= 'MIME-Version: 1.0' . "\n";
		$raw_message .= 'Content-type: ' . ($this->hasInlineAttachments() ? 'multipart/related' : 'Multipart/Mixed') . '; boundary="' . $boundary . '"' . "\n";
		$raw_message .= "\n--{$boundary}\n";
		$raw_message .= 'Content-type: Multipart/Alternative; boundary="alt-' . $boundary . '"' . "\n";

		if ($this->messagetext != null && strlen($this->messagetext) > 0) {
			$charset = empty($this->messageTextCharset) ? '' : "; charset=\"{$this->messageTextCharset}\"";
			$raw_message .= "\n--alt-{$boundary}\n";
			$raw_message .= 'Content-Type: text/plain' . $charset . "\n\n";
			$raw_message .= $this->messagetext . "\n";
		}

		if ($this->messagehtml != null && strlen($this->messagehtml) > 0) {
			$charset = empty($this->messageHtmlCharset) ? '' : "; charset=\"{$this->messageHtmlCharset}\"";
			$raw_message .= "\n--alt-{$boundary}\n";
			$raw_message .= 'Content-Type: text/html' . $charset . "\n\n";
			$raw_message .= $this->messagehtml . "\n";
		}
		$raw_message .= "\n--alt-{$boundary}--\n";

		foreach ($this->attachments as $attachment) {
			$raw_message .= "\n--{$boundary}\n";
			$raw_message .= 'Content-Type: ' . $attachment['mimeType'] . '; name="' . $attachment['name'] . '"' . "\n";
			$raw_message .= 'Content-Disposition: ' . $attachment['attachmentType'] . "\n";
			if (!empty($attachment['contentId'])) {
				$raw_message .= 'Content-ID: ' . $attachment['contentId'] . '' . "\n";
			}
			$raw_message .= 'Content-Transfer-Encoding: base64' . "\n";
			$raw_message .= "\n" . chunk_split(base64_encode($attachment['data']), 76, "\n") . "\n";
		}

		$raw_message .= "\n--{$boundary}--\n";

		if (!$encode) {
			return $raw_message;
		}

		$this->raw_message = base64_encode($raw_message);

		return $this->raw_message;
	}

	public function encodeRecipients($recipient) {
		if (is_array($recipient)) {
			return join(', ', array_map(array($this, 'encodeRecipients'), $recipient));
		}

		if (preg_match("/(.*)<(.*)>/", $recipient, $regs)) {
			$recipient = '=?' . $this->recipientsCharset . '?B?' . base64_encode($regs[1]) . '?= <' . $regs[2] . '>';
		}

		return $recipient;
	}

	public function validate() {
		// at least one destination is required
		if (count($this->to) == 0 && count($this->cc) == 0 && count($this->bcc) == 0) {
			return false;
		}

		// sender is required
		if ($this->from == null || strlen($this->from) == 0) {
			return false;
		}

		// subject is required
		if (($this->subject == null || strlen($this->subject) == 0)) {
			return false;
		}

		// message is required
		if ((empty($this->messagetext) || strlen((string) $this->messagetext) == 0)
			&& (empty($this->messagehtml) || strlen((string) $this->messagehtml) == 0)) {
			return false;
		}

		return true;
	}
}

class SimpleEmailService {
	const AWS_CA_CENTRAL_1 = 'email.ca-central-1.amazonaws.com';
	const AWS_AP_NORTHEAST_1 = 'email.ap-northeast-1.amazonaws.com';
	const AWS_AP_NORTHEAST_2 = 'email.ap-northeast-2.amazonaws.com';
	const AWS_AP_SOUTH_1 = 'email.ap-south-1.amazonaws.com';
	const AWS_AP_SOUTHEAST_1 = 'email.ap-southeast-1.amazonaws.com';
	const AWS_AP_SOUTHEAST_2 = 'email.ap-southeast-2.amazonaws.com';
	const AWS_EU_CENTRAL_1 = 'email.eu-central-1.amazonaws.com';
	const AWS_EU_WEST_1 = 'email.eu-west-1.amazonaws.com';
	const AWS_EU_WEST_2 = 'email.eu-west-2.amazonaws.com';
	const AWS_EU_NORTH_1 = 'email.eu-north-1.amazonaws.com';
	const AWS_SA_EAST_1 = 'email.sa-east-1.amazonaws.com';
	const AWS_US_EAST_1 = 'email.us-east-1.amazonaws.com';
	const AWS_US_EAST_2 = 'email.us-east-2.amazonaws.com';
	const AWS_US_GOV_WEST_1 = 'email.us-gov-west-1.amazonaws.com';
	const AWS_US_WEST_2 = 'email.us-west-2.amazonaws.com';

	const AWS_EU_WEST1 = 'email.eu-west-1.amazonaws.com';

	const REQUEST_SIGNATURE_V3 = 'v4';  // For BW compatibility reasons.
	const REQUEST_SIGNATURE_V4 = 'v4';

	protected $__host;
	protected $__accessKey;
	protected $__secretKey;
	protected $__trigger_errors;
	protected $__bulk_sending_mode = false;
	protected $__ses_request = null;
	protected $__verifyHost = true;
	protected $__verifyPeer = true;
	protected $__requestSignatureVersion;

	public function __construct($accessKey = null, $secretKey = null, $host = self::AWS_US_EAST_1, $trigger_errors = true, $requestSignatureVersion = self::REQUEST_SIGNATURE_V4) {
		if ($accessKey !== null && $secretKey !== null) {
			$this->setAuth($accessKey, $secretKey);
		}
		$this->__host = $host;
		$this->__trigger_errors = $trigger_errors;
		$this->__requestSignatureVersion = $requestSignatureVersion;
	}

	public function setRequestSignatureVersion($requestSignatureVersion) {
		$this->__requestSignatureVersion = $requestSignatureVersion;

		return $this;
	}

	public function getRequestSignatureVersion() {
		return $this->__requestSignatureVersion;
	}

	public function setAuth($accessKey, $secretKey) {
		$this->__accessKey = $accessKey;
		$this->__secretKey = $secretKey;

		return $this;
	}

	public function setHost($host = self::AWS_US_EAST_1) {
		$this->__host = $host;

		return $this;
	}

	public function enableVerifyHost($enable = true) {
		$this->__verifyHost = (bool)$enable;

		return $this;
	}

	public function enableVerifyPeer($enable = true) {
		$this->__verifyPeer = (bool)$enable;

		return $this;
	}

	public function verifyHost() {
		return $this->__verifyHost;
	}

	public function verifyPeer() {
		return $this->__verifyPeer;
	}


	public function getHost() {
		return $this->__host;
	}

	public function getAccessKey() {
		return $this->__accessKey;
	}

	public function getSecretKey() {
		return $this->__secretKey;
	}

	public function getVerifyPeer() {
		return $this->__verifyPeer;
	}

	public function getVerifyHost() {
		return $this->__verifyHost;
	}

	public function getBulkMode() {
		return $this->__bulk_sending_mode;
	}


	public function setVerifyHost($enable = true) {
		$this->__verifyHost = (bool)$enable;
		return $this;
	}

	public function setVerifyPeer($enable = true) {
		$this->__verifyPeer = (bool)$enable;
		return $this;
	}

	public function setBulkMode($enable = true) {
		$this->__bulk_sending_mode = (bool)$enable;
		return $this;
	}

	public function listVerifiedEmailAddresses() {
		$ses_request = $this->getRequestHandler('GET');
		$ses_request->setParameter('Action', 'ListVerifiedEmailAddresses');

		$ses_response = $ses_request->getResponse();
		if($ses_response->error === false && $ses_response->code !== 200) {
			$ses_response->error = array('code' => $ses_response->code, 'message' => 'Unexpected HTTP status');
		}
		if($ses_response->error !== false) {
			$this->__triggerError('listVerifiedEmailAddresses', $ses_response->error);
			return false;
		}

		$response = array();
		if(!isset($ses_response->body)) {
			return $response;
		}

		$addresses = array();
		foreach($ses_response->body->ListVerifiedEmailAddressesResult->VerifiedEmailAddresses->member as $address) {
			$addresses[] = (string)$address;
		}

		$response['Addresses'] = $addresses;
		$response['RequestId'] = (string)$ses_response->body->ResponseMetadata->RequestId;

		return $response;
	}

	public function verifyEmailAddress($email) {
		$ses_request = $this->getRequestHandler('POST');
		$ses_request->setParameter('Action', 'VerifyEmailAddress');
		$ses_request->setParameter('EmailAddress', $email);

		$ses_response = $ses_request->getResponse();
		if($ses_response->error === false && $ses_response->code !== 200) {
			$ses_response->error = array('code' => $ses_response->code, 'message' => 'Unexpected HTTP status');
		}
		if($ses_response->error !== false) {
			$this->__triggerError('verifyEmailAddress', $ses_response->error);
			return false;
		}

		$response['RequestId'] = (string)$ses_response->body->ResponseMetadata->RequestId;
		return $response;
	}

	public function deleteVerifiedEmailAddress($email) {
		$ses_request = $this->getRequestHandler('DELETE');
		$ses_request->setParameter('Action', 'DeleteVerifiedEmailAddress');
		$ses_request->setParameter('EmailAddress', $email);

		$ses_response = $ses_request->getResponse();
		if($ses_response->error === false && $ses_response->code !== 200) {
			$ses_response->error = array('code' => $ses_response->code, 'message' => 'Unexpected HTTP status');
		}
		if($ses_response->error !== false) {
			$this->__triggerError('deleteVerifiedEmailAddress', $ses_response->error);
			return false;
		}

		$response['RequestId'] = (string)$ses_response->body->ResponseMetadata->RequestId;
		return $response;
	}

	public function getSendQuota() {
		$ses_request = $this->getRequestHandler('GET');
		$ses_request->setParameter('Action', 'GetSendQuota');

		$ses_response = $ses_request->getResponse();
		if($ses_response->error === false && $ses_response->code !== 200) {
			$ses_response->error = array('code' => $ses_response->code, 'message' => 'Unexpected HTTP status');
		}
		if($ses_response->error !== false) {
			$this->__triggerError('getSendQuota', $ses_response->error);
			return false;
		}

		$response = array();
		if(!isset($ses_response->body)) {
			return $response;
		}

		$response['Max24HourSend'] = (string)$ses_response->body->GetSendQuotaResult->Max24HourSend;
		$response['MaxSendRate'] = (string)$ses_response->body->GetSendQuotaResult->MaxSendRate;
		$response['SentLast24Hours'] = (string)$ses_response->body->GetSendQuotaResult->SentLast24Hours;
		$response['RequestId'] = (string)$ses_response->body->ResponseMetadata->RequestId;

		return $response;
	}

	public function getSendStatistics() {
		$ses_request = $this->getRequestHandler('GET');
		$ses_request->setParameter('Action', 'GetSendStatistics');

		$ses_response = $ses_request->getResponse();
		if($ses_response->error === false && $ses_response->code !== 200) {
			$ses_response->error = array('code' => $ses_response->code, 'message' => 'Unexpected HTTP status');
		}
		if($ses_response->error !== false) {
			$this->__triggerError('getSendStatistics', $ses_response->error);
			return false;
		}

		$response = array();
		if(!isset($ses_response->body)) {
			return $response;
		}

		$datapoints = array();
		foreach($ses_response->body->GetSendStatisticsResult->SendDataPoints->member as $datapoint) {
			$p = array();
			$p['Bounces'] = (string)$datapoint->Bounces;
			$p['Complaints'] = (string)$datapoint->Complaints;
			$p['DeliveryAttempts'] = (string)$datapoint->DeliveryAttempts;
			$p['Rejects'] = (string)$datapoint->Rejects;
			$p['Timestamp'] = (string)$datapoint->Timestamp;

			$datapoints[] = $p;
		}

		$response['SendDataPoints'] = $datapoints;
		$response['RequestId'] = (string)$ses_response->body->ResponseMetadata->RequestId;

		return $response;
	}


	public function sendEmail($sesMessage, $use_raw_request = false , $trigger_error = null) {
		if(!$sesMessage->validate()) {
			$this->__triggerError('sendEmail', 'Message failed validation.');
			return false;
		}

		$ses_request = $this->getRequestHandler('POST');
		$action = !empty($sesMessage->attachments) || $use_raw_request ? 'SendRawEmail' : 'SendEmail';
		$ses_request->setParameter('Action', $action);

		// Works with both calls
		if (!is_null($sesMessage->configuration_set)) {
			$ses_request->setParameter('ConfigurationSetName', $sesMessage->configuration_set);
		}

		if($action == 'SendRawEmail') {
			// https://docs.aws.amazon.com/ses/latest/APIReference/API_SendRawEmail.html
			$ses_request->setParameter('RawMessage.Data', $sesMessage->getRawMessage());
		} else {
			$i = 1;
			foreach($sesMessage->to as $to) {
				$ses_request->setParameter('Destination.ToAddresses.member.'.$i, $sesMessage->encodeRecipients($to));
				$i++;
			}

			if(is_array($sesMessage->cc)) {
				$i = 1;
				foreach($sesMessage->cc as $cc) {
					$ses_request->setParameter('Destination.CcAddresses.member.'.$i, $sesMessage->encodeRecipients($cc));
					$i++;
				}
			}

			if(is_array($sesMessage->bcc)) {
				$i = 1;
				foreach($sesMessage->bcc as $bcc) {
					$ses_request->setParameter('Destination.BccAddresses.member.'.$i, $sesMessage->encodeRecipients($bcc));
					$i++;
				}
			}

			if(is_array($sesMessage->replyto)) {
				$i = 1;
				foreach($sesMessage->replyto as $replyto) {
					$ses_request->setParameter('ReplyToAddresses.member.'.$i, $sesMessage->encodeRecipients($replyto));
					$i++;
				}
			}

			$ses_request->setParameter('Source', $sesMessage->encodeRecipients($sesMessage->from));

			if($sesMessage->returnpath != null) {
				$ses_request->setParameter('ReturnPath', $sesMessage->returnpath);
			}

			if($sesMessage->subject != null && strlen($sesMessage->subject) > 0) {
				$ses_request->setParameter('Message.Subject.Data', $sesMessage->subject);
				if($sesMessage->subjectCharset != null && strlen($sesMessage->subjectCharset) > 0) {
					$ses_request->setParameter('Message.Subject.Charset', $sesMessage->subjectCharset);
				}
			}


			if($sesMessage->messagetext != null && strlen($sesMessage->messagetext) > 0) {
				$ses_request->setParameter('Message.Body.Text.Data', $sesMessage->messagetext);
				if($sesMessage->messageTextCharset != null && strlen($sesMessage->messageTextCharset) > 0) {
					$ses_request->setParameter('Message.Body.Text.Charset', $sesMessage->messageTextCharset);
				}
			}

			if($sesMessage->messagehtml != null && strlen($sesMessage->messagehtml) > 0) {
				$ses_request->setParameter('Message.Body.Html.Data', $sesMessage->messagehtml);
				if($sesMessage->messageHtmlCharset != null && strlen($sesMessage->messageHtmlCharset) > 0) {
					$ses_request->setParameter('Message.Body.Html.Charset', $sesMessage->messageHtmlCharset);
				}
			}

			$i = 1;
			foreach($sesMessage->message_tags as $key => $value) {
				$ses_request->setParameter('Tags.member.'.$i.'.Name', $key);
				$ses_request->setParameter('Tags.member.'.$i.'.Value', $value);
				$i++;
			}
		}

		$ses_response = $ses_request->getResponse();
		if($ses_response->error === false && $ses_response->code !== 200) {
			$response = array(
				'code' => $ses_response->code,
				'error' => array('Error' => array('message' => 'Unexpected HTTP status')),
			);
			return $response;
		}
		if($ses_response->error !== false) {
			if (($this->__trigger_errors && ($trigger_error !== false)) || $trigger_error === true) {
				$this->__triggerError('sendEmail', $ses_response->error);
				return false;
			}
			return $ses_response;
		}

		$response = array(
			'MessageId' => (string)$ses_response->body->{"{$action}Result"}->MessageId,
			'RequestId' => (string)$ses_response->body->ResponseMetadata->RequestId,
		);
		return $response;
	}

	public function __triggerError($functionname, $error)
	{
		if($error == false) {
			trigger_error(sprintf("SimpleEmailService::%s(): Encountered an error, but no description given", $functionname), E_USER_WARNING);
		}
		else if(isset($error['curl']) && $error['curl'])
		{
			trigger_error(sprintf("SimpleEmailService::%s(): %s %s", $functionname, $error['code'], $error['message']), E_USER_WARNING);
		}
		else if(isset($error['Error']))
		{
			$e = $error['Error'];
			$message = sprintf("SimpleEmailService::%s(): %s - %s: %s\nRequest Id: %s\n", $functionname, $e['Type'], $e['Code'], $e['Message'], $error['RequestId']);
			trigger_error($message, E_USER_WARNING);
		}
		else {
			trigger_error(sprintf("SimpleEmailService::%s(): Encountered an error: %s", $functionname, $error), E_USER_WARNING);
		}
	}

	public function setRequestHandler(SimpleEmailServiceRequest $ses_request = null) {
		if (!is_null($ses_request)) {
			$ses_request->setSES($this);
		}

		$this->__ses_request = $ses_request;

		return $this;
	}

	public function getRequestHandler($verb) {
		if (empty($this->__ses_request)) {
			$this->__ses_request = new SimpleEmailServiceRequest($this, $verb);
		} else {
			$this->__ses_request->setVerb($verb);
		}

		return $this->__ses_request;
	}
}

//
function sendSESEmail($to_name, $to_address, $subject, $body) {
	global $CONFIG;

	$m = new SimpleEmailServiceMessage();
	$m->addTo("{$to_name} <{$to_address}>");
	$m->setFrom("Contact GeoTales <contact@geotales.io>");
	$m->setSubject($subject);
	$m->setMessageFromString($body);

	$ses = new SimpleEmailService($CONFIG['aws_access_key_id'], $CONFIG['aws_secret_access_key'], $CONFIG['aws_ses_region']);
	$ses->sendEmail($m);

	return true;
}
