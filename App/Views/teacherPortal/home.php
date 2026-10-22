<?php
require __DIR__ . '../../../config/variables.php';

$schoolTerm = isset($this->view->SchoolTermActive[0]->option_text) ? $this->view->SchoolTermActive[0]->option_text : null;
$unity = $this->view->unitControlCurrent[0]->option_value;

// Agenda: próximas avaliações primeiro, depois as já realizadas (mais recentes primeiro)
$now = (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->getTimestamp();
$upcoming = [];
$done = [];
foreach ($this->view->listExam as $exam) {
    $exam->timestamp = strtotime($exam->realize_date);
    $exam->timestamp >= $now ? $upcoming[] = $exam : $done[] = $exam;
}
usort($upcoming, function ($a, $b) { return $a->timestamp <=> $b->timestamp; });
usort($done, function ($a, $b) { return $b->timestamp <=> $a->timestamp; });
$agenda = array_merge($upcoming, $done);
$classes = [];
foreach ($this->view->listExam as $exam) $classes[$exam->acronym_series . $exam->ballot . $exam->shift] = true;
?>

<section id="home">

    <div class="row main-container">

        <div class="col-lg-11 mx-auto">

            <div class="m-page-head">
                <div>
                    <h5>Olá, <?= htmlspecialchars(explode(' ', $_SESSION['Teacher']['name'])[0]) ?></h5>
                    <p><?= $schoolTerm ? "Período letivo $schoolTerm · {$unity}ª unidade aberta" : 'Nenhum período letivo ativo' ?></p>
                </div>
                <div class="m-actions">
                    <a href="/portal-docente/chamada" class="btn btn-primary"><i class="fas fa-clipboard-check mr-2"></i>Fazer chamada</a>
                    <a href="/portal-docente/turmas" class="btn btn-light"><i class="fas fa-users mr-2"></i>Minhas turmas</a>
                </div>
            </div>

            <a href="/portal-docente/chamada" class="card m-cta">
                <span class="m-cta-icon"><i class="fas fa-bolt"></i></span>
                <span class="m-cta-body">
                    <strong>Chamada rápida</strong>
                    <span>Todos começam presentes — toque só em quem faltou. Leva menos de 30 segundos.</span>
                </span>
                <span class="m-cta-go">Começar</span>
            </a>

            <div class="m-kpis">
                <div class="card m-kpi">
                    <div class="m-kpi-icon"><i class="fas fa-calendar-check"></i></div>
                    <div>
                        <div class="m-kpi-label">Avaliações pendentes</div>
                        <div class="m-kpi-value"><?= count($upcoming) ?></div>
                    </div>
                </div>
                <div class="card m-kpi">
                    <div class="m-kpi-icon"><i class="fas fa-clipboard-check"></i></div>
                    <div>
                        <div class="m-kpi-label">Avaliações realizadas</div>
                        <div class="m-kpi-value"><?= count($done) ?></div>
                    </div>
                </div>
                <div class="card m-kpi">
                    <div class="m-kpi-icon"><i class="fas fa-users"></i></div>
                    <div>
                        <div class="m-kpi-label">Turmas com avaliações</div>
                        <div class="m-kpi-value"><?= count($classes) ?></div>
                    </div>
                </div>
                <div class="card m-kpi">
                    <div class="m-kpi-icon"><i class="fas fa-boxes"></i></div>
                    <div>
                        <div class="m-kpi-label">Unidade atual</div>
                        <div class="m-kpi-value"><?= $unity ?>ª</div>
                    </div>
                </div>
            </div>

            <div class="m-grid m-grid-even">

                <div class="card m-panel">
                    <div class="m-panel-head">
                        <h6>Agenda de avaliações</h6>
                        <a href="/portal-docente/turmas">Abrir turmas</a>
                    </div>

                    <?php if (count($agenda) > 0) { ?>
                        <ul class="m-list m-list-tall">
                            <?php foreach ($agenda as $exam) {
                                $pending = $exam->timestamp >= $now;
                            ?>
                                <li>
                                    <div class="m-date">
                                        <strong><?= date('d', $exam->timestamp) ?></strong>
                                        <span><?= ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'][date('n', $exam->timestamp) - 1] ?></span>
                                    </div>
                                    <div class="m-list-body">
                                        <span class="m-list-name"><?= htmlspecialchars($exam->exam_description) ?> · <?= htmlspecialchars($exam->discipline_name) ?></span>
                                        <span class="m-list-meta"><?= $exam->acronym_series ?>ª <?= $exam->ballot ?> - <?= $exam->course ?> · <?= $exam->shift ?> · <?= $exam->exam_value ?> <?= $exam->exam_value > 1 ? 'pontos' : 'décimos' ?></span>
                                    </div>
                                    <span class="m-pill <?= $pending ? 'm-pill-info' : 'm-pill-ok' ?>"><?= $pending ? 'Pendente' : 'Realizada' ?></span>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } else { ?>
                        <div class="m-empty">
                            <img src="<?= $app_url ?>/assets/img/illustrations/calendar.svg" alt="">
                            <p>Nenhuma avaliação criada ainda.</p>
                        </div>
                    <?php } ?>
                </div>

                <div class="card m-panel">
                    <div class="m-panel-head"><h6>Atividades recentes</h6></div>
                    <div class="p-0 recent-activities">
                        <?php $teacherName = "Você" ?>
                        <?php require '../App/Views/admin/management/components/recentActivities.php' ?>
                    </div>
                </div>

            </div>

        </div>

    </div>

</section>
