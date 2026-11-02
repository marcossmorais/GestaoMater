<?php
/**
 * Aba "Virada de ano": escolhe origem e destino, mostra o plano e confirma
 */
$periods = $this->view->periods;
$plan = $this->view->plan;
$done = $this->view->done;
$yearOf = [];
foreach ($periods as $p) $yearOf[(int) $p->id] = $p->year . ' · ' . mb_strtolower($p->situation);
?>

<?php if ($done) { ?>
    <div class="m-imp-alert is-ok" role="status">
        <i class="fas fa-check-circle"></i>
        <span>Virada concluída: <strong><?= (int) $done['classes'] ?></strong> turma(s) criada(s) com <strong><?= (int) $done['disciplines'] ?></strong> disciplina(s),
        <strong><?= (int) $done['enrollments'] ?></strong> matrícula(s) (<?= (int) $done['promovidos'] ?> promovidos, <?= (int) $done['retidos'] ?> retidos) e
        <strong><?= (int) $done['concluintes'] ?></strong> concluinte(s). Registrada na auditoria.</span>
    </div>
<?php } ?>

<div class="card m-panel">
    <div class="m-panel-head"><h6>Do período ao novo ano</h6></div>
    <p class="m-obs-note">Copia as turmas com suas disciplinas e professores para o novo período e rematricula: aprovado sobe de série, reprovado repete, quem está na última série conclui. Alunos que já têm matrícula no destino não são mexidos. O novo período precisa estar criado em <a href="/admin/gestao/periodo-letivo">Gestão &gt; Período letivo</a>.</p>

    <form class="m-imp-rollover" method="get" action="/admin/importacao">
        <input type="hidden" name="aba" value="virada">
        <input type="hidden" name="previa" value="1">
        <label><span>Origem</span>
            <select name="origem" class="form-control">
                <?php foreach ($periods as $p) { ?><option value="<?= (int) $p->id ?>" <?= (int) $p->id === $this->view->origin ? 'selected' : '' ?>><?= htmlspecialchars($yearOf[(int) $p->id]) ?></option><?php } ?>
            </select>
        </label>
        <label><span>Destino</span>
            <select name="destino" class="form-control">
                <?php foreach ($periods as $p) { ?><option value="<?= (int) $p->id ?>" <?= (int) $p->id === $this->view->target ? 'selected' : '' ?>><?= htmlspecialchars($yearOf[(int) $p->id]) ?></option><?php } ?>
            </select>
        </label>
        <label><span>Aluno sem resultado final</span>
            <select name="sem_resultado" class="form-control">
                <?php foreach ($this->view->pendingOptions as $key => $label) { ?><option value="<?= $key ?>" <?= $key === $this->view->pending ? 'selected' : '' ?>><?= $label ?></option><?php } ?>
            </select>
        </label>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-2"></i>Ver prévia</button>
    </form>

    <?php if (count($periods) < 2) { ?>
        <p class="m-imp-alert mt-3"><i class="fas fa-info-circle"></i>Só existe um período letivo. Crie o próximo em Gestão &gt; Período letivo.</p>
    <?php } elseif ($this->view->origin === $this->view->target) { ?>
        <p class="m-imp-alert is-error mt-3"><i class="fas fa-exclamation-circle"></i>Origem e destino precisam ser períodos diferentes.</p>
    <?php } ?>
</div>

<?php if ($plan) {
    $c = $plan['counts'];
    $nothing = !$plan['toCreate'] && !$plan['moves'] && !$plan['graduates'];
?>
    <div class="m-kpis m-obs-kpis">
        <div class="card m-kpi"><div class="m-kpi-icon"><i class="fas fa-users"></i></div><div><div class="m-kpi-label">Turmas a criar</div><div class="m-kpi-value"><?= count($plan['toCreate']) ?></div></div></div>
        <div class="card m-kpi"><div class="m-kpi-icon"><i class="fas fa-book"></i></div><div><div class="m-kpi-label">Disciplinas copiadas</div><div class="m-kpi-value"><?= $plan['disciplines'] ?></div></div></div>
        <div class="card m-kpi"><div class="m-kpi-icon"><i class="fas fa-level-up-alt"></i></div><div><div class="m-kpi-label">Promovidos</div><div class="m-kpi-value"><?= $c['promovidos'] ?></div></div></div>
        <div class="card m-kpi"><div class="m-kpi-icon"><i class="fas fa-redo"></i></div><div><div class="m-kpi-label">Retidos</div><div class="m-kpi-value"><?= $c['retidos'] ?></div></div></div>
        <div class="card m-kpi"><div class="m-kpi-icon"><i class="fas fa-graduation-cap"></i></div><div><div class="m-kpi-label">Concluintes</div><div class="m-kpi-value"><?= $c['concluintes'] ?></div></div></div>
    </div>

    <?php if ($c['sem_resultado'] || $c['ja_matriculados']) { ?>
        <div class="m-imp-alert"><i class="fas fa-info-circle"></i>
            <span><?= $c['sem_resultado'] ? '<strong>' . $c['sem_resultado'] . '</strong> aluno(s) ainda sem resultado final (situação "Matriculado") não serão rematriculados. Lance aprovado/reprovado ou mude a opção acima. ' : '' ?>
            <?= $c['ja_matriculados'] ? '<strong>' . $c['ja_matriculados'] . '</strong> aluno(s) já matriculados no destino ficam como estão.' : '' ?></span>
        </div>
    <?php } ?>

    <div class="m-imp-grid">
        <div class="card m-panel">
            <div class="m-panel-head"><h6>Turmas no destino</h6></div>
            <?php if (!$plan['destinations']) { ?>
                <p class="m-obs-note">Nenhum aluno será rematriculado.</p>
            <?php } else { ?>
                <div class="m-obs-scroll">
                    <table class="m-obs-table">
                        <thead><tr><th>Turma</th><th>Alunos</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($plan['destinations'] as $d) { ?>
                                <tr>
                                    <td data-label="Turma"><?= htmlspecialchars($d['label']) ?></td>
                                    <td data-label="Alunos"><?= $d['total'] ?></td>
                                    <td data-label="Situação"><?= $d['new'] ? '<span class="m-pill m-pill-info">nova</span>' : '<span class="m-pill m-pill-ok">já existe</span>' ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } ?>
        </div>

        <div class="card m-panel">
            <div class="m-panel-head"><h6>Turmas que serão criadas</h6></div>
            <?php if (!$plan['toCreate']) { ?>
                <p class="m-obs-note">Todas as turmas já existem no destino.</p>
            <?php } else { ?>
                <div class="m-imp-chips">
                    <?php foreach ($plan['toCreate'] as $t) { ?><span title="<?= $t['reason'] === 'cópia' ? 'Cópia com disciplinas e professores' : 'Série seguinte, sem disciplinas' ?>"><?= htmlspecialchars($t['label']) ?><?= $t['reason'] === 'cópia' ? '' : ' *' ?></span><?php } ?>
                </div>
                <?php if (array_filter($plan['toCreate'], function ($t) { return $t['reason'] !== 'cópia'; })) { ?>
                    <p class="m-obs-note mt-2">* Turma nova para receber promovidos, sem disciplinas: vincule em Gestão &gt; Turmas.</p>
                <?php } ?>
            <?php } ?>
        </div>
    </div>

    <div class="card m-panel m-imp-confirm">
        <?php if ($nothing) { ?>
            <p><strong>Nada a fazer</strong> com essas opções.</p>
        <?php } else { ?>
            <p>Tudo será gravado de uma vez no período <strong><?= htmlspecialchars($yearOf[$this->view->target]) ?></strong>. Se algo falhar, nada fica gravado. As matrículas do período de origem não são alteradas.</p>
            <form method="post" action="/admin/importacao/virada/confirmar"
                  onsubmit="if (!confirm('Confirmar a virada de ano? Esta ação cria turmas e matrículas no novo período.')) return false; this.querySelector('button').disabled = true">
                <input type="hidden" name="origem" value="<?= $this->view->origin ?>">
                <input type="hidden" name="destino" value="<?= $this->view->target ?>">
                <input type="hidden" name="sem_resultado" value="<?= htmlspecialchars($this->view->pending) ?>">
                <input type="hidden" name="hash" value="<?= htmlspecialchars($plan['hash']) ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-check mr-2"></i>Confirmar virada de ano</button>
            </form>
        <?php } ?>
    </div>
<?php } ?>
