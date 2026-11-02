<?php
// Simuladores de uma matéria (bimestre e média do ano): usados em Desempenho e no Boletim. Entrada: $d, $rule, $fmt, $counts, $studentExams.
// simulador do bimestre: primeiro bimestre ainda aberto (sem as 3 notas), avaliação por avaliação
$bimUnit = null;
foreach ($d['units'] as $u => $unit) { if (!$unit['closed']) { $bimUnit = $u; break; } }
if ($bimUnit !== null) {
    $given = array_values(array_filter($studentExams[(int) $d['id']][$bimUnit] ?? [], function ($g) { return $g !== null; }));
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

<?php if ($d['simulation']['missing'] > 0) { ?>
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
