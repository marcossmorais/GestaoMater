<?php
require __DIR__ . '../../../config/variables.php';

$schoolTerm = isset($this->view->SchoolTermActive[0]->option_text) ? $this->view->SchoolTermActive[0]->option_text : null;
$openUnits = $this->view->openUnits;
$today = $this->view->today;
$lessonsDate = $this->view->lessonsDate;
$isToday = $lessonsDate === $today;
$lessons = $this->view->lessons;
$pending = $this->view->pending;
$atRisk = $this->view->atRisk;

$weekdays = ['', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado', 'domingo'];
$months = ['', 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
$longDate = function ($date) use ($weekdays, $months) {
    $t = strtotime($date);
    return $weekdays[date('N', $t)] . ', ' . date('j', $t) . ' de ' . $months[date('n', $t)];
};
$shortDate = function ($date) { return implode('/', array_slice(array_reverse(explode('-', $date)), 0, 2)); };

$classes = [];
foreach ($this->view->disciplineClasses as $c) $classes[(int) $c->discipline_class_id] = $c;
$className = function ($id) use ($classes) {
    $c = $classes[(int) $id] ?? null;
    return $c ? $c->series_acronym . 'ª ' . $c->ballot . ' · ' . $c->discipline_name : '';
};

$now = (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->format('H:i');
$pendingTotal = count($pending['lessons']) + count($pending['exams']) + count($pending['daily']);
$homerooms = $this->view->homerooms;
?>

<section id="home">

    <div class="row main-container">

        <div class="col-lg-11 mx-auto">

            <?php
            $hour = (int) substr($now, 0, 2);
            $greeting = $hour < 12 ? 'Bom dia' : ($hour < 18 ? 'Boa tarde' : 'Boa noite');
            $todayCount = $isToday ? count($lessons) : 0;
            ?>
            <div class="m-hero">
                <div class="m-hero-text">
                    <span class="m-hero-kicker"><?= ucfirst($longDate($today)) ?><?= $schoolTerm ? ' · ' . max($openUnits) . 'º bimestre' : '' ?></span>
                    <h5><?= $greeting ?>, <?= htmlspecialchars(explode(' ', $_SESSION['Teacher']['name'])[0]) ?>!</h5>
                    <p><?= $todayCount ? "Você tem $todayCount aula" . ($todayCount > 1 ? 's' : '') . ' hoje. Bora começar?' : 'Hoje não tem aula no seu horário. Aproveite para colocar as notas em dia.' ?></p>
                </div>
                <img class="m-hero-art" src="<?= $app_url ?>/assets/img/illustrations/teacher.svg" alt="">
            </div>

            <nav class="m-tiles" aria-label="Atalhos">
                <a class="m-tile m-subj-green" href="/portal-docente/chamada"><span class="m-tile-ico"><i class="fas fa-clipboard-check"></i></span><strong>Chamada rápida</strong><small>Toque só em quem faltou</small></a>
                <a class="m-tile m-subj-coral" href="/portal-docente/diario"><span class="m-tile-ico"><i class="fas fa-book-open"></i></span><strong>Planejamento</strong><small>Calendário e horário</small></a>
                <a class="m-tile m-subj-sky" href="/portal-docente/notas"><span class="m-tile-ico"><i class="fas fa-star"></i></span><strong>Notas</strong><small>1ª, 2ª e 3ª avaliação</small></a>
                <a class="m-tile m-subj-purple" href="/portal-docente/mural"><span class="m-tile-ico"><i class="fas fa-thumbtack"></i></span><strong>Mural</strong><small>Avisos para a turma</small></a>
            </nav>

            <?php
            $charts = $this->view->charts;
            $activity = $this->view->activity;
            $status = $charts['status'];
            $studentsTotal = array_sum($status);
            $unity = max($openUnits);
            $statusLegend = [
                ['Na média ou acima', $status['ok'], '#22B07D'],
                ['Abaixo, ainda parcial', $status['partial'], '#0EA5E9'],
                ['Aguarda recuperação', $status['recovery'], '#F2643D'],
                ['Sem nota ainda', $status['none'], '#B8C5DC'],
            ];
            $bandColors = ['#F2643D', '#0EA5E9', '#2F6FED', '#22B07D'];
            $readPct = $activity['expected'] ? (int) round(100 * $activity['read'] / $activity['expected']) : 0;
            ?>

            <h5 class="m-section-title">Como vão os seus alunos <span class="m-pill m-pill-info"><?= $unity ?>º bimestre</span></h5>

            <div class="m-charts">

                <div class="card m-panel m-chart-card">
                    <div class="m-panel-head"><h6>Situação dos alunos</h6></div>
                    <div class="m-donut"><canvas id="chartStatus" aria-label="Situação dos alunos no bimestre"></canvas><div class="m-donut-center"><strong><?= (int) $studentsTotal ?></strong><small>alunos</small></div></div>
                    <ul class="m-legend">
                        <?php foreach ($statusLegend as [$label, $count, $color]) { ?>
                            <li><i style="background: <?= $color ?>"></i><span><?= $label ?></span><b><?= (int) $count ?></b></li>
                        <?php } ?>
                    </ul>
                </div>

                <div class="card m-panel m-chart-card">
                    <div class="m-panel-head"><h6>Faixas de nota</h6></div>
                    <div class="m-donut"><canvas id="chartBands" aria-label="Quantidade de notas por faixa"></canvas></div>
                    <ul class="m-legend">
                        <?php $i = 0; foreach ($charts['bands'] as $label => $count) { ?>
                            <li><i style="background: <?= $bandColors[$i++] ?>"></i><span><?= $label ?></span><b><?= (int) $count ?></b></li>
                        <?php } ?>
                    </ul>
                </div>

                <div class="card m-panel m-chart-card m-chart-wide">
                    <div class="m-panel-head"><h6>Média por turma</h6><small class="m-muted-note m-0">linha vermelha: média <?= str_replace('.', ',', (string) $this->view->rule['passing']) ?></small></div>
                    <div class="m-chart-box"><canvas id="chartClassAverage" aria-label="Média de cada turma"></canvas></div>
                </div>

                <div class="card m-panel m-chart-card m-chart-wide">
                    <div class="m-panel-head"><h6>Alunos em atenção por turma</h6><span class="m-pill m-pill-bad"><?= count($atRisk) ?></span></div>
                    <div class="m-chart-box"><canvas id="chartAttention" aria-label="Alunos em atenção por turma"></canvas></div>
                    <?php if ($atRisk) { ?>
                        <details class="m-attention-list" data-attention>
                            <summary><i class="fas fa-list mr-2"></i><span data-attention-title>Ver os <?= count($atRisk) ?> alunos</span></summary>
                            <ul class="m-todo">
                                <?php foreach ($atRisk as $s) { ?>
                                    <li data-turma="<?= htmlspecialchars($s['label']) ?>"><a href="/portal-docente/aluno?id=<?= $s['enrollmentId'] ?>">
                                        <span><strong><?= htmlspecialchars($s['name']) ?></strong><small><?= htmlspecialchars($s['label'] . ' · ' . $s['discipline']) ?> · <?= htmlspecialchars(implode(' · ', $s['reasons'])) ?></small></span>
                                    </a></li>
                                <?php } ?>
                            </ul>
                        </details>
                    <?php } ?>
                </div>

                <div class="card m-panel m-chart-card m-chart-wide">
                    <div class="m-panel-head"><h6>Faltas por turma</h6><small class="m-muted-note m-0">sem as abonadas</small></div>
                    <div class="m-chart-box"><canvas id="chartAbsences" aria-label="Faltas de cada turma no bimestre"></canvas></div>
                    <h6 class="m-data-sub">Quem mais está faltando</h6>
                    <?php $topAbsences = $this->view->topAbsences; ?>
                    <?php if (!$topAbsences) { ?>
                        <p class="m-muted-note mb-0">Nenhuma falta sem abono neste bimestre.</p>
                    <?php } else { $maxAbsences = max(array_column($topAbsences, 'absences')); ?>
                        <ol class="m-rank">
                            <?php foreach ($topAbsences as $s) { ?>
                                <li>
                                    <a href="/portal-docente/aluno?id=<?= (int) $s->id ?>">
                                        <span class="m-rank-name"><strong><?= htmlspecialchars($s->name) ?></strong><small><?= htmlspecialchars($s->label) ?></small></span>
                                        <span class="m-rank-bar" aria-hidden="true"><i style="width:<?= round($s->absences / $maxAbsences * 100) ?>%"></i></span>
                                        <b><?= (int) $s->absences ?> falta<?= $s->absences > 1 ? 's' : '' ?></b>
                                    </a>
                                </li>
                            <?php } ?>
                        </ol>
                    <?php } ?>
                </div>

                <div class="card m-panel m-chart-card m-chart-wide">
                    <div class="m-panel-head"><h6>O que os alunos estão fazendo</h6><a href="/portal-docente/mural">Mural</a></div>
                    <?php if (!$activity['notices']) { ?>
                        <div class="m-empty">Publique um aviso no mural para acompanhar quem leu.</div>
                    <?php } else { ?>
                        <div class="m-activity">
                            <div class="m-donut m-donut-sm"><canvas id="chartReads" aria-label="Leitura dos avisos"></canvas><div class="m-donut-center"><strong><?= $readPct ?>%</strong><small>leram</small></div></div>
                            <ul class="m-read-list">
                                <?php foreach ($activity['notices'] as $n) { $pct = $n->students ? min(100, round(100 * $n->read_total / $n->students)) : 0; ?>
                                    <li>
                                        <span class="m-read-text"><?= htmlspecialchars($n->text) ?></span>
                                        <small><?= htmlspecialchars($n->label) ?> · <?= htmlspecialchars($n->date) ?> · <?= (int) $n->read_total ?> de <?= (int) $n->students ?></small>
                                        <span class="m-bar"><i class="m-bar-fill is-read" style="width: <?= $pct ?>%"></i></span>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                        <?php if ($activity['feed']) { ?>
                            <h6 class="m-data-sub">Últimas leituras</h6>
                            <ul class="m-feed">
                                <?php foreach ($activity['feed'] as $f) { ?>
                                    <li><i class="fas fa-check-circle"></i><span><a href="/portal-docente/aluno?id=<?= (int) $f->enrollment_id ?>"><strong><?= htmlspecialchars($f->name) ?></strong></a> leu “<?= htmlspecialchars($f->text) ?>”<small><?= htmlspecialchars($f->label) ?> · <?= htmlspecialchars($f->at) ?></small></span></li>
                                <?php } ?>
                            </ul>
                        <?php } ?>
                    <?php } ?>
                </div>

            </div>

            <script>
                window.MATER_HOME = <?= json_encode([
                    'status' => array_map(function ($l) { return ['label' => $l[0], 'value' => (int) $l[1], 'color' => $l[2]]; }, $statusLegend),
                    'bands' => array_map(null, array_keys($charts['bands']), array_values($charts['bands']), $bandColors),
                    'classes' => $charts['classes'],
                    'passing' => (float) $this->view->rule['passing'],
                    'reads' => ['read' => (int) $activity['read'], 'expected' => (int) $activity['expected']],
                ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
            </script>
            <script src="<?= asset('/assets/js/teacherHome.js') ?>" defer></script>

            <div class="m-grid">

                <div class="card m-panel">
                    <div class="m-panel-head">
                        <h6><?= $isToday ? 'Aulas de hoje' : 'Próximas aulas · ' . $longDate($lessonsDate) ?></h6>
                        <a href="/portal-docente/diario?aba=horario">Horário</a>
                    </div>

                    <?php if (!$lessons) { ?>
                        <div class="m-empty">Nenhuma aula no seu horário nos próximos dias.</div>
                    <?php } else { ?>
                        <ul class="m-today">
                            <?php foreach ($lessons as $l) {
                                $now_ = $isToday && $now >= $l->start && $now <= $l->finish;
                                $done = $l->attendance_at && $l->lesson_done;
                            ?>
                                <li class="m-today-item <?= \App\Tools\Subject::css($l->discipline) ?><?= $now_ ? ' is-now' : '' ?><?= $done ? ' is-done' : '' ?>">
                                    <div class="m-today-time"><strong><?= $l->start ?></strong><span><?= $l->finish ?></span></div>
                                    <span class="m-att-class-badge"><?= htmlspecialchars($l->label) ?></span>
                                    <div class="m-today-body">
                                        <strong><i class="fas <?= \App\Tools\Subject::icon($l->discipline) ?> m-subj-ico" aria-hidden="true"></i><?= htmlspecialchars($l->discipline) ?><?= $now_ ? ' <span class="m-pill m-pill-info">agora</span>' : '' ?></strong>
                                        <small><?= htmlspecialchars($l->course) ?> · <?= htmlspecialchars($l->shift) ?><?= $l->count > 1 ? ' · ' . $l->count . ' aulas seguidas' : '' ?></small>
                                        <?php if ($isToday) { ?>
                                            <span class="m-today-status">
                                                <span class="<?= $l->attendance_at ? 'is-ok' : '' ?>"><i class="fas <?= $l->attendance_at ? 'fa-check-circle' : 'fa-circle' ?>"></i>Chamada<?= $l->attendance_at ? ' ' . $l->attendance_at : '' ?></span>
                                                <span class="<?= $l->lesson_done ? 'is-ok' : '' ?>"><i class="fas <?= $l->lesson_done ? 'fa-check-circle' : 'fa-circle' ?>"></i>Diário</span>
                                            </span>
                                        <?php } ?>
                                    </div>
                                    <?php if ($isToday && !$l->attendance_at) { ?>
                                        <a class="btn btn-primary" href="/portal-docente/chamada?turma=<?= (int) $l->discipline_class_id ?>">Chamada</a>
                                    <?php } elseif ($isToday && !$l->lesson_done) { ?>
                                        <a class="btn btn-light" href="/portal-docente/diario?turma=<?= (int) $l->discipline_class_id ?>">Diário</a>
                                    <?php } elseif ($isToday) { ?>
                                        <span class="m-pill m-pill-ok">Concluída</span>
                                    <?php } ?>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } ?>
                </div>

                <div class="m-stack">

                    <div class="card m-panel">
                        <div class="m-panel-head">
                            <h6>Pendências</h6>
                            <?php if ($pendingTotal) { ?><span class="m-pill m-pill-warn"><?= $pendingTotal ?></span><?php } ?>
                        </div>
                        <?php if (!$pendingTotal) { ?>
                            <div class="m-empty"><i class="fas fa-check-circle mr-2"></i>Tudo em dia.</div>
                        <?php } else { ?>
                            <ul class="m-todo">
                                <?php foreach ($pending['daily'] as $p) { ?>
                                    <li><a href="/portal-docente/turma?id=<?= (int) $p->class_id ?>&ver=diario&data=<?= htmlspecialchars($p->date) ?>">
                                        <i class="fas fa-book-open"></i>
                                        <span><strong>Diário do dia <?= $shortDate($p->date) ?></strong><small><?= htmlspecialchars($homerooms[(int) $p->class_id] ?? '') ?> · chamada feita, dia sem registro</small></span>
                                    </a></li>
                                <?php } ?>
                                <?php foreach ($pending['lessons'] as $p) { ?>
                                    <li><a href="/portal-docente/diario?turma=<?= (int) $p->discipline_class_id ?>&data=<?= htmlspecialchars($p->date) ?>">
                                        <i class="fas fa-book-open"></i>
                                        <span><strong>Diário de <?= $shortDate($p->date) ?></strong><small><?= htmlspecialchars($className($p->discipline_class_id)) ?> · chamada feita, aula sem registro</small></span>
                                    </a></li>
                                <?php } ?>
                                <?php foreach ($pending['exams'] as $p) { ?>
                                    <li><a href="/portal-docente/notas?turma=<?= (int) $p->discipline_class_id ?>&avaliacao=<?= (int) $p->id ?>">
                                        <i class="fas fa-marker"></i>
                                        <span><strong>Notas da <?= htmlspecialchars($p->description) ?></strong><small><?= htmlspecialchars($className($p->discipline_class_id)) ?> · <?= (int) $p->graded ?> de <?= (int) $p->students ?> lançadas</small></span>
                                    </a></li>
                                <?php } ?>
                            </ul>
                        <?php } ?>
                    </div>

                </div>

            </div>

        </div>

    </div>

</section>
