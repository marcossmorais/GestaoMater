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
$pendingTotal = count($pending['lessons']) + count($pending['exams']);
?>

<section id="home">

    <div class="row main-container">

        <div class="col-lg-11 mx-auto">

            <div class="m-page-head">
                <div>
                    <h5>Olá, <?= htmlspecialchars(explode(' ', $_SESSION['Teacher']['name'])[0]) ?></h5>
                    <p><?= ucfirst($longDate($today)) ?><?= $schoolTerm ? ' · ' . max($openUnits) . 'º bimestre aberto' : ' · nenhum período letivo ativo' ?></p>
                </div>
            </div>

            <?php if (!$isToday || !$lessons) { ?>
                <a href="/portal-docente/chamada" class="card m-cta">
                    <span class="m-cta-icon"><i class="fas fa-bolt"></i></span>
                    <span class="m-cta-body">
                        <strong>Chamada rápida</strong>
                        <span>Todos começam presentes — toque só em quem faltou.</span>
                    </span>
                    <span class="m-cta-go">Começar</span>
                </a>
            <?php } ?>

            <div class="m-grid">

                <div class="card m-panel">
                    <div class="m-panel-head">
                        <h6><?= $isToday ? 'Aulas de hoje' : 'Próximas aulas · ' . $longDate($lessonsDate) ?></h6>
                        <a href="/portal-docente/horario">Horário</a>
                    </div>

                    <?php if (!$lessons) { ?>
                        <div class="m-empty">Nenhuma aula no seu horário nos próximos dias.</div>
                    <?php } else { ?>
                        <ul class="m-today">
                            <?php foreach ($lessons as $l) {
                                $now_ = $isToday && $now >= $l->start && $now <= $l->finish;
                                $done = $l->attendance_at && $l->lesson_done;
                            ?>
                                <li class="m-today-item<?= $now_ ? ' is-now' : '' ?><?= $done ? ' is-done' : '' ?>">
                                    <div class="m-today-time"><strong><?= $l->start ?></strong><span><?= $l->finish ?></span></div>
                                    <span class="m-att-class-badge"><?= htmlspecialchars($l->label) ?></span>
                                    <div class="m-today-body">
                                        <strong><?= htmlspecialchars($l->discipline) ?><?= $now_ ? ' <span class="m-pill m-pill-info">agora</span>' : '' ?></strong>
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

                    <div class="card m-panel">
                        <div class="m-panel-head">
                            <h6>Alunos em atenção</h6>
                            <span class="m-pill m-pill-info"><?= max($openUnits) ?>º bim.</span>
                        </div>
                        <?php if (!$atRisk) { ?>
                            <div class="m-empty">Nenhum aluno abaixo de <?= str_replace('.', ',', (string) $this->view->rule['passing']) ?> ou com muitas faltas.</div>
                        <?php } else { ?>
                            <ul class="m-todo">
                                <?php foreach (array_slice($atRisk, 0, 8) as $s) { ?>
                                    <li><a href="/portal-docente/aluno?id=<?= $s['enrollmentId'] ?>">
                                        <i class="fas fa-user"></i>
                                        <span><strong><?= htmlspecialchars($s['name']) ?></strong><small><?= htmlspecialchars($s['label'] . ' · ' . $s['discipline']) ?> · <?= htmlspecialchars(implode(' · ', $s['reasons'])) ?></small></span>
                                    </a></li>
                                <?php } ?>
                            </ul>
                            <?php if (count($atRisk) > 8) { ?><p class="m-muted-note mb-0">e mais <?= count($atRisk) - 8 ?> — veja em Turmas.</p><?php } ?>
                        <?php } ?>
                    </div>

                </div>

            </div>

        </div>

    </div>

</section>
