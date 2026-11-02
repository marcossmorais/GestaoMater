<?php
require __DIR__ . '/../../config/variables.php';

$student = $this->view->student;
$rule = $this->view->rule;
$mine = $this->view->mine;
$performance = $this->view->performance;
$fmt = function ($n) { return $n === null ? '–' : str_replace('.', ',', (string) round((float) $n, 1)); };

// avaliações do professor agrupadas por disciplina e bimestre
$exams = [];
foreach ($this->view->exams as $e) $exams[(int) $e->discipline_class_id][(int) $e->unity][] = $e;
$own = array_values(array_filter($performance, function ($p) use ($mine) { return in_array((int) $p['id'], $mine, true); }));

$words = preg_split('/\s+/', trim($student->name));
$initials = mb_strtoupper(mb_substr($words[0], 0, 1) . (count($words) > 1 ? mb_substr(end($words), 0, 1) : ''));
$occ = $this->view->occurrences;
$examTypes = $this->view->examTypes;
// suas disciplinas primeiro; as demais só para consulta
$ordered = array_merge($own, array_values(array_filter($performance, function ($p) use ($mine) { return !in_array((int) $p['id'], $mine, true); })));
?>

<section id="studentFile" class="m-att">

    <div class="row main-container">

        <div class="col-lg-11 mx-auto">

            <div class="m-att-head">
                <a href="/portal-docente/turma?id=<?= (int) $student->class_id ?>" class="m-icon-btn" aria-label="Voltar para a turma"><i class="fas fa-arrow-left"></i></a>
                <span class="m-grade-avatar m-student-avatar"><?php if ($student->photo) { ?><img src="<?= $app_url ?>/assets/img/studentProfilePhotos/<?= htmlspecialchars($student->photo) ?>" alt=""><?php } else { echo htmlspecialchars($initials); } ?></span>
                <div class="m-att-head-title">
                    <h5><?= htmlspecialchars($student->name) ?></h5>
                    <p><?= htmlspecialchars($this->view->classLabel) ?><?= $occ->total ? ' · ' . (int) $occ->total . ' ocorrência' . ($occ->total > 1 ? 's' : '') : '' ?></p>
                </div>
            </div>

            <?php
            // gráfico: nota do aluno x média da turma em cada matéria, por bimestre
            $palette = ['blue' => '#2F80ED', 'coral' => '#F2643D', 'sky' => '#0EA5E9', 'green' => '#22B07D', 'purple' => '#7B61FF', 'teal' => '#12A9B4', 'lime' => '#2FA84F', 'pink' => '#E8508C', 'rose' => '#E5486D', 'magenta' => '#C04CD9', 'indigo' => '#4F5BD5', 'slate' => '#64748B'];
            $chart = ['passing' => (float) $rule['passing'], 'unity' => (int) $this->view->unity, 'units' => (int) $rule['units'], 'subjects' => []];
            foreach ($performance as $p) {
                $chart['subjects'][] = [
                    'name' => $p['discipline'],
                    'color' => $palette[\App\Tools\Subject::color($p['discipline'])],
                    'student' => array_map(function ($u) { return $u['grade'] === null ? null : round((float) $u['grade'], 2); }, array_values($p['units'])),
                    'class' => array_map(function ($u) use ($p) { return $this->view->classAverages[(int) $p['id']][$u] ?? null; }, range(1, (int) $rule['units'])),
                ];
            }
            $absences = array_sum(array_column($performance, 'absences'));
            ?>

            <div class="m-seg m-file-tabs" role="group" aria-label="Seções do perfil" data-file-tabs>
                <button type="button" class="is-active" data-tab="resumo"><i class="fas fa-id-card mr-2"></i>Resumo</button>
                <button type="button" data-tab="notas"><i class="fas fa-marker mr-2"></i>Notas</button>
                <button type="button" data-tab="observacoes"><i class="fas fa-comment-dots mr-2"></i>Observações</button>
            </div>

            <div data-tab-panel="resumo">

            <div class="m-file-grid">
                <div class="card m-panel m-file-data">
                    <div class="m-panel-head"><h6><i class="fas fa-id-card mr-2"></i>Dados do aluno</h6></div>
                    <dl class="m-data-list">
                        <div><dt>Matrícula</dt><dd>nº <?= (int) $student->id ?><?= $student->enrolled_at ? ' · desde ' . htmlspecialchars($student->enrolled_at) : '' ?></dd></div>
                        <div><dt>Nascimento</dt><dd><?= htmlspecialchars($student->birth_date) ?> · <?= (int) $student->age ?> anos</dd></div>
                        <div><dt>Turma</dt><dd><?= htmlspecialchars($this->view->classLabel) ?></dd></div>
                        <div><dt>Necessidade especial</dt><dd><?= $student->pcd && mb_strtolower($student->pcd) !== 'não' ? '<span class="m-pill m-pill-warn">' . htmlspecialchars($student->pcd) . '</span>' : 'Não informada' ?></dd></div>
                        <div><dt>Faltas no ano</dt><dd><?= (int) $absences ?></dd></div>
                        <div><dt>Ocorrências</dt><dd><?= (int) $occ->total ?><?= $occ->elogios ? ' · ' . (int) $occ->elogios . ' elogio' . ($occ->elogios > 1 ? 's' : '') : '' ?></dd></div>
                    </dl>
                    <h6 class="m-data-sub">Responsáveis</h6>
                    <?php if (!$this->view->guardians) { ?>
                        <p class="m-muted-note mb-0">Nenhum responsável cadastrado pela secretaria.</p>
                    <?php } else { ?>
                        <ul class="m-guardians">
                            <?php foreach ($this->view->guardians as $g) { ?>
                                <li>
                                    <span><strong><?= htmlspecialchars($g->name) ?></strong><small><?= htmlspecialchars($g->relation) ?><?= $g->pedagogical ? ' · responsável pedagógico' : '' ?></small></span>
                                    <?php if ($g->phone) { ?><a class="btn btn-light btn-sm" href="tel:<?= htmlspecialchars(preg_replace('/\D/', '', $g->phone)) ?>"><i class="fas fa-phone mr-1"></i><?= htmlspecialchars($g->phone) ?></a><?php } ?>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } ?>
                </div>

                <div class="card m-panel m-file-chart">
                    <div class="m-panel-head">
                        <h6><i class="fas fa-chart-bar mr-2"></i>Comparado com a turma</h6>
                        <div class="m-seg" data-chart-unit>
                            <?php for ($u = 1; $u <= $rule['units']; $u++) { ?>
                                <button type="button" class="<?= $u === (int) $this->view->unity ? 'is-active' : '' ?>" data-unit="<?= $u ?>"><?= $u ?>º</button>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="m-chart-box"><canvas id="studentCompareChart" aria-label="Nota do aluno e média da turma por matéria"></canvas></div>
                    <p class="m-muted-note mb-0">Barras coloridas: nota do aluno · cinza: média da turma · linha: média <?= $fmt($rule['passing']) ?></p>
                </div>
            </div>

            <script>window.MATER_STUDENT_CHART = <?= json_encode($chart, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;</script>

            </div>

            <div data-tab-panel="notas" hidden>

            <div class="card m-panel mb-3">
                <div class="m-panel-head">
                    <h6>Notas de cada avaliação</h6>
                    <div class="m-seg" role="group" aria-label="Bimestre" data-grades-unit>
                        <?php for ($u = 1; $u <= $rule['units']; $u++) { ?>
                            <button type="button" class="<?= $u === (int) $this->view->unity ? 'is-active' : '' ?>" data-unit="<?= $u ?>"><?= $u ?>º</button>
                        <?php } ?>
                    </div>
                </div>

                <?php for ($u = 1; $u <= $rule['units']; $u++) { ?>
                    <div class="m-gradelines" data-grades-panel="<?= $u ?>" <?= $u === (int) $this->view->unity ? '' : 'hidden' ?>>
                        <?php foreach ($ordered as $p) {
                            $dc = (int) $p['id'];
                            $isMine = in_array($dc, $mine, true);
                            $unit = $p['units'][$u];
                            $list = $exams[$dc][$u] ?? [];
                            $regular = array_values(array_filter($list, function ($e) { return !$e->recovery; }));
                            $recovery = array_values(array_filter($list, function ($e) { return $e->recovery; }));
                            $state = $unit['grade'] === null ? '' : ($unit['grade'] < $rule['passing'] ? 'is-low' : 'is-ok');
                            $slots = max((int) $rule['perBimester'], count($regular));
                        ?>
                            <div class="m-gradeline <?= \App\Tools\Subject::css($p['discipline']) ?>">
                                <div class="m-gradeline-name">
                                    <strong><i class="fas <?= \App\Tools\Subject::icon($p['discipline']) ?> m-subj-ico" aria-hidden="true"></i><?= htmlspecialchars($p['discipline']) ?></strong>
                                    <small><?= htmlspecialchars($p['teacher']) ?></small>
                                    <?php if ($isMine) { ?><a href="/portal-docente/notas?turma=<?= $dc ?>">Lançar notas</a><?php } ?>
                                </div>
                                <ul class="m-gradeline-exams">
                                    <?php for ($i = 0; $i < $slots; $i++) { $e = $regular[$i] ?? null; $types = $e ? array_map(function ($t) use ($examTypes) { return $examTypes[$t] ?? $t; }, array_filter(explode(',', $e->types))) : []; ?>
                                        <li class="<?= $e && $e->grade !== null && $e->grade < $rule['passing'] ? 'is-low' : '' ?>" <?= $e ? 'title="' . htmlspecialchars($e->description) . '"' : '' ?>>
                                            <span><?= $i + 1 ?>ª</span>
                                            <b><?= $e ? $fmt($e->grade) : '–' ?></b>
                                            <small><?= $e ? htmlspecialchars(implode(', ', $types) ?: $e->date) : 'não aplicada' ?></small>
                                        </li>
                                    <?php } ?>
                                    <?php if ($recovery) { $e = $recovery[0]; ?>
                                        <li class="<?= $e->grade !== null && $e->grade < $rule['passing'] ? 'is-low' : '' ?>" title="Recuperação">
                                            <span>Rec.</span>
                                            <b><?= $fmt($e->grade) ?></b>
                                            <small><?= htmlspecialchars($e->date) ?></small>
                                        </li>
                                    <?php } ?>
                                </ul>
                                <div class="m-gradeline-final <?= $state ?>">
                                    <span>Bimestre</span>
                                    <strong><?= $fmt($unit['grade']) ?><?= $unit['recovered'] ? '<sup>R</sup>' : ($unit['grade'] !== null && !$unit['closed'] ? '<sup>*</sup>' : '') ?></strong>
                                    <?php if ($unit['needsRecovery']) { ?><span class="m-pill m-pill-bad">Aguarda recuperação</span><?php } ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
                <p class="m-muted-note mb-0 mt-2">Em vermelho: abaixo de <?= $fmt($rule['passing']) ?> · <sup>R</sup> recuperação · <sup>*</sup> parcial</p>
            </div>

            <div class="card m-panel mb-3">
                <div class="m-panel-head">
                    <h6>Resumo do ano</h6>
                    <span class="m-muted-note m-0"><i class="fas fa-eye mr-1"></i>só leitura nas outras</span>
                </div>
                <div class="m-overview-wrap">
                    <table class="m-overview">
                        <thead>
                            <tr>
                                <th>Disciplina</th>
                                <?php for ($u = 1; $u <= $rule['units']; $u++) { ?><th><?= $u ?>º</th><?php } ?>
                                <th>Faltas</th>
                                <th>Freq.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($performance as $p) { $isMine = in_array((int) $p['id'], $mine, true); ?>
                                <tr>
                                    <td title="<?= htmlspecialchars($p['teacher']) ?>"><?= htmlspecialchars($p['discipline']) ?><?= $isMine ? ' <i class="fas fa-pen fa-xs"></i>' : '' ?></td>
                                    <?php foreach ($p['units'] as $unit) {
                                        $class = $unit['grade'] === null ? '' : ($unit['recovered'] ? 'is-rec' : ($unit['grade'] < $rule['passing'] ? 'is-low' : ''));
                                    ?>
                                        <td class="<?= $class ?>"><?= $fmt($unit['grade']) ?><?= $unit['recovered'] ? '<sup>R</sup>' : ($unit['grade'] !== null && !$unit['closed'] ? '<sup>*</sup>' : '') ?></td>
                                    <?php } ?>
                                    <td><?= (int) $p['absences'] ?><?= $p['excused'] ? ' <small>(' . (int) $p['excused'] . ' abon.)</small>' : '' ?></td>
                                    <td><?= $p['attendance'] === null ? '–' : $fmt($p['attendance']) . '%' ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <p class="m-muted-note mb-0 mt-2"><sup>R</sup> recuperação · <sup>*</sup> parcial · em vermelho: abaixo de <?= $fmt($rule['passing']) ?> · <i class="fas fa-pen fa-xs"></i> você edita</p>
            </div>

            </div>

            <div data-tab-panel="observacoes" hidden>

            <?php if ($own) {
                $quickNotes = $this->view->quickNotes;
                $quickDisciplines = array_map(function ($p) { return ['id' => (int) $p['id'], 'name' => $p['discipline'], 'subject' => \App\Tools\Subject::css($p['discipline'])]; }, $own);
            ?>
                <div class="card m-panel mb-3">
                    <?php require __DIR__ . '/components/quickNote.php'; ?>
                </div>
            <?php } ?>

            <div class="card m-panel mb-3">
                <div class="m-panel-head"><h6><i class="fas fa-comment-dots mr-2"></i>Histórico</h6></div>
                <?php $noteDisciplines = array_values(array_unique(array_map(function ($n) { return $n->discipline; }, $this->view->notes))); ?>
                <?php if (count($noteDisciplines) > 1) { ?>
                    <div class="m-filter-chips" data-notes-filter role="group" aria-label="Filtrar por matéria">
                        <button type="button" class="m-att-subject is-active" data-notes-value="">Todas</button>
                        <?php foreach ($noteDisciplines as $d) { ?><button type="button" class="m-att-subject" data-notes-value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></button><?php } ?>
                    </div>
                <?php } ?>
                <ul class="m-notes" data-notes>
                    <?php $lastDay = null; foreach ($this->view->notes as $n) { ?>
                        <?php if ($n->date !== $lastDay) { $lastDay = $n->date; ?><li class="m-day-header" data-day><?= htmlspecialchars($n->date === date('d/m/Y') ? 'Hoje · ' . substr($n->date, 0, 5) : $n->date) ?></li><?php } ?>
                        <li class="<?= \App\Tools\Subject::css($n->discipline) ?>" data-note-discipline="<?= htmlspecialchars($n->discipline) ?>"><p><?= nl2br(htmlspecialchars($n->text)) ?></p><small><i class="fas <?= \App\Tools\Subject::icon($n->discipline) ?> m-subj-ico"></i><?= htmlspecialchars($n->discipline) ?> · <?= htmlspecialchars($n->teacher) ?></small></li>
                    <?php } ?>
                </ul>
                <?php if (!$this->view->notes) { ?><p class="m-empty mb-0" data-notes-empty>Nenhuma observação ainda.</p><?php } ?>
            </div>

            </div>

        </div>
    </div>

</section>

<script>
    window.MATER_STUDENT = { enrollment: <?= (int) $student->id ?>, teacher: <?= json_encode($_SESSION['Teacher']['name'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?> };
</script>
<script src="<?= asset('/assets/js/quickNote.js') ?>" defer></script>
<script src="<?= asset('/assets/js/studentFile.js') ?>" defer></script>
