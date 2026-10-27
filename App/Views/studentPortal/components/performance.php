<?php
// Desempenho por disciplina, frequência com abonos e simulador de notas — portal do aluno
$rule = $this->view->performanceRule;
$performance = $this->view->performance ?? [];

$statusLabel = [
    'aprovado' => ['Média atingida', 'm-pill-ok'],
    'possivel' => ['Em andamento', 'm-pill-info'],
    'impossivel' => ['Atenção', 'm-pill-bad'],
];

$fmt = function ($n) { return str_replace('.', ',', (string) round((float) $n, 1)); };
?>

<section class="m-perf" id="studentPerformance">
    <div class="col-lg-11 mx-auto">

        <div class="m-page-head">
            <div>
                <h5>Desempenho por matéria</h5>
                <p>Média para aprovação: <strong><?= $fmt($rule['passing']) ?></strong> em <?= $rule['units'] ?> unidades de <?= $fmt($rule['maxPerUnit']) ?> pontos · frequência mínima <?= $rule['minAttendance'] ?>%.</p>
            </div>
            <a class="btn btn-light" href="/portal-aluno/boletim/documento" target="_blank" rel="noopener"><i class="fas fa-file-signature mr-2"></i>Boletim com validação</a>
        </div>

        <?php if (!$performance) { ?>
            <div class="card m-board-empty">Nenhuma disciplina vinculada à sua turma.</div>
        <?php } ?>

        <div class="m-perf-grid">
            <?php foreach ($performance as $d) {
                $sim = $d['simulation'];
                [$label, $pill] = $statusLabel[$sim['status']]; ?>
                <article class="card m-perf-card" data-perf='<?= htmlspecialchars(json_encode(['units' => array_map(function ($u) { return $u['closed'] ? $u['earned'] : null; }, $d['units'])]), ENT_QUOTES) ?>'>
                    <header>
                        <div>
                            <strong><?= htmlspecialchars($d['discipline']) ?></strong>
                            <small><?= htmlspecialchars($d['teacher']) ?></small>
                        </div>
                        <span class="m-pill <?= $pill ?>"><?= $label ?></span>
                    </header>

                    <div class="m-perf-units" aria-label="Pontos por unidade">
                        <?php foreach ($d['units'] as $u => $unit) {
                            $pct = $rule['maxPerUnit'] > 0 ? min(100, 100 * $unit['earned'] / $rule['maxPerUnit']) : 0;
                            $low = $unit['closed'] && $unit['earned'] < $rule['passing']; ?>
                            <div class="m-perf-unit">
                                <span class="m-perf-bar"><span class="<?= $low ? 'is-low' : '' ?>" style="height: <?= round($pct) ?>%"></span></span>
                                <strong><?= $unit['distributed'] > 0 ? $fmt($unit['earned']) : '–' ?></strong>
                                <small><?= $u ?>ª un.<?= !$unit['closed'] && $unit['distributed'] > 0 ? '<br>' . $fmt($unit['distributed']) . ' de ' . $fmt($rule['maxPerUnit']) . ' pts' : '' ?></small>
                            </div>
                        <?php } ?>
                    </div>

                    <p class="m-perf-sim">
                        <?php if ($sim['status'] === 'aprovado') { ?>
                            <i class="fas fa-check-circle mr-1"></i>Você já alcançou a média nesta matéria.
                        <?php } elseif ($sim['needed'] !== null) { ?>
                            <i class="fas fa-bullseye mr-1"></i>Precisa de <strong><?= $fmt($sim['needed']) ?></strong> em cada uma das <?= $sim['missing'] ?> unidade<?= $sim['missing'] > 1 ? 's' : '' ?> que falta<?= $sim['missing'] > 1 ? 'm' : '' ?><?= $sim['status'] === 'impossivel' ? ' — acima do máximo; procure o professor sobre a recuperação' : '' ?>.
                        <?php } else { ?>
                            <i class="fas fa-exclamation-circle mr-1"></i>Média final <?= $fmt($sim['average']) ?>. Procure o professor sobre a recuperação.
                        <?php } ?>
                    </p>

                    <div class="m-perf-att">
                        <span><i class="fas fa-user-times mr-1"></i><?= (int) $d['absences'] ?> falta<?= $d['absences'] == 1 ? '' : 's' ?><?= $d['excused'] ? ' · ' . (int) $d['excused'] . ' abonada' . ($d['excused'] > 1 ? 's' : '') : '' ?></span>
                        <?php if ($d['attendance'] !== null) { ?>
                            <span class="<?= $d['attendance'] < $rule['minAttendance'] ? 'is-low' : '' ?>"><?= $fmt($d['attendance']) ?>% de frequência</span>
                        <?php } ?>
                    </div>

                    <?php if ($sim['missing'] > 0) { ?>
                        <details class="m-perf-try">
                            <summary><i class="fas fa-calculator mr-1"></i>Simular minhas próximas notas</summary>
                            <div class="m-perf-try-fields">
                                <?php foreach ($d['units'] as $u => $unit) { if ($unit['closed']) continue; ?>
                                    <label><span><?= $u ?>ª unidade</span>
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
