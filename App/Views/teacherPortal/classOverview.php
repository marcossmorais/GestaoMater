<?php
require __DIR__ . '/../../config/variables.php';

$overview = $this->view->overview;
$editable = $this->view->editable;
$unity = $this->view->unity;
$rule = $this->view->rule;
$fmt = function ($n) { return str_replace('.', ',', (string) round((float) $n, 1)); };
$tab = $this->view->tab;
$roster = $this->view->roster;
$quickNotes = $this->view->quickNotes;
$quickDisciplines = $this->view->quickDisciplines;
$link = function ($u, $t) { return '?id=' . (int) $this->view->classId . '&unidade=' . (int) $u . '&ver=' . $t; };
?>

<section id="classOverview" class="m-att">

    <div class="row main-container">

        <div class="col-lg-11 mx-auto">

            <div class="m-att-head">
                <a href="/portal-docente/turmas" class="m-icon-btn" aria-label="Voltar"><i class="fas fa-arrow-left"></i></a>
                <div class="m-att-head-title">
                    <h5><?= htmlspecialchars($this->view->classLabel) ?></h5>
                    <p><?= count($roster) ?> alunos · toque no aluno para abrir o perfil</p>
                </div>
            </div>

            <div class="card m-att-toolbar">
                <div class="m-att-field">
                    <span class="m-att-label">Ver</span>
                    <div class="m-seg">
                        <a href="<?= $link($unity, 'alunos') ?>" role="button" class="btn <?= $tab === 'alunos' ? 'is-active' : '' ?>"><i class="fas fa-users mr-2"></i>Alunos</a>
                        <a href="<?= $link($unity, 'notas') ?>" role="button" class="btn <?= $tab === 'notas' ? 'is-active' : '' ?>"><i class="fas fa-table mr-2"></i>Notas da turma</a>
                    </div>
                </div>
                <div class="m-att-field">
                    <span class="m-att-label">Bimestre</span>
                    <div class="m-seg">
                        <?php for ($u = 1; $u <= $rule['units']; $u++) { ?>
                            <a href="<?= $link($u, $tab) ?>" role="button" class="btn <?= $u === $unity ? 'is-active' : '' ?>" style="min-width:44px"><?= $u ?>º</a>
                        <?php } ?>
                    </div>
                </div>
                <?php if ($tab === 'alunos') { ?>
                    <div class="m-att-field m-att-field-grow">
                        <span class="m-att-label">Buscar aluno</span>
                        <div class="m-att-search">
                            <i class="fas fa-search"></i>
                            <input type="search" data-roster-search placeholder="Digite um nome" autocomplete="off">
                        </div>
                    </div>
                <?php } else { ?>
                    <a class="btn btn-light" href="/portal-docente/notas"><i class="fas fa-pen mr-2"></i>Lançar notas</a>
                <?php } ?>
            </div>

            <?php if ($tab === 'alunos') { ?>

                <?php if (!$roster) { ?>
                    <div class="card m-panel m-empty">Nenhum aluno ativo nesta turma.</div>
                <?php } else { ?>
                    <div class="m-att-grid m-roster" data-roster>
                        <?php foreach ($roster as $index => $s) {
                            $words = preg_split('/\s+/', trim($s->name));
                            $initials = mb_strtoupper(mb_substr($words[0], 0, 1) . (count($words) > 1 ? mb_substr(end($words), 0, 1) : ''));
                            $ring = min((int) $s->absences, 20) / 20 * 100;
                        ?>
                            <article class="m-att-student m-roster-card" data-name="<?= htmlspecialchars($s->name) ?>">
                                <a class="m-roster-link" href="/portal-docente/aluno?id=<?= (int) $s->id ?>" aria-label="Abrir o perfil de <?= htmlspecialchars($s->name) ?>">
                                    <span class="m-att-num"><?= $index + 1 ?></span>
                                    <span class="m-att-avatar"><?php if ($s->photo) { ?><img src="<?= $app_url ?>/assets/img/studentProfilePhotos/<?= htmlspecialchars($s->photo) ?>" alt="" loading="lazy"><?php } else { ?><span style="--h:<?= crc32($s->name) % 360 ?>"><?= htmlspecialchars($initials) ?></span><?php } ?></span>
                                    <span class="m-att-name"><?= htmlspecialchars($s->name) ?></span>
                                    <span class="m-att-ring <?= $s->absences >= 10 ? 'is-high' : ($s->absences >= 5 ? 'is-mid' : '') ?>" style="--p:<?= $ring ?>"><strong><?= (int) $s->absences ?></strong></span>
                                    <small class="m-att-ring-label">faltas no bimestre</small>
                                </a>
                                <?php if ($quickDisciplines) { ?>
                                    <button type="button" class="btn btn-light m-roster-note" data-note-student="<?= (int) $s->id ?>" data-note-name="<?= htmlspecialchars($s->name) ?>"><i class="fas fa-comment-medical mr-2"></i>Observação</button>
                                <?php } ?>
                            </article>
                        <?php } ?>
                    </div>
                <?php } ?>

            <?php } else { ?>

            <p class="m-muted-note">Nota do bimestre (média das <?= (int) $rule['perBimester'] ?> avaliações; abaixo de <?= $fmt($rule['passing']) ?>, vale a maior entre o bimestre e a recuperação). Você edita só as disciplinas com <i class="fas fa-pen fa-xs"></i>.</p>

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
                                    <td><a href="/portal-docente/aluno?id=<?= (int) $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></a></td>
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

            <?php } ?>

        </div>
    </div>

    <?php if ($tab === 'alunos' && $quickDisciplines) { ?>
        <div class="m-sheet" data-note-sheet hidden>
            <div class="card m-panel m-sheet-box" role="dialog" aria-modal="true" aria-labelledby="noteSheetTitle">
                <div class="m-panel-head">
                    <h6 id="noteSheetTitle" data-note-name></h6>
                    <button type="button" class="m-icon-btn" data-note-close aria-label="Fechar"><i class="fas fa-times"></i></button>
                </div>
                <?php require __DIR__ . '/components/quickNote.php'; ?>
            </div>
        </div>
    <?php } ?>

</section>

<script src="<?= asset('/assets/js/quickNote.js') ?>" defer></script>
<script src="<?= asset('/assets/js/classRoster.js') ?>" defer></script>
