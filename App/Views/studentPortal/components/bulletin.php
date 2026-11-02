<?php
// Boletim do aluno: nota de cada bimestre (0 a 10, recuperação aplicada), média do ano e faltas — regra do MATER
$rule = $this->view->rule;
$rows = $this->view->rows;
$fmt = function ($n) { return $n === null ? '–' : str_replace('.', ',', (string) round((float) $n, 1)); };
$counts = function ($unit) { return $unit['closed'] && !$unit['needsRecovery']; };
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
            <p>Nota de cada bimestre de 0 a 10 (média das <?= (int) $rule['perBimester'] ?> avaliações; abaixo de <?= $fmt($rule['passing']) ?>, vale a maior nota entre o bimestre e a recuperação). Média do ano = soma dos <?= (int) $rule['units'] ?> bimestres ÷ <?= (int) $rule['units'] ?>.</p>
        </div>
        <a class="btn btn-primary" href="/portal-aluno/boletim/documento" target="_blank" rel="noopener"><i class="fas fa-file-download mr-2"></i>Baixar boletim</a>
    </div>

    <?php if (!$rows) { ?>
        <div class="m-empty">Nenhuma disciplina vinculada à sua turma.</div>
    <?php } else { ?>
        <div class="m-overview-wrap">
            <table class="m-overview m-bulletin-table">
                <thead>
                    <tr>
                        <th>Matéria</th>
                        <?php for ($u = 1; $u <= $rule['units']; $u++) { ?><th><?= $u ?>º bim.</th><?php } ?>
                        <th>Média do ano</th>
                        <th>Faltas</th>
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
                            <td data-label="Matéria"><i class="fas <?= \App\Tools\Subject::icon($d['discipline']) ?> m-subj-ico" aria-hidden="true"></i><?= htmlspecialchars($d['discipline']) ?></td>
                            <?php foreach ($d['units'] as $u => $unit) {
                                $class = $unit['grade'] === null ? '' : ($unit['recovered'] ? 'is-rec' : ($unit['grade'] < $rule['passing'] ? 'is-low' : ''));
                            ?>
                                <td data-label="<?= $u ?>º bim." class="<?= $class ?>"><?= $fmt($unit['grade']) ?><?= $unit['recovered'] ? '<sup>R</sup>' : ($unit['grade'] !== null && !$unit['closed'] ? '<sup>*</sup>' : '') ?></td>
                            <?php } ?>
                            <td data-label="Média do ano"><strong><?= $closedAll ? $fmt($sim['average']) : '–' ?></strong></td>
                            <td data-label="Faltas"><?= (int) $d['absences'] ?><?= $d['excused'] ? ' <small>(' . (int) $d['excused'] . ' abon.)</small>' : '' ?></td>
                            <td data-label="Situação"><span class="m-pill <?= $pill ?>"><?= $label ?></span></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <p class="m-muted-note mt-2 mb-0"><sup>R</sup> nota da recuperação · <sup>*</sup> parcial (faltam avaliações) · em vermelho: abaixo de <?= $fmt($rule['passing']) ?> · a média do ano aparece quando os <?= (int) $rule['units'] ?> bimestres fecham</p>
    <?php } ?>

</div>
