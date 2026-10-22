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
                                <a class="nav-link" aria-expanded="false" href="#" data-toggle="collapse" data-target="#averageNote" href="#">Notas e médias</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" aria-expanded="false" href="#" data-toggle="collapse" data-target="#studentSchedule">Horários</a>
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

                                        <?php if (count($this->view->listExam) > 1) { ?>

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

                                                        <div class="col-lg-12 exam-description"><?= $value->discipline_name ?> - <?= $value->exam_description ?> - <?= $value->unity ?> ª Unidade </div>

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

                                        foreach ($this->view->listWarning as $key => $value) {
                                            $data['dados'][] = ['tipo' => 'warning', 'value' => $value];
                                        }


                                        if (count($data) != 0) {

                                            foreach ($data['dados'] as $key => $part) {
                                                $date = $part['value']->post_date;
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

                                        <?php if (count($this->view->checkForRegistration) != 1 && $this->view->currentStatusRematrium[0]->option_value == 1 && $this->view->studentDataGeneral[0]->school_term_situation == 1) { ?>

                                            <div class="col-sm-11 mx-auto card mb-3">

                                                <div class="row p-2">

                                                    <div class="col-lg-12">

                                                        <div class="row d-flex align-items-center">

                                                            <h5 class="col-lg-8">Rematrícula</h5>

                                                            <div class="col-lg-4 d-flex justify-content-end align-items-center"><i class="fas fa-paperclip text-secondary" data-toggle="tooltip" data-placement="left" title="Fixado"></i></div>

                                                        </div>

                                                        <div class="row">

                                                            <form action="" class="col-lg-12" id="addRematrug">

                                                                <input type="hidden" name="enrollmentId" value="<?= $_SESSION['Student']['enrollmentId'] ?>">

                                                                <div class="form-row mt-3 ">

                                                                    <div class="form-group col-lg-8">

                                                                        <label for="">Você deseja ser rematrículado ?</label><br>

                                                                        <?php foreach ($this->view->rematrugSituationList as $key => $value) { ?>

                                                                            <div class="custom-control custom-radio custom-control-inline mt-3">
                                                                                <input type="radio" id="rematricula<?= $value->option_value ?>" value="<?= $value->option_value ?>" name="rematrugSituation" class="custom-control-input">
                                                                                <label class="custom-control-label" for="rematricula<?= $value->option_value ?>"><?= $value->option_text ?></label>
                                                                            </div>

                                                                        <?php } ?>

                                                                    </div>

                                                                    <div class="form-group col-lg-3 ml-auto">

                                                                        <label for="">&nbsp;</label></br>

                                                                        <button id="buttonAddRematrug" type="submit" class="btn main-button text-white w-100">Confirmar</button>

                                                                    </div>

                                                                </div>

                                                            </form>

                                                        </div>

                                                    </div>

                                                </div>

                                            </div>

                                        <?php } ?>

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
                                                                    <div class="col-lg-8"><small class="font-weight-bold text-important">Você obteve um total de <?= $value['value']->total_lack ?> faltas</small></div>

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

                                                                <textarea class="col-lg-12 form-control p-3" disabled name="" id="" cols="40" rows="4" value=""><?= $value['value']->warning ?></textarea>

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

                        <div class="row">

                            <div class="col-lg-11 mx-auto">

                                <div class="row mt-3 d-flex mb-4">

                                    <div class="accordion col-lg-12" id="averageAndNotes">

                                        <p class="text-right">

                                            <a class="btn" type="button" data-toggle="collapse" data-target="#notes" aria-expanded="true" aria-controls="notes">
                                                <i class="fas fa-grip-vertical mr-2"></i> Notas das avaliações
                                            </a>
                                            <a class="btn" type="button" data-toggle="collapse" data-target="#average" aria-expanded="false" aria-controls="average">
                                                <i class="fas fa-tasks mr-2"></i> Médias gerais
                                            </a>

                                        </p>


                                        <div id="average" class="collapse" data-parent="#averageAndNotes">

                                            <div class="card col-lg-12">

                                                <div class='col-lg-11 mx-auto'>

                                                    <form id="seekAverageStudentProfile" class="text-dark mt-3 accordion" action="">

                                                        <input type="hidden" value="<?= $_SESSION['Student']['enrollmentId'] ?>" name="enrollmentId">
                                                        <input type="hidden" value="<?= $_SESSION['Student']['classId'] ?>" name="classId">

                                                        <div class="form-row mt-3">

                                                            <div class="form-group col-lg-4 col-8">

                                                                <label for="">Disciplina:</label>

                                                                <select id="disciplineClass" class="form-control custom-select" name="disciplineClass" required>

                                                                    <option value="0">Todas</option>

                                                                    <?php foreach ($this->view->linkedDisciplines as $key => $discipline) { ?>

                                                                        <option value="<?= $discipline->option_value ?>"><?= $discipline->option_text ?></option>

                                                                    <?php } ?>

                                                                </select>

                                                            </div>

                                                            <div class="form-group col-lg-2 col-4">

                                                                <label for="">Unidade:</label>

                                                                <select id="unity" class="form-control custom-select" name="unity" required>

                                                                    <option value="0">Todas</option>

                                                                        <?php foreach ($this->view->unity as $key => $unity) { ?>

                                                                            <option value="<?= $unity->option_value ?>"><?= $unity->option_text ?></option>

                                                                    <?php } ?>

                                                                </select>

                                                            </div>



                                                            <div class="form-group col-lg-4 col-8">

                                                                <label for="">Status da média:</label>

                                                                <select id="noteStatus" class="form-control custom-select" name="noteStatus" required>

                                                                    <option value="0">Todos os status</option>
                                                                    <option value="Aprovado">Aprovado</option>
                                                                    <option value="Reprovado">Reprovado</option>

                                                                </select>

                                                            </div>

                                                            <div class="form-group col-lg-2 col-4">
                                                                <label for="">&nbsp;</label>

                                                                <div>
                                                                    <a class="btn btn-light w-100 p-2" href="" data-toggle="collapse" data-target="#activate-advanced-search-accordion" aria-expanded="false" aria-controls="activate-advanced-search-accordion" data-toggle="tooltip" data-placement="top" title="Busca avançada"><i class="fas fa-filter"></i></a>
                                                                </div>
                                                            </div>

                                                        </div>

                                                        <div id="activate-advanced-search-accordion" class="collapse" data-parent="#seekAverageStudentProfile">

                                                            <div class="form-row">

                                                                <div class="form-group col-lg-3 col-6">

                                                                    <label for="">Ordenar por:</label>

                                                                    <select id="orderBy" class="form-control custom-select" name="orderBy" required>

                                                                        <option value="highestGrade">Maior média</option>
                                                                        <option value="lowestGrade">Menor média</option>
                                                                        <option value="alphabetical">Ordem Alfabética</option>

                                                                    </select>

                                                                </div>

                                                                <div class="form-group col-lg-3 col-6">

                                                                    <label for="">Tipo da média:</label>

                                                                    <select id="averageType" class="form-control custom-select" name="averageType" required>

                                                                        <option value="averageUnity">Média unidade</option>
                                                                        <option value="averageEnd">Média final</option>

                                                                    </select>

                                                                </div>

                                                            </div>

                                                        </div>

                                                    </form>

                                                </div>

                                                <div containerStudentsProfileAverage class="col-lg-11 mx-auto table-responsive"></div>

                                            </div>

                                        </div>

                                        <div id="notes" class="collapse show" data-parent="#averageAndNotes">

                                            <div class="card col-lg-12">

                                                <div class="col-lg-11 mx-auto">

                                                    <form id="seekNoteExamStudent" class="mt-3 text-dark" action="">

                                                        <input type="hidden" value="<?= $_SESSION['Student']['enrollmentId'] ?>" name="enrollmentId">
                                                        <input type="hidden" value="<?= $_SESSION['Student']['classId'] ?>" name="classId">


                                                        <div class="form-row mt-3">

                                                            <div class="form-group col-lg-5">
                                                                <label for="">Nome da avaliacão:</label>
                                                                <input name="examDescription" id="examDescription" type="text" placeholder="Nome da avaliação" class="form-control">
                                                            </div>

                                                            <div class="form-group col-lg-4">

                                                                <label for="">Disciplina:</label>

                                                                <select id="disciplineClass" class="form-control custom-select" name="disciplineClass" required>

                                                                    <option value="0">Todas</option>

                                                                    <?php foreach ($this->view->linkedDisciplines as $key => $discipline) { ?>

                                                                        <option value="<?= $discipline->option_value ?>"><?= $discipline->option_text ?></option>

                                                                    <?php } ?>

                                                                </select>

                                                            </div>

                                                            <div class="form-group col-lg-3">
                                                                <label for="">Unidade:</label>

                                                                <select id="unity" class="form-control custom-select" name="unity" required>

                                                                    <option value="0">Todas</option>

                                                                        <?php foreach ($this->view->unity as $key => $unity) { ?>

                                                                            <option value="<?= $unity->option_value ?>"><?= $unity->option_text ?></option>

                                                                    <?php } ?>

                                                                </select>

                                                            </div>

                                                        </div>

                                                    </form>

                                                </div>

                                                <div class="col-lg-11 mx-auto table-responsive">

                                                    <hr>

                                                    <table class="table col-lg-12 table-hover table-borderless table-striped p-0" id="note-table">
                                                        <thead>
                                                            <tr>
                                                                <th scope="col">#</th>
                                                                <th scope="col">Descrição</th>
                                                                <th class="text-center" scope="col">Resultado</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody containerListNote></tbody>
                                                    </table>
                                                </div>



                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

                                <div class="collapse mt-3" id="studentSchedule" data-parent="#studentPortal-accordion">

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
                            <a class="nav-link" aria-expanded="false" href="#" data-toggle="collapse" data-target="#studentSchedule"><i class="fas fa-calendar-alt"></i> <span>Horários</span></a>
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