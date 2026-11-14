<?php
// Auditoria: quem fez o quê e quando (App/Models/Admin/AuditLog.php)
$f = $this->view->filters;
$options = $this->view->options;
$result = $this->view->result;
$total = $this->view->total;

$query = array_filter(array_diff_key($f, ['pagina' => 1]), function ($v) { return $v !== '' && $v !== 0; });
$pageUrl = function ($page) use ($query) { return '/admin/auditoria?' . http_build_query($query + ['pagina' => $page]); };
$actionClass = ['INSERT' => 'is-new', 'UPDATE' => 'is-edit', 'DELETE' => 'is-del', 'NEGADO' => 'is-deny', 'LOGIN_NEGADO' => 'is-deny', 'EXPORTAR' => 'is-edit'];
$start = ($f['pagina'] - 1) * \App\Models\Admin\AuditLog::PER_PAGE;
?>

<section id="audit">
    <div class="row main-container">
        <div class="col-lg-11 mx-auto">

            <div class="m-page-head">
                <div>
                    <h5>Auditoria</h5>
                    <p>Quem lançou, alterou ou excluiu notas, faltas, chamadas, diários, atestados e ocorrências, quem entrou nos portais e quem baixou documentos. O registro não pode ser apagado nem alterado.</p>
                </div>
            </div>

            <form class="card m-panel m-audit-filters" method="get" action="/admin/auditoria">
                <label>De <input type="date" class="form-control" name="de" value="<?= htmlspecialchars($f['de']) ?>"></label>
                <label>Até <input type="date" class="form-control" name="ate" value="<?= htmlspecialchars($f['ate']) ?>"></label>
                <label>Quem
                    <select class="form-control" name="quem">
                        <option value="">Todos</option>
                        <?php foreach (\App\Models\Admin\AuditLog::ACTOR_TYPES as $k => $label) { ?>
                            <option value="<?= $k ?>" <?= $f['quem'] === $k ? 'selected' : '' ?>><?= $label ?></option>
                        <?php } ?>
                    </select>
                </label>
                <label>Nome de quem fez <input type="search" class="form-control" name="nome" value="<?= htmlspecialchars($f['nome']) ?>" placeholder="Ex.: Helena"></label>
                <label>O quê
                    <select class="form-control" name="oque">
                        <option value="">Tudo</option>
                        <?php foreach ($options['entities'] as $k => $label) { ?>
                            <option value="<?= htmlspecialchars($k) ?>" <?= $f['oque'] === $k ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php } ?>
                    </select>
                </label>
                <label>Ação
                    <select class="form-control" name="acao">
                        <option value="">Todas</option>
                        <?php foreach (\App\Models\Admin\AuditLog::ACTIONS as $k => $label) { ?>
                            <option value="<?= $k ?>" <?= $f['acao'] === $k ? 'selected' : '' ?>><?= $label ?></option>
                        <?php } ?>
                    </select>
                </label>
                <label>Turma
                    <select class="form-control" name="turma">
                        <option value="">Todas</option>
                        <?php foreach ($options['classes'] as $c) { ?>
                            <option value="<?= (int) $c->id ?>" <?= (int) $c->id === $f['turma'] ? 'selected' : '' ?>><?= htmlspecialchars($c->label) ?></option>
                        <?php } ?>
                    </select>
                </label>
                <?php if ($f['aluno']) { ?>
                    <div class="m-audit-student">
                        <span>Aluno</span>
                        <strong><?= htmlspecialchars($options['student'] ?? '#' . $f['aluno']) ?></strong>
                        <input type="hidden" name="aluno" value="<?= (int) $f['aluno'] ?>">
                        <a href="<?= htmlspecialchars('/admin/auditoria?' . http_build_query(array_diff_key($query, ['aluno' => 1]))) ?>">tirar</a>
                    </div>
                <?php } else { ?>
                    <label>Aluno <input type="search" class="form-control" name="alunoNome" value="<?= htmlspecialchars($this->view->studentName) ?>" placeholder="Nome (3 letras ou mais)"></label>
                <?php } ?>
                <div class="m-audit-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter mr-2"></i>Filtrar</button>
                    <a class="btn btn-light" href="/admin/auditoria">Limpar</a>
                </div>
            </form>

            <?php if ($this->view->studentChoices) { ?>
                <div class="card m-panel mt-3">
                    <p class="mb-2">Mais de um aluno com esse nome. Escolha:</p>
                    <ul class="m-audit-choices">
                        <?php foreach ($this->view->studentChoices as $s) { ?>
                            <li><a href="<?= htmlspecialchars('/admin/auditoria?' . http_build_query($query + ['aluno' => $s->id])) ?>"><?= htmlspecialchars($s->name) ?></a></li>
                        <?php } ?>
                    </ul>
                </div>
            <?php } elseif ($this->view->studentName !== '' && !$f['aluno']) { ?>
                <p class="m-muted-note mt-3">Nenhum aluno encontrado com "<?= htmlspecialchars($this->view->studentName) ?>".</p>
            <?php } ?>

            <div class="m-audit-bar">
                <span><?= $total > 10000 ? 'Mais de 10.000' : number_format($total, 0, ',', '.') ?> registro<?= $total === 1 ? '' : 's' ?> de <?= date('d/m/Y', strtotime($f['de'])) ?> a <?= date('d/m/Y', strtotime($f['ate'])) ?></span>
                <?php if ($total) { ?>
                    <a class="btn btn-light" href="<?= htmlspecialchars('/admin/auditoria/exportar?' . http_build_query($query + ($this->view->studentName !== '' && !$f['aluno'] ? ['alunoNome' => $this->view->studentName] : []))) ?>"><i class="fas fa-file-csv mr-2"></i>Exportar CSV<?= $total > \App\Models\Admin\AuditLog::EXPORT_LIMIT ? ' (primeiros ' . number_format(\App\Models\Admin\AuditLog::EXPORT_LIMIT, 0, ',', '.') . ')' : '' ?></a>
                    <button type="button" class="btn btn-light" onclick="window.print()"><i class="fas fa-print mr-2"></i>Imprimir / PDF</button>
                <?php } ?>
            </div>

            <div class="card m-panel m-audit-wrap">
                <?php if (!$result['rows']) { ?>
                    <div class="m-empty">Nenhum registro com esses filtros.</div>
                <?php } else { ?>
                    <table class="m-audit">
                        <thead>
                            <tr><th>Quando</th><th>Quem</th><th>Ação</th><th>O quê</th><th>Detalhe</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($result['rows'] as $r) { ?>
                                <tr>
                                    <td data-label="Quando"><?= htmlspecialchars($r->quando) ?></td>
                                    <td data-label="Quem"><strong><?= htmlspecialchars($r->who) ?></strong><?php if ($r->who !== $r->who_type) { ?><small><?= htmlspecialchars($r->who_type) ?></small><?php } ?></td>
                                    <td data-label="Ação"><span class="m-audit-action <?= $actionClass[$r->acao] ?? '' ?>"><?= htmlspecialchars($r->action) ?></span></td>
                                    <td data-label="O quê"><?= htmlspecialchars($r->what) ?></td>
                                    <td data-label="Detalhe"><div>
                                        <?= htmlspecialchars($r->summary) ?>
                                        <?php if ($r->changes) { ?>
                                            <ul class="m-audit-changes">
                                                <?php foreach ($r->changes as [$label, $old, $new]) { ?>
                                                    <li><span><?= htmlspecialchars($label) ?>:</span> <del><?= htmlspecialchars($old) ?></del> → <ins><?= htmlspecialchars($new) ?></ins></li>
                                                <?php } ?>
                                            </ul>
                                        <?php } ?>
                                    </div></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } ?>
            </div>

            <?php if ($f['pagina'] > 1 || $result['more']) { ?>
                <nav class="m-audit-pages" aria-label="Páginas">
                    <?php if ($f['pagina'] > 1) { ?><a class="btn btn-light" href="<?= htmlspecialchars($pageUrl($f['pagina'] - 1)) ?>"><i class="fas fa-chevron-left mr-2"></i>Mais recentes</a><?php } ?>
                    <span>Registros <?= $start + 1 ?>–<?= $start + count($result['rows']) ?></span>
                    <?php if ($result['more']) { ?><a class="btn btn-light" href="<?= htmlspecialchars($pageUrl($f['pagina'] + 1)) ?>">Mais antigos<i class="fas fa-chevron-right ml-2"></i></a><?php } ?>
                </nav>
            <?php } ?>

        </div>
    </div>
</section>
