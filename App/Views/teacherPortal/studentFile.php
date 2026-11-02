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

            <?php foreach ($own as $p) { $dc = (int) $p['id']; ?>
                <div class="card m-panel mb-3">
                    <div class="m-panel-head">
                        <h6><?= htmlspecialchars($p['discipline']) ?> <span class="m-pill m-pill-info ml-1">sua disciplina</span></h6>
                        <a href="/portal-docente/notas?turma=<?= $dc ?>">Lançar notas</a>
                    </div>

                    <div class="m-bim-grid">
                        <?php for ($u = 1; $u <= $rule['units']; $u++) {
                            $unit = $p['units'][$u];
                            $list = $exams[$dc][$u] ?? [];
                            $state = $unit['grade'] === null ? '' : ($unit['needsRecovery'] ? 'is-low' : ($unit['grade'] < $rule['passing'] ? ($unit['closed'] ? 'is-low' : 'is-warn') : 'is-ok'));
                        ?>
                            <div class="m-bim <?= $state ?>">
                                <div class="m-bim-head">
                                    <span><?= $u ?>º bimestre</span>
                                    <strong><?= $fmt($unit['grade']) ?><?= $unit['recovered'] ? '<sup>R</sup>' : ($unit['grade'] !== null && !$unit['closed'] ? '<sup>*</sup>' : '') ?></strong>
                                </div>
                                <?php if (!$list) { ?>
                                    <p class="m-muted-note m-0">Sem avaliações.</p>
                                <?php } else { ?>
                                    <ul>
                                        <?php foreach ($list as $e) { ?>
                                            <li><span><?= htmlspecialchars($e->description) ?> <small><?= htmlspecialchars($e->date) ?></small></span><b><?= $fmt($e->grade) ?></b></li>
                                        <?php } ?>
                                    </ul>
                                <?php } ?>
                                <?php if ($unit['needsRecovery']) { ?><span class="m-pill m-pill-bad">Aguarda recuperação</span><?php } ?>
                            </div>
                        <?php } ?>
                    </div>

                    <div class="m-quick" data-quick data-dc="<?= $dc ?>">
                        <span class="m-att-label">Observação rápida</span>
                        <div class="m-att-subjects-list">
                            <?php foreach ($this->view->quickNotes as $q) { ?>
                                <button type="button" class="m-att-subject" data-quick-note="<?= htmlspecialchars($q) ?>"><?= htmlspecialchars($q) ?></button>
                            <?php } ?>
                        </div>
                        <form class="m-quick-form" data-quick-form>
                            <input type="text" class="form-control" maxlength="1000" placeholder="Ou escreva uma observação…" aria-label="Observação sobre o aluno">
                            <button type="submit" class="btn btn-primary">Salvar</button>
                        </form>
                    </div>
                </div>
            <?php } ?>

            <div class="card m-panel mb-3">
                <div class="m-panel-head">
                    <h6>Todas as disciplinas</h6>
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

            <div class="card m-panel mb-3">
                <div class="m-panel-head"><h6>Observações</h6></div>
                <ul class="m-notes" data-notes>
                    <?php foreach ($this->view->notes as $n) { ?>
                        <li><p><?= nl2br(htmlspecialchars($n->text)) ?></p><small><?= htmlspecialchars($n->discipline) ?> · <?= htmlspecialchars($n->teacher) ?> · <?= htmlspecialchars($n->date) ?></small></li>
                    <?php } ?>
                </ul>
                <?php if (!$this->view->notes) { ?><p class="m-empty mb-0" data-notes-empty>Nenhuma observação ainda.</p><?php } ?>
            </div>

        </div>
    </div>

</section>

<script>
    window.MATER_STUDENT = { enrollment: <?= (int) $student->id ?>, teacher: <?= json_encode($_SESSION['Teacher']['name'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?> };
</script>
<script src="<?= asset('/assets/js/studentFile.js') ?>" defer></script>
