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

$logged_in = false;
if(isset($_SESSION['user_id']) && validUserID($PDO, $_SESSION['user_id'])) {
	$logged_in = true;
	$user_id = $_SESSION['user_id'];
}

if(!isset($_GET['id'])) {
	http_response_code(422); exit;
}
$id = $_GET['id'];


$stmt = $PDO->prepare("SELECT title, description, thumbnail FROM \"Map\" WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();

?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<meta http-equiv="x-ua-compatible" content="ie=edge" />
		<meta name="viewport" content="minimal-ui, width=device-width, height=device-height, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no, shrink-to-fit=no, target-densitydpi=device-dpi" />

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
		<!--link rel="stylesheet" href="lib/jquery-ui/jquery-ui.min.css" /-->
		<link rel="stylesheet" href="lib/bootstrap/css/bootstrap.min.css" />
		<link rel="stylesheet" href="lib/leaflet/leaflet.css" />
		<link rel="stylesheet" href="lib/leaflet.zoomhome/leaflet.zoomhome.css" />
		<link rel="stylesheet" href="lib/leaflet.easybutton/easy-button.css" />
		<link rel="stylesheet" href="lib/leaflet.contextmenu/leaflet.contextmenu.min.css" />
		<link rel="stylesheet" href="lib/leaflet.centercontrol/leaflet-control-topcenter.css" />

		<!-- Load src/ CSS -->
		<link rel="stylesheet" href="assets/main_pres_1666621876.css" />

		<style type="text/css"></style>
	</head>
	<body>



		<!-- Image modal -->
		<div class="modal fade" id="imageModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl modal-fullscreen-lg-down">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<img alt="Could not load image" class="img-fluid mx-auto d-block" id="imgPreview" />
					</div>
				</div>
			</div>
		</div>



		<!-- Password modal -->
		<div class="modal fade" id="passwordModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-scrollable modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="passwordModalLabel">Enter password</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<div class="container-fluid">
							<div class="row">
								<div class="col">
									<input type="password" class="form-control" id="passwordInput" />
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" id="enter">Enter</button>
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
			<div class="row g-0" id="main">
				<div class="col">
					<div class="shadow" id="map"></div>

					<div class="card shadow" id="textbox">
						<div class="card-body">
							<div id="content"></div>
						</div>
					</div>

					<div role="group" class="btn-group btn-group-sm" id="sceneNav" aria-label="Scene navigation">
						<button type="button" class="btn btn-light" id="prev">
							<i class="fas fa-chevron-left"></i>
						</button>
						<div role="group" class="btn-group dropup" id="bookmarks">
							<button type="button" class="btn btn-light dropdown-toggle px-3" id="bookmarksDropdown" data-bs-toggle="dropdown" aria-expanded="false">
								<i class="fas fa-bookmark"></i>
							</button>
							<ul class="dropdown-menu" aria-labelledby="bookmarksDropdown">
								<li><h6 class="dropdown-header">Bookmarks</h6></li>
							</ul>
						</div>
						<button type="button" class="btn btn-light" id="next">
							<i class="fas fa-chevron-right"></i>
						</button>
					</div>
				</div>
			</div>
		</div>

		<!-- Load lib/ JS -->
		<script type="text/javascript" src="lib/fontawesome/js/all.min.js"></script>
		<!--script type="text/javascript" src="lib/jquery/jquery-3.6.0.slim.min.js"></script-->
		<script type="text/javascript" src="lib/jquery-ui/external/jquery/jquery.js"></script>
		<!--script type="text/javascript" src="lib/jquery-ui/jquery-ui.min.js"></script-->
		<script type="text/javascript" src="lib/bootstrap/js/bootstrap.bundle.min.js"></script>
		<script type="text/javascript" src="lib/sjcl/sjcl.js"></script>
		<script type="text/javascript" src="lib/leaflet/leaflet.js"></script>
		<script type="text/javascript" src="lib/leaflet.providers/leaflet-providers.js"></script>
		<script type="text/javascript" src="lib/leaflet.zoomhome/leaflet.zoomhome.js"></script>
		<script type="text/javascript" src="lib/leaflet.slideto/Leaflet.SlideTo.js"></script>
		<script type="text/javascript" src="lib/leaflet.easybutton/easy-button.js"></script>
		<script type="text/javascript" src="lib/leaflet.contextmenu/leaflet.contextmenu.min.js"></script>
		<script type="text/javascript" src="lib/leaflet.centercontrol/leaflet-control-topcenter.js"></script>

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
				  _IS_MOBILE = window.navigator ? /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(window.navigator.userAgent) : false;
			var _PASSWORD = "";

			$.ajax({
				type: "POST",
				url: "api.php",
				data: { "op": "map_view", "id": _ID },
				dataType: "json",
				success: function(result, status, xhr) { console.log("View registered"); },
				error: function(xhr, status, error) { console.log(xhr.status, error); }
			});

			let _OPTIONS = {
				aspectratio: 16/9,
				animationspeed: 2000,
				panningspeed: null
			};

			let _SCENES,
				_TEXTBOXES,
				_MAP;

			const _BASEMAPS = [
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
				{ name: "Esri.DeLorme",			tiles: L.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/Specialty/DeLorme_World_Base_Map/MapServer/tile/{z}/{y}/{x}",						{ minZoom: 1, maxZoom: 11, attribution: "Tiles &copy; Esri &mdash; Copyright: &copy;2012 DeLorme" }),																																																															preview: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/5/10/15" },
				{ name: "Esri.TopoMap",			tiles: L.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}",										{ minZoom: 0, maxZoom: 18, attribution: "Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ, TomTom, Intermap, iPC, USGS, FAO, NPS, NRCAN, GeoBase, Kadaster NL, Ordnance Survey, Esri Japan, METI, Esri China (Hong Kong), and the GIS User Community" }),																										preview: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/5/10/15" },
				{ name: "Esri.Imagery",			tiles: L.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",											{ minZoom: 0, maxZoom: 18, attribution: "Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community" }),																																										preview: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/5/10/15" },
				{ name: "Esri.Terrain",			tiles: L.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/World_Terrain_Base/MapServer/tile/{z}/{y}/{x}",									{ minZoom: 0, maxZoom: 13, attribution: "Tiles &copy; Esri &mdash; Source: USGS, Esri, TANA, DeLorme, and NPS" }),																																																												preview: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Terrain_Base/MapServer/tile/5/10/15" },
				{ name: "Esri.OceanBasemap",	tiles: L.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/Ocean_Basemap/MapServer/tile/{z}/{y}/{x}",											{ minZoom: 0, maxZoom: 13, attribution: "Tiles &copy; Esri &mdash; Sources: GEBCO, NOAA, CHS, OSU, UNH, CSUMB, National Geographic, DeLorme, NAVTEQ, and Esri" }),																																																preview: "https://server.arcgisonline.com/ArcGIS/rest/services/Ocean_Basemap/MapServer/tile/5/10/15" },
				{ name: "Esri.NatGeo",			tiles: L.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/NatGeo_World_Map/MapServer/tile/{z}/{y}/{x}",										{ minZoom: 0, maxZoom: 12, attribution: "Tiles &copy; Esri &mdash; National Geographic, Esri, DeLorme, NAVTEQ, UNEP-WCMC, USGS, NASA, ESA, METI, NRCAN, GEBCO, NOAA, iPC" }),																																													preview: "https://server.arcgisonline.com/ArcGIS/rest/services/NatGeo_World_Map/MapServer/tile/5/10/15" },
				{ name: "Esri.GrayCanvas",		tiles: L.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}",							{ minZoom: 0, maxZoom: 16, attribution: "Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ" }),																																																																	preview: "https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/5/10/15" },
				{ name: "CartoDB.Positron",		tiles: L.tileLayer("https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png",																		{ subdomains: "abcd", minZoom: 0, maxZoom: 20, attribution: "&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors &copy; <a href=\"https://carto.com/attributions\">CARTO</a>" }),																																			preview: "https://b.basemaps.cartocdn.com/light_all/5/15/10.png" },
				{ name: "CartoDB.DarkMatter",	tiles: L.tileLayer("https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png",																			{ subdomains: "abcd", minZoom: 0, maxZoom: 20, attribution: "&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors &copy; <a href=\"https://carto.com/attributions\">CARTO</a>" }),																																			preview: "https://b.basemaps.cartocdn.com/dark_all/5/15/10.png" },
				{ name: "CartoDB.Voyager",		tiles: L.tileLayer("https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png",																{ subdomains: "abcd", minZoom: 0, maxZoom: 20, attribution: "&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors &copy; <a href=\"https://carto.com/attributions\">CARTO</a>" }),																																			preview: "https://b.basemaps.cartocdn.com/rastertiles/voyager/5/15/10.png" },
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
		<script type="text/javascript" src="assets/main_pres_1666621876.js"></script>

	</body>
</html>
