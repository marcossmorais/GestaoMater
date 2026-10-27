<?php
require __DIR__ . '/../../config/variables.php';

$overview = $this->view->overview;
$editable = $this->view->editable;
$unity = $this->view->unity;
?>

<section id="classOverview" class="m-att">

    <div class="row main-container">

        <div class="col-lg-11 mx-auto">

            <div class="m-att-head">
                <a href="/portal-docente/turmas" class="m-icon-btn" aria-label="Voltar"><i class="fas fa-arrow-left"></i></a>
                <div class="m-att-head-title">
                    <h5><?= htmlspecialchars($this->view->classLabel) ?></h5>
                    <p>Desempenho geral da turma · pontos na unidade (você edita só as disciplinas destacadas)</p>
                </div>
            </div>

            <div class="card m-att-toolbar">
                <div class="m-att-field">
                    <span class="m-att-label">Unidade</span>
                    <div class="m-seg">
                        <?php for ($u = 1; $u <= 3; $u++) { ?>
                            <a href="?id=<?= (int) $this->view->classId ?>&unidade=<?= $u ?>" role="button" class="btn <?= $u === $unity ? 'is-active' : '' ?>" style="min-width:44px"><?= $u ?>ª</a>
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
                                        <small>de <?= (float) $d->distributed ?></small>
                                    </th>
                                <?php } ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($overview['students'] as $s) { ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['name']) ?></td>
                                    <?php foreach ($overview['disciplines'] as $d) {
                                        $points = $s['points'][(int) $d->id] ?? null;
                                        $low = $points !== null && (float) $d->distributed > 0 && $points < 0.6 * (float) $d->distributed; ?>
                                        <td class="<?= $low ? 'is-low' : '' ?>"><?= $points === null ? '–' : str_replace('.', ',', (string) $points) ?></td>
                                    <?php } ?>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } ?>
            </div>

        </div>
    </div>

</section>
