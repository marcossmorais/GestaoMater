<?php

if (!isset($_SESSION)) session_start();

isset($_SESSION['Student']) ? '' : header('Location: /portal-aluno');

require __DIR__ . '../../../../config/variables.php';
?>

<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title> Mater - Portal do aluno </title>
    <link href="<?= $app_url ?>/node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= $app_url ?>/node_modules/@fortawesome/fontawesome-free/css/all.css" rel="stylesheet">
    <script> if (localStorage.getItem('nightModeStudent')) { document.documentElement.classList.add('nightMode') } </script>
    <link rel="stylesheet" href="<?= asset('/assets/css/stylesheet.css') ?>">
	<link rel="stylesheet" href="<?= asset('/assets/css/mater.css') ?>">
    <link rel="preload" href="<?= $app_url ?>/assets/fonts/nunito/nunito-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= asset('/assets/css/edu.css') ?>">
    <link rel="shortcut icon" type="imagem/x-icon" href="<?= $app_url ?>/assets/img/logo-components/logo.png" />
</head>

<body id="studentPortal">

    <div class="container-fluid">

        <div id="pagina" class="row accordion">

            <div class="col-lg-12 p-0" id="studentPortal-accordion">

                <nav id="studentPortalNavbar" class="navbar navbar-expand d-flex align-items-center">

                    <a class="logo" href="#">
                        <img class="d-block" id="logo-mater-main" src="<?= $app_url ?>/assets/img/logo-components/logo-completa-480.png" alt="">
                    </a>

                    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="navbarNav">

                        <ul class="navbar-nav d-flex align-items-center justify-content-center">

                            <li class="nav-item active">
                                <a class="nav-link" aria-expanded="true" href="#" data-toggle="collapse" data-target="#mural"><i class="fas fa-thumbtack m-nav-ico m-subj-pink"></i>Mural</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" aria-expanded="false" href="#" data-toggle="collapse" data-target="#bulletin"><i class="fas fa-star m-nav-ico m-subj-sky"></i>Boletim</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" aria-expanded="false" href="#" data-toggle="collapse" data-target="#class" href="#"><i class="fas fa-users m-nav-ico m-subj-purple"></i>Turma</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" aria-expanded="false" href="#" data-toggle="collapse" data-target="#averageNote" href="#"><i class="fas fa-chart-line m-nav-ico m-subj-green"></i>Desempenho</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" aria-expanded="false" href="#" data-toggle="collapse" data-target="#frequency"><i class="fas fa-user-check m-nav-ico m-subj-green"></i> Frequência</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" aria-expanded="false" href="#" data-toggle="collapse" data-target="#classSchedule"><i class="fas fa-clock m-nav-ico m-subj-indigo"></i>Horários</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" aria-expanded="false" href="#" data-toggle="collapse" data-target="#examGuide"><i class="fas fa-route m-nav-ico m-subj-teal"></i>Roteiro de provas</a>
                            </li>


                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="studentDeclaracoesMenu" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-file-contract mr-1"></i> Declarações
                                </a>
                                <div class="dropdown-menu shadow" aria-labelledby="studentDeclaracoesMenu">
                                    <a class="dropdown-item" href="/portal-aluno/declaracao/matricula" target="_blank">
                                        <i class="fas fa-file-alt mr-2 text-primary"></i> Declaração de Matrícula (PDF)
                                    </a>
                                    <a class="dropdown-item" href="/portal-aluno/declaracao/frequencia" target="_blank">
                                        <i class="fas fa-calendar-check mr-2 text-success"></i> Declaração de Frequência (PDF)
                                    </a>
                                </div>
                            </li>

                        </ul>

                        <ul class="navbar-nav ml-auto d-flex align-items-center">

                            <li class="nav-item"><?php $topbarPortal = 'Student'; require __DIR__ . '/../../components/topbarTools.php'; ?></li>

                            <?php $photo = empty($_SESSION['Student']['profilePhoto']) ? 'foto-vazia.jpg' : $_SESSION['Student']['profilePhoto'] ?>

                            <li class="nav-item nav-fixed d-none d-md-block d-flex align-items-center">
                                <a class="nav-link" href="#">
                                    <img data-toggle="tooltip" data-placement="bottom" title="Ver perfil do aluno" class="foto-perfil" src="<?= $app_url ?>/assets/img/studentProfilePhotos/<?= $photo ?>">
                                </a>
                            </li>

                            <li class="nav-item class">
                                <a class="nav-link" aria-expanded="false" href="#"><?= $_SESSION['Student']['class'] ?></a>
                            </li>

                            <li class="nav-item mr-2 nav-fixed">
                                <a href="/portal-aluno/sair" data-toggle="tooltip" data-placement="bottom" title="Sair da conta"><i class="fas fa-sign-out-alt"></i></a>
                            </li>

                        </ul>

                    </div>
                </nav>


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

                <?php
                $firstName = explode(' ', trim($_SESSION['Student']['name']))[0];
                $hour = (int) (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->format('G');
                ?>
                <div class="col-lg-11 mx-auto mt-4">
                    <div class="m-hero m-hero-student">
                        <div class="m-hero-text">
                            <span class="m-sticker"><i class="fas fa-star"></i><?= htmlspecialchars($_SESSION['Student']['class']) ?></span>
                            <h5><?= $hour < 12 ? 'Bom dia' : ($hour < 18 ? 'Boa tarde' : 'Boa noite') ?>, <?= htmlspecialchars($firstName) ?>!</h5>
                            <p>Veja os avisos da turma, o seu boletim e o que vem por aí.</p>
                        </div>
                        <img class="m-hero-art" src="<?= $app_url ?>/assets/img/illustrations/students.svg" alt="">
                    </div>
                </div>

                <div class="col-lg-12">

                    <div class="collapse show mt-3" id="mural" data-parent="#studentPortal-accordion">

                        <div class="col-lg-11 mx-auto main-content">

                            <div class="row mt-4 d-flex justify-content-between ">

                                <div class="col-12 side-mural">

                                    <div class="m-mural-layout">
                                        <div class="m-mural-main">
                                            <?php require __DIR__ . '/../components/board.php'; ?>
                                            <?php require __DIR__ . '/../components/occurrences.php'; ?>
                                        </div>
                                        <aside class="m-mural-aside">
                                            <?php require __DIR__ . '/../components/updates.php'; ?>
                                        </aside>
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="collapse mt-4" id="bulletin" data-parent="#studentPortal-accordion">

                        <div containerBulletin class="col-lg-11 mx-auto card mb-4 mt-3 p-sm-5"></div>

                        <input id="name" type="hidden" value="<?= $_SESSION['Student']['name'] ?>">
                        <input id="courseModality" value="<?= $this->view->studentDataGeneral[0]->course_modality ?>" type="hidden">
                        <input type="hidden" id="schoolYear" value="<?= $this->view->studentDataGeneral[0]->school_year ?>">
                        <input type="hidden" id="numberClassroom" value="<?= $this->view->studentDataGeneral[0]->number_classroom ?>">

                        <input type="hidden" id="classData" value="<?= $this->view->studentDataGeneral[0]->acronym_series ?> <?= $this->view->studentDataGeneral[0]->ballot ?> - <?= $this->view->studentDataGeneral[0]->course ?> - <?= $this->view->studentDataGeneral[0]->shift ?>">

                        <?php $modality = $this->view->studentDataGeneral[0]->course_modality_id == 1 ? '' : 'Técnico' ?>

                        <input type="hidden" id="series" value="<?= $this->view->studentDataGeneral[0]->acronym_series ?>ª - <?= $modality ?> em <?= $this->view->studentDataGeneral[0]->course_name ?> ">

                    </div>

                    <div class="collapse mt-3" id="class" data-parent="#studentPortal-accordion">

                        <div class="row">

                            <div class="col-lg-11 mx-auto">

                                <?php require __DIR__ . '/../components/classmates.php'; ?>

                            </div>

                        </div>

                    </div>


                    <div class="collapse mt-3" id="averageNote" data-parent="#studentPortal-accordion">

                        <div class="row">
                            <div class="col-lg-11 mx-auto">
                                <?php require __DIR__ . '/../components/classCompare.php'; ?>
                            </div>
                        </div>

                    </div>

                    <div class="collapse mt-3" id="frequency" data-parent="#studentPortal-accordion">

                        <?php require __DIR__ . '/../components/frequency.php'; ?>

                    </div>

                    <div class="collapse mt-3" id="classSchedule" data-parent="#studentPortal-accordion">

                        <?php require __DIR__ . '/../components/schedule.php'; ?>

                    </div>

                    <div class="collapse mt-3" id="examGuide" data-parent="#studentPortal-accordion">

                        <?php require __DIR__ . '/../components/examGuide.php'; ?>

                    </div>

                </div>

            </div>


            <div class="toast" id="toastContainer">
					<div class="toast-header text-white">
						<strong class="text-center mx-auto"><span class="icon-toast mr-3"><i class="fas text-white"></i></span>
						<span class="toast-data"></span></strong>
					</div>
				</div>

            <nav class="navbar fixed-bottom navbar-expand p-2 rounded-0" id="navbarBottomStudentPortal" style="border-radius: 0px">

                <div class="collapse navbar-collapse" id="navbarSupportedContent">

                    <ul class="navbar-nav">

                        <li class="nav-item">
                            <a class="nav-link" aria-expanded="true" href="#" data-toggle="collapse" data-target="#mural"><i class="fas fa-home"></i> <span>Home</span></a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" aria-expanded="false" data-toggle="collapse" data-target="#averageNote" href="#"><i class="fas fa-chart-bar"></i> <span>Comparar</span></a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" aria-expanded="false" href="#" data-toggle="collapse" data-target="#class"><i class="fas fa-users"></i> <span>Turma</span></a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" aria-expanded="false" href="#" data-toggle="collapse" data-target="#frequency"><i class="fas fa-user-check"></i> <span>Faltas</span></a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" aria-expanded="false" href="#" data-toggle="collapse" data-target="#bulletin"><i class="fas fa-book-open"></i> <span>Boletim</span></a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" aria-expanded="false" href="#" data-toggle="collapse" data-target="#classSchedule"><i class="fas fa-clock"></i> <span>Horários</span></a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" aria-expanded="false" href="#" data-toggle="collapse" data-target="#examGuide"><i class="fas fa-route"></i> <span>Provas</span></a>
                        </li>

                    </ul>

                </div>

            </nav>
        </div>
    </div>

</body>

<script src="<?= $app_url ?>/node_modules/jquery/dist/jquery.min.js"></script>

<script src="<?= $app_url ?>/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>



<script src="<?= $app_url ?>/node_modules/chart.js/dist/Chart.min.js"></script>

<script src="<?= $app_url ?>/node_modules/jquery-mask-plugin/dist/jquery.mask.min.js"></script>

<script src="<?= asset('/assets/js/utilities/Tools.js') ?>"></script>

<script src="<?= asset('/assets/js/utilities/Validation.js') ?>"></script>

<script src="<?= asset('/assets/js/utilities/Application.js') ?>"></script>

<script src="<?= asset('/assets/js/utilities/Management.js') ?>"></script>

<script src="<?= asset('/assets/js/utilities/style.js') ?>"></script>

<script src="<?= asset('/assets/js/main.js') ?>"></script>

<script src="<?= asset('/assets/js/mater.js') ?>"></script>

<script src="<?= asset('/assets/js/utilities/Bulletin.js') ?>"></script>

<script>
    window.MATER_PERFORMANCE = <?= json_encode($this->view->performanceRule) ?>;
</script>

<script src="<?= asset('/assets/js/performance.js') ?>" defer></script>

<script>
    // tocar de novo na aba aberta não fecha a seção (a tela ficava vazia)
    document.querySelectorAll('#studentPortalNavbar [data-toggle="collapse"], #navbarBottomStudentPortal [data-toggle="collapse"]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            var target = document.querySelector(link.dataset.target);
            if (target && target.classList.contains('show')) { event.preventDefault(); event.stopPropagation(); }
        }, true);
    });

    // depois de enviar um atestado, volta para a aba Frequência
    if (new URLSearchParams(window.location.search).get('aba') === 'frequencia') {
        document.querySelector('#studentPortalNavbar [data-target="#frequency"]').click();
    }
</script>

</html>
<script>
    async function acknowledgeOccurrence(id) {
        if (!confirm('Confirmar ciência dos responsáveis sobre este comunicado escolar?')) return;
        try {
            const formData = new FormData();
            formData.append('id', id);
            const response = await fetch('/portal-aluno/ocorrencias/dar-ciencia', {
                method: 'POST',
                body: formData
            });
            const res = await response.json();
            if (!response.ok || res.status !== 'success') throw new Error(res.error || 'Erro ao registrar ciência.');

            const box = document.getElementById('occAckBox_' + id);
            if (box) {
                box.innerHTML = '<span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i> Ciência dos Pais Confirmada</span>';
            }
            alert('Ciência confirmada com sucesso!');
        } catch (err) {
            alert(err.message || 'Erro ao confirmar ciência.');
        }
    }
</script>
