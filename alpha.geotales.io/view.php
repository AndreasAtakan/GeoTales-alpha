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

$logged_in = false; $photo = ""; $paid = false;
if(isset($_SESSION['user_id']) && validUserID($PDO, $_SESSION['user_id'])) {
	$logged_in = true;
	$user_id = $_SESSION['user_id'];
	$username = getUsername($PDO, $user_id);
	$photo = getUserPhoto($PDO, $user_id);
	$paid = getUserPaid($PDO, $user_id);
}

if(!isset($_GET['id'])) {
	http_response_code(422); exit;
}
$id = $_GET['id'];


$op = $_REQUEST['op'] ?? null;
if($op == "clone") {
	if(!$logged_in) { http_response_code(401); exit; }

	if(!isset($_POST['password'])) {
		http_response_code(422); exit;
	}
	$password = $_POST['password'];

	$id = mapClone($PDO, $user_id, $id, $password);
	if(!$id) {
		$checkout = paymentCreateCheckout($PDO, $user_id);
		header("location: {$checkout}"); exit;
	}

	header("location: edit.php?id={$id}"); exit;
}


$stmt = $PDO->prepare("SELECT title, description, thumbnail, published_date FROM \"Map\" WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();


$link = "{$CONFIG['host']}/view.php?id={$id}";
$embedLink = "<iframe src=\"{$CONFIG['host']}/pres.php?id={$id}\" width=\"100%\" height=\"650\" allowfullscreen=\"true\" style=\"border:none !important;\"></iframe>";

?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<meta http-equiv="x-ua-compatible" content="ie=edge" />
		<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no, shrink-to-fit=no, target-densitydpi=device-dpi" />

		<title>GeoTales – <?php echo $row['title']; ?></title>
		<meta name="title" content="GeoTales – <?php echo $row['title']; ?>" />
		<meta name="description" content="<?php echo $row['description']; ?>" />

		<!-- Open Graph / Facebook -->
		<meta property="og:type" content="website" />
		<meta property="og:url" content="https://geotales.io/" />
		<meta property="og:title" content="GeoTales – <?php echo $row['title']; ?>" />
		<meta property="og:description" content="<?php echo $row['description']; ?>" />
		<meta property="og:site_name" content="GeoTales" />
		<meta property="og:image" content="<?php echo $row['thumbnail']; ?>" />
		<!--meta property="og:image:type" content="image/png" /-->

		<!-- Twitter -->
		<meta property="twitter:card" content="summary_large_image" />
		<meta name="twitter:site" content="@Geotales_io" />
		<meta name="twitter:creator" content="@Geotales_io" />
		<meta property="twitter:url" content="https://geotales.io/" />
		<meta property="twitter:title" content="GeoTales – <?php echo $row['title']; ?>" />
		<meta property="twitter:description" content="<?php echo $row['description']; ?>" />
		<meta property="twitter:image" content="<?php echo $row['thumbnail']; ?>" />

		<link rel="icon" href="assets/logo.png" />

		<!-- Load lib/ CSS -->
		<link rel="stylesheet" href="lib/fontawesome/css/all.min.css" />
		<link rel="stylesheet" href="lib/jquery-ui/jquery-ui.min.css" />
		<link rel="stylesheet" href="lib/bootstrap/css/bootstrap.min.css" />

		<!-- Load src/ CSS -->
		<link rel="stylesheet" href="main.css" />

		<style type="text/css">
			:root {
				--app-height: 100%;
			}

			html, body {
				/**/
			}
			html.noOverflow { overflow-y: hidden; }
			body.noOverflow { overflow-y: hidden; }

			#main { background-color: #e6e6e6; }

			#header { height: 39px; }
			#content {
				height: calc(100vh - 39px);
			}

			#mapSection { overflow-y: hidden; }

			.fullscreen#mapSection {
				position: absolute;
				top: 0;
				left: 0;
				width: 100vw;
				height: 100vh;
				height: var(--app-height);
				z-index: 1031;
				padding: 0 !important;
			}

			#infoTab .nav-link { color: grey; }
			#infoTab .nav-link.active { background-color: grey; color: white; }

			@media (max-width: 575.98px) {
				#content { height: auto; }
				#mapSection { min-height: calc(100vh - 39px); }
			}
		</style>
	</head>
	<body>

		<!-- Share modal -->
		<div class="modal fade" id="shareModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-scrollable modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="shareModalLabel">Share</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<div class="container-fluid">
							<div class="row mb-1">
								<div class="col">
									<div class="input-group input-group-lg">
										<input type="text" class="form-control" id="linkInput" aria-label="linkInput" aria-describedby="copyLink" readonly value="<?php echo $link; ?>" />
										<button class="btn btn-outline-secondary" type="button" id="copyLink" title="Copy to clipboard"><i class="fas fa-copy"></i></button>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col">
									<div class="input-group input-group-sm">
										<input type="text" class="form-control" id="embedInput" aria-label="embedInput" aria-describedby="copyEmbed" readonly value="" />
										<button class="btn btn-outline-secondary" type="button" id="copyEmbed" title="Copy to clipboard"><i class="fas fa-copy"></i></button>
									</div>
								</div>
							</div>

							<div class="row my-3">
								<hr />
							</div>

							<div class="row">
								<div class="col col-md-7">
							<?php if($logged_in) { ?>
						<form method="post" id="clone">
									<input type="hidden" name="op" value="clone" />
									<input type="hidden" name="password" value="" />
									<button type="submit" class="btn btn-sm btn-outline-secondary mb-2" title="Make a clone of this GeoTale">Clone</button>
						</form>
							<?php } ?>
								</div>
								<div class="col col-md-1">
									<a role="button" class="btn btn-lg btn-outline-light" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $link; ?>" id="facebook" target="_blank"><i class="fab fa-facebook" style="color: #4267B2;"></i></a>
								</div>
								<div class="col col-md-1">
									<a role="button" class="btn btn-lg btn-outline-light" href="https://twitter.com/intent/tweet?url=<?php echo $link; ?>&text=" id="twitter" target="_blank"><i class="fab fa-twitter" style="color: #1DA1F2;"></i></a>
								</div>
								<div class="col col-md-1">
									<a role="button" class="btn btn-lg btn-outline-light" href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo $link; ?>" id="linkedin" target="_blank"><i class="fab fa-linkedin" style="color: #0072b1;"></i></a>
								</div>
								<div class="col col-md-1">
									<a role="button" class="btn btn-lg btn-outline-light" href="https://pinterest.com/pin/create/button/?url=<?php echo $link; ?>&media=&description=" id="pinterest" target="_blank"><i class="fab fa-pinterest" style="color: #E60023;"></i></a>
								</div>
								<div class="col col-md-1">
									<a role="button" class="btn btn-lg btn-outline-light" href="mailto:?&subject=&cc=&bcc=&body=<?php echo $link; ?>%0A" id="email"><i class="fas fa-envelope" style="color: grey;"></i></a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

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



		<div class="container-fluid p-0" id="main">
			<div class="row g-0" id="header">
				<div class="col">
					<nav class="navbar navbar-expand-sm navbar-dark fixed-top shadow px-2" style="background-color: #eba937; padding-top: 0.25rem; padding-bottom: 0.25rem;">
						<a class="navbar-brand py-0" href="index.php" style="line-height: 0;">
							<img src="assets/logo.png" alt="GeoTales" width="auto" height="20" />
						</a>

						<button class="navbar-toggler py-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
							<span class="navbar-toggler-icon"></span>
						</button>

						<div class="collapse navbar-collapse" id="navbarContent">
							<ul class="navbar-nav mb-0 px-0 w-100">
						<?php if($logged_in) { ?>
								<li class="nav-item dropdown ms-sm-auto">
									<a class="nav-link dropdown-toggle py-1 py-sm-0" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
										<img class="rounded" src="<?php echo $photo; ?>" alt="&nbsp;" width="auto" height="31" />
									</a>
									<ul class="dropdown-menu dropdown-menu-sm-end" aria-labelledby="navbarUserDropdown">
										<li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#shareModal">Share</a></li>
										<li><hr class="dropdown-divider" /></li>
										<li><a class="dropdown-item" href="maps.php">My GeoTales</a></li>
										<li><a class="dropdown-item" href="profile.php">Profile</a></li>
										<li><hr class="dropdown-divider" /></li>
										<li><a class="dropdown-item" href="about.php">About</a></li>
										<li><a class="dropdown-item" href="signout.php">Sign out</a></li>
									</ul>
								</li>
						<?php }else{ ?>
								<li class="nav-item ms-sm-auto">
									<a role="button" class="btn btn-sm btn-light my-1 my-sm-0" href="index.php?return_url=view.php?id=<?php echo $id; ?>">Sign in</a>
								</li>
						<?php } ?>
							</ul>
						</div>
					</nav>
				</div>
			</div>

			<div class="row g-0" id="content">
				<div class="col" id="mapSection">
					<iframe id="pres" src="pres.php?id=<?php echo $id; ?>" width="100%" height="100%" allowfullscreen="true" style="border: none !important;"></iframe>
				</div>
			</div>
		</div>

		<!-- Load lib/ JS -->
		<script type="text/javascript" src="lib/fontawesome/js/all.min.js"></script>
		<!--script type="text/javascript" src="lib/jquery/jquery-3.6.0.slim.min.js"></script-->
		<script type="text/javascript" src="lib/jquery-ui/external/jquery/jquery.js"></script>
		<script type="text/javascript" src="lib/jquery-ui/jquery-ui.min.js"></script>
		<script type="text/javascript" src="lib/jquery-resizable/jquery-resizable.min.js"></script>
		<script type="text/javascript" src="lib/bootstrap/js/bootstrap.bundle.min.js"></script>

		<!-- Load src/ JS -->
		<script type="text/javascript">
			"use strict";

			window.onload = function(ev) {

				const _ID = `<?php echo $id; ?>`,
					  _USERNAME = `<?php echo $username; ?>`,
					  _USER_PHOTO = `<?php echo $photo; ?>`;

				$("#shareModal input#embedInput").val(`<?php echo $embedLink; ?>`);

				$.ajax({
					type: "POST",
					url: "api.php",
					data: { "op": "analytics", "agent": window.navigator ? window.navigator.userAgent : "" },
					dataType: "json",
					success: function(result, status, xhr) { console.log("Analytics registered"); },
					error: function(xhr, status, error) { console.log(xhr.status, error); }
				});

				const appHeight = ev => { document.documentElement.style.setProperty("--app-height", `${window.innerHeight}px`); };
				$(window).on("resize", appHeight); appHeight();

				window.addEventListener("message", function(ev) {
					if(ev.data == "fullscreenEnter") {
						$("html, body").addClass("noOverflow");
						$("#mapSection").addClass("fullscreen");
					}
					else
					if(ev.data == "fullscreenExit") {
						$("html, body").removeClass("noOverflow");
						$("#mapSection").removeClass("fullscreen");
					}
				});

				$("#shareModal button#copyLink").click(ev => {  navigator.clipboard.writeText( $("#shareModal input#linkInput").val() ); });
				$("#shareModal button#copyEmbed").click(ev => {  navigator.clipboard.writeText( $("#shareModal input#embedInput").val() ); });
				if(document.forms.clone) {
					document.forms.clone.onsubmit = function(ev) { ev.preventDefault();
						$(ev.target.elements.password).val(
							$("iframe#pres")[0].contentWindow["_PASSWORD"]
						);
						ev.target.submit();
					};
				}

			};
		</script>

	</body>
</html>
