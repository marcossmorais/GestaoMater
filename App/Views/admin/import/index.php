<?php
use App\Models\Admin\Importer;

$tab = $this->view->tab;
$type = $this->view->type;
$period = $this->view->activePeriod;
$columns = Importer::columns($type);
?>

<section id="import">
    <div class="row main-container">
        <div class="col-lg-11 mx-auto">

            <div class="m-page-head">
                <div>
                    <h5>Importação</h5>
                    <p>Carga de alunos, professores e turmas por planilha, com prévia antes de gravar, e virada de ano.</p>
                </div>
            </div>

            <nav class="m-obs-tabs" aria-label="Seções">
                <a href="/admin/importacao" class="<?= $tab === 'arquivo' ? 'is-active' : '' ?>" <?= $tab === 'arquivo' ? 'aria-current="page"' : '' ?>><i class="fas fa-file-csv"></i>Importar arquivo</a>
                <a href="/admin/importacao?aba=virada" class="<?= $tab === 'virada' ? 'is-active' : '' ?>" <?= $tab === 'virada' ? 'aria-current="page"' : '' ?>><i class="fas fa-redo"></i>Virada de ano</a>
            </nav>

            <?php if ($this->view->error) { ?>
                <div class="m-imp-alert is-error" role="alert"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($this->view->error) ?></div>
            <?php } ?>

            <?php if ($tab === 'virada') { require __DIR__ . '/components/rollover.php'; } else { ?>

                <?php if (!$period) { ?>
                    <div class="m-imp-alert is-error"><i class="fas fa-exclamation-circle"></i>Não há período letivo ativo. Ative um em <a href="/admin/gestao/periodo-letivo">Gestão &gt; Período letivo</a> para importar.</div>
                <?php } ?>

                <div class="m-imp-grid">
                    <form class="card m-panel m-imp-upload" method="post" action="/admin/importacao/previa" enctype="multipart/form-data">
                        <div class="m-panel-head"><h6>1. Escolha o que importar</h6></div>

                        <div class="m-imp-types" role="radiogroup" aria-label="Tipo de importação">
                            <?php foreach (Importer::TYPES as $key => $label) { ?>
                                <a href="/admin/importacao?tipo=<?= $key ?>" role="radio" aria-checked="<?= $key === $type ? 'true' : 'false' ?>" class="<?= $key === $type ? 'is-active' : '' ?>"><?= $label ?></a>
                            <?php } ?>
                        </div>
                        <input type="hidden" name="tipo" value="<?= htmlspecialchars($type) ?>">

                        <div class="m-panel-head mt-3"><h6>2. Envie a planilha</h6></div>
                        <p class="m-obs-note">CSV (separado por ponto e vírgula ou vírgula, como o Excel salva) ou JSON, até 2 MB. A primeira linha tem os nomes das colunas. Nada é gravado antes da prévia.</p>

                        <label class="m-imp-file">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span data-file-name>Selecionar arquivo .csv ou .json</span>
                            <input type="file" name="arquivo" accept=".csv,.json,.txt,text/csv,application/json" required
                                   onchange="this.previousElementSibling.textContent = this.files[0] ? this.files[0].name : 'Selecionar arquivo .csv ou .json'">
                        </label>

                        <div class="m-imp-actions">
                            <button type="submit" class="btn btn-primary" <?= $period ? '' : 'disabled' ?>><i class="fas fa-search mr-2"></i>Ver prévia</button>
                            <a href="/admin/importacao/modelo?tipo=<?= htmlspecialchars($type) ?>" class="btn btn-light"><i class="fas fa-download mr-2"></i>Planilha-modelo</a>
                        </div>
                    </form>

                    <div class="card m-panel">
                        <div class="m-panel-head"><h6>Colunas de <?= mb_strtolower(Importer::TYPES[$type]) ?></h6></div>
                        <p class="m-imp-subhead">Obrigatórias</p>
                        <ul class="m-imp-columns">
                            <?php foreach ($columns as $key => [$label, $required]) { if (!$required) continue; ?>
                                <li><code><?= $key ?></code><span><?= htmlspecialchars($label) ?></span></li>
                            <?php } ?>
                        </ul>
                        <?php $optional = array_filter($columns, function ($c) { return !$c[1]; }); if ($optional) { ?>
                            <p class="m-imp-subhead">Opcionais</p>
                            <div class="m-imp-chips">
                                <?php foreach ($optional as $key => [$label]) { ?><span title="<?= htmlspecialchars($label) ?>"><code><?= $key ?></code></span><?php } ?>
                            </div>
                        <?php } ?>

                        <?php if ($type === 'alunos') { ?>
                            <p class="m-obs-note mt-3">
                                Aluno com CPF já cadastrado não é duplicado: só ganha a matrícula na turma, se ainda não tiver uma no período<?= $period ? ' ' . htmlspecialchars($period['year']) : '' ?>.
                                Turmas aceitas:
                            </p>
                            <div class="m-imp-chips">
                                <?php foreach ($this->view->classes as $c) { ?><span><?= htmlspecialchars(str_replace(' · ', ' ', $c['label'])) ?></span><?php } ?>
                                <?php if (!$this->view->classes) { ?><span>Nenhuma turma no período ativo</span><?php } ?>
                            </div>
                        <?php } elseif ($type === 'professores') { ?>
                            <p class="m-obs-note mt-3">Professor com CPF já cadastrado é ignorado. O vínculo com turmas e disciplinas continua em Gestão &gt; Turmas.</p>
                        <?php } else { ?>
                            <p class="m-obs-note mt-3">As turmas entram no período ativo. Turma igual (mesma série, letra, curso e turno) é ignorada. A sala precisa estar cadastrada em Gestão &gt; Salas.</p>
                        <?php } ?>
                    </div>
                </div>

            <?php } ?>

        </div>
    </div>
</section>
