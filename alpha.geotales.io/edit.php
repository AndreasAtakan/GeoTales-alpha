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
$paid = getUserPaid($PDO, $user_id);


if(!isset($_GET['id'])) {
	http_response_code(422); exit;
}
$id = $_GET['id'];


$stmt = $PDO->prepare("SELECT title, description FROM \"Map\" WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();

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

		<link rel="icon" href="assets/logo.png" />

		<!-- Load lib/ CSS -->
		<link rel="stylesheet" href="lib/fontawesome/css/all.min.css" />
		<link rel="stylesheet" href="lib/jquery-ui/jquery-ui.min.css" />
		<link rel="stylesheet" href="lib/bootstrap/css/bootstrap.min.css" />
		<link rel="stylesheet" href="lib/leaflet/leaflet.css" />
		<!--link rel="stylesheet" href="lib/leaflet.fullscreen/leaflet.fullscreen.css" /-->
		<link rel="stylesheet" href="lib/leaflet.zoomhome/leaflet.zoomhome.css" />
		<!--link rel="stylesheet" href="lib/leaflet.locatecontrol/L.Control.Locate.min.css" /-->
		<!--link rel="stylesheet" href="lib/leaflet.draw/leaflet.draw.css" /-->
		<link rel="stylesheet" href="lib/leaflet.geoman/leaflet-geoman.css" />
		<link rel="stylesheet" href="lib/leaflet.easybutton/easy-button.css" />
		<!--link rel="stylesheet" href="lib/leaflet.htmllegend/L.Control.HtmlLegend.css" /-->
		<link rel="stylesheet" href="lib/leaflet.contextmenu/leaflet.contextmenu.min.css" />
		<!--link rel="stylesheet" href="lib/leaflet.centercontrol/leaflet-control-topcenter.css" /-->
		<!--link rel="stylesheet" href="lib/prism/prism.css" /-->
		<link rel="stylesheet" href="lib/trumbowyg/ui/trumbowyg.min.css" />
		<link rel="stylesheet" href="lib/trumbowyg/plugins/colors/ui/trumbowyg.colors.min.css" />
		<!--link rel="stylesheet" href="lib/trumbowyg/plugins/highlight/ui/trumbowyg.highlight.min.css" /-->
		<link rel="stylesheet" href="lib/trumbowyg/plugins/specialchars/ui/trumbowyg.specialchars.min.css" />
		<link rel="stylesheet" href="lib/trumbowyg/plugins/table/ui/trumbowyg.table.min.css" />

		<!-- Load src/ CSS -->
		<link rel="stylesheet" href="assets/main_edit_1666621876.css" />
	</head>
	<body>

		<!-- Project import -->
		<input type="file" class="form-control" id="projectFileInput" style="display: none;" />

		<!-- GeoJSON import modal -->
		<div class="modal fade" id="geojsonImportModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" tabindex="-1" aria-labelledby="geojsonImportModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-scrollable modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="geojsonImportModalLabel">Import GeoJSON</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<div class="container-fluid">
							<div class="row">
								<div class="col col-md-10">
									<label for="fileInput" class="form-label">Choose GeoJSON-file</label>
									<input type="file" class="form-control form-control-sm" id="fileInput" accept="application/json,.json,application/geo+json,.geojson" />
								</div>
							</div>
							<div class="row mt-3">
								<div class="col col-md-10">
									<label for="lineThickness" class="form-label">Line options</label>
									<div class="input-group input-group-sm">
										<input type="color" class="form-control form-control-color" id="lineColor" value="#563d7c" title="Choose color" />
										<input type="number" min="1" max="8" class="form-control" id="lineThickness" placeholder="Thickness" />
										<input type="number" min="0" max="0.9" step="0.1" class="form-control" id="lineTransparency" placeholder="Transparency" />
									</div>
								</div>
							</div>
							<div class="row mt-3">
								<div class="col col-md-10">
									<label for="fillTransparency" class="form-label">Fill options</label>
									<div class="input-group input-group-sm">
										<input type="color" class="form-control form-control-color" id="fillColor" value="#563d7c" title="Choose color" />
										<input type="number" min="0" max="1" step="0.1" class="form-control" id="fillTransparency" placeholder="Transparency" />
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
						<button type="button" class="btn btn-secondary" id="import" <?php echo !$paid ? "disabled" : ""; ?>>Import</button>
					</div>
				</div>
			</div>
		</div>

		<!-- GEDCOM import modal -->
		<div class="modal fade" id="gedcomImportModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" tabindex="-1" aria-labelledby="gedcomImportModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-scrollable modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="gedcomImportModalLabel">Import GEDCOM</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<div class="mb-3">
							<label for="fileInput" class="form-label">Choose GEDCOM-file</label>
							<input type="file" class="form-control form-control-sm" id="fileInput" />
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
						<button type="button" class="btn btn-secondary" id="import" <?php echo !$paid ? "disabled" : ""; ?>>Import</button>
					</div>
				</div>
			</div>
		</div>



		<!-- Options modal -->
		<div class="modal fade" id="optionsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" tabindex="-1" aria-labelledby="optionsModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-scrollable modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="optionsModalLabel">Options</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<div class="container-fluid">
							<div class="row">
								<div class="col">
									<label for="animationSpeed" class="form-label">Default animation speed (sec.)</label>
									<input type="number" class="form-control" id="animationSpeed" min="0.2" max="6" step="0.1" value="2" title="Default speed of map-object animations" />
								</div>
								<div class="col">
									<label for="panningSpeed" class="form-label">Map panning speed (sec.)</label>
									<input type="number" class="form-control" id="panningSpeed" min="0.5" max="8" step="0.1" placeholder="auto" title="Speed of map-panning between scenes. Auto means the speed will depend on distance" />
								</div>
							</div>
							<div class="row">
								<div class="col col-md-10 mt-4">
									<label for="aspectRatio" class="form-label">Map aspect ratio</label>
									<select class="form-select" id="aspectRatio" aria-label="Map aspect ratio">
										<option value="" selected disabled></option>
										<option value="16/9">16/9</option>
										<option value="4/3">4/3</option>
										<option value="9/16">9/16</option>
									</select>
								</div>
							</div>
							<div class="row">
								<div class="col mt-4">
									<div class="form-check">
										<input type="checkbox" class="form-check-input" value="" id="objectsOptIn">
										<label for="objectsOptIn" class="form-check-label">
											Keep map-objects when creating new scene
										</label>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
					</div>
				</div>
			</div>
		</div>



		<!-- Basemap modal -->
		<div class="modal fade" id="basemapModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" tabindex="-1" aria-labelledby="basemapModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-scrollable modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="basemapModalLabel">Basemap</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<div class="container-fluid">
							<input type="file" class="form-control form-control-sm" id="basemapFile" accept="image/gif, image/jpeg, image/png, image/webp" style="display: none;" />

							<div class="row mt-2">
								<div class="col">
									<div class="accordion" id="basemapAccordion">
										<div class="accordion-item">
											<h2 class="accordion-header" id="availBasemapAccordionHeading">
												<button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#availBasemapAccordionCollapse" aria-expanded="false" aria-controls="availBasemapAccordionCollapse">
													Available
												</button>
											</h2>
											<div id="availBasemapAccordionCollapse" class="accordion-collapse collapse" aria-labelledby="availBasemapAccordionHeading" data-bs-parent="#basemapAccordion">
												<div class="accordion-body">
													<div class="row row-cols-2 row-cols-md-3" id="basemapChoose"></div>
												</div>
											</div>
										</div>
										<div class="accordion-item">
											<h2 class="accordion-header" id="imgBasemapAccordionHeading">
												<button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#imgBasemapAccordionCollapse" aria-expanded="false" aria-controls="imgBasemapAccordionCollapse">
													Uploaded
												</button>
											</h2>
											<div id="imgBasemapAccordionCollapse" class="accordion-collapse collapse" aria-labelledby="imgBasemapAccordionHeading" data-bs-parent="#basemapAccordion">
												<div class="accordion-body">
													<div class="row row-cols-2 row-cols-md-3" id="imgBasemapChoose"></div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>

							<div class="row mt-4 mb-2">
								<div class="col">
									<label for="basemapLink"><small>Or link to an online basemap</small></label>
									<div class="input-group input-group-sm">
										<input type="text" class="form-control" id="basemapLink" aria-label="Url" aria-describedby="keyText" placeholder="URL" <?php echo !$paid ? "disabled" : ""; ?> />
										<input type="text" class="form-control" id="basemapKey" aria-label="Access key" aria-describedby="keyText" placeholder="Access key (optional)" <?php echo !$paid ? "disabled" : ""; ?> />
										<button type="button" class="btn btn-outline-secondary" id="basemapFetch" <?php echo !$paid ? "disabled" : ""; ?>>Apply</button>
									</div>
									<div id="keyText" class="form-text">XYZ-tiles or Mapbox style. Access key is required with Mapbox style</div>
								</div>
							</div>

							<div class="row my-2">
								<div class="col">
									<hr />
								</div>
							</div>

							<div class="row my-2">
								<div class="col">
									<label for="wmsLink"><small>Add a WMS layer</small></label>
									<div class="input-group input-group-sm">
										<button type="button" class="btn btn-outline-secondary" id="wmsRemove" title="Remove current WMS layer" <?php echo !$paid ? "disabled" : ""; ?>><i class="fas fa-minus"></i></button>
										<input type="text" class="form-control" id="wmsLink" aria-label="Url" aria-describedby="wmsText" placeholder="URL" <?php echo !$paid ? "disabled" : ""; ?> />
										<input type="text" class="form-control" id="wmsLayer" aria-label="Layer" aria-describedby="wmsText" placeholder="Layer" <?php echo !$paid ? "disabled" : ""; ?> />
										<select class="form-select" id="wmsFormat" aria-label="Format" style="max-width: 150px;" <?php echo !$paid ? "disabled" : ""; ?>>
											<option value="image/png" selected>image/png</option>
											<option value="image/jpeg">image/jpeg</option>
										</select>
										<select class="form-select" id="wmsVersion" aria-label="Version" style="max-width: 100px;" <?php echo !$paid ? "disabled" : ""; ?>>
											<option value="1.3.0" selected>1.3.0</option>
											<option value="1.1.1">1.1.1</option>
										</select>
										<button type="button" class="btn btn-outline-secondary" id="wmsAdd" <?php echo !$paid ? "disabled" : ""; ?>>Add</button>
									</div>
									<div id="wmsText" class="form-text">WMS layer will be added on top of the basemap. Only support for one WMS layer per scene</div>
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
					</div>
				</div>
			</div>
		</div>



		<!-- Scene warning modal -->
		<div class="modal fade" id="sceneWarningModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" tabindex="-1" aria-labelledby="sceneWarningModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-scrollable modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="sceneWarningModalLabel">Delete scene</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<p>Are you sure you want to delete the current scene?</p>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
						<button type="button" class="btn btn-danger" id="delete">Delete</button>
					</div>
				</div>
			</div>
		</div>



		<!-- Textbox options modal -->
		<div class="modal fade" id="textboxOptionsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" tabindex="-1" aria-labelledby="textboxOptionsModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-scrollable modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="textboxOptionsModalLabel">Book options</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<div class="container-fluid">
							<div class="row">
								<div class="col col-md-10">
									<label for="bookOrientation" class="form-label">Book orientation</label>
									<select class="form-select" id="bookOrientation" aria-label="Book orientation">
										<option value="left" selected>Left</option>
										<option value="right">Right</option>
									</select>
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
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





		<div class="container-fluid p-0">
			<div class="row g-0" style="height: 39px;">
				<div class="col">
					<nav class="navbar navbar-expand-sm navbar-dark fixed-top shadow px-2" style="background-color: #eba937; padding-top: 0.25rem; padding-bottom: 0.25rem;">
						<a class="navbar-brand py-0 mx-2" href="maps.php" style="line-height: 0;">
							<img src="assets/logo.png" alt="GeoTales" width="auto" height="20" />
						</a>

						<button class="navbar-toggler py-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
							<span class="navbar-toggler-icon"></span>
						</button>

						<div class="collapse navbar-collapse" id="navbarContent">
							<ul class="navbar-nav mb-0 px-0 w-100">
								<li class="nav-item dropdown">
									<a class="nav-link dropdown-toggle py-0" href="#" id="navbarFileDropdown" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
										File
									</a>
									<ul class="dropdown-menu" aria-labelledby="navbarFileDropdown" style="max-height: calc(100vh - 39px); overflow-y: visible;">
										<li class="dropend">
											<button type="button" class="dropdown-toggle dropdown-item" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
												Import
											</button>
											<ul class="dropdown-menu">
												<li><button type="button" class="dropdown-item" onclick="$('#projectFileInput').click();" <?php echo !$paid ? "disabled" : ""; ?>>Project file</button></li>
												<li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#geojsonImportModal" <?php echo !$paid ? "disabled" : ""; ?>>GeoJSON</button></li>
												<li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#gedcomImportModal" <?php echo !$paid ? "disabled" : ""; ?>>GEDCOM</button></li>
											</ul>
										</li>
										<li class="dropend">
											<button type="button" class="dropdown-toggle dropdown-item" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
												Export as
											</button>
											<ul class="dropdown-menu">
												<li><button type="button" class="dropdown-item" id="export" data-type="project" <?php echo !$paid ? "disabled" : ""; ?>>Project file</button></li>
												<li><button type="button" class="dropdown-item" id="export" data-type="geojson" <?php echo !$paid ? "disabled" : ""; ?>>GeoJSON</button></li>
											</ul>
										</li>
										<li><hr class="dropdown-divider" /></li>
										<li><a class="dropdown-item" href="#" id="save">Save</a></li>
										<li><hr class="dropdown-divider" /></li>
										<li><a class="dropdown-item" href="maps.php">Exit</a></li>
									</ul>
								</li>
								<li class="nav-item dropdown">
									<a class="nav-link dropdown-toggle py-0" href="#" id="navbarEditDropdown" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
										Edit
									</a>
									<ul class="dropdown-menu" aria-labelledby="navbarEditDropdown" style="max-height: calc(100vh - 39px); overflow-y: visible;">
										<li class="dropend">
											<button type="button" class="dropdown-toggle dropdown-item" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
												Scene
											</button>
											<ul class="dropdown-menu">
												<li><button type="button" class="dropdown-item" onclick="_SCENES.add();">Add new scene</button></li>
												<li><button type="button" class="dropdown-item" onclick="_SCENES.capture();">Recapture</button></li>
												<li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#sceneWarningModal">Delete</button></li>
												<li><button type="button" class="dropdown-item" onclick="_SCENES.setBookmark(true);">Bookmark scene</button></li>
												<li><button type="button" class="dropdown-item" onclick="_SCENES.setBookmark(false);">Unbookmark scene</button></li>
												<li><button type="button" class="dropdown-item" onclick="_SCENES.copyBounds();">Copy scene position</button></li>
												<li><button type="button" class="dropdown-item" onclick="_SCENES.pasteBounds();">Paste scene position</button></li>
											</ul>
										</li>
										<li class="dropend">
											<button type="button" class="dropdown-toggle dropdown-item" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
												Book
											</button>
											<ul class="dropdown-menu">
												<li><button type="button" class="dropdown-item" onclick="_TEXTBOXES.add();">Add book</button></li>
												<li><button type="button" class="dropdown-item" onclick="_TEXTBOXES.setLock(true);">Lock</button></li>
												<li><button type="button" class="dropdown-item" onclick="_TEXTBOXES.setLock(false);">Unlock</button></li>
												<li><button type="button" class="dropdown-item" onclick="_TEXTBOXES.delete(_SCENES.active);">Remove</button></li>
												<li class="dropend">
													<button type="button" class="dropdown-toggle dropdown-item" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
														Set orientation
													</button>
													<ul class="dropdown-menu">
														<li><button type="button" class="dropdown-item" onclick="_TEXTBOXES.setOrientation('left');">Left</button></li>
														<li><button type="button" class="dropdown-item" onclick="_TEXTBOXES.setOrientation('right');">Right</button></li>
													</ul>
												</li>
											</ul>
										</li>
										<li class="dropend">
											<button type="button" class="dropdown-toggle dropdown-item" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
												Map
											</button>
											<ul class="dropdown-menu">
												<li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#basemapModal">Change basemap</button></li>
												<li class="dropend">
													<button type="button" class="dropdown-toggle dropdown-item" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
														Set aspect ratio
													</button>
													<ul class="dropdown-menu">
														<li><button type="button" class="dropdown-item" onclick="_MAP.setAspectRatio(16/9);">16/9</button></li>
														<li><button type="button" class="dropdown-item" onclick="_MAP.setAspectRatio(4/3);">4/3</button></li>
														<li><button type="button" class="dropdown-item" onclick="_MAP.setAspectRatio(9/16);">9/16</button></li>
													</ul>
												</li>
											</ul>
										</li>
									</ul>
								</li>
								<li class="nav-item mb-2 mb-sm-0 me-auto">
									<a class="nav-link py-0" href="#" data-bs-toggle="modal" data-bs-target="#optionsModal">Options</a>
								</li>

								<li class="nav-item mb-2 mb-sm-0 me-4">
									<div class="btn-group btn-group-sm" role="group" aria-label="Save/Preview">
										<a role="button" class="btn btn-light" href="#" id="save">Save</a>
										<a role="button" class="btn btn-outline-light" href="view.php?id=<?php echo $id; ?>" target="_blank">View</a>
									</div>
								</li>

								<li class="nav-item dropdown">
									<a class="nav-link dropdown-toggle py-1 py-sm-0" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
										<img class="rounded" src="<?php echo $photo; ?>" alt="&nbsp;" width="auto" height="25" />
									</a>
									<ul class="dropdown-menu dropdown-menu-sm-end" aria-labelledby="navbarUserDropdown">
										<li><a class="dropdown-item" href="maps.php">My GeoTales</a></li>
										<li><a class="dropdown-item" href="profile.php">Profile</a></li>
										<li><hr class="dropdown-divider" /></li>
										<li><a class="dropdown-item" href="about.php">About</a></li>
										<li><a class="dropdown-item" href="signout.php">Sign out</a></li>
									</ul>
								</li>
							</ul>
						</div>
					</nav>
				</div>
			</div>

			<div class="row g-0" id="sceneRow" style="height: 49px;">
				<div class="col shadow" style="z-index: 1003;">
					<div class="row g-0">
						<div class="col text-center p-2" style="max-width: 150px;">
							<button type="button" class="btn btn-sm btn-light" id="delete" title="Delete current scene" data-bs-toggle="modal" data-bs-target="#sceneWarningModal" disabled>
								<i class="fas fa-trash"></i>
							</button>

							<button type="button" class="btn btn-sm btn-light" id="recapture" title="Recapture scene" disabled>
								<i class="fas fa-camera"></i>
							</button>

							<button type="button" class="btn btn-sm btn-light" id="add" title="Add new scene" style="width: 60px;">
								<i class="fas fa-plus"></i>
							</button>
						</div>

						<div class="col px-2" tabindex="0" style="max-width: calc(100% - 150px); border-left: 1px solid grey;">
							<ul class="list-group list-group-horizontal" id="scenes"></ul>
						</div>
					</div>
				</div>
			</div>

			<div class="row g-0" id="mapRow" style="height: calc(100vh - 39px - 49px);">
				<div class="col">
					<div class="shadow" id="map"></div>
					<div class="shadow" id="textbox">
						<div id="banner">
							<button type="button" class="btn btn-outline-secondary btn-sm py-0" id="options" title="Book options" data-bs-toggle="modal" data-bs-target="#textboxOptionsModal" style="float: left;">
								<i class="fas fa-cog"></i>
							</button>
							<input type="checkbox" class="form-check-input ms-1" value="" id="lock" title="Lock" />
							<button type="button" class="btn btn-outline-secondary btn-sm py-0" id="close" title="Remove book" style="float: right;">
								<i class="fas fa-times"></i>
							</button>
						</div>
						<div id="content"></div>
					</div>
				</div>
			</div>
		</div>

		<!-- NOTE: placeholders for trumbowyg image upload and avatar icon upload -->
		<input type="file" id="_img_textbox" accept="image/gif, image/jpeg, image/png, image/webp" style="display: none;" />
		<input type="file" id="_img_icon" accept="image/gif, image/jpeg, image/png, image/webp" style="display: none;" />

		<!-- Load lib/ JS -->
		<script type="text/javascript" src="lib/fontawesome/js/all.min.js"></script>
		<!--script type="text/javascript" src="lib/jquery/jquery-3.6.0.slim.min.js"></script-->
		<script type="text/javascript" src="lib/jquery-ui/external/jquery/jquery.js"></script>
		<script type="text/javascript" src="lib/jquery-ui/jquery-ui.min.js"></script>
		<script type="text/javascript" src="lib/jquery-resizable/jquery-resizable.min.js"></script>
		<script type="text/javascript" src="lib/bootstrap/js/bootstrap.bundle.min.js"></script>
		<script type="text/javascript" src="lib/leaflet/leaflet.js"></script>
		<script type="text/javascript" src="lib/leaflet.providers/leaflet-providers.js"></script>
		<!--script type="text/javascript" src="lib/leaflet.fullscreen/Leaflet.fullscreen.min.js"></script-->
		<script type="text/javascript" src="lib/leaflet.zoomhome/leaflet.zoomhome.js"></script>
		<!--script type="text/javascript" src="lib/leaflet.locatecontrol/L.Control.Locate.min.js"></script-->
		<!--script type="text/javascript" src="lib/leaflet.draw/leaflet.draw.js"></script-->
		<script type="text/javascript" src="lib/leaflet.geoman/leaflet-geoman.min.js"></script>
		<script type="text/javascript" src="lib/leaflet.slideto/Leaflet.SlideTo.js"></script>
		<script type="text/javascript" src="lib/leaflet.easybutton/easy-button.js"></script>
		<!--script type="text/javascript" src="lib/leaflet.htmllegend/L.Control.HtmlLegend.js"></script-->
		<script type="text/javascript" src="lib/leaflet.contextmenu/leaflet.contextmenu.min.js"></script>
		<!--script type="text/javascript" src="lib/leaflet.centercontrol/leaflet-control-topcenter.js"></script-->
		<!--script type="text/javascript" src="lib/geotiff/geotiff.js"></script-->
		<!--script type="text/javascript" src="lib/plotty/plotty.min.js"></script-->
		<!--script type="text/javascript" src="lib/leaflet.geotiff/leaflet-geotiff.js"></script-->
		<!--script type="text/javascript" src="lib/leaflet.geotiff/leaflet-geotiff-plotty.js"></script-->
		<!--script type="text/javascript" src="lib/leaflet.geotiff/leaflet-geotiff-vector-arrows.js"></script-->
		<!--script type="text/javascript" src="lib/prism/prism.js"></script-->
		<script type="text/javascript" src="lib/trumbowyg/trumbowyg.min.js"></script>
		<script type="text/javascript" src="lib/trumbowyg/plugins/base64/trumbowyg.base64.min.js"></script>
		<script type="text/javascript" src="lib/trumbowyg/plugins/colors/trumbowyg.colors.min.js"></script>
		<script type="text/javascript" src="lib/trumbowyg/plugins/fontfamily/trumbowyg.fontfamily.min.js"></script>
		<script type="text/javascript" src="lib/trumbowyg/plugins/fontsize/trumbowyg.fontsize.min.js"></script>
		<!--script type="text/javascript" src="lib/trumbowyg/plugins/highlight/trumbowyg.highlight.min.js"></script-->
		<script type="text/javascript" src="lib/trumbowyg/plugins/history/trumbowyg.history.min.js"></script>
		<script type="text/javascript" src="lib/trumbowyg/plugins/indent/trumbowyg.indent.min.js"></script>
		<script type="text/javascript" src="lib/trumbowyg/plugins/lineheight/trumbowyg.lineheight.min.js"></script>
		<script type="text/javascript" src="lib/trumbowyg/plugins/pasteimage/trumbowyg.pasteimage.min.js"></script>
		<script type="text/javascript" src="lib/trumbowyg/plugins/resizimg/trumbowyg.resizimg.min.js"></script>
		<script type="text/javascript" src="lib/trumbowyg/plugins/specialchars/trumbowyg.specialchars.min.js"></script>
		<script type="text/javascript" src="lib/trumbowyg/plugins/table/trumbowyg.table.min.js"></script>

		<!--script type="text/javascript" src="lib/leaflet.gridlayer/L.GridLayer.js"></script-->
		<script type="text/javascript" src="lib/leaflet.tilelayer.mars/L.TileLayer.Mars.js"></script>

		<!-- Set superglobals and init -->
		<script type="text/javascript">
			"use strict";

			$.ajax({
				type: "POST",
				url: "api.php",
				data: { "op": "analytics", "agent": window.navigator ? window.navigator.userAgent : "" },
				dataType: "json",
				success: function(result, status, xhr) { console.log("Analytics registered"); },
				error: function(xhr, status, error) { console.log(xhr.status, error); }
			});

			const _ID = `<?php echo $id; ?>`,
				  _TITLE = `<?php echo $row['title']; ?>`,
				  _HOST = window.location.host,
				  _USER_PAID = `<?php echo $paid; ?>`;

			let _OPTIONS = {
				aspectratio: 16/9,
				animationspeed: 2000,
				panningspeed: null,
				objectOptIn: false
			};

			let _SCENES,
				_TEXTBOXES,
				_MAP;

			let _ICONS = [
				"assets/user-circle-solid.svg"
			];

			let _BASEMAPS = [
				{ name: "Blank",				tiles: L.tileLayer("",																																		{ minZoom: 0, maxZoom: 22, attribution: `&copy; <a href="https://${_HOST}" target="_blank">GeoTales</a>` }),																																																													preview: "assets/blank.png" },
				{ name: "OpenStreetMap",		tiles: L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",																					{ minZoom: 0, maxZoom: 19, attribution: "&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors" }),																																																							preview: "https://b.tile.openstreetmap.org/5/15/10.png" },
				{ name: "OpenStreetMap.DE",		tiles: L.tileLayer("https://{s}.tile.openstreetmap.de/tiles/osmde/{z}/{x}/{y}.png",																			{ minZoom: 0, maxZoom: 18, attribution: "&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors" }),																																																							preview: "https://b.tile.openstreetmap.de/tiles/osmde/5/15/10.png" },
				{ name: "OpenStreetMap.FR",		tiles: L.tileLayer("https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png",																				{ minZoom: 0, maxZoom: 20, attribution: "&copy; OpenStreetMap France | &copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors" }),																																															preview: "https://b.tile.openstreetmap.fr/osmfr/5/15/10.png" },
				{ name: "OpenStreetMap.HOT",	tiles: L.tileLayer("https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png",																					{ minZoom: 0, maxZoom: 19, attribution: "&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors, Tiles style by <a href=\"https://www.hotosm.org/\" target=\"_blank\">Humanitarian OpenStreetMap Team</a> hosted by <a href=\"https://openstreetmap.fr/\" target=\"_blank\">OpenStreetMap France</a>" }),					preview: "https://b.tile.openstreetmap.fr/hot/5/15/10.png" },
				{ name: "OpenTopoMap",			tiles: L.tileLayer("https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png",																						{ minZoom: 0, maxZoom: 17, attribution: "Map data: &copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors, <a href=\"http://viewfinderpanoramas.org\">SRTM</a> | Map style: &copy; <a href=\"https://opentopomap.org\">OpenTopoMap</a> (<a href=\"https://creativecommons.org/licenses/by-sa/3.0/\">CC-BY-SA</a>)" }),		preview: "https://b.tile.opentopomap.org/5/15/10.png" },
				{ name: "Stamen.Toner",			tiles: L.tileLayer("https://stamen-tiles-{s}.a.ssl.fastly.net/toner/{z}/{x}/{y}{r}.png",																	{ subdomains: "abcd", minZoom: 0, maxZoom: 20, attribution: "Map tiles by <a href=\"http://stamen.com\">Stamen Design</a>, <a href=\"http://creativecommons.org/licenses/by/3.0\">CC BY 3.0</a> &mdash; Map data &copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors" }),													preview: "https://stamen-tiles-b.a.ssl.fastly.net/toner/5/15/10.png" },
				{ name: "Stamen.TonerLite",		tiles: L.tileLayer("https://stamen-tiles-{s}.a.ssl.fastly.net/toner-lite/{z}/{x}/{y}{r}.png",																{ subdomains: "abcd", minZoom: 0, maxZoom: 20, attribution: "Map tiles by <a href=\"http://stamen.com\">Stamen Design</a>, <a href=\"http://creativecommons.org/licenses/by/3.0\">CC BY 3.0</a> &mdash; Map data &copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors" }),													preview: "https://stamen-tiles-b.a.ssl.fastly.net/toner-lite/5/15/10.png" },
				{ name: "Stamen.Watercolor",	tiles: L.tileLayer("https://stamen-tiles-{s}.a.ssl.fastly.net/watercolor/{z}/{x}/{y}.jpg",																	{ subdomains: "abcd", minZoom: 0, maxZoom: 16, attribution: "Map tiles by <a href=\"http://stamen.com\">Stamen Design</a>, <a href=\"http://creativecommons.org/licenses/by/3.0\">CC BY 3.0</a> &mdash; Map data &copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors" }),													preview: "https://stamen-tiles-b.a.ssl.fastly.net/watercolor/5/15/10.png" },
				{ name: "Stamen.Terrain",		tiles: L.tileLayer("https://stamen-tiles-{s}.a.ssl.fastly.net/terrain/{z}/{x}/{y}{r}.png",																	{ subdomains: "abcd", minZoom: 0, maxZoom: 14, attribution: "Map tiles by <a href=\"http://stamen.com\">Stamen Design</a>, <a href=\"http://creativecommons.org/licenses/by/3.0\">CC BY 3.0</a> &mdash; Map data &copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors" }),													preview: "https://stamen-tiles-b.a.ssl.fastly.net/terrain/5/15/10.png" },
				{ name: "Esri.StreetMap",		tiles: L.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}",										{ minZoom: 0, maxZoom: 18, attribution: "Tiles &copy; Esri &mdash; Source: Esri, DeLorme, NAVTEQ, USGS, Intermap, iPC, NRCAN, Esri Japan, METI, Esri China (Hong Kong), Esri (Thailand), TomTom, 2012" }),																																						preview: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/5/10/15" },
				{ name: "Esri.DeLorme",			tiles: L.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/Specialty/DeLorme_World_Base_Map/MapServer/tile/{z}/{y}/{x}",						{ minZoom: 1, maxZoom: 11, attribution: "Tiles &copy; Esri &mdash; Copyright: &copy;2012 DeLorme" }),																																																															preview: "https://server.arcgisonline.com/ArcGIS/rest/services/Specialty/DeLorme_World_Base_Map/MapServer/tile/5/10/15" },
				{ name: "Esri.TopoMap",			tiles: L.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}",										{ minZoom: 0, maxZoom: 18, attribution: "Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ, TomTom, Intermap, iPC, USGS, FAO, NPS, NRCAN, GeoBase, Kadaster NL, Ordnance Survey, Esri Japan, METI, Esri China (Hong Kong), and the GIS User Community" }),																										preview: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/5/10/15" },
				{ name: "Esri.Imagery",			tiles: L.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",											{ minZoom: 0, maxZoom: 18, attribution: "Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community" }),																																										preview: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/5/10/15" },
				{ name: "Esri.Terrain",			tiles: L.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/World_Terrain_Base/MapServer/tile/{z}/{y}/{x}",									{ minZoom: 0, maxZoom: 13, attribution: "Tiles &copy; Esri &mdash; Source: USGS, Esri, TANA, DeLorme, and NPS" }),																																																												preview: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Terrain_Base/MapServer/tile/5/10/15" },
				{ name: "Esri.OceanBasemap",	tiles: L.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/Ocean_Basemap/MapServer/tile/{z}/{y}/{x}",											{ minZoom: 0, maxZoom: 13, attribution: "Tiles &copy; Esri &mdash; Sources: GEBCO, NOAA, CHS, OSU, UNH, CSUMB, National Geographic, DeLorme, NAVTEQ, and Esri" }),																																																preview: "https://server.arcgisonline.com/ArcGIS/rest/services/Ocean_Basemap/MapServer/tile/5/10/15" },
				{ name: "Esri.NatGeo",			tiles: L.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/NatGeo_World_Map/MapServer/tile/{z}/{y}/{x}",										{ minZoom: 0, maxZoom: 12, attribution: "Tiles &copy; Esri &mdash; National Geographic, Esri, DeLorme, NAVTEQ, UNEP-WCMC, USGS, NASA, ESA, METI, NRCAN, GEBCO, NOAA, iPC" }),																																													preview: "https://server.arcgisonline.com/ArcGIS/rest/services/NatGeo_World_Map/MapServer/tile/5/10/15" },
				{ name: "Esri.GrayCanvas",		tiles: L.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}",							{ minZoom: 0, maxZoom: 16, attribution: "Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ" }),																																																																	preview: "https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/5/10/15" },
				{ name: "CartoDB.Positron",		tiles: L.tileLayer("https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png",																		{ subdomains: "abcd", minZoom: 0, maxZoom: 20, attribution: "&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors &copy; <a href=\"https://carto.com/attributions\">CARTO</a>" }),																																			preview: "https://b.basemaps.cartocdn.com/light_all/5/15/10.png" },
				{ name: "CartoDB.DarkMatter",	tiles: L.tileLayer("https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png",																			{ subdomains: "abcd", minZoom: 0, maxZoom: 20, attribution: "&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors &copy; <a href=\"https://carto.com/attributions\">CARTO</a>" }),																																			preview: "https://b.basemaps.cartocdn.com/dark_all/5/15/10.png" },
				{ name: "CartoDB.Voyager",		tiles: L.tileLayer("https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png",																{ subdomains: "abcd", minZoom: 0, maxZoom: 20, attribution: "&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors &copy; <a href=\"https://carto.com/attributions\">CARTO</a>" }),																																			preview: "https://b.basemaps.cartocdn.com/rastertiles/voyager/5/15/10.png" },
				{ name: "CyclOSM",				tiles: L.tileLayer("https://{s}.tile-cyclosm.openstreetmap.fr/cyclosm/{z}/{x}/{y}.png",																		{ subdomains: "abc", minZoom: 0, maxZoom: 20, attribution: "<a href=\"https://github.com/cyclosm/cyclosm-cartocss-style/releases\" title=\"CyclOSM - Open Bicycle render\">CyclOSM</a> | Map data: &copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors" }),																preview: "https://c.tile-cyclosm.openstreetmap.fr/cyclosm/5/15/10.png" },
				{ name: "MtbMap",				tiles: L.tileLayer("http://tile.mtbmap.cz/mtbmap_tiles/{z}/{x}/{y}.png",																					{ minZoom: 0, maxZoom: 18, attribution: "&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors &amp; USGS" }),																																																				preview: "https://tile.mtbmap.cz/mtbmap_tiles/5/15/10.png" },
				{ name: "HikeBike",				tiles: L.tileLayer("https://tiles.wmflabs.org/hikebike/{z}/{x}/{y}.png",																					{ minZoom: 0, maxZoom: 19, attribution: "&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors" }),																																																							preview: "https://tiles.wmflabs.org/hikebike/5/15/10.png" },
				{ name: "NASA.EarthAtNight",	tiles: L.tileLayer("https://map1.vis.earthdata.nasa.gov/wmts-webmerc/VIIRS_CityLights_2012/default//GoogleMapsCompatible_Level{maxZoom}/{z}/{y}/{x}.jpg",	{ minZoom: 1, maxZoom:  8, attribution: "Imagery provided by services from the Global Imagery Browse Services (GIBS), operated by the NASA/GSFC/Earth Science Data and Information System (<a href=\"https://earthdata.nasa.gov\">ESDIS</a>) with funding provided by NASA/HQ.", bounds: [[-85.0511287776,-179.999999975],[85.0511287776,179.999999975]] }),	preview: "https://map1.vis.earthdata.nasa.gov/wmts-webmerc/VIIRS_CityLights_2012/default//GoogleMapsCompatible_Level8/5/10/15.jpg" },
				{ name: "USGS.Topo",			tiles: L.tileLayer("https://basemap.nationalmap.gov/arcgis/rest/services/USGSTopo/MapServer/tile/{z}/{y}/{x}",												{ minZoom: 0, maxZoom: 16, attribution: "Tiles courtesy of the <a href=\"https://usgs.gov/\">U.S. Geological Survey</a>" }),																																																									preview: "https://basemap.nationalmap.gov/arcgis/rest/services/USGSTopo/MapServer/tile/5/10/15" },
				{ name: "USGS.Imagery",			tiles: L.tileLayer("https://basemap.nationalmap.gov/arcgis/rest/services/USGSImageryOnly/MapServer/tile/{z}/{y}/{x}",										{ minZoom: 0, maxZoom: 16, attribution: "Tiles courtesy of the <a href=\"https://usgs.gov/\">U.S. Geological Survey</a>" }),																																																									preview: "https://basemap.nationalmap.gov/arcgis/rest/services/USGSImageryOnly/MapServer/tile/5/10/15" },
				{ name: "USGS.ImageryTopo",		tiles: L.tileLayer("https://basemap.nationalmap.gov/arcgis/rest/services/USGSImageryTopo/MapServer/tile/{z}/{y}/{x}",										{ minZoom: 0, maxZoom: 16, attribution: "Tiles courtesy of the <a href=\"https://usgs.gov/\">U.S. Geological Survey</a>" }),																																																									preview: "https://basemap.nationalmap.gov/arcgis/rest/services/USGSImageryTopo/MapServer/tile/5/10/15" },

				{ name: "Google Maps – Streets",				tiles: L.tileLayer("https://mt2.google.com/vt/lyrs=m&x={x}&y={y}&z={z}",								{ minZoom: 0, maxZoom: 20, attribution: "&copy; <a href=\"https://www.google.com/intl/no_no/help/terms_maps/\">Google Maps</a>" }),	preview: "https://mt2.google.com/vt/lyrs=m&x=15&y=10&z=5" },
				{ name: "Google Maps – Hybrid",					tiles: L.tileLayer("https://mt2.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}",								{ minZoom: 0, maxZoom: 20, attribution: "&copy; <a href=\"https://www.google.com/intl/no_no/help/terms_maps/\">Google Maps</a>" }),	preview: "https://mt2.google.com/vt/lyrs=s,h&x=15&y=10&z=5" },
				{ name: "Google Maps – Satellite",				tiles: L.tileLayer("https://mt2.google.com/vt/lyrs=s&x={x}&y={y}&z={z}",								{ minZoom: 0, maxZoom: 20, attribution: "&copy; <a href=\"https://www.google.com/intl/no_no/help/terms_maps/\">Google Maps</a>" }),	preview: "https://mt2.google.com/vt/lyrs=s&x=15&y=10&z=5" },
				{ name: "Google Maps – Terrain",				tiles: L.tileLayer("https://mt2.google.com/vt/lyrs=p&x={x}&y={y}&z={z}",								{ minZoom: 0, maxZoom: 20, attribution: "&copy; <a href=\"https://www.google.com/intl/no_no/help/terms_maps/\">Google Maps</a>" }),	preview: "https://mt2.google.com/vt/lyrs=p&x=15&y=10&z=5" },
				{ name: "Digital Atlas of the Roman Empire",	tiles: L.tileLayer("https://dh.gu.se/tiles/imperium/{z}/{x}/{y}.png",									{ minZoom: 4, maxZoom: 11, attribution: "&copy; <a href=\"https://dh.gu.se/dare/\">University of Gothenburg</a>" }),				preview: "https://dh.gu.se/tiles/imperium/4/8/5.png" },
				{ name: "Google – Star map",					tiles: L.tileLayer("https://mw1.google.com/mw-planetary/sky/skytiles_v1/{x}_{y}_{z}.jpg",				{ minZoom: 0, maxZoom: 15, attribution: "&copy; <a href=\"https://www.google.com/sky/\">Google Sky</a>" }),							preview: "https://mw1.google.com/mw-planetary/sky/skytiles_v1/15_9_5.jpg" },
				{ name: "Google – Lunar surface",				tiles: L.tileLayer("https://mw1.google.com/mw-planetary/lunar/lunarmaps_v1/clem_bw/{z}/{x}/{y}.jpg",	{ tms: true, minZoom: 0, maxZoom: 9, attribution: "&copy; <a href=\"https://www.google.com/moon/\">Google Moon</a>" }),				preview: "https://mw1.google.com/mw-planetary/lunar/lunarmaps_v1/clem_bw/5/15/21.jpg" },
				{ name: "Google – Lunar elevation",				tiles: L.tileLayer("https://mw1.google.com/mw-planetary/lunar/lunarmaps_v1/terrain/{z}/{x}/{y}.jpg",	{ tms: true, minZoom: 0, maxZoom: 7, attribution: "&copy; <a href=\"https://www.google.com/moon/\">Google Moon</a>" }),				preview: "https://mw1.google.com/mw-planetary/lunar/lunarmaps_v1/terrain/5/18/19.jpg" },
				{ name: "Google – Martian surface",				tiles: L.tileLayer.mars(																				{ layer: "visible", minZoom: 0, maxZoom: 9, attribution: "&copy; <a href=\"https://www.google.com/mars/\">Google Mars</a>" }),		preview: "https://mw1.google.com/mw-planetary/mars/visible/tqssrr.jpg" },
				{ name: "Google – Martian surface infrared",	tiles: L.tileLayer.mars(																				{ layer: "infrared", minZoom: 0, maxZoom: 9, attribution: "&copy; <a href=\"https://www.google.com/mars/\">Google Mars</a>" }),		preview: "https://mw1.google.com/mw-planetary/mars/infrared/ttrqrq.jpg" },
				{ name: "Google – Martian elevation",			tiles: L.tileLayer.mars(																				{ layer: "elevation", minZoom: 0, maxZoom: 8, attribution: "&copy; <a href=\"https://www.google.com/mars/\">Google Mars</a>" }),	preview: "https://mw1.google.com/mw-planetary/mars/elevation/tqtsrs.jpg" }
			];
		</script>

		<!-- Load src/ JS -->
		<script type="text/javascript" src="assets/main_edit_1666621876.js"></script>

	</body>
</html>
