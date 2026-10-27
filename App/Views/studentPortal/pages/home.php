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
    <link rel="shortcut icon" type="imagem/x-icon" href="<?= $app_url ?>/assets/img/logo-components/logo.png" />
</head>

<body id="studentPortal">

    <div class="container-fluid">

        <div id="pagina" class="row accordion">

            <div class="col-lg-12 p-0" id="studentPortal-accordion">

                <nav id="studentPortalNavbar" class="navbar navbar-expand d-flex align-items-center">

                    <a class="logo" href="#">
                        <img class="d-block" id="logo-mater-main" src="<?= $app_url ?>/assets/img/logo-components/logo-completa.png" alt="">
                    </a>

                    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="navbarNav">

                        <ul class="navbar-nav d-flex align-items-center justify-content-center">

                            <li class="nav-item active">
                                <a class="nav-link" aria-expanded="true" href="#" data-toggle="collapse" data-target="#mural">Mural</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" aria-expanded="false" href="#" data-toggle="collapse" data-target="#bulletin">Boletim</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" aria-expanded="false" href="#" data-toggle="collapse" data-target="#class" href="#">Turma</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" aria-expanded="false" href="#" data-toggle="collapse" data-target="#averageNote" href="#">Desempenho</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" aria-expanded="false" href="#" data-toggle="collapse" data-target="#studentSchedule">Agenda</a>
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

                <div class="col-lg-12 mt-3">

                    <div class="collapse show mt-3" id="mural" data-parent="#studentPortal-accordion">

                        <div class="col-lg-11 mx-auto main-content">

                            <div class="row mt-4 d-flex justify-content-between ">

                                <div class="col-sm-4 col-lg-3 side-exams">

                                    <div class="row side-exams-content">

                                        <?php

                                        if (count($this->view->listExam) != 0) {

                                            foreach ($this->view->listExam as $key => $part) {
                                                $sort[$key] = strtotime($part->post_date);
                                            }

                                            array_multisort($sort, SORT_DESC, $this->view->listExam);
                                        }

                                        $photoDir =  "$app_url/assets/img/teacherProfilePhotos/";

                                        ?>


                                        <h5 class="p-2 col-11 p-sm-0">Atividades agendadas</h5>

                                        <?php if (count($this->view->listExam) > 0) { ?>

                                            <?php foreach ($this->view->listExam as $key => $value) { ?>

                                                <div class="col-11 card mb-3">

                                                    <div class="row d-flex justify-content-center align-items-center">

                                                        <div class="col-2"><img class="miniature-photo" src='<?= $value->profile_photo == null ? $photoDir . "foto-vazia.jpg" : $photoDir . $value->profile_photo ?>' alt="" onerror='this.src="<?= $photoDir . "foto-vazia.jpg" ?>"'></div>

                                                        <div class="col-10 name-teacher"><?= $value->teacher_name ?></div>

                                                    </div>

                                                    <div class="row">

                                                        <?php

                                                        $data = explode(' ', $value->realize_date);
                                                        $data = explode('-', $data[0]);

                                                        $pointsOrTenths = '';

                                                        switch ($value->exam_value) {
                                                            case $value->exam_value > 1:
                                                                $pointsOrTenths = " pontos";
                                                                break;
                                                            case $value->exam_value < 1:
                                                                $pointsOrTenths = " décimos";
                                                                break;
                                                            case $value->exam_value == 1:
                                                                $pointsOrTenths = " ponto";
                                                                break;
                                                        }

                                                        ?>

                                                        <div class="col-lg-12 exam-description"><?= htmlspecialchars($value->discipline_name) ?> - <?= htmlspecialchars($value->exam_description) ?> - <?= $value->unity ?> ª Unidade </div>

                                                    </div>

                                                    <div class="row exam-description-footer">

                                                        <div class="col-6">Valor: <?= $value->exam_value ?> <?= $pointsOrTenths ?></div>

                                                        <div class="col-6">Data: <?= $data[2] ?>-<?= $data[1] ?>-<?= $data[0] ?></div>

                                                    </div>

                                                </div>

                                            <?php }
                                        } else { ?>


                                            <div class="col-lg-11 card mb-3">
                                                <p>Nenhum exame adicionado até o momento</p>
                                            </div>

                                        <?php } ?>

                                    </div>

                                </div>

                                <div class="col-sm-8 col-lg-9 side-mural">

                                    <?php require __DIR__ . '/../components/board.php'; ?>

                                    <div class="row">

                                        <h5 class="title-mural">Postagens recentes</h5>

                                        <?php

                                        $data = [];
                                        $photoDir =  "$app_url/assets/img/teacherProfilePhotos/";
                                        $sort = [];

                                        foreach ($this->view->listNote as $key => $note) {
                                            $data['dados'][] = ['tipo' => 'note', 'value' => $note];
                                        }

                                        foreach ($this->view->listObservation as $key => $observation) {
                                            $data['dados'][] = ['tipo' => 'observation', 'value' => $observation];
                                        }

                                        foreach ($this->view->lackList as $key => $lack) {
                                            $data['dados'][] = ['tipo' => 'lack', 'value' => $lack];
                                        }

                                        foreach ($this->view->disciplineAverageList as $key => $disciplineAverage) {
                                            $data['dados'][] = ['tipo' => 'disciplineAverage', 'value' => $disciplineAverage];
                                        }

                                        if (!empty($this->view->listOccurrences)) {
                                            foreach ($this->view->listOccurrences as $key => $occ) {
                                                $data['dados'][] = ['tipo' => 'occurrence', 'value' => $occ];
                                            }
                                        }


                                        if (count($data) != 0) {

                                            foreach ($data['dados'] as $key => $part) {
                                                $date = $part['value']->post_date ?? $part['value']->data_ocorrencia_fmt ?? date('Y-m-d');
                                                $sort[$key] = $date;
                                            }

                                            array_multisort($sort, SORT_DESC, $data['dados']);
                                        }

                                        if (!function_exists("currentDate")) {
function currentDate($array)
{

                                            date_default_timezone_set('America/Sao_Paulo');
                                            $today = date('d-m');

                                            $dataFull = explode(' ', $array);
                                            $data = explode('-', $dataFull[0]);
                                            $horas = explode(':', $dataFull[1]);

                                            $data = $data[2] . '/' . $data[1];
                                            $horas = $horas[0] . ':' . $horas[1];

                                            $data = ($data == $today ? ' Hoje' : ' em ' . $data . ' as ' . $horas);

                                            return $data;
                                        }
}

                                        ?>

                                        <?php if (count($data) != 0) { ?>

                                            <?php foreach ($data['dados'] as $key => $value) {

                                                if ($value['tipo'] == 'note') { ?>

                                                    <div class="col-11 mx-auto card mb-3">

                                                        <div class="row p-2">

                                                            <div class="col-sm-1  d-flex justify-content-center align-items-center"><img class="miniature-photo" src='<?= $value['value']->teacher_profile_photo == null ? $photoDir . "foto-vazia.jpg" : $photoDir . $value['value']->teacher_profile_photo ?>' alt="" onerror='this.src="<?= $photoDir . "foto-vazia.jpg" ?>"'></div>

                                                            <div class="col-sm-11">

                                                                <p class="mt-2 text-description mb-2"><?= $value['value']->teacher_name ?> atribuiu sua nota na avaliação - <?= $value['value']->exam_description ?> - da <?= $value['value']->unity ?> unidade de <?= $value['value']->discipline_name ?></p>

                                                            </div>

                                                        </div>

                                                        <div class="row p-2">

                                                            <div class="col-12">
                                                                <div class="row">
                                                                    <div class="col-lg-8"><small class="font-weight-bold text-important">Você obteve <?= number_format($value['value']->note_value, 1, '.', '') ?> de um total de <?= number_format($value['value']->exam_value, 1, '.', '') ?> pontos</small></div>

                                                                    <div class="col-lg-4 d-flex justify-content-end"><small class="text-muted p-0 mt-3"> <i class="fas fa-history mr-2"></i>Postado <?= currentDate($value['value']->post_date) ?></small></div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                    </div>

                                                <?php } else if ($value['tipo'] == 'observation') { ?>

                                                    <div class="col-11 mx-auto card mb-3">

                                                        <div class="row p-2">

                                                            <div class="col-sm-1 d-flex justify-content-center align-items-start mx-auto"><img class="miniature-photo" src='<?= $value['value']->teacher_profile_photo == null ? $photoDir . "foto-vazia.jpg" : $photoDir . $value['value']->teacher_profile_photo ?>' alt="" onerror='this.src="<?= $photoDir . "foto-vazia.jpg" ?>"'></div>

                                                            <div class="col-sm-11">

                                                                <p class="mt-2 text-description mb-3">Você recebeu uma observação de <?= $value['value']->teacher_name ?> referente a <?= $value['value']->unity ?> unidade de <?= $value['value']->discipline_name ?></p>

                                                            </div>

                                                        </div>

                                                        <div class="row p-2">

                                                            <div class="col-lg-12">

                                                                <textarea class="col-lg-12 form-control p-3" disabled name="" id="" cols="30" rows="3" value=""><?= $value['value']->observation_description ?></textarea>

                                                                <small class="text-muted col-lg-12 d-flex justify-content-end align-items-center mt-3 p-0"> <i class="fas fa-history mr-2"></i>Postado <?= currentDate($value['value']->post_date) ?></small>

                                                            </div>

                                                        </div>

                                                    </div>


                                                <?php } else if ($value['tipo'] == 'lack') { ?>

                                                    <div class="col-11 mx-auto card mb-3">

                                                        <div class="row p-2">

                                                            <div class="col-sm-1 d-flex justify-content-center align-items-center mx-auto"><img class="miniature-photo" src='<?= $value['value']->teacher_profile_photo == null ? $photoDir . "foto-vazia.jpg" : $photoDir . $value['value']->teacher_profile_photo ?>' alt="" onerror='this.src="<?= $photoDir . "foto-vazia.jpg" ?>"'></div>

                                                            <div class="col-sm-11">

                                                                <p class="mt-2 text-description mb-2"><?= $value['value']->teacher_name ?> atribuiu suas faltas da <?= $value['value']->unity ?> unidade de <?= $value['value']->discipline_name ?></p>

                                                            </div>

                                                        </div>

                                                        <div class="row p-2">

                                                            <div class="col-lg-12">
                                                                <div class="row">
                                                                    <div class="col-lg-8">
                                                                        <small class="font-weight-bold text-important">Você obteve um total de <?= $value['value']->total_lack ?> faltas</small>
                                                                        <?php if (!empty($value['value']->excused_lack) && $value['value']->excused_lack > 0) { ?>
                                                                            <span class="badge badge-success ml-2" title="Faltas abonadas por atestado médico ou justificativa regulamentar"><i class="fas fa-check mr-1"></i><?= $value['value']->excused_lack ?> justificadas por atestado</span>
                                                                        <?php } ?>
                                                                    </div>

                                                                    <div class="col-lg-4 d-flex justify-content-end mt-xs-3"><small class="text-muted p-0 mt-3"> <i class="fas fa-history mr-2"></i>Postado <?= currentDate($value['value']->post_date) ?></small></div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                    </div>


                                                <?php } else if ($value['tipo'] == 'disciplineAverage') { ?>


                                                    <div class="col-11 mx-auto card mb-3">

                                                        <div class="row p-2">

                                                            <div class="col-sm-1 d-flex justify-content-center align-items-center mx-auto"><img class="miniature-photo" src='<?= $value['value']->teacher_profile_photo == null ? $photoDir . "foto-vazia.jpg" : $photoDir . $value['value']->teacher_profile_photo ?>' alt="" onerror='this.src="<?= $photoDir . "foto-vazia.jpg" ?>"'></div>

                                                            <div class="col-sm-11">

                                                                <p class="mt-2 text-description mb-2"><?= $value['value']->teacher_name ?> atribuiu a sua média final na disciplina de <?= $value['value']->discipline_name ?></p>

                                                            </div>

                                                        </div>

                                                        <div class="row p-2">

                                                            <div class="col-lg-12">
                                                                <div class="row">
                                                                    <div class="col-lg-8"><small class="font-weight-bold text-important">Sua média final foi de <?= number_format($value['value']->average, 1, '.', '') ?> - Você está <?= lcfirst($value['value']->subtitle) ?> na disciplina</small></div>

                                                                    <div class="col-lg-4 d-flex justify-content-end mt-3"><small class="text-muted p-0"> <i class="fas fa-history mr-2"></i>Postado <?= currentDate($value['value']->post_date) ?></small></div>
                                                                </div>
                                                            </div>

                                                        </div>

                                                    </div>


                                                <?php } else if ($value['tipo'] == 'occurrence') { 
    $occ = $value['value'];
    $icon = 'fa-info-circle text-secondary';
    $badgeStyle = 'badge-secondary';
    if ($occ->categoria === 'Elogio') {
        $icon = 'fa-award text-success';
        $badgeStyle = 'badge-success';
    } else if ($occ->categoria === 'Disciplinar') {
        $icon = 'fa-exclamation-triangle text-warning';
        $badgeStyle = 'badge-warning text-dark';
    } else if ($occ->categoria === 'Pedagógica') {
        $icon = 'fa-book-reader text-primary';
        $badgeStyle = 'badge-primary';
    } else if ($occ->categoria === 'Saúde') {
        $icon = 'fa-heartbeat text-danger';
        $badgeStyle = 'badge-danger';
    }
?>
    <div class="col-11 mx-auto card mb-3 shadow-sm border" style="border-radius: 12px;" data-portal-occ="<?= $occ->id_ocorrencia ?>">
        <div class="row p-3">
            <div class="col-sm-1 d-flex justify-content-center align-items-center">
                <i class="fas <?= $icon ?> fa-2x"></i>
            </div>
            <div class="col-sm-11">
                <div class="d-flex align-items-center justify-content-between flex-wrap">
                    <div>
                        <span class="badge <?= $badgeStyle ?> mr-2"><?= $occ->categoria ?></span>
                        <strong class="text-dark"><?= htmlspecialchars($occ->titulo) ?></strong>
                    </div>
                    <small class="text-muted"><i class="fas fa-calendar-alt mr-1"></i><?= $occ->data_ocorrencia_fmt ?></small>
                </div>
                <p class="mt-2 text-muted mb-2"><?= nl2br(htmlspecialchars($occ->descricao)) ?></p>

                <?php if (!empty($occ->medida_tomada)) { ?>
                    <div class="p-2 mb-2 rounded bg-light small">
                        <strong>Providência / Medida:</strong> <?= htmlspecialchars($occ->medida_tomada) ?>
                    </div>
                <?php } ?>

                <div class="d-flex align-items-center justify-content-between flex-wrap mt-2 pt-2 border-top">
                    <small class="text-muted"><i class="fas fa-user-edit mr-1"></i>Registrado por <?= htmlspecialchars($occ->registrado_por) ?></small>

                    <?php if ($occ->requer_ciencia) { ?>
                        <div id="occAckBox_<?= $occ->id_ocorrencia ?>">
                            <?php if ($occ->ciencia_responsavel) { ?>
                                <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i> Ciência dos Pais Confirmada</span>
                            <?php } else { ?>
                                <button type="button" class="btn btn-sm btn-outline-success font-weight-bold" onclick="acknowledgeOccurrence(<?= $occ->id_ocorrencia ?>)">
                                    <i class="fas fa-signature mr-1"></i> Confirmar Ciência dos Responsáveis
                                </button>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

<?php } else if ($value['tipo'] == 'warning') { ?>


                                                    <div class="col-11 mx-auto card mb-3">

                                                        <div class="row p-2">

                                                            <div class="col-sm-1 mx-auto d-flex justify-content-center align-items-start"><img class="miniature-photo" src='<?= $value['value']->teacher_profile_photo == null ? $photoDir . "foto-vazia.jpg" : $photoDir . $value['value']->teacher_profile_photo ?>' alt="" onerror='this.src="<?= $photoDir . "foto-vazia.jpg" ?>"'></div>

                                                            <div class="col-sm-11">

                                                                <p class="mt-2 text-description"><?= $value['value']->teacher_name ?> adicionou um aviso referente a disciplina de <?= $value['value']->discipline_name ?></p>

                                                            </div>

                                                        </div>

                                                        <div class="row p-2">
                                                            <div class="col-lg-12">

                                                                <textarea class="col-lg-12 form-control p-3" disabled name="" id="" cols="40" rows="4" value="" ><?= htmlspecialchars($value['value']->warning) ?></textarea>

                                                                <small class="text-muted col-lg-12 d-flex justify-content-end align-items-center mt-3 p-0"> <i class="fas fa-history mr-2"></i>Postado <?= currentDate($value['value']->post_date) ?></small>

                                                            </div>

                                                        </div>

                                                    </div>

                                            <?php }
                                            }
                                        } else { ?>

                                            <div class="col-sm-11 mx-auto posts-null card mb-3">Nenhuma postagem até o momento</div>

                                        <?php } ?>

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

                                <div class="row mt-3 d-flex mb-4">

                                    <div class="accordion col-lg-12" id="studentsTeachersAccordion">

                                        <p class="text-right">

                                            <a class="btn" type="button" data-toggle="collapse" data-target="#students" aria-expanded="true" aria-controls="students">
                                                <span><i class="fas fa-users mr-2"></i> Alunos</span>
                                            </a>

                                            <a class="btn" type="button" data-toggle="collapse" data-target="#teachers" aria-expanded="false" aria-controls="teachers">
                                                <span><i class="fas fa-chalkboard-teacher mr-2"></i> Docentes</span>
                                            </a>

                                        </p>


                                        <div id="students" class="collapse show" data-parent="#studentsTeachersAccordion">

                                            <div class="card col-lg-12 table-responsive mb-5">

                                                <table class="table table-hover mt-3 table-borderless" id="table-students">

                                                    <thead>
                                                        <tr>
                                                            <th colspan="2">Nome do aluno</th>
                                                            <th>Email</th>
                                                            <th>Status</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody class="">

                                                        <?php require '../App/Views/admin/student/components/studentListing.php' ?>

                                                    </tbody>

                                                </table>

                                            </div>

                                        </div>

                                        <div id="teachers" class="collapse" data-parent="#studentsTeachersAccordion">

                                            <div class="card col-lg-12 table-responsive mb-5">

                                                <table class="table table-hover mt-3 table-borderless" id="table-teachers">

                                                    <thead>
                                                        <tr>
                                                            <th colspan="2">Nome do docente</th>
                                                            <th>Email</th>
                                                            <th>Disciplina</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody class="">

                                                        <?php require '../App/Views/admin/teacher/components/teacherListing.php' ?>

                                                    </tbody>

                                                </table>



                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="collapse mt-3" id="averageNote" data-parent="#studentPortal-accordion">

                        <?php require __DIR__ . '/../components/performance.php'; ?>

                    </div>

                </div>

            </div>

                                <div class="collapse mt-3" id="studentSchedule" data-parent="#studentPortal-accordion">

                        <?php require __DIR__ . '/../components/agenda.php'; ?>

                        <div class="row">

                            <div class="col-lg-11 mx-auto main-content">

                                <?php
                                $scheduleByDay = [];
                                for ($d = 2; $d <= 6; $d++) $scheduleByDay[$d] = [];
                                $timeSlots = [];
                                if (!empty($this->view->classSchedule)) {
                                    foreach ($this->view->classSchedule as $s) {
                                        $scheduleByDay[$s->dia_semana][] = $s;
                                        if (!isset($timeSlots[$s->ordem_aula])) {
                                            $timeSlots[$s->ordem_aula] = [
                                                'ordem' => $s->ordem_aula,
                                                'start' => $s->hora_inicio,
                                                'end'   => $s->hora_fim,
                                            ];
                                        }
                                    }
                                    ksort($timeSlots);
                                }
                                $dayNames = $this->view->dayNames ?? \App\Models\GeneralManagement\ClassSchedule::dayNames();
                                $shortDayNames = $this->view->shortDayNames ?? \App\Models\GeneralManagement\ClassSchedule::shortDayNames();
                                $studentCurrentDay = (int) date('N') + 1;
                                if ($studentCurrentDay < 2 || $studentCurrentDay > 6) $studentCurrentDay = 2;
                                ?>

                                <!-- Cabeçalho com estilo Sophia / Superapp -->
                                <div class="m-page-head mt-3">
                                    <div>
                                        <h5>Quadro de horários</h5>
                                        <p>Horários das aulas e professores da sua turma (<?= htmlspecialchars($_SESSION['Student']['class']) ?>).</p>
                                    </div>
                                    <div class="m-actions align-items-center">
                                        <span class="m-pill m-pill-info"><i class="fas fa-clock mr-1"></i> 25 aulas semanais</span>
                                        <div class="m-view-switch m-seg" data-student-view-mode>
                                            <button type="button" class="is-active" data-mode="timeline"><i class="fas fa-list-ul mr-1"></i> Por dia</button>
                                            <button type="button" data-mode="grid"><i class="fas fa-table mr-1"></i> Grade completa</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- MODO 1: VISÃO POR DIA (TIMELINE ESPAÇOSA E LIMPA) -->
                                <div id="studentTimelineView">

                                    <!-- Seletor de Dias em Abas Grandes -->
                                    <div class="m-day-tabs" data-student-day-tabs>
                                        <?php for ($d = 2; $d <= 6; $d++) { 
                                            $count = count($scheduleByDay[$d] ?? []);
                                            $isActive = ($d === $studentCurrentDay);
                                        ?>
                                            <button type="button" class="m-day-tab <?= $isActive ? 'is-active' : '' ?>" data-day="<?= $d ?>">
                                                <span class="m-day-tab-name"><?= $dayNames[$d] ?></span>
                                                <span class="m-day-tab-badge"><?= $count ?> aula<?= $count !== 1 ? 's' : '' ?></span>
                                            </button>
                                        <?php } ?>
                                    </div>

                                    <!-- Lista de Cards do Dia Selecionado -->
                                    <?php for ($d = 2; $d <= 6; $d++) { 
                                        $slots = $scheduleByDay[$d] ?? [];
                                        $isShown = ($d === $studentCurrentDay);
                                    ?>
                                        <div class="m-day-content <?= $isShown ? 'is-visible' : '' ?>" data-student-day-panel="<?= $d ?>">

                                            <?php if (count($slots) > 0) { ?>

                                                <div class="m-timeline-list">
                                                    <?php foreach ($slots as $slot) { ?>
                                                        <div class="card m-timeline-card">
                                                            
                                                            <div class="m-timeline-time">
                                                                <span class="m-time-pill"><?= $slot->hora_inicio ?> – <?= $slot->hora_fim ?></span>
                                                                <small class="m-ordem-text"><?= $slot->ordem_aula ?>ª aula</small>
                                                            </div>

                                                            <div class="m-timeline-info">
                                                                <div class="m-timeline-tags">
                                                                    <span class="m-tag m-tag-teacher"><i class="fas fa-chalkboard-teacher mr-1"></i><?= htmlspecialchars($slot->nome_professor) ?></span>
                                                                    <span class="m-tag m-tag-room"><i class="fas fa-map-marker-alt mr-1"></i><?= htmlspecialchars($slot->sala) ?></span>
                                                                </div>
                                                                <h6 class="m-timeline-title"><?= htmlspecialchars($slot->nome_disciplina) ?></h6>
                                                            </div>

                                                        </div>
                                                    <?php } ?>
                                                </div>

                                            <?php } else { ?>

                                                <div class="card m-panel m-empty py-5">
                                                    <img src="<?= $app_url ?>/assets/img/illustrations/calendar.svg" alt="" style="max-width: 140px;">
                                                    <h6>Nenhuma aula agendada para <?= $dayNames[$d] ?></h6>
                                                    <p class="text-muted">Aproveite para revisar os conteúdos e tarefas.</p>
                                                </div>

                                            <?php } ?>

                                        </div>
                                    <?php } ?>

                                </div>

                                <!-- MODO 2: GRADE COMPLETA (MATRIZ SEMANAL VISUAL) -->
                                <div id="studentGridView" style="display: none;">
                                    <div class="card m-panel p-0 overflow-hidden">
                                        <div class="table-responsive">
                                            <table class="table table-bordered m-schedule-table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 120px;" class="text-center">Horário</th>
                                                        <?php for ($d = 2; $d <= 6; $d++) { ?>
                                                            <th class="text-center <?= $d === $studentCurrentDay ? 'bg-primary-soft' : '' ?>">
                                                                <?= $shortDayNames[$d] ?>
                                                            </th>
                                                        <?php } ?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    foreach ($timeSlots as $tKey => $tInfo) { 
                                                    ?>
                                                        <tr>
                                                            <td class="m-matrix-time text-center">
                                                                <strong><?= $tInfo['start'] ?></strong>
                                                                <small><?= $tInfo['end'] ?></small>
                                                            </td>

                                                            <?php for ($d = 2; $d <= 6; $d++) { 
                                                                $matched = null;
                                                                foreach ($scheduleByDay[$d] ?? [] as $s) {
                                                                    if ($s->ordem_aula == $tKey) {
                                                                        $matched = $s;
                                                                        break;
                                                                    }
                                                                }
                                                            ?>
                                                                <td class="m-matrix-cell <?= $matched ? 'is-occupied' : 'is-free' ?>">
                                                                    <?php if ($matched) { ?>
                                                                        <div class="m-cell-content">
                                                                            <span class="m-cell-badge"><?= $matched->sigla_disciplina ?></span>
                                                                            <strong class="m-cell-disc"><?= htmlspecialchars($matched->nome_disciplina) ?></strong>
                                                                            <small class="m-cell-room"><i class="fas fa-chalkboard-teacher mr-1"></i><?= htmlspecialchars($matched->nome_professor) ?></small>
                                                                            <small class="text-muted"><i class="fas fa-map-marker-alt mr-1"></i><?= htmlspecialchars($matched->sala) ?></small>
                                                                        </div>
                                                                    <?php } else { ?>
                                                                        <span class="m-cell-empty">—</span>
                                                                    <?php } ?>
                                                                </td>
                                                            <?php } ?>
                                                        </tr>

                                                        <?php if ($tKey == 2) { ?>
                                                            <tr class="m-recreio-row">
                                                                <td colspan="6" class="text-center">
                                                                    <i class="fas fa-coffee mr-2"></i> Intervalo / Recreio
                                                                </td>
                                                            </tr>
                                                        <?php } ?>

                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

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
                            <a class="nav-link" aria-expanded="false" data-toggle="collapse" data-target="#averageNote" href="#"><i class="fas fa-layer-group"></i> <span>Notas</span></a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" aria-expanded="false" href="#" data-toggle="collapse" data-target="#class"><i class="fas fa-users"></i> <span>Turma</span></a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" aria-expanded="false" href="#" data-toggle="collapse" data-target="#bulletin"><i class="fas fa-book-open"></i> <span>Boletim</span></a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" aria-expanded="false" href="#" data-toggle="collapse" data-target="#studentSchedule"><i class="fas fa-calendar-alt"></i> <span>Agenda</span></a>
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
