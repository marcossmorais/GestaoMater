<?php
require __DIR__ . '../../../config/variables.php';

$photoDir = "$app_url/assets/img/studentProfilePhotos/";
$schoolTerm = isset($this->view->SchoolTermActive[0]->option_text) ? $this->view->SchoolTermActive[0]->option_text : null;
$totals = $this->view->totals;
?>

<section id="home">

    <div class="row main-container">

        <div class="col-lg-11 mx-auto">

            <div class="m-page-head">
                <div>
                    <h5>Visão geral</h5>
                    <p><?= $schoolTerm ? "Período letivo $schoolTerm · {$this->view->unitControlCurrent[0]->option_value}ª unidade aberta" : 'Nenhum período letivo ativo' ?></p>
                </div>
                <div class="m-actions">
                    <a href="/admin/aluno/cadastro" class="btn btn-primary"><i class="fas fa-user-plus mr-2"></i>Matricular aluno</a>
                    <a href="/admin/professor/cadastro" class="btn btn-light"><i class="fas fa-chalkboard-teacher mr-2"></i>Cadastrar docente</a>
                    <a href="/admin/gestao/turma" class="btn btn-light"><i class="fas fa-users mr-2"></i>Turmas</a>
                </div>
            </div>

            <div class="m-kpis">

                <div class="card m-kpi">
                    <div class="m-kpi-icon"><i class="fas fa-user-graduate"></i></div>
                    <div>
                        <div class="m-kpi-label">Alunos ativos</div>
                        <div class="m-kpi-value"><?= $this->view->studentTotal ?>
                            <?php if ($this->view->studentsAddedToday[0]->total_student > 0) { ?>
                                <span class="m-kpi-extra" title="Matriculados hoje">+<?= $this->view->studentsAddedToday[0]->total_student ?> hoje</span>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="card m-kpi">
                    <div class="m-kpi-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div>
                        <div class="m-kpi-label">Docentes</div>
                        <div class="m-kpi-value"><?= $totals->teachers ?></div>
                    </div>
                </div>

                <div class="card m-kpi">
                    <div class="m-kpi-icon"><i class="fas fa-users"></i></div>
                    <div>
                        <div class="m-kpi-label">Turmas no período</div>
                        <div class="m-kpi-value"><?= $totals->classes ?></div>
                    </div>
                </div>

                <div class="card m-kpi">
                    <div class="m-kpi-icon"><i class="fas fa-book"></i></div>
                    <div>
                        <div class="m-kpi-label">Disciplinas</div>
                        <div class="m-kpi-value"><?= $totals->disciplines ?></div>
                    </div>
                </div>

            </div>

            <div class="m-grid">

                <div class="card m-panel">
                    <div class="m-panel-head">
                        <h6>Alunos por curso</h6>
                        <a href="/admin/gestao/curso">Ver cursos</a>
                    </div>
                    <div class="m-chart"><canvas id="studenDivisionChartCourse"></canvas></div>
                </div>

                <div class="card m-panel">
                    <div class="m-panel-head">
                        <h6>Matriculados recentemente</h6>
                        <a href="/admin/aluno/lista">Ver todos</a>
                    </div>

                    <?php if (count($this->view->recentlyEnrolledStudents) >= 1) { ?>
                        <ul class="m-list">
                            <?php foreach ($this->view->recentlyEnrolledStudents as $value) {
                                $date = explode('-', explode(' ', $value->initial_enrollment_date)[0]);
                            ?>
                                <li>
                                    <img src="<?= $photoDir . ($value->profile_photo ?: 'foto-vazia.jpg') ?>" onerror="this.src='<?= $photoDir ?>foto-vazia.jpg'" alt="" loading="lazy">
                                    <span class="m-list-name" title="<?= htmlspecialchars($value->student_name) ?>"><?= htmlspecialchars($value->student_name) ?></span>
                                    <span class="m-list-meta"><?= $date[2] ?>/<?= $date[1] ?></span>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } else { ?>
                        <div class="m-empty">
                            <img src="<?= $app_url ?>/assets/img/illustrations/enrollment_null.svg" alt="">
                            <p>Nenhum aluno matriculado ainda.</p>
                            <a href="/admin/aluno/cadastro" class="btn btn-primary">Matricular alunos</a>
                        </div>
                    <?php } ?>
                </div>

            </div>

            <div class="m-grid">

                <div class="card m-panel admin-portal">
                    <div class="m-panel-head">
                        <h6>Atividades recentes</h6>
                    </div>
                    <div class="p-0 recent-activities">
                        <?php require '../App/Views/admin/management/components/recentActivities.php' ?>
                    </div>
                </div>

                <div class="m-stack">
                    <div class="card m-panel">
                        <div class="m-panel-head"><h6>Situação no ano letivo</h6></div>
                        <div class="m-chart-sm"><canvas id="graphCurrentStudentSituation"></canvas></div>
                    </div>
                    <div class="card m-panel">
                        <div class="m-panel-head"><h6>Respostas da rematrícula</h6></div>
                        <div class="m-chart-sm"><canvas id="graphSituationEngramentesReceived"></canvas></div>
                    </div>
                </div>

            </div>

        </div>

    </div>

</section>
