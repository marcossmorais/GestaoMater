<?php
// Fund. 1: substituições do titular da turma (perfil da turma e /admin/gestao/turma/perfil-turma/substituicao/lista)
$statusLabel = ['ativa' => ['badge-success', 'Em andamento'], 'agendada' => ['badge-info', 'Agendada'], 'encerrada' => ['badge-secondary', 'Encerrada']];
?>
<?php if (!$this->view->substitutions) { ?>
    <p class="text-muted mb-0">Nenhuma substituição agendada ou recente.</p>
<?php } else { ?>
    <ul class="list-unstyled mb-0">
        <?php foreach ($this->view->substitutions as $s) { $badge = $statusLabel[$s->status]; ?>
            <li class="d-flex flex-wrap align-items-center py-2 border-bottom" style="gap:.5rem">
                <span class="badge <?= $badge[0] ?>"><?= $badge[1] ?></span>
                <span style="flex:1 1 200px">
                    <strong><?= htmlspecialchars($s->teacher) ?></strong> · <?= htmlspecialchars($s->start_date) ?> a <?= htmlspecialchars($s->end_date) ?>
                    <?php if ($s->reason) { ?><br><small class="text-muted"><?= htmlspecialchars($s->reason) ?></small><?php } ?>
                </span>
                <?php if ($s->status !== 'encerrada') { ?>
                    <button type="button" class="btn btn-outline-danger btn-sm" style="min-height:44px" data-substitution-delete="<?= (int) $s->id ?>">Remover</button>
                <?php } ?>
            </li>
        <?php } ?>
    </ul>
<?php } ?>
