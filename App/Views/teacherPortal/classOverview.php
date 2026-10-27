<?php
require __DIR__ . '/../../config/variables.php';

$overview = $this->view->overview;
$editable = $this->view->editable;
$unity = $this->view->unity;
$rule = $this->view->rule;
$fmt = function ($n) { return str_replace('.', ',', (string) round((float) $n, 1)); };
?>

<section id="classOverview" class="m-att">

    <div class="row main-container">

        <div class="col-lg-11 mx-auto">

            <div class="m-att-head">
                <a href="/portal-docente/turmas" class="m-icon-btn" aria-label="Voltar"><i class="fas fa-arrow-left"></i></a>
                <div class="m-att-head-title">
                    <h5><?= htmlspecialchars($this->view->classLabel) ?></h5>
                    <p>Nota do bimestre (média das <?= (int) $rule['perBimester'] ?> avaliações; abaixo de <?= $fmt($rule['passing']) ?>, vale a recuperação). Você edita só as disciplinas com <i class="fas fa-pen fa-xs"></i>.</p>
                </div>
            </div>

            <div class="card m-att-toolbar">
                <div class="m-att-field">
                    <span class="m-att-label">Bimestre</span>
                    <div class="m-seg">
                        <?php for ($u = 1; $u <= $rule['units']; $u++) { ?>
                            <a href="?id=<?= (int) $this->view->classId ?>&unidade=<?= $u ?>" role="button" class="btn <?= $u === $unity ? 'is-active' : '' ?>" style="min-width:44px"><?= $u ?>º</a>
                        <?php } ?>
                    </div>
                </div>
                <a class="btn btn-light" href="/portal-docente/notas"><i class="fas fa-pen mr-2"></i>Lançar notas</a>
            </div>

            <div class="card m-panel m-overview-wrap">
                <?php if (!$overview['students']) { ?>
                    <div class="m-empty">Nenhum aluno ativo nesta turma.</div>
                <?php } else { ?>
                    <table class="m-overview">
                        <thead>
                            <tr>
                                <th>Aluno</th>
                                <?php foreach ($overview['disciplines'] as $d) { $mine = in_array((int) $d->id, $editable, true); ?>
                                    <th class="<?= $mine ? 'is-mine' : '' ?>" title="<?= htmlspecialchars($d->name . ' · ' . $d->teacher) ?>">
                                        <?= htmlspecialchars($d->acronym ?: $d->name) ?><?= $mine ? ' <i class="fas fa-pen fa-xs"></i>' : '' ?>
                                    </th>
                                <?php } ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($overview['students'] as $s) { ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['name']) ?></td>
                                    <?php foreach ($overview['disciplines'] as $d) {
                                        $g = $s['grades'][(int) $d->id] ?? null;
                                        if (!$g || $g['grade'] === null) { echo '<td>–</td>'; continue; }
                                        $class = $g['recovered'] ? 'is-rec' : ($g['grade'] < $rule['passing'] ? 'is-low' : '');
                                        $title = $g['recovered'] ? 'Recuperação (média era ' . $fmt($g['average']) . ')' : ($g['needsRecovery'] ? 'Abaixo da média: aguarda recuperação' : ($g['closed'] ? 'Bimestre fechado' : 'Parcial'));
                                        ?>
                                        <td class="<?= $class ?>" title="<?= htmlspecialchars($title) ?>"><?= $fmt($g['grade']) ?><?= $g['recovered'] ? '<sup>R</sup>' : (!$g['closed'] ? '<sup>*</sup>' : '') ?></td>
                                    <?php } ?>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <p class="m-empty text-left mb-0 mt-2"><sup>R</sup> nota da recuperação · <sup>*</sup> parcial (faltam avaliações) · em vermelho: abaixo de <?= $fmt($rule['passing']) ?></p>
                <?php } ?>
            </div>

        </div>
    </div>

</section>
