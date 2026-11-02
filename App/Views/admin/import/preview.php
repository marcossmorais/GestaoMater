<?php
$preview = $this->view->preview;
$counts = $preview['counts'];
$blocked = $counts['erro'] > 0 || $preview['missing'];
$toSave = $counts['novo'] + $counts['matricular'];

$statusLabel = [
    'novo' => ['m-pill-ok', 'Novo'],
    'matricular' => ['m-pill-info', 'Só matrícula'],
    'ignorar' => ['m-pill-warn', 'Ignorado'],
    'erro' => ['m-pill-bad', 'Erro'],
];

// erros primeiro, para corrigir mais rápido
$rows = $preview['rows'];
usort($rows, function ($a, $b) {
    return (($a['status'] === 'erro') ? 0 : 1) <=> (($b['status'] === 'erro') ? 0 : 1) ?: $a['line'] <=> $b['line'];
});
$headers = $rows ? array_keys($rows[0]['summary']) : [];
?>

<section id="import">
    <div class="row main-container">
        <div class="col-lg-11 mx-auto">

            <div class="m-page-head">
                <div>
                    <a href="/admin/importacao?tipo=<?= htmlspecialchars($preview['type']) ?>" class="m-obs-back"><i class="fas fa-arrow-left mr-2"></i>Importação</a>
                    <h5>Prévia: <?= htmlspecialchars($this->view->typeLabel) ?></h5>
                    <p><?= htmlspecialchars($this->view->fileName) ?> · <?= count($rows) ?> linha<?= count($rows) === 1 ? '' : 's' ?> · nada foi gravado ainda</p>
                </div>
            </div>

            <div class="m-kpis m-obs-kpis">
                <div class="card m-kpi"><div class="m-kpi-icon"><i class="fas fa-plus"></i></div><div><div class="m-kpi-label">Novos</div><div class="m-kpi-value"><?= $counts['novo'] ?></div></div></div>
                <?php if ($preview['type'] === 'alunos') { ?>
                    <div class="card m-kpi"><div class="m-kpi-icon"><i class="fas fa-user-check"></i></div><div><div class="m-kpi-label">Só matrícula</div><div class="m-kpi-value"><?= $counts['matricular'] ?></div></div></div>
                <?php } ?>
                <div class="card m-kpi"><div class="m-kpi-icon"><i class="fas fa-forward"></i></div><div><div class="m-kpi-label">Ignorados</div><div class="m-kpi-value"><?= $counts['ignorar'] ?></div></div></div>
                <div class="card m-kpi"><div class="m-kpi-icon"><i class="fas fa-times"></i></div><div><div class="m-kpi-label">Com erro</div><div class="m-kpi-value <?= $counts['erro'] ? 'is-low' : '' ?>"><?= $counts['erro'] ?></div></div></div>
            </div>

            <?php if ($preview['missing']) { ?>
                <div class="m-imp-alert is-error"><i class="fas fa-exclamation-circle"></i>Faltam colunas obrigatórias: <?= htmlspecialchars(implode(', ', $preview['missing'])) ?>. Baixe a planilha-modelo para ver os nomes.</div>
            <?php } ?>
            <?php if ($preview['unknown']) { ?>
                <div class="m-imp-alert"><i class="fas fa-info-circle"></i>Colunas ignoradas (nome não reconhecido): <?= htmlspecialchars(implode(', ', $preview['unknown'])) ?>.</div>
            <?php } ?>

            <div class="card m-panel m-imp-confirm">
                <?php if ($blocked) { ?>
                    <p><strong>Corrija os erros e envie o arquivo de novo.</strong> A importação é tudo ou nada: com qualquer linha com erro, nada é gravado.</p>
                    <a href="/admin/importacao?tipo=<?= htmlspecialchars($preview['type']) ?>" class="btn btn-primary"><i class="fas fa-upload mr-2"></i>Enviar outro arquivo</a>
                <?php } elseif ($toSave === 0) { ?>
                    <p><strong>Nada novo para gravar.</strong> Todas as linhas já estão cadastradas.</p>
                    <a href="/admin/importacao?tipo=<?= htmlspecialchars($preview['type']) ?>" class="btn btn-light">Voltar</a>
                <?php } else { ?>
                    <p>Serão gravados <strong><?= $counts['novo'] ?></strong> <?= $preview['type'] === 'turmas' ? 'turma(s)' : 'cadastro(s)' ?><?= $counts['matricular'] ? ' e <strong>' . $counts['matricular'] . '</strong> matrícula(s) de alunos já cadastrados' : '' ?>, de uma vez. Se algo falhar, nada fica gravado.</p>
                    <form method="post" action="/admin/importacao/confirmar" onsubmit="this.querySelector('button').disabled = true">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($this->view->token) ?>">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check mr-2"></i>Importar <?= $toSave ?> linha<?= $toSave === 1 ? '' : 's' ?></button>
                        <a href="/admin/importacao?tipo=<?= htmlspecialchars($preview['type']) ?>" class="btn btn-light">Cancelar</a>
                    </form>
                <?php } ?>
            </div>

            <div class="card m-panel">
                <div class="m-obs-scroll">
                    <table class="m-obs-table">
                        <thead>
                            <tr><th>Linha</th><th>Situação</th><?php foreach ($headers as $h) { ?><th><?= htmlspecialchars($h) ?></th><?php } ?><th>Observação</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r) { [$class, $label] = $statusLabel[$r['status']]; ?>
                                <tr>
                                    <td data-label="Linha"><?= $r['line'] ?></td>
                                    <td data-label="Situação"><span class="m-pill <?= $class ?>"><?= $label ?></span></td>
                                    <?php foreach ($headers as $h) { ?><td data-label="<?= htmlspecialchars($h) ?>"><?= htmlspecialchars((string) ($r['summary'][$h] ?? '')) ?></td><?php } ?>
                                    <td data-label="Observação" class="m-imp-msg <?= $r['status'] === 'erro' ? 'is-low' : '' ?>"><?= htmlspecialchars(implode(' ', $r['messages'])) ?: '—' ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</section>
