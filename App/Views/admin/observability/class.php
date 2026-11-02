<?php
require __DIR__ . '/components/helpers.php';

$summary = $this->view->summary;
$rule = $this->view->rule;
$class = $this->view->class;
$query = $this->view->query;
$passing = $rule['passing'];

$filterAction = '/admin/observabilidade/turma';
$filterHidden = ['id' => $class['id']];
$filterShift = false;

$tds = array_values($summary['disciplineClasses']);
$low = function ($grade) use ($passing) {
    return $grade !== null && $grade < $passing - 0.0001 ? 'is-low' : '';
};
?>

<section id="observability">
    <div class="row main-container">
        <div class="col-lg-11 mx-auto">

            <div class="m-page-head">
                <div>
                    <a href="/admin/observabilidade?<?= htmlspecialchars(http_build_query($query)) ?>" class="m-obs-back"><i class="fas fa-arrow-left mr-2"></i>Observabilidade</a>
                    <h5><?= htmlspecialchars($class['label']) ?></h5>
                    <p>Alunos em risco primeiro: média abaixo de <?= obsGrade($passing) ?> em alguma disciplina ou frequência abaixo de <?= obsPct($rule['minAttendance']) ?>.</p>
                </div>
                <?php require __DIR__ . '/components/filters.php' ?>
            </div>

            <div class="m-kpis m-obs-kpis">
                <div class="card m-kpi">
                    <div class="m-kpi-icon"><i class="fas fa-user-graduate"></i></div>
                    <div><div class="m-kpi-label">Alunos ativos</div><div class="m-kpi-value"><?= $class['students'] ?></div></div>
                </div>
                <div class="card m-kpi">
                    <div class="m-kpi-icon"><i class="fas fa-chart-bar"></i></div>
                    <div><div class="m-kpi-label">Média da turma</div><div class="m-kpi-value <?= $low($class['average']) ?>"><?= obsGrade($class['average']) ?></div></div>
                </div>
                <div class="card m-kpi">
                    <div class="m-kpi-icon"><i class="fas fa-calendar-check"></i></div>
                    <div><div class="m-kpi-label">Frequência</div><div class="m-kpi-value"><?= obsPct($class['attendance']) ?></div></div>
                </div>
                <div class="card m-kpi">
                    <div class="m-kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div><div class="m-kpi-label">Em risco</div><div class="m-kpi-value"><?= $class['atRisk'] ?></div></div>
                </div>
                <div class="card m-kpi">
                    <div class="m-kpi-icon"><i class="fas fa-clipboard-list"></i></div>
                    <div><div class="m-kpi-label">Ocorrências</div><div class="m-kpi-value"><?= $class['occurrences'] ?><?= $class['pendingAck'] ? '<span class="m-kpi-extra m-obs-warn">' . $class['pendingAck'] . ' sem ciência</span>' : '' ?></div></div>
                </div>
            </div>

            <div class="card m-panel">
                <div class="m-panel-head"><h6>Disciplinas</h6></div>
                <div class="m-obs-scroll"><table class="m-obs-table">
                    <thead>
                        <tr><th>Disciplina</th><th>Professor</th><th>Média</th><th>Abaixo da média</th><th>Chamadas</th><th>Última chamada</th><th>Avaliações</th><th>Notas pendentes</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tds as $t) { ?>
                            <tr>
                                <td data-label="Disciplina"><?= htmlspecialchars($t['discipline']) ?></td>
                                <td data-label="Professor"><button type="button" class="m-obs-link" data-teacher-profile="<?= $t['teacherId'] ?>"><?= htmlspecialchars($t['teacher']) ?></button></td>
                                <td data-label="Média" class="<?= $low($t['average']) ?>"><?= obsGrade($t['average']) ?></td>
                                <td data-label="Abaixo da média"><?= obsPct($t['belowShare']) ?></td>
                                <td data-label="Chamadas"><?= $t['lessons'] ?> de <?= $t['expected'] ?> <small class="m-obs-note"><?= obsPct($t['callShare']) ?></small></td>
                                <td data-label="Última chamada"><?= $t['lastCall'] ?: '—' ?></td>
                                <td data-label="Avaliações"><?= $t['exams'] ?> de <?= $t['expectedExams'] ?></td>
                                <td data-label="Notas pendentes"><?= $t['pendingGrades'] ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table></div>
            </div>

            <div class="card m-panel">
                <div class="m-panel-head"><h6>Alunos</h6><small class="m-obs-note">Toque no nome para abrir o perfil</small></div>

                <?php if (!$this->view->students) { ?>
                    <div class="m-empty"><p>Nenhum aluno ativo nesta turma.</p></div>
                <?php } else { ?>
                    <div class="m-obs-scroll"><table class="m-obs-table">
                        <thead>
                            <tr>
                                <th>Aluno</th><th>Situação</th><th>Média</th><th>Disciplinas abaixo</th><th>Frequência</th><th>Faltas</th><th>Ocorrências</th>
                                <?php foreach ($tds as $t) { ?><th class="m-obs-discipline" title="<?= htmlspecialchars($t['discipline']) ?>"><?= htmlspecialchars(mb_substr($t['discipline'], 0, 4)) ?></th><?php } ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->view->students as $s) { ?>
                                <tr>
                                    <td data-label="Aluno"><button type="button" class="m-obs-link" data-student-profile="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></button></td>
                                    <td data-label="Situação">
                                        <?php if ($s['atRisk']) { ?>
                                            <span class="m-pill m-pill-bad"><?= $s['below'] ? 'Nota' : '' ?><?= $s['below'] && $s['lowAttendance'] ? ' e ' : '' ?><?= $s['lowAttendance'] ? 'Frequência' : '' ?></span>
                                        <?php } elseif ($s['average'] === null) { ?>
                                            <span class="m-pill m-pill-info">Sem notas</span>
                                        <?php } else { ?>
                                            <span class="m-pill m-pill-ok">Regular</span>
                                        <?php } ?>
                                    </td>
                                    <td data-label="Média" class="<?= $low($s['average']) ?>"><?= obsGrade($s['average']) ?></td>
                                    <td data-label="Disciplinas abaixo"><?= $s['below'] ?></td>
                                    <td data-label="Frequência" class="<?= $s['lowAttendance'] ? 'is-low' : '' ?>"><?= obsPct($s['attendance']) ?></td>
                                    <td data-label="Faltas"><?= $s['absenceTotal'] ?></td>
                                    <td data-label="Ocorrências"><?= $s['occurrences'] ?><?= $s['pendingAck'] ? ' <small class="m-obs-note">' . $s['pendingAck'] . ' sem ciência</small>' : '' ?></td>
                                    <?php foreach ($tds as $t) { $g = $s['grades'][$t['id']] ?? null; ?>
                                        <td data-label="<?= htmlspecialchars($t['discipline']) ?>" class="m-obs-discipline <?= $low($g) ?>"><?= obsGrade($g) ?></td>
                                    <?php } ?>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table></div>
                <?php } ?>
            </div>

            <?php require __DIR__ . '/components/profileModals.php' ?>

        </div>
    </div>
</section>
