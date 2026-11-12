<?php
require __DIR__ . '/../../config/variables.php';

$enrollment = $this->view->enrollment;
$rule = $this->view->rule;
$rows = $this->view->rows;
$fmt = function ($n) { return $n === null ? '–' : str_replace('.', ',', (string) round((float) $n, 1)); };
$statusText = ['aprovado' => 'Média atingida', 'possivel' => 'Em andamento', 'impossivel' => 'Abaixo da média'];
// Fund. 1: frequência em dias (chamada do titular), não por disciplina
$daily = $this->view->dailyAttendance ?? null;

$docTitle = 'Boletim — ' . $enrollment->name;
$docHeading = 'Boletim escolar · ' . $enrollment->school_year;
$docPrintLabel = 'Imprimir / salvar PDF';
require __DIR__ . '/documentHead.php';
?>

        <div class="meta">
            <div><span>Aluno(a)</span><strong><?= htmlspecialchars($enrollment->name) ?></strong></div>
            <div><span>Turma</span><?= htmlspecialchars($enrollment->acronym_series) ?>ª <?= htmlspecialchars($enrollment->ballot) ?> · <?= htmlspecialchars($enrollment->course_name) ?></div>
            <div><span>Turno</span><?= htmlspecialchars($enrollment->shift) ?></div>
            <div><span>Matrícula</span><?= (int) $enrollment->enrollment_id ?></div>
            <?php if ($daily !== null) { ?>
                <div><span>Frequência</span><?= $daily['attendance'] === null ? '–' : $fmt($daily['attendance']) . '%' ?> · <?= (int) $daily['absences'] ?> dia(s) de falta em <?= (int) $daily['days'] ?> letivo(s)<?= $daily['excused'] ? ', ' . (int) $daily['excused'] . ' abonado(s)' : '' ?></div>
            <?php } ?>
        </div>

        <div>
            <table class="sheet-table sheet-bulletin">
                <thead>
                    <tr>
                        <th>Disciplina</th>
                        <?php for ($u = 1; $u <= $rule['units']; $u++) { ?><th><?= $u ?>º bim.</th><?php } ?>
                        <th>Média do ano</th>
                        <?php if ($daily === null) { ?><th>Faltas (abonadas)</th><?php } ?>
                        <th>Situação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $d) { ?>
                        <tr>
                            <td><?= htmlspecialchars($d['discipline']) ?></td>
                            <?php foreach ($d['units'] as $unit) { ?>
                                <td class="<?= $unit['grade'] !== null && $unit['closed'] && $unit['grade'] < $rule['passing'] ? 'low' : '' ?>"><?= $unit['grade'] === null ? '–' : $fmt($unit['grade']) . ($unit['recovered'] ? ' <small>(R)</small>' : (!$unit['closed'] ? ' <small>*</small>' : '')) ?></td>
                            <?php } ?>
                            <td><?= $d['simulation']['missing'] ? '–' : $fmt($d['simulation']['average']) ?></td>
                            <?php if ($daily === null) { ?><td><?= (int) $d['absences'] ?> (<?= (int) $d['excused'] ?>)</td><?php } ?>
                            <td><?= $statusText[$d['simulation']['status']] ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <p class="rule"><?= $rule['units'] ?> bimestres; nota do bimestre = média das <?= (int) $rule['perBimester'] ?> avaliações (0 a <?= $fmt($rule['maxPerUnit']) ?>); média <?= $fmt($rule['passing']) ?> por bimestre. (R) = vale a recuperação (maior que a média do bimestre). * = parcial. Média do ano = soma dos <?= $rule['units'] ?> bimestres ÷ <?= $rule['units'] ?> (precisa de <?= $fmt($rule['passing']) ?>); aparece quando os <?= $rule['units'] ?> bimestres fecham. Faltas são informativas e não entram na nota.</p>

        <footer>
            <div>
                Documento emitido eletronicamente em <?= date('d/m/Y') ?>.<br>
                Código de validação: <span class="code"><?= htmlspecialchars($this->view->authCode) ?></span><br>
                Confira em <?= htmlspecialchars($this->view->validationUrl) ?>
            </div>
            <div class="qr" aria-label="QR code de validação"><?= $this->view->qrSvg ?></div>
        </footer>
<?php require __DIR__ . '/documentFoot.php'; ?>
