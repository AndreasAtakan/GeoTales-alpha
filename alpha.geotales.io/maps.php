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

// Not logged in
if(!isset($_SESSION['user_id']) || !validUserID($PDO, $_SESSION['user_id'])) {
	header("location: index.php?return_url=maps.php"); exit;
}
$user_id = $_SESSION['user_id'];
$username = getUsername($PDO, $user_id);
$photo = getUserPhoto($PDO, $user_id);


$op = $_REQUEST['op'] ?? null;
if($op == "create") {
	if(!isset($_POST['title'])
	|| !isset($_POST['description'])
	|| !isset($_POST['password'])) { http_response_code(422); exit; }

	$title = sanitize($_POST['title']);
	$description = sanitize($_POST['description']);
	$thumbnail = uploadCreate($PDO, $user_id, "thumbnail", $_FILES["thumbnail"]["tmp_name"], $_FILES["thumbnail"]["name"]);
	$password = $_POST['password']; mb_substr($password, 0, 64);

	$id = mapCreate($PDO, $user_id, $title, $description, $thumbnail, $password);
	if(!$id) {
		$checkout = paymentCreateCheckout($PDO, $user_id);
		header("location: {$checkout}"); exit;
	}

	header("location: edit.php?id={$id}"); exit;
}
else
if($op == "edit") {
	if(!isset($_POST['id'])
	|| !isset($_POST['title'])
	|| !isset($_POST['description'])
	|| !isset($_POST['password'])) { http_response_code(422); exit; }
	$id = $_POST['id'];

	if(!userMapCanWrite($PDO, $user_id, $id)) { http_response_code(401); exit; }

	$title = sanitize($_POST['title']);
	$description = sanitize($_POST['description']);
	$thumbnail = uploadCreate($PDO, $user_id, "thumbnail", $_FILES["thumbnail"]["tmp_name"], $_FILES["thumbnail"]["name"]);
	$password = $_POST['password']; mb_substr($password, 0, 64);

	$r = mapUpdate($PDO, $id, $title, $description, $thumbnail, $password);
	if(!$r) { http_response_code(500); exit; }
}
else
if($op == "delete") {
	if(!isset($_POST['id'])) { http_response_code(422); exit; }
	$id = $_POST['id'];
	if(!userMapCanWrite($PDO, $user_id, $id)) { http_response_code(401); exit; }

	$r = mapDelete($PDO, $id);
	if(!$r) { http_response_code(500); exit; }
}
else
if($op == "republish") {
	if(!isset($_POST['id'])) { http_response_code(422); exit; }
	$id = $_POST['id'];
	if(!userMapCanWrite($PDO, $user_id, $id)) { http_response_code(401); exit; }

	mapRepublish($PDO, $id);
}


$search = "%";
if(isset($_GET['search'])) { $search .= "{$_GET['search']}%"; }

$stmt = $PDO->prepare("
	SELECT
		M.id AS id,
		M.title AS title,
		M.description AS description,
		M.created_date AS created_date,
		M.thumbnail AS thumbnail,
		M.published_date IS NOT NULL AS published
	FROM
		\"User_Map\" AS UM INNER JOIN
		\"Map\" AS M
			ON UM.map_id = M.id
	WHERE
		UM.status IN ('owner', 'editor') AND
		UM.user_id = ? AND
		LOWER(M.title) LIKE LOWER(?)
	ORDER BY
		M.created_date DESC
");
$stmt->execute([$user_id, $search]);
$rows = $stmt->fetchAll();
$count = $stmt->rowCount();

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

		<!-- New map modal -->
		<div class="modal fade" id="newModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" tabindex="-1" aria-labelledby="newModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-scrollable modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="newModalLabel">New</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
			<form method="post" enctype="multipart/form-data">
					<div class="modal-body">
						<div class="container-fluid">
							<input type="hidden" name="op" value="create" />
							<input type="hidden" name="password" value="" />

							<div class="row mb-3">
								<div class="col">
									<label for="titleInput" class="form-label">Title</label>
									<input type="text" class="form-control" name="title" id="titleInput" aria-describedby="titleHelp" maxlength="65" />
									<div id="titleHelp" class="form-text">Max 65 characters</div>
								</div>
							</div>

							<div class="row mb-3">
								<div class="col">
									<label for="descriptionInput" class="form-label">Description</label>
									<textarea class="form-control" name="description" id="descriptionInput" rows="5"></textarea>
								</div>
							</div>

							<div class="row mb-3">
								<div class="col">
									<label for="passwordInput" class="form-label">Password</label>
									<input type="text" class="form-control form-control-sm" id="passwordInput" aria-describedby="passwordHelp" />
									<div id="passwordHelp" class="form-text">Will be required when viewing the GeoTale</div>
								</div>
								<div class="col">
									<label for="thumbnailInput" class="form-label">Thumbnail</label>
									<input type="file" class="form-control form-control-sm" name="thumbnail" id="thumbnailInput" accept="image/gif, image/jpeg, image/png, image/webp" />
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
						<button type="submit" class="btn btn-primary">Create</button>
					</div>
			</form>
				</div>
			</div>
		</div>

<?php
if($count > 0) {
	foreach($rows as $row) {
?>
		<!-- Edit modal -->
		<div class="modal fade editModal" id="editModal_<?php echo $row['id']; ?>" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-scrollable modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="editModalLabel">Edit</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
			<form method="post" enctype="multipart/form-data">
					<div class="modal-body">
						<div class="container-fluid">
							<input type="hidden" name="op" value="edit" />
							<input type="hidden" name="id" value="<?php echo $row['id']; ?>" />
							<input type="hidden" name="password" value="" />

							<div class="row mb-3">
								<div class="col">
									<label for="titleInput" class="form-label">Title</label>
									<input type="text" class="form-control" name="title" id="titleInput" aria-describedby="titleHelp" maxlength="65" value="<?php echo $row['title']; ?>" />
									<div id="titleHelp" class="form-text">Max 65 characters</div>
								</div>
							</div>

							<div class="row mb-3">
								<div class="col">
									<label for="descriptionInput" class="form-label">Description</label>
									<textarea class="form-control" name="description" id="descriptionInput" rows="5"><?php echo $row['description']; ?></textarea>
								</div>
							</div>

							<div class="row mb-3">
								<div class="col">
									<label for="passwordInput" class="form-label">Password</label>
									<div class="input-group input-group-sm">
										<button type="button" class="btn btn-outline-secondary" id="pwRemove" title="Remove password" data-id="<?php echo $row['id']; ?>"><i class="fas fa-minus"></i></button>
										<input type="text" class="form-control" id="passwordInput" aria-describedby="passwordHelp" data-id="<?php echo $row['id']; ?>" />
									</div>
									<div id="passwordHelp" class="form-text">Will be required when viewing the GeoTale</div>
								</div>
								<div class="col">
									<label for="thumbnailInput" class="form-label">Thumbnail</label>
									<input type="file" class="form-control form-control-sm" name="thumbnail" id="thumbnailInput" accept="image/gif, image/jpeg, image/png, image/webp" />
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
						<button type="submit" class="btn btn-primary">Save changes</button>
					</div>
			</form>
				</div>
			</div>
		</div>

<?php $link = "{$CONFIG['host']}/view.php?id={$row['id']}"; ?>
		<!-- Share modal -->
		<div class="modal fade shareModal" id="shareModal_<?php echo $row['id']; ?>" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
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
										<button type="button" class="btn btn-outline-secondary" id="copyLink" title="Copy to clipboard" data-id="<?php echo $row['id']; ?>"><i class="fas fa-copy"></i></button>
									</div>
								</div>
							</div>

							<div class="row">
								<div class="col">
									<div class="input-group input-group-sm">
										<input type="text" class="form-control" id="embedInput" aria-label="embedInput" aria-describedby="copyEmbed" readonly value="" />
										<button type="button" class="btn btn-outline-secondary" id="copyEmbed" title="Copy to clipboard" data-id="<?php echo $row['id']; ?>"><i class="fas fa-copy"></i></button>
									</div>
								</div>
							</div>

							<div class="row my-3">
								<hr />
							</div>

							<div class="row">
								<div class="col-sm-7">
						<form method="post">
									<input type="hidden" name="op" value="republish" />
									<input type="hidden" name="id" value="<?php echo $row['id']; ?>" />
									<button type="submit" class="btn btn-sm btn-outline-secondary" title="<?php echo $row['published'] ? "GeoTale no longer visible on the home-page" : "Will make your GeoTale visible on the home-page"; ?>"><?php echo $row['published'] ? "Unpublish" : "Publish to gallery"; ?></button>
						</form>
								</div>
								<div class="col-sm-1">
									<a role="button" class="btn btn-outline-light" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $link; ?>" id="facebook" target="_blank"><i class="fab fa-facebook" style="color: #4267B2;"></i></a>
								</div>
								<div class="col-sm-1">
									<a role="button" class="btn btn-outline-light" href="https://twitter.com/intent/tweet?url=<?php echo $link; ?>&text=" id="twitter" target="_blank"><i class="fab fa-twitter" style="color: #1DA1F2;"></i></a>
								</div>
								<div class="col-sm-1">
									<a role="button" class="btn btn-outline-light" href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo $link; ?>" id="linkedin" target="_blank"><i class="fab fa-linkedin" style="color: #0072b1;"></i></a>
								</div>
								<div class="col-sm-1">
									<a role="button" class="btn btn-outline-light" href="https://pinterest.com/pin/create/button/?url=<?php echo $link; ?>&media=&description=" id="pinterest" target="_blank"><i class="fab fa-pinterest" style="color: #E60023;"></i></a>
								</div>
								<div class="col-sm-1">
									<a role="button" class="btn btn-outline-light" href="mailto:?&subject=&cc=&bcc=&body=<?php echo $link; ?>%0A" id="email"><i class="fas fa-envelope" style="color: grey;"></i></a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Delete modal -->
		<div class="modal fade" id="deleteModal_<?php echo $row['id']; ?>" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-scrollable modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="deleteModalLabel">Delete GeoTale</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<p>Are you sure you want to delete? This can not be undone.</p>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
			<form method="post">
						<input type="hidden" name="op" value="delete" />
						<input type="hidden" name="id" value="<?php echo $row['id']; ?>" />
						<button type="submit" class="btn btn-danger">Delete</button>
			</form>
					</div>
				</div>
			</div>
		</div>
<?php
	}
}
?>

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
								<a class="nav-link active" aria-current="page" href="maps.php">
									<i class="fas fa-map"></i> My GeoTales
								</a>
							</li>

							<li class="nav-item dropdown">
								<a class="nav-link dropdown-toggle" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
									<img class="rounded" src="<?php echo $photo; ?>" alt="&nbsp;" width="auto" height="25" />
								</a>
								<ul class="dropdown-menu dropdown-menu-sm-end" aria-labelledby="navbarUserDropdown">
									<li><a class="dropdown-item" href="profile.php">Profile</a></li>
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

				<div class="row">
					<div class="col">
						<form method="get">
							<div class="row mb-2">
								<div class="col-sm-3 order-sm-2 mb-4 mb-sm-0">
									<button type="button" class="btn btn-primary float-sm-end mt-2 mt-sm-0" data-bs-toggle="modal" data-bs-target="#newModal">New GeoTale</button>
								</div>
								<div class="col-sm-9 order-sm-1">
									<div class="input-group d-inline-flex" style="max-width: 650px;">
										<a role="button" class="btn btn-outline-secondary" href="maps.php" title="Clear search"><i class="fas fa-minus"></i></a>
										<input type="text" class="form-control" name="search" placeholder="Search title" aria-label="search" aria-describedby="search-button" value="<?php echo $_GET['search'] ?? ""; ?>" />
										<button type="submit" class="btn btn-secondary" id="search-button">Search</button>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>

				<div class="row my-4">
					<div class="col"></div>
				</div>

				<div class="row">
					<div class="col">
						<div class="table-responsive" style="min-height: 300px;">
							<table class="table table-striped table-hover">
						<?php
							if($count > 0) {
						?>
								<caption>Your GeoTales</caption>
								<thead>
									<tr>
										<th scope="col">#</th>
										<th scope="col"></th>
										<th scope="col"></th>
										<th scope="col"></th>
										<th scope="col"></th>
										<th scope="col"></th>
									</tr>
								</thead>
								<tbody>
						<?php
								foreach($rows as $row) {
									$created_date = date_format(date_create($row['created_date']), "d.M Y, H:i");
						?>
									<tr>
										<th style="width: 8.33%;" scope="row">
											<img class="img-fluid" src="<?php echo $row['thumbnail']; ?>" alt="#" />
										</th>
										<td style="width: 16.66%;"><?php echo $row['title']; ?></td>
										<td style="width: 25%; max-width: 65px;" class="text-truncate"><?php echo $row['description']; ?></td>
										<td style="width: 16.66%;"><?php echo $created_date; ?></td>
										<td style="width: 8.33%;">
											<div class="btn-group btn-group-sm" role="group" aria-label="view-edit">
												<a role="button" class="btn btn-outline-secondary" href="edit.php?id=<?php echo $row['id']; ?>">Edit</a>
												<a role="button" class="btn btn-outline-secondary" href="view.php?id=<?php echo $row['id']; ?>">View</a>
											</div>
										</td>
										<td style="width: 8.33%;">
											<div class="dropdown float-end">
												<button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="optionsDropdown<?php echo $row['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
													<i class="fas fa-ellipsis-v"></i>
												</button>
												<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="optionsDropdown<?php echo $row['id']; ?>" style="min-width: 0;">
													<li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editModal_<?php echo $row['id']; ?>"><i class="fas fa-pen"></i></button></li>
													<li><button type="button" class="dropdown-item" id="share" data-id="<?php echo $row['id']; ?>" data-bs-toggle="modal" data-bs-target="#shareModal_<?php echo $row['id']; ?>"><i class="fas fa-share-alt"></i></button></li>
													<li><hr class="dropdown-divider"></li>
													<li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#deleteModal_<?php echo $row['id']; ?>"><i class="fas fa-trash"></i></button></li>
												</ul>
											</div>
										</td>
									</tr>
						<?php } ?>
								</tbody>
						<?php }else{ ?>
								<caption>No GeoTales found</caption>
						<?php } ?>
							</table>
						</div>
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

			window.onload = function(ev) {

				$.ajax({
					type: "POST",
					url: "api.php",
					data: { "op": "analytics", "agent": window.navigator ? window.navigator.userAgent : "" },
					dataType: "json",
					success: function(result, status, xhr) { console.log("Analytics registered"); },
					error: function(xhr, status, error) { console.log(xhr.status, error); }
				});

				$("#newModal input#titleInput, #editModal input#titleInput").change(ev => {
					let v = $(ev.target).val();
					if(v.length > 65) { $(ev.target).val(v.substring(0, 65)); }
				});

				$("#newModal input#passwordInput").change(ev => {
					let v = $(ev.target).val();
					$("#newModal input[name=\"password\"]").val(
						v === "" ? null : sjcl.codec.hex.fromBits(sjcl.hash.sha256.hash( v ))
					);
				});
				$(".editModal input#passwordInput").change(ev => {
					let v = $(ev.target).val();
					$(`#editModal_${$(ev.target).data("id")} input[name=\"password\"]`).val(
						v === "" ? null : sjcl.codec.hex.fromBits(sjcl.hash.sha256.hash( v ))
					);
				});

				$("button#share").click(ev => {
					let id = $(ev.target).data("id") || $(ev.target).parents("button").data("id"), host = window.location.host;
					$(`#shareModal_${id} input#embedInput`).val(`<iframe src="https://${host}/pres.php?id=${id}" width="100%" height="450" allowfullscreen="true" style="border:none !important;"></iframe>`);
				});
				$(".shareModal button#copyLink").click(ev => {
					let id = $(ev.target).data("id") || $(ev.target).parents("button").data("id");
					navigator.clipboard.writeText( $(`#shareModal_${id} input#linkInput`).val() );
				});
				$(".shareModal button#copyEmbed").click(ev => {
					let id = $(ev.target).data("id") || $(ev.target).parents("button").data("id");
					navigator.clipboard.writeText( $(`#shareModal_${id} input#embedInput`).val() );
				});

				$(".editModal button#pwRemove").click(ev => {
					let id = $(ev.target).data("id") || $(ev.target).parents("button").data("id");

					$("#loadingModal").modal("show");

					$.ajax({
						type: "POST",
						url: "api.php",
						data: { "op": "map_password_remove", "id": id },
						dataType: "json",
						success: function(result, status, xhr) {
							setTimeout(function() { $("#loadingModal").modal("hide"); }, 750);
						},
						error: function(xhr, status, error) {
							console.error(xhr.status, error);
							setTimeout(function() { $(`#editModal_${id}`).modal("hide"); $("#loadingModal").modal("hide"); $("#errorModal").modal("show"); }, 750);
						}
					});
				});

			};
		</script>

	</body>
</html>
