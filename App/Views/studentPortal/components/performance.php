<?php
// Desempenho por disciplina (bimestres com recuperação), faltas e simulador de notas — portal do aluno
$rule = $this->view->performanceRule;
$performance = $this->view->performance ?? [];

$statusLabel = [
    'aprovado' => ['Média atingida', 'm-pill-ok'],
    'possivel' => ['Em andamento', 'm-pill-info'],
    'impossivel' => ['Atenção', 'm-pill-bad'],
];

$fmt = function ($n) { return str_replace('.', ',', (string) round((float) $n, 1)); };

// bimestre que conta na média final: fechado e, se ficou abaixo de 7, com a recuperação lançada
$counts = function ($unit) { return $unit['closed'] && !$unit['needsRecovery']; };
?>

<section class="m-perf" id="studentPerformance">
    <div class="col-lg-11 mx-auto">

        <div class="m-page-head">
            <div>
                <h5>Desempenho por matéria</h5>
                <p><?= $rule['units'] ?> bimestres · <?= $rule['perBimester'] ?> avaliações de 0 a <?= $fmt($rule['maxPerUnit']) ?> por bimestre · média <strong><?= $fmt($rule['passing']) ?></strong> por bimestre. Abaixo de <?= $fmt($rule['recoveryBelow']) ?>, tem recuperação (vale a maior nota). Média do ano = soma dos <?= $rule['units'] ?> bimestres ÷ <?= $rule['units'] ?>; precisa de <strong><?= $fmt($rule['passing']) ?></strong>.</p>
            </div>
            <a class="btn btn-light" href="/portal-aluno/boletim/documento" target="_blank" rel="noopener"><i class="fas fa-file-signature mr-2"></i>Boletim com validação</a>
        </div>

        <?php if (!$performance) { ?>
            <div class="card m-board-empty">Nenhuma disciplina vinculada à sua turma.</div>
        <?php } ?>

        <div class="m-perf-grid">
            <?php foreach ($performance as $d) {
                $sim = $d['simulation'];
                [$label, $pill] = $d['pendingRecovery'] ? ['Recuperação', 'm-pill-warn'] : $statusLabel[$sim['status']]; ?>
                <article class="card m-perf-card <?= \App\Tools\Subject::css($d['discipline']) ?>" data-perf='<?= htmlspecialchars(json_encode(['units' => array_map(function ($u) use ($counts) { return $counts($u) ? $u['grade'] : null; }, $d['units'])]), ENT_QUOTES) ?>'>
                    <header>
                        <span class="m-perf-ico"><i class="fas <?= \App\Tools\Subject::icon($d['discipline']) ?>" aria-hidden="true"></i></span>
                        <div>
                            <strong><?= htmlspecialchars($d['discipline']) ?></strong>
                            <small><?= htmlspecialchars($d['teacher']) ?></small>
                        </div>
                        <span class="m-pill <?= $pill ?>"><?= $label ?></span>
                    </header>

                    <div class="m-perf-units" aria-label="Nota por bimestre">
                        <?php foreach ($d['units'] as $u => $unit) {
                            $grade = $unit['grade'];
                            $pct = $grade === null ? 0 : min(100, 100 * $grade / $rule['maxPerUnit']);
                            $low = $grade !== null && $unit['closed'] && $grade < $rule['passing']; ?>
                            <div class="m-perf-unit">
                                <span class="m-perf-bar"><span class="<?= $low ? 'is-low' : '' ?>" style="height: <?= round($pct) ?>%"></span></span>
                                <strong><?= $grade === null ? '–' : $fmt($grade) ?></strong>
                                <small><?= $u ?>º bim.<?= ($grade !== null && !$unit['closed']) ? '<br>' . (int) $unit['graded'] . ' de ' . (int) $rule['perBimester'] . ' aval.' : '' ?></small>
                                <?php if ($unit['recovered']) { ?>
                                    <span class="m-perf-tag is-rec" title="Média era <?= $fmt($unit['average']) ?>">Recup.</span>
                                <?php } elseif ($unit['recoveryTaken']) { ?>
                                    <span class="m-perf-tag" title="Recuperação menor que a média: vale a do bimestre">Manteve</span>
                                <?php } elseif ($unit['needsRecovery']) { ?>
                                    <span class="m-perf-tag is-need">Recuperação</span>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>

                    <p class="m-perf-sim">
                        <?php if ($d['pendingRecovery']) { ?>
                            <i class="fas fa-redo mr-1"></i>Recuperação do <?= implode('º e ', $d['pendingRecovery']) ?>º bimestre: se a nota for maior, ela fica no lugar da do bimestre.
                        <?php } elseif ($sim['status'] === 'aprovado') { ?>
                            <i class="fas fa-check-circle mr-1"></i>Você já garantiu média do ano <?= $fmt($rule['passing']) ?> nesta matéria.
                        <?php } elseif ($sim['needed'] !== null) { ?>
                            <i class="fas fa-bullseye mr-1"></i>Para média do ano <?= $fmt($rule['passing']) ?>, precisa de <strong><?= $fmt($sim['needed']) ?></strong> em cada um dos <?= $sim['missing'] ?> bimestre<?= $sim['missing'] > 1 ? 's' : '' ?> que falta<?= $sim['missing'] > 1 ? 'm' : '' ?><?= $sim['status'] === 'impossivel' ? ' — acima de 10; procure o professor' : '' ?>.
                        <?php } else { ?>
                            <i class="fas fa-exclamation-circle mr-1"></i>Média do ano <?= $fmt($sim['average']) ?>, abaixo de <?= $fmt($rule['passing']) ?>. Procure o professor.
                        <?php } ?>
                    </p>

                    <div class="m-perf-att">
                        <span><i class="fas fa-user-times mr-1"></i><?= (int) $d['absences'] ?> falta<?= $d['absences'] == 1 ? '' : 's' ?><?= $d['excused'] ? ' · ' . (int) $d['excused'] . ' abonada' . ($d['excused'] > 1 ? 's' : '') : '' ?></span>
                        <?php if ($d['attendance'] !== null) { ?>
                            <span><?= $fmt($d['attendance']) ?>% de frequência</span>
                        <?php } ?>
                    </div>

                    <?php
                    // simulador do bimestre: primeiro bimestre ainda aberto (sem as 3 notas), avaliação por avaliação
                    $bimUnit = null;
                    foreach ($d['units'] as $u => $unit) { if (!$unit['closed']) { $bimUnit = $u; break; } }
                    if ($bimUnit !== null) {
                        $given = array_values(array_filter($this->view->studentExams[(int) $d['id']][$bimUnit] ?? [], function ($g) { return $g !== null; }));
                    ?>
                        <details class="m-perf-try" data-bim-sim data-bim='<?= htmlspecialchars(json_encode(['unit' => $bimUnit, 'grades' => $given]), ENT_QUOTES) ?>'>
                            <summary><i class="fas fa-pencil-alt mr-1"></i>Simular o <?= $bimUnit ?>º bimestre</summary>
                            <div class="m-perf-try-fields">
                                <?php for ($i = 0; $i < $rule['perBimester']; $i++) { ?>
                                    <label><span><?= $i + 1 ?>ª avaliação</span>
                                        <?php if (isset($given[$i])) { ?>
                                            <input type="text" class="form-control" value="<?= $fmt($given[$i]) ?>" disabled aria-label="<?= $i + 1 ?>ª avaliação (nota lançada)">
                                        <?php } else { ?>
                                            <input type="text" inputmode="decimal" class="form-control" data-bim-slot placeholder="0 a 10" autocomplete="off">
                                        <?php } ?>
                                    </label>
                                <?php } ?>
                            </div>
                            <p class="m-perf-try-result" data-bim-result aria-live="polite"></p>
                        </details>
                    <?php } ?>

                    <?php if ($sim['missing'] > 0) { ?>
                        <details class="m-perf-try">
                            <summary><i class="fas fa-calculator mr-1"></i>Simular a média do ano</summary>
                            <div class="m-perf-try-fields">
                                <?php foreach ($d['units'] as $u => $unit) { if ($counts($unit)) continue; ?>
                                    <label><span><?= $u ?>º bimestre<?= $unit['needsRecovery'] ? ' (recup.)' : '' ?></span>
                                        <input type="text" inputmode="decimal" class="form-control" data-try-unit="<?= $u ?>" placeholder="0 a <?= $fmt($rule['maxPerUnit']) ?>" autocomplete="off">
                                    </label>
                                <?php } ?>
                            </div>
                            <p class="m-perf-try-result" data-try-result aria-live="polite">Digite as notas que você espera tirar.</p>
                        </details>
                    <?php } ?>
                </article>
            <?php } ?>
        </div>
    </div>
</section>

<script>
    window.MATER_PERFORMANCE = <?= json_encode($rule) ?>;
</script>
<script src="<?= asset('/assets/js/performance.js') ?>" defer></script>
