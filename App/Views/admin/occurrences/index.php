<?php
// Ocorrências: lista geral com o painel de ciência pendente (#23)
use App\Models\TeacherStudent\Occurrence;

$f = $this->view->filters;
$result = $this->view->result;
$summary = $this->view->summary;
$total = $result['total'];

$query = array_filter(array_diff_key($f, ['pagina' => 1]), function ($v) { return $v !== '' && $v !== 0; });
// situação vazia (todas) precisa ir na URL, senão volta para "pendente"
if ($f['situacao'] === '') $query['situacao'] = '';
$pageUrl = function ($page) use ($query) { return '/admin/ocorrencias?' . http_build_query($query + ['pagina' => $page]); };
$start = ($f['pagina'] - 1) * Occurrence::PER_PAGE;
$situations = ['pendente' => 'Aguardando ciência', 'ciente' => 'Família ciente', 'sem-ciencia' => 'Não exige ciência', '' => 'Todas'];
$categoryClass = ['Disciplinar' => 'is-del', 'Elogio' => 'is-new', 'Pedagógica' => 'is-edit', 'Saúde' => 'is-deny'];
?>

<section id="adminOccurrences">
    <div class="row main-container">
        <div class="col-lg-11 mx-auto">

            <div class="m-page-head">
                <div>
                    <h5>Ocorrências</h5>
                    <p>Todas as ocorrências registradas, com a ciência da família. O registro continua no perfil do aluno (Aluno → Lista → perfil → Ocorrências). A família vê e confirma pelo portal do aluno, onde as pendentes aparecem em destaque no topo do mural.</p>
                </div>
            </div>

            <div class="m-occ-summary">
                <a class="card m-occ-stat <?= $summary->total ? 'is-warn' : '' ?>" href="/admin/ocorrencias?situacao=pendente&amp;de=2000-01-01">
                    <strong><?= (int) $summary->total ?></strong>
                    <span>aguardando ciência da família</span>
                </a>
                <div class="card m-occ-stat <?= $summary->late ? 'is-late' : '' ?>">
                    <strong><?= (int) $summary->late ?></strong>
                    <span>há mais de 7 dias</span>
                </div>
                <div class="card m-occ-stat">
                    <strong><?= $summary->total ? (int) $summary->oldest_days . ' dia' . ((int) $summary->oldest_days === 1 ? '' : 's') : '—' ?></strong>
                    <span>a mais antiga esperando</span>
                </div>
            </div>

            <form class="card m-panel m-audit-filters" method="get" action="/admin/ocorrencias">
                <label>Situação
                    <select class="form-control" name="situacao">
                        <?php foreach ($situations as $k => $label) { ?>
                            <option value="<?= $k ?>" <?= $f['situacao'] === $k ? 'selected' : '' ?>><?= $label ?></option>
                        <?php } ?>
                    </select>
                </label>
                <label>De <input type="date" class="form-control" name="de" value="<?= htmlspecialchars($f['de']) ?>"></label>
                <label>Até <input type="date" class="form-control" name="ate" value="<?= htmlspecialchars($f['ate']) ?>"></label>
                <label>Categoria
                    <select class="form-control" name="categoria">
                        <option value="">Todas</option>
                        <?php foreach (Occurrence::CATEGORIES as $c) { ?><option <?= $f['categoria'] === $c ? 'selected' : '' ?>><?= $c ?></option><?php } ?>
                    </select>
                </label>
                <label>Gravidade
                    <select class="form-control" name="gravidade">
                        <option value="">Todas</option>
                        <?php foreach (Occurrence::SEVERITIES as $s) { ?><option <?= $f['gravidade'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php } ?>
                    </select>
                </label>
                <label>Turma
                    <select class="form-control" name="turma">
                        <option value="">Todas</option>
                        <?php foreach ($this->view->classes as $c) { ?>
                            <option value="<?= (int) $c->id ?>" <?= (int) $c->id === $f['turma'] ? 'selected' : '' ?>><?= htmlspecialchars($c->label) ?></option>
                        <?php } ?>
                    </select>
                </label>
                <label>Aluno <input type="search" class="form-control" name="aluno" value="<?= htmlspecialchars($f['aluno']) ?>" placeholder="Nome (2 letras ou mais)"></label>
                <div class="m-audit-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter mr-2"></i>Filtrar</button>
                    <a class="btn btn-light" href="/admin/ocorrencias">Limpar</a>
                </div>
            </form>

            <div class="m-audit-bar">
                <span><?= number_format($total, 0, ',', '.') ?> ocorrência<?= $total === 1 ? '' : 's' ?> · <?= htmlspecialchars($situations[$f['situacao']]) ?> · de <?= date('d/m/Y', strtotime($f['de'])) ?> a <?= date('d/m/Y', strtotime($f['ate'])) ?></span>
            </div>

            <div class="card m-panel m-audit-wrap">
                <?php if (!$result['rows']) { ?>
                    <div class="m-empty"><?= $f['situacao'] === 'pendente' ? 'Nenhuma ocorrência aguardando ciência com esses filtros.' : 'Nenhuma ocorrência com esses filtros.' ?></div>
                <?php } else { ?>
                    <table class="m-audit">
                        <thead>
                            <tr><th>Data</th><th>Aluno</th><th>Ocorrência</th><th>Ciência da família</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($result['rows'] as $r) { $days = (int) $r->waiting_days; ?>
                                <tr>
                                    <td data-label="Data"><?= htmlspecialchars($r->date) ?></td>
                                    <td data-label="Aluno"><div><strong><?= htmlspecialchars($r->student) ?></strong><small><?= htmlspecialchars($r->class_label ?: 'sem turma') ?></small></div></td>
                                    <td data-label="Ocorrência"><div>
                                        <span class="m-audit-action <?= $categoryClass[$r->category] ?? '' ?>"><?= htmlspecialchars($r->category) ?></span>
                                        <span class="m-occ-severity"><?= htmlspecialchars($r->severity) ?></span>
                                        <strong class="d-block mt-1"><?= htmlspecialchars($r->title) ?></strong>
                                        <small>Registrado por <?= htmlspecialchars($r->author) ?></small>
                                    </div></td>
                                    <td data-label="Ciência"><div>
                                        <?php if (!$r->requires_ack) { ?>
                                            <span class="m-occ-ack">Não exige</span>
                                        <?php } elseif ($r->acknowledged) { ?>
                                            <span class="m-occ-ack is-done"><i class="fas fa-check-circle mr-1"></i><?= $r->acknowledged_at ? 'Ciente em ' . htmlspecialchars($r->acknowledged_at) : 'Ciente' ?></span>
                                        <?php } else { ?>
                                            <span class="m-occ-ack <?= $days > 7 ? 'is-late' : 'is-wait' ?>"><i class="fas fa-hourglass-half mr-1"></i><?= $days === 0 ? 'Aguardando desde hoje' : 'Aguardando há ' . $days . ' dia' . ($days === 1 ? '' : 's') ?></span>
                                        <?php } ?>
                                        <?php if (!$r->visible) { ?><small>Oculta para a família</small><?php } ?>
                                    </div></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } ?>
            </div>

            <?php if ($f['pagina'] > 1 || $result['more']) { ?>
                <nav class="m-audit-pages" aria-label="Páginas">
                    <?php if ($f['pagina'] > 1) { ?><a class="btn btn-light" href="<?= htmlspecialchars($pageUrl($f['pagina'] - 1)) ?>"><i class="fas fa-chevron-left mr-2"></i>Anteriores</a><?php } ?>
                    <span>Ocorrências <?= $start + 1 ?>–<?= $start + count($result['rows']) ?></span>
                    <?php if ($result['more']) { ?><a class="btn btn-light" href="<?= htmlspecialchars($pageUrl($f['pagina'] + 1)) ?>">Próximas<i class="fas fa-chevron-right ml-2"></i></a><?php } ?>
                </nav>
            <?php } ?>

        </div>
    </div>
</section>
