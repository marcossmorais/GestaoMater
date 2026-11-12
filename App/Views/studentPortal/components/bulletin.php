<?php
// Boletim do aluno: nota de cada bimestre (0 a 10, recuperação aplicada), média do ano e faltas — regra do MATER
$rule = $this->view->rule;
$rows = $this->view->rows;
$fmt = function ($n) { return $n === null ? '–' : str_replace('.', ',', (string) round((float) $n, 1)); };
$counts = function ($unit) { return $unit['closed'] && !$unit['needsRecovery']; };
// Fund. 1: frequência em dias (chamada do titular), não por matéria
$daily = $this->view->dailyAttendance ?? null;
$pctText = function ($n) { return $n === null ? '–' : str_replace('.', ',', (string) round((float) $n, 1)) . '%'; };
$status = [
    'aprovado' => ['Aprovado', 'm-pill-ok'],
    'possivel' => ['Em andamento', 'm-pill-info'],
    'impossivel' => ['Atenção', 'm-pill-bad'],
];
?>

<div class="m-bulletin">

    <div class="m-page-head">
        <div>
            <h5>Boletim</h5>
            <p>Nota de cada bimestre de 0 a 10 (média das <?= (int) $rule['perBimester'] ?> avaliações; abaixo de <?= $fmt($rule['passing']) ?>, vale a maior nota entre o bimestre e a recuperação). Toque na matéria para ver AV1, AV2, AV3, recuperação e o simulador. Média do ano = soma dos <?= (int) $rule['units'] ?> bimestres ÷ <?= (int) $rule['units'] ?>.</p>
        </div>
        <a class="btn btn-primary" href="/portal-aluno/boletim/documento" target="_blank" rel="noopener"><i class="fas fa-file-download mr-2"></i>Baixar boletim</a>
    </div>

    <?php if (!$rows) { ?>
        <div class="m-empty">Nenhuma disciplina vinculada à sua turma.</div>
    <?php } else { ?>
        <?php if ($daily !== null) { ?>
            <p class="m-muted-note"><strong>Frequência: <?= $pctText($daily['attendance']) ?></strong> · <?= (int) $daily['absences'] ?> dia<?= (int) $daily['absences'] === 1 ? '' : 's' ?> de falta em <?= (int) $daily['days'] ?> dia<?= (int) $daily['days'] === 1 ? '' : 's' ?> letivo<?= (int) $daily['days'] === 1 ? '' : 's' ?><?= $daily['excused'] ? ' (' . (int) $daily['excused'] . ' abonado' . ((int) $daily['excused'] === 1 ? '' : 's') . ')' : '' ?>.</p>
        <?php } ?>
        <div class="m-bul-tools"><button type="button" class="btn btn-light" data-bul-all aria-expanded="false"><i class="fas fa-expand-alt mr-2" aria-hidden="true"></i><span>Expandir todas</span></button></div>
        <div class="m-overview-wrap">
            <table class="m-overview m-bulletin-table">
                <thead>
                    <tr>
                        <th>Matéria</th>
                        <?php for ($u = 1; $u <= $rule['units']; $u++) { ?><th><?= $u ?>º bim.</th><?php } ?>
                        <th>Média do ano</th>
                        <?php if ($daily === null) { ?><th>Faltas</th><?php } ?>
                        <th>Situação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $d) {
                        $sim = $d['simulation'];
                        $closedAll = count(array_filter($d['units'], $counts)) === (int) $rule['units'];
                        [$label, $pill] = $d['pendingRecovery'] ? ['Recuperação', 'm-pill-warn'] : $status[$sim['status']];
                    ?>
                        <tr class="<?= \App\Tools\Subject::css($d['discipline']) ?>">
                            <td data-label="Matéria"><button type="button" class="m-bul-toggle" data-bul-toggle aria-expanded="false"><i class="fas fa-chevron-right" aria-hidden="true"></i><i class="fas <?= \App\Tools\Subject::icon($d['discipline']) ?> m-subj-ico" aria-hidden="true"></i><?= htmlspecialchars($d['discipline']) ?></button></td>
                            <?php foreach ($d['units'] as $u => $unit) {
                                $class = $unit['grade'] === null ? '' : ($unit['recovered'] ? 'is-rec' : ($unit['grade'] < $rule['passing'] ? 'is-low' : ''));
                            ?>
                                <td data-label="<?= $u ?>º bim." class="<?= $class ?>"><?= $fmt($unit['grade']) ?><?= $unit['recovered'] ? '<sup>R</sup>' : ($unit['grade'] !== null && !$unit['closed'] ? '<sup>*</sup>' : '') ?></td>
                            <?php } ?>
                            <td data-label="Média do ano"><strong><?= $closedAll ? $fmt($sim['average']) : '–' ?></strong></td>
                            <?php if ($daily === null) { ?><td data-label="Faltas"><?= (int) $d['absences'] ?><?= $d['excused'] ? ' <small>(' . (int) $d['excused'] . ' abon.)</small>' : '' ?></td><?php } ?>
                            <td data-label="Situação"><span class="m-pill <?= $pill ?>"><?= $label ?></span></td>
                        </tr>
                        <tr class="m-bul-detail <?= \App\Tools\Subject::css($d['discipline']) ?>" hidden>
                            <td colspan="<?= (int) $rule['units'] + ($daily === null ? 4 : 3) ?>">
                                <div data-perf='<?= htmlspecialchars(json_encode(['units' => array_map(function ($unit) use ($counts) { return $counts($unit) ? $unit['grade'] : null; }, $d['units'])]), ENT_QUOTES) ?>'>
                                    <table class="m-bul-exams">
                                        <thead>
                                            <tr>
                                                <th>Bim.</th>
                                                <?php for ($i = 1; $i <= (int) $rule['perBimester']; $i++) { ?><th>AV<?= $i ?></th><?php } ?>
                                                <th>Rec.</th>
                                                <th>Nota</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($d['units'] as $u => $unit) {
                                                $list = $this->view->exams[(int) $d['id']][$u] ?? [];
                                                $regular = array_values(array_filter($list, function ($e) { return !$e->recovery; }));
                                                $recovery = array_values(array_filter($list, function ($e) { return $e->recovery; }))[0] ?? null;
                                                $cell = function ($e) use ($fmt, $rule) {
                                                    $types = $e ? \App\Models\TeacherStudent\Gradebook::typeLabels($e->types ?? '') : '';
                                                    $low = $e && $e->grade !== null && $e->grade < $rule['passing'];
                                                    return '<td class="' . ($low ? 'is-low' : '') . '" title="' . htmlspecialchars($e ? trim($types . ' · ' . $e->date, ' ·') : 'Ainda não aplicada') . '">' . ($e ? $fmt($e->grade) : '–') . '</td>';
                                                };
                                            ?>
                                                <tr>
                                                    <th>B<?= (int) $u ?></th>
                                                    <?php for ($i = 0; $i < (int) $rule['perBimester']; $i++) echo $cell($regular[$i] ?? null); ?>
                                                    <?= $cell($recovery) ?>
                                                    <td class="m-bul-final <?= $unit['grade'] !== null && $unit['grade'] < $rule['passing'] ? 'is-low' : '' ?>"><?= $fmt($unit['grade']) ?></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                    <?php $studentExams = $this->view->studentExams; require __DIR__ . '/gradeSimulators.php'; ?>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <p class="m-muted-note mt-2 mb-0"><sup>R</sup> nota da recuperação · <sup>*</sup> parcial (faltam avaliações) · em vermelho: abaixo de <?= $fmt($rule['passing']) ?></p>
    <?php } ?>

</div>
