<?php
/*******************************************************************************
* Copyright (C) Nordfjord EDB AS - All Rights Reserved                         *
*                                                                              *
* Unauthorized copying of this file, via any medium is strictly prohibited     *
* Proprietary and c.phponfidential                                             *
* Written by Andreas Atakan <aca@geotales.io>, January 2022                    *
*******************************************************************************/

ini_set('display_errors', 'On'); ini_set('html_errors', 0); error_reporting(-1);

//session_set_cookie_params(['SameSite' => 'None', 'Secure' => true]);
session_start();

include "init.php";
include_once("helper.php");

// Not logged in
if(!isset($_SESSION['user_id']) || !validUserID($PDO, $_SESSION['user_id'])) {
	header("location: index.php?return_url=profile.php"); exit;
}
$user_id = $_SESSION['user_id'];
$paid = getUserPaid($PDO, $user_id);

$op = $_REQUEST['op'] ?? null;
if($op == "update") {
	$photo = uploadCreate($PDO, $user_id, "profile_photo", $_FILES["photo"]["tmp_name"], $_FILES["photo"]["name"]);

	$r = updateUser(
		$PDO,
		$user_id,
		isset($_POST['username']) ? sanitize($_POST['username']) : null,
		isset($_POST['email']) ? sanitize($_POST['email']) : null,
		$photo,
		isset($_POST['password']) ? sanitize($_POST['password']) : null
	);
	if(!$r) { http_response_code(500); exit; }
}
else
if($op == "payment") {
	$ref = null;
	if($paid) { $ref = paymentCreatePortal($PDO, $user_id); }
	else{ $ref = paymentCreateCheckout($PDO, $user_id); }
	header("location: {$ref}"); exit;
}

$username = getUsername($PDO, $user_id);
$email = getUserEmail($PDO, $user_id);
$photo = getUserPhoto($PDO, $user_id);

?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<meta http-equiv="x-ua-compatible" content="ie=edge" />
		<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no, shrink-to-fit=no, target-densitydpi=device-dpi" />

		<title>GeoTales – Tales on a map</title>
		<meta name="title" content="GeoTales" />
		<meta name="description" content="Tales on a map" />

		<link rel="icon" href="assets/logo.png" />

		<!-- Load lib/ CSS -->
		<link rel="stylesheet" href="lib/fontawesome/css/all.min.css" />
		<link rel="stylesheet" href="lib/jquery-ui/jquery-ui.min.css" />
		<link rel="stylesheet" href="lib/bootstrap/css/bootstrap.min.css" />

		<!-- Load src/ CSS -->
		<link rel="stylesheet" href="main.css" />

		<style type="text/css">
			html, body {
				/**/
			}

			main {
				margin-top: calc(3rem + 50px);
			}
		</style>
	</head>
	<body>

		<!-- Loading modal -->
		<div class="modal fade" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-scrollable modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="loadingModalLabel">Loading</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<div class="spinner-border text-primary" role="status">
							<span class="visually-hidden">Loading...</span>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Error modal -->
		<div class="modal fade" id="errorModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-scrollable modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="errorModalLabel">Error</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<p>Something went wrong. Please try again.</p>
					</div>
				</div>
			</div>
		</div>



		<header>
			<nav class="navbar navbar-expand-sm navbar-dark fixed-top shadow px-2 px-sm-3 py-1" style="background-color: #eba937;">
				<div class="container">
					<a class="navbar-brand" href="maps.php">
						<img src="assets/logo.png" alt="GeoTales" width="auto" height="30" /><small>eoTales</small>
					</a>

					<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
						<span class="navbar-toggler-icon"></span>
					</button>

					<div class="collapse navbar-collapse" id="navbarContent">
						<ul class="navbar-nav mb-2 mb-sm-0 px-2 px-sm-0 w-100">
							<li class="nav-item ms-sm-auto me-sm-2">
								<a class="nav-link" href="maps.php">
									<i class="fas fa-map"></i> My GeoTales
								</a>
							</li>

							<li class="nav-item dropdown">
								<a class="nav-link dropdown-toggle" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
									<img class="rounded" src="<?php echo $photo; ?>" alt="&nbsp;" width="auto" height="25" />
								</a>
								<ul class="dropdown-menu dropdown-menu-sm-end" aria-labelledby="navbarUserDropdown">
									<li><a class="dropdown-item active" href="profile.php">Profile</a></li>
									<li><hr class="dropdown-divider"></li>
									<li><a class="dropdown-item" href="about.php">About</a></li>
									<li><a class="dropdown-item" href="signout.php">Sign out</a></li>
								</ul>
							</li>
						</ul>
					</div>
				</div>
			</nav>
		</header>

		<main role="main">
			<div class="container" id="main">
				<div class="row my-5">
					<div class="col">
						<hr />
					</div>
				</div>

				<div class="row mb-3">
					<div class="col-sm-8">
						<h2>Edit profile</h2>
						<p class="text-muted">
							Signed in as: <strong><?php echo $username; ?></strong>
						</p>
					</div>
					<div class="col-sm-4">
						<img class="rounded img-fluid" src="<?php echo $photo; ?>" alt="&nbsp;" style="max-height: 100px;" />
					</div>
				</div>

				<div class="row" style="max-width: 550px;">
					<div class="col">
						<form method="post" autocomplete="on" enctype="multipart/form-data" id="edit">
							<input type="hidden" name="op" value="update" />

							<div class="mb-3">
								<label for="username" class="form-label">Username</label>
								<input type="text" name="username" class="form-control" id="username" value="<?php echo $username; ?>" />
							</div>
							<div class="mb-3">
								<label for="email" class="form-label">E-Mail</label>
								<input type="email" name="email" class="form-control" id="email" value="<?php echo $email; ?>" />
							</div>
							<div class="mb-5">
								<label for="photoUpload" class="form-label">Profile picture</label>
								<input type="file" name="photo" class="form-control" id="photoUpload" accept="image/gif, image/jpeg, image/png, image/webp" />
							</div>
							<div class="mb-1">
								<label for="pw1" class="form-label">New password</label>
								<div class="input-group">
									<input type="password" name="pw1" class="form-control" id="pw1" aria-label="New password" aria-describedby="pwShow" />
									<button type="button" class="btn btn-outline-secondary" id="pwShow" title="Toggle password"><i class="fas fa-eye"></i></button>
								</div>
							</div>
							<div class="mb-3">
								<input type="password" name="pw2" class="form-control" id="pw2" aria-label="Confirm password" />
								<label for="pw2" class="form-label small text-muted">Confirm password</label>
							</div>
							<input type="hidden" name="password" />
							<button type="submit" class="btn btn-secondary float-end">Save</button>
						</form>
					</div>
				</div>

				<div class="row my-5">
					<div class="col"></div>
				</div>

				<div class="row">
					<div class="col">
				<?php if($paid) { ?>
						<button type="button" class="btn btn-outline-secondary" id="managePayment">Manage my subscription</button>
						<p class="text-muted mt-3">
							You have a premium account. Change or cancel your subscription here.
						</p>
				<?php }else{ ?>
						<button type="button" class="btn btn-outline-secondary" id="addPayment">Add subscription</button>
						<p class="text-muted mt-3">
							You have a free account. Add a subscription to upgrade.
						</p>
				<?php } ?>
					</div>
				</div>

				<div class="row my-5">
					<div class="col">
						<hr />
					</div>
				</div>

			</div>
		</main>

		<footer class="py-3 shadow" style="background-color: #e6e6e6;">
			<div class="container">
				<div class="row">
					<div class="col-sm-4 mt-2">
						<center>
							<div class="btn-group btn-group-lg" role="group" aria-label="Socials">
								<a role="button" class="btn btn-outline-light" href="https://www.facebook.com/Geotales-107125105285825" target="_blank">
									<i class="fab fa-facebook" style="color: #4267b2;"></i>
								</a>
								<a role="button" class="btn btn-outline-light" href="https://twitter.com/Geotales_io" target="_blank">
									<i class="fab fa-twitter" style="color: #1da1f2;"></i>
								</a>
							</div>
						</center>
					</div>
					<div class="col-sm-4 mt-2">
						<center>
							<img class="d-none d-sm-block" src="assets/logo.png" alt="GeoTales" width="auto" height="40" />
						</center>
					</div>
					<div class="col-sm-4 mt-2">
						<p class="text-muted text-center">© <?php echo date("Y"); ?> <a class="text-decoration-none" href="<?php echo $CONFIG['host']; ?>"><?php echo $CONFIG['host']; ?></a> – all rights reserved</p>
						<p class="text-muted text-center">
							<a class="text-decoration-none" href="terms.php">Terms and conditions</a>
						</p>
						<p class="text-muted text-center">
							<a class="text-decoration-none" href="mailto:<?php echo $CONFIG['email']; ?>"><?php echo $CONFIG['email']; ?></a>
						</p>
					</div>
				</div>
			</div>
		</footer>

		<!-- Load lib/ JS -->
		<script type="text/javascript" src="lib/fontawesome/js/all.min.js"></script>
		<!--script type="text/javascript" src="lib/jquery/jquery-3.6.0.slim.min.js"></script-->
		<script type="text/javascript" src="lib/jquery-ui/external/jquery/jquery.js"></script>
		<script type="text/javascript" src="lib/jquery-ui/jquery-ui.min.js"></script>
		<script type="text/javascript" src="lib/jquery-resizable/jquery-resizable.min.js"></script>
		<script type="text/javascript" src="lib/bootstrap/js/bootstrap.bundle.min.js"></script>
		<script type="text/javascript" src="lib/sjcl/sjcl.js"></script>

		<!-- Load src/ JS -->
		<script type="text/javascript">
			"use strict";

			const _USERNAME = `<?php echo $username; ?>`;

			window.onload = function(ev) {

				$.ajax({
					type: "POST",
					url: "api.php",
					data: { "op": "analytics", "agent": window.navigator ? window.navigator.userAgent : "" },
					dataType: "json",
					success: function(result, status, xhr) { console.log("Analytics registered"); },
					error: function(xhr, status, error) { console.log(xhr.status, error); }
				});

				$("form#edit input#username").change(ev => {
					let el = document.forms.edit.elements;
					let username = $(ev.target).val();

					$.ajax({
						type: "GET",
						url: "api.php",
						data: {
							"op": "user_is_username_unique",
							"username": username
						},
						dataType: "json",
						success: function(result, status, xhr) {
							if(result.isUnique) {
								$(ev.target).removeClass("is-invalid");
								el.username.setCustomValidity("");
							}
							else
							if(username !== _USERNAME) {
								$(ev.target).addClass("is-invalid");
								el.username.setCustomValidity("Username taken");
							}
						},
						error: function(xhr, status, error) {
							console.log(xhr.status, error);
							$("#errorModal").modal("show");
						}
					});
				});

				let pwShow = false;
				$("button#pwShow").click(ev => {
					pwShow = !pwShow;
					$("form#edit input#pw1, form#edit input#pw2").prop("type", pwShow ? "text" : "password");
				});

				$("form#edit input#pw1, form#edit input#pw2").change(ev => {
					let el = document.forms.edit.elements;
					let pw1 = $("form#edit input#pw1").val(),
						pw2 = $("form#edit input#pw2").val();

					if(pw1 !== pw2) {
						$("form#edit input#pw1, form#edit input#pw2").addClass("is-invalid");
						el.pw1.setCustomValidity("Passwords unequal");
						el.pw2.setCustomValidity("Passwords unequal");
					}else{
						$("form#edit input#pw1, form#edit input#pw2").removeClass("is-invalid");
						el.pw1.setCustomValidity("");
						el.pw2.setCustomValidity("");
						$(el.password).val( sjcl.codec.hex.fromBits(sjcl.hash.sha256.hash( el.pw2.value )) );
					}
				});

				$("button#addPayment").click(ev => {
					$("#loadingModal").modal("show");

					$.ajax({
						type: "POST",
						url: "api.php",
						data: { "op": "payment_create_checkout_session" },
						dataType: "json",
						success: function(result, status, xhr) {
							window.location.assign(result.url);
						},
						error: function(xhr, status, error) {
							console.log(xhr.status, error);
							setTimeout(function() { $("#loadingModal").modal("hide"); $("#errorModal").modal("show"); }, 750);
						}
					});
				});

				$("button#managePayment").click(ev => {
					$("#loadingModal").modal("show");

					$.ajax({
						type: "POST",
						url: "api.php",
						data: { "op": "payment_create_portal_session" },
						dataType: "json",
						success: function(result, status, xhr) {
							window.location.assign(result.url);
						},
						error: function(xhr, status, error) {
							console.log(xhr.status, error);
							setTimeout(function() { $("#loadingModal").modal("hide"); $("#errorModal").modal("show"); }, 750);
						}
					});
				});

			};
		</script>

	</body>
</html>
