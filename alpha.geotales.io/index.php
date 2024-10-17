<?php
/*******************************************************************************
* Copyright (C) Nordfjord EDB AS - All Rights Reserved                         *
*                                                                              *
* Unauthorized copying of this file, via any medium is strictly prohibited     *
* Proprietary and confidential                                                 *
* Written by Andreas Atakan <aca@geotales.io>, January 2022                    *
*******************************************************************************/

ini_set('display_errors', 'On'); ini_set('html_errors', 0); error_reporting(-1);

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

$signin_failed = $_GET['signin_failed'] ?? false; $signin_failed = $signin_failed == "true" ? true : false;
$signin_username = null;
if($signin_failed) { $signin_username = $_GET['username']; }

$password_reset = $_GET['password_reset'] ?? false; $password_reset = $password_reset == "true" ? true : false;

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
				background-image: url('assets/background.png');
				background-size: cover;
				background-repeat: no-repeat;
				background-position: center;
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
					<a class="navbar-brand" href="index.php">
						<img src="assets/logo.png" alt="GeoTales" width="auto" height="30" />
					</a>
				</div>
			</nav>
		</header>

		<main role="main">
			<div class="container" id="main">
				<div class="row my-5">
					<div class="col"></div>
				</div>

				<div class="row mx-auto" style="max-width: 350px;">
					<div class="col">
						<p class="lead mb-0">GeoTales Alpha</p>
						<p>Log in to the previous version of GeoTales</p>

					<?php if($signin_failed) { ?>
						<div role="alert" class="alert alert-danger alert-dismissible fade show">
							Username or password is <strong>incorrect</strong>.
							<a href="signreset.php?return_url=<?php echo $loc; ?>&username=<?php echo $signin_username; ?>">Reset password</a>
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
						</div>
					<?php } ?>

					<?php if($password_reset) { ?>
						<div role="alert" class="alert alert-info alert-dismissible fade show">
							Your password was reset and your new password was sent by email.
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
						</div>
					<?php } ?>

						<form action="signauth.php" method="post" autocomplete="on" id="signin">
							<div class="mb-3">
								<label for="username" class="form-label">Username</label>
								<input type="text" name="username" class="form-control" id="username" required />
							</div>
							<div class="mb-3">
								<label for="pw" class="form-label">Password</label>
								<div class="input-group">
									<input type="password" name="pw" class="form-control" id="pw" aria-label="Password" aria-describedby="pwShow" required />
									<button type="button" class="btn btn-outline-secondary" id="pwShow" title="Toggle password"><i class="fas fa-eye"></i></button>
								</div>
							</div>
							<input type="hidden" name="password" />
							<input type="hidden" name="return_url" value="<?php echo $loc; ?>" />
							<button type="submit" class="btn btn-primary">Sign in</button>
						</form>

						<p class="text-muted mt-3">
							Don't have an account? <strong><a href="signup.php?return_url=<?php echo $loc; ?>">Sign up here</a></strong>
						</p>

						<p class="text-muted mb-3">
							<strong><a href="signreset.php?return_url=<?php echo $loc; ?>">Reset password</a></strong>
						</p>
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

			window.onload = function(ev) {

				$.ajax({
					type: "POST",
					url: "api.php",
					data: { "op": "analytics", "agent": window.navigator ? window.navigator.userAgent : "" },
					dataType: "json",
					success: function(result, status, xhr) { console.log("Analytics registered"); },
					error: function(xhr, status, error) { console.log(xhr.status, error); }
				});

				let pwShow = false;
				$("button#pwShow").click(ev => {
					pwShow = !pwShow;
					$("form#signin input#pw").prop("type", pwShow ? "text" : "password");
				});

				document.forms.signin.onsubmit = function(ev) { ev.preventDefault();
					let form = ev.target;
					let el = form.elements;

					$(el.password).val( sjcl.codec.hex.fromBits(sjcl.hash.sha256.hash( el.pw.value )) );
					form.submit();
				};

			};
		</script>

	</body>
</html>
