<?php
require __DIR__ . '/../../config/variables.php';

$enrollment = $this->view->enrollment;
$rule = $this->view->rule;
$rows = $this->view->rows;
$fmt = function ($n) { return $n === null ? '–' : str_replace('.', ',', (string) round((float) $n, 1)); };
$statusText = ['aprovado' => 'Média atingida', 'possivel' => 'Em andamento', 'impossivel' => 'Abaixo da média'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Boletim — <?= htmlspecialchars($enrollment->name) ?></title>
    <style>
        :root { --text: #0F1B33; --muted: #5B6B85; --border: #DCE3EF; --primary: #1868E8; --bad: #DC2626; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #F4F7FC; color: var(--text); font: 14px/1.45 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 24px 16px; }
        .doc { max-width: 820px; margin: 0 auto; background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 28px; }
        .actions { max-width: 820px; margin: 0 auto 12px; text-align: right; }
        .actions button { min-height: 44px; padding: 0 18px; border: 0; border-radius: 10px; background: var(--primary); color: #fff; font-weight: 700; font-size: 15px; cursor: pointer; }
        header { display: flex; justify-content: space-between; align-items: center; gap: 16px; border-bottom: 2px solid var(--primary); padding-bottom: 12px; margin-bottom: 16px; }
        header img { height: 48px; }
        h1 { font-size: 18px; margin: 0; }
        .meta { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 6px 16px; margin-bottom: 16px; }
        .meta span { color: var(--muted); display: block; font-size: 12px; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { border: 1px solid var(--border); padding: 6px 8px; text-align: center; white-space: nowrap; }
        th:first-child, td:first-child { text-align: left; white-space: normal; }
        thead th { background: #EEF3FC; }
        .low { color: var(--bad); font-weight: 700; }
        .rule { margin: 12px 0 0; color: var(--muted); font-size: 12px; }
        footer { display: flex; justify-content: space-between; align-items: center; gap: 16px; border-top: 1px dashed var(--border); margin-top: 20px; padding-top: 12px; font-size: 12px; color: var(--muted); }
        .qr { width: 96px; height: 96px; flex: 0 0 96px; }
        .qr svg { width: 100%; height: 100%; }
        .code { font-family: ui-monospace, Consolas, monospace; font-weight: 700; color: var(--text); letter-spacing: .06em; }
        @media print { body { background: #fff; padding: 0; } .actions { display: none; } .doc { border: 0; padding: 0; } }
    </style>
</head>
<body>
    <div class="actions"><button type="button" onclick="window.print()">Imprimir / salvar PDF</button></div>

    <article class="doc">
        <header>
            <div>
                <h1>Boletim escolar · <?= htmlspecialchars($enrollment->school_year) ?></h1>
                <div><?= htmlspecialchars(getenv('SCHOOL_NAME') ?: 'MATER Colégio e Curso') ?></div>
            </div>
            <img src="<?= $app_url ?>/assets/img/logo-components/logo.png" alt="MATER">
        </header>

        <div class="meta">
            <div><span>Aluno(a)</span><strong><?= htmlspecialchars($enrollment->name) ?></strong></div>
            <div><span>Turma</span><?= htmlspecialchars($enrollment->acronym_series) ?>ª <?= htmlspecialchars($enrollment->ballot) ?> · <?= htmlspecialchars($enrollment->course_name) ?></div>
            <div><span>Turno</span><?= htmlspecialchars($enrollment->shift) ?></div>
            <div><span>Matrícula</span><?= (int) $enrollment->enrollment_id ?></div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Disciplina</th>
                        <?php for ($u = 1; $u <= $rule['units']; $u++) { ?><th><?= $u ?>ª un.</th><?php } ?>
                        <th>Média parcial</th>
                        <th>Faltas (abonadas)</th>
                        <th>Situação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $d) { ?>
                        <tr>
                            <td><?= htmlspecialchars($d['discipline']) ?></td>
                            <?php foreach ($d['units'] as $unit) { ?>
                                <td class="<?= $unit['closed'] && $unit['earned'] < $rule['passing'] ? 'low' : '' ?>"><?= $unit['distributed'] > 0 ? $fmt($unit['earned']) : '–' ?></td>
                            <?php } ?>
                            <td><?= $fmt($d['simulation']['average']) ?></td>
                            <td><?= (int) $d['absences'] ?> (<?= (int) $d['excused'] ?>)</td>
                            <td><?= $statusText[$d['simulation']['status']] ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <p class="rule">Média para aprovação <?= $fmt($rule['passing']) ?> em <?= $rule['units'] ?> unidades de <?= $fmt($rule['maxPerUnit']) ?> pontos. Unidades com pontos ainda em distribuição mostram a nota parcial.</p>

        <footer>
            <div>
                Documento emitido eletronicamente em <?= date('d/m/Y') ?>.<br>
                Código de validação: <span class="code"><?= htmlspecialchars($this->view->authCode) ?></span><br>
                Confira em <?= htmlspecialchars($this->view->validationUrl) ?>
            </div>
            <div class="qr" aria-label="QR code de validação"><?= $this->view->qrSvg ?></div>
        </footer>
    </article>
</body>
</html>
