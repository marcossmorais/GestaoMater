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
$tabs = ['chamada' => ['fa-clipboard-check', 'Chamada'], 'alunos' => ['fa-users', 'Alunos'], 'notas' => ['fa-table', 'Notas da turma']];
// Fund. 1: chamada só para o titular (diária); especialista não vê a aba
if (!$this->view->canTakeAttendance) unset($tabs['chamada']);
$isDaily = $this->view->isDaily;
$daily = $this->view->daily;
// Fund. 1: o titular registra um diário único por dia, ao lado da chamada
if ($daily) $tabs = array_slice($tabs, 0, 1, true) + ['diario' => ['fa-book-open', 'Diário do dia']] + array_slice($tabs, 1, null, true);
$plainTab = in_array($tab, ['chamada', 'diario'], true); // sem bimestre nem busca de aluno
$ownDisciplines = $this->view->ownDisciplines;
$showDisciplines = $tab === 'chamada' && !$isDaily && count($ownDisciplines) > 1;
$absenceLabel = $isDaily ? 'dias de falta no bimestre' : 'faltas no bimestre';
?>

<section id="classOverview" class="m-att">

    <div class="row main-container">

        <div class="col-lg-11 mx-auto">

            <div class="m-att-head">
                <div class="m-att-head-title">
                    <h5><?= htmlspecialchars($this->view->classLabel) ?></h5>
                    <p><?= $tab === 'notas' ? count($overview['students']) : count($roster) ?> alunos<?= $tab === 'alunos' ? ' · toque no aluno para abrir o perfil' : ($tab === 'chamada' ? ($isDaily ? ' · chamada do dia: todos começam presentes, toque em quem faltou' : ' · todos começam presentes, toque em quem faltou') : ($tab === 'diario' ? ' · um registro por dia para a turma; salvar de novo corrige' : '')) ?></p>
                </div>
            </div>

            <div class="card m-att-toolbar">
                <form class="m-att-field m-class-filter" action="/portal-docente/turma" method="get" data-auto-submit>
                    <label class="m-att-label" for="classFilter">Turma</label>
                    <select class="form-control" id="classFilter" name="id">
                        <?php foreach ($this->view->classFilter as $id => $label) { ?>
                            <option value="<?= (int) $id ?>" <?= $id === (int) $this->view->classId ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php } ?>
                    </select>
                    <input type="hidden" name="ver" value="<?= htmlspecialchars($tab) ?>">
                    <input type="hidden" name="unidade" value="<?= (int) $unity ?>">
                </form>
                <div class="m-att-field">
                    <span class="m-att-label">Ver</span>
                    <div class="m-seg m-class-tabs">
                        <?php foreach ($tabs as $key => $t) { ?>
                            <a href="<?= $link($unity, $key) ?>" role="button" class="btn <?= $tab === $key ? 'is-active' : '' ?>" <?= $tab === $key ? 'aria-current="page"' : '' ?>><i class="fas <?= $t[0] ?> mr-2"></i><?= $t[1] ?></a>
                        <?php } ?>
                    </div>
                </div>
                <?php if ($showDisciplines) { ?>
                    <form class="m-att-field m-class-filter" action="/portal-docente/turma" method="get" data-auto-submit>
                        <label class="m-att-label" for="disciplineFilter">Disciplina</label>
                        <select class="form-control" id="disciplineFilter" name="disciplina">
                            <?php foreach ($ownDisciplines as $d) { ?>
                                <option value="<?= (int) $d->discipline_class_id ?>" <?= (int) $d->discipline_class_id === (int) $this->view->discipline ? 'selected' : '' ?>><?= htmlspecialchars($d->discipline_name) ?></option>
                            <?php } ?>
                        </select>
                        <input type="hidden" name="id" value="<?= (int) $this->view->classId ?>">
                        <input type="hidden" name="ver" value="chamada">
                    </form>
                <?php } ?>
                <?php if (!$plainTab) { ?>
                <div class="m-att-field">
                    <span class="m-att-label">Bimestre</span>
                    <div class="m-seg">
                        <?php for ($u = 1; $u <= $rule['units']; $u++) { ?>
                            <a href="<?= $link($u, $tab) ?>" role="button" class="btn <?= $u === $unity ? 'is-active' : '' ?>" style="min-width:44px"><?= $u ?>º</a>
                        <?php } ?>
                    </div>
                </div>
                <?php } ?>
                <?php if (!$plainTab) { ?>
                    <div class="m-att-field m-att-field-grow">
                        <span class="m-att-label">Buscar aluno</span>
                        <div class="m-att-search">
                            <i class="fas fa-search"></i>
                            <input type="search" data-roster-search placeholder="Digite um nome" autocomplete="off">
                        </div>
                    </div>
                <?php } ?>
                <?php if ($tab === 'notas') { ?>
                    <label class="m-overview-low"><input type="checkbox" data-roster-low> Só abaixo da média</label>
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
                                    <small class="m-att-ring-label"><?= $absenceLabel ?></small>
                                </a>
                                <?php if ($quickDisciplines) { ?>
                                    <button type="button" class="btn btn-light m-roster-note" data-note-student="<?= (int) $s->id ?>" data-note-name="<?= htmlspecialchars($s->name) ?>"><i class="fas fa-comment-medical mr-2"></i>Observação</button>
                                <?php } ?>
                            </article>
                        <?php } ?>
                    </div>
                <?php } ?>

            <?php } elseif ($tab === 'chamada') {
                $openUnits = $this->view->openUnits;
                require __DIR__ . '/components/attendanceMark.php';
            } elseif ($tab === 'diario') {
                require __DIR__ . '/components/dailyLog.php';
            } else {
                $grade = function ($n) { return number_format((float) $n, 1, ',', ''); };
                $totals = [];
            ?>

            <p class="m-muted-note">Nota do bimestre: média das <?= (int) $rule['perBimester'] ?> avaliações; abaixo de <?= $fmt($rule['passing']) ?>, vale a maior entre o bimestre e a recuperação. Você edita as colunas com <i class="fas fa-pen fa-xs"></i>.</p>

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
                                <th title="Disciplinas abaixo da média">Abaixo</th>
                            </tr>
                        </thead>
                        <tbody data-roster>
                            <?php foreach ($overview['students'] as $s) {
                                $low = 0;
                                ob_start();
                                foreach ($overview['disciplines'] as $d) {
                                    $mine = in_array((int) $d->id, $editable, true) ? 'is-mine' : '';
                                    $acronym = htmlspecialchars($d->acronym ?: $d->name);
                                    $g = $s['grades'][(int) $d->id] ?? null;
                                    if (!$g || $g['grade'] === null) { echo '<td class="' . $mine . '" data-label="' . $acronym . '">–</td>'; continue; }
                                    $below = $g['grade'] < $rule['passing'];
                                    $low += $below ? 1 : 0;
                                    $totals[(int) $d->id][] = (float) $g['grade'];
                                    $class = $g['recovered'] ? 'is-rec' : ($below ? 'is-low' : '');
                                    $title = $g['recovered'] ? 'Recuperação (média era ' . $fmt($g['average']) . ')' : ($g['needsRecovery'] ? 'Abaixo da média: aguarda recuperação' : ($g['closed'] ? 'Bimestre fechado' : 'Parcial'));
                                    ?>
                                    <td class="<?= $mine ?> <?= $class ?>" data-label="<?= $acronym ?>" title="<?= htmlspecialchars($title) ?>"><span><?= $grade($g['grade']) ?><?= $g['recovered'] ? '<sup>R</sup>' : (!$g['closed'] ? '<sup>*</sup>' : '') ?></span></td>
                                <?php }
                                $cells = ob_get_clean();
                                ?>
                                <tr data-name="<?= htmlspecialchars($s['name']) ?>" data-low="<?= $low ?>">
                                    <td><a href="/portal-docente/aluno?id=<?= (int) $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></a></td>
                                    <?= $cells ?>
                                    <td class="m-overview-count <?= $low ? 'has-low' : '' ?>" data-label="Abaixo"><?= $low ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Média da turma</td>
                                <?php foreach ($overview['disciplines'] as $d) {
                                    $values = $totals[(int) $d->id] ?? [];
                                    $average = $values ? array_sum($values) / count($values) : null;
                                    ?>
                                    <td class="<?= in_array((int) $d->id, $editable, true) ? 'is-mine' : '' ?>" data-label="<?= htmlspecialchars($d->acronym ?: $d->name) ?>"><?= $average === null ? '–' : $grade($average) ?></td>
                                <?php } ?>
                                <td class="m-overview-count"></td>
                            </tr>
                        </tfoot>
                    </table>
                <?php } ?>
            </div>
            <?php if ($overview['students']) { ?>
                <p class="m-muted-note mt-2 mb-0"><sup>R</sup> nota da recuperação · <sup>*</sup> parcial (faltam avaliações) · em vermelho: abaixo de <?= $fmt($rule['passing']) ?></p>
            <?php } ?>

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
<?php if ($tab === 'diario') { ?>
    <script src="<?= asset('/assets/js/dailyLog.js') ?>" defer></script>
<?php } ?>
<?php if ($tab === 'chamada') {
    $disciplineClasses = $this->view->disciplineClasses;
    require __DIR__ . '/components/classCards.php';
?>
    <script>
        window.MATER_ATTENDANCE = {
            classes: <?= json_encode($classesJson, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>,
            current: <?= (int) $this->view->discipline ?>,
            // Fund. 1: chamada diária da turma (titular); null nas demais etapas
            daily: <?= json_encode($daily, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>,
            photoBase: <?= json_encode($app_url . '/assets/img/studentProfilePhotos/') ?>
        };
    </script>
    <script src="<?= asset('/assets/js/attendance.js') ?>" defer></script>
<?php } ?>
