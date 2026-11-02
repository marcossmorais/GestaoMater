<?php

if (!isset($_SESSION)) session_start();

isset($_SESSION['Teacher']) ? '' : header('Location: /portal-docente');

require __DIR__ . '/../../config/variables.php';
?>

<html lang="pt-br">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title> Mater - Portal do docente</title>
	<link rel = "shortcut icon" type = "imagem/x-icon" href = "<?= $app_url ?>/assets/img/logo-components/logo.png"/>
	<link rel="manifest" href="/manifest.json">
	<meta name="theme-color" content="#0838B8">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-title" content="Mater">
	<link rel="apple-touch-icon" href="<?= $app_url ?>/assets/img/app/icon-192.png">
	<script> if (localStorage.getItem('nightModeTeacher')) { document.documentElement.classList.add('nightMode') } </script>
	<link rel="stylesheet" href="<?= asset('/assets/css/stylesheet.css') ?>">
	<link rel="stylesheet" href="<?= asset('/assets/css/mater.css') ?>">
	<link href="<?= $app_url ?>/node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="<?= $app_url ?>/node_modules/@fortawesome/fontawesome-free/css/all.css" rel="stylesheet">

</head>

<body id="teacherPortal">

	<div class="container-fluid">

		<div class="row">

			<div class="col-lg-2 col-md-3 col-sm-2 panel-side">
				<?php require '../App/Views/teacherPortal/components/painelLeft.php' ?>
			</div>

			<div id="pagina" class="col-12 col-md-10 col-lg-10 col-sm-12 ml-auto">

				<?php require '../App/Views/teacherPortal/components/navbar.php' ?>

				<div class="toast" id="toastContainer">
					<div class="toast-header text-white">
						<strong class="text-center mx-auto"><span class="icon-toast mr-3"><i class="fas text-white"></i></span>
						<span class="toast-data"></span></strong>
					</div>
				</div>


				<?= $this->content() ?>


				<div class="modal fade modal-profile" id="settingsModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-hidden="true">

					<div class="modal-dialog modal-full">
						<div class="modal-content">
							<div class="row">
								<div class="col-lg-12"> <button type="button" class="close text-rig" data-dismiss="modal" aria-label="Close" data-toggle="modal" data-target="#settingsModal">
										<span aria-hidden="true"><i class="fas fa-times-circle text-info mr-3 mt-2"></i></span>
									</button></div>
							</div>

							<div containerSettingsModal class="modal-body"></div>
						</div>
					</div>
				</div>

				<?php require '../App/Views/teacherPortal/components/navbarBottom.php' ?>

			</div>
		</div>
	</div>



</body>

<script src="<?= $app_url ?>/node_modules/jquery/dist/jquery.min.js"></script>

<script src="<?= $app_url ?>/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>





<script src="<?= $app_url ?>/node_modules/jquery-mask-plugin/dist/jquery.mask.min.js"></script>

<script src="<?= asset('/assets/js/utilities/Tools.js') ?>"></script>

<script src="<?= asset('/assets/js/utilities/Validation.js') ?>"></script>

<script src="<?= asset('/assets/js/utilities/Application.js') ?>"></script>

<script src="<?= asset('/assets/js/utilities/Management.js') ?>"></script>

<script src="<?= asset('/assets/js/utilities/style.js') ?>"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script src="<?= asset('/assets/js/utilities/chartJs/config.js') ?>"></script> 

<script src="<?= asset('/assets/js/utilities/chartJs/teacherPortal.js') ?>"></script> 

<script src="<?= asset('/assets/js/main.js') ?>"></script>

<script src="<?= asset('/assets/js/mater.js') ?>"></script>

<script src="<?= asset('/assets/js/pwa.js') ?>"></script>

<script src="<?= asset('/assets/js/utilities/Bulletin.js') ?>"></script>

</html>