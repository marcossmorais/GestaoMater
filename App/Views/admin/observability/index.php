<?php
require __DIR__ . '/components/helpers.php';

$summary = $this->view->summary;
$rule = $this->view->rule;
$tab = $this->view->tab;
$query = $this->view->query;
$passing = $rule['passing'];

$filterAction = '/admin/observabilidade';
$filterHidden = ['aba' => $tab];

// números do topo
$students = count($summary['students']);
$atRisk = count(array_filter($summary['students'], function ($s) { return $s['atRisk']; }));
$averages = array_filter(array_column($summary['students'], 'average'), function ($v) { return $v !== null; });
$schoolAverage = $averages ? array_sum($averages) / count($averages) : null;
$pendingGrades = array_sum(array_column($summary['disciplineClasses'], 'pendingGrades'));
$lateTeachers = count(array_filter($summary['teachers'], function ($t) { return $t['status'] === 'atrasado'; }));

$tabUrl = function ($name) use ($query) {
    return '/admin/observabilidade?' . http_build_query($query + ['aba' => $name]);
};
$classUrl = function ($id) use ($query) {
    return '/admin/observabilidade/turma?' . http_build_query(['id' => $id] + $query);
};
$low = function ($grade) use ($passing) {
    return $grade !== null && $grade < $passing - 0.0001 ? 'is-low' : '';
};
?>

<section id="observability">
    <div class="row main-container">
        <div class="col-lg-11 mx-auto">

            <div class="m-page-head">
                <div>
                    <h5>Observabilidade 360°</h5>
                    <p>Desempenho, frequência e pendências por turma, disciplina e professor. Média de aprovação <?= obsGrade($passing) ?> · frequência mínima <?= obsPct($rule['minAttendance']) ?>.</p>
                </div>
                <?php require __DIR__ . '/components/filters.php' ?>
            </div>

            <div class="m-kpis m-obs-kpis">
                <div class="card m-kpi">
                    <div class="m-kpi-icon"><i class="fas fa-user-graduate"></i></div>
                    <div><div class="m-kpi-label">Alunos ativos</div><div class="m-kpi-value"><?= $students ?></div></div>
                </div>
                <div class="card m-kpi">
                    <div class="m-kpi-icon"><i class="fas fa-chart-bar"></i></div>
                    <div><div class="m-kpi-label">Média geral</div><div class="m-kpi-value"><?= obsGrade($schoolAverage) ?></div></div>
                </div>
                <div class="card m-kpi">
                    <div class="m-kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div><div class="m-kpi-label">Alunos em risco</div><div class="m-kpi-value"><?= $atRisk ?></div></div>
                </div>
                <div class="card m-kpi">
                    <div class="m-kpi-icon"><i class="fas fa-pen"></i></div>
                    <div><div class="m-kpi-label">Notas pendentes</div><div class="m-kpi-value"><?= $pendingGrades ?></div></div>
                </div>
                <div class="card m-kpi">
                    <div class="m-kpi-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div><div class="m-kpi-label">Docentes com atraso</div><div class="m-kpi-value"><?= $lateTeachers ?></div></div>
                </div>
            </div>

            <nav class="m-obs-tabs" aria-label="Visões">
                <a href="<?= htmlspecialchars($tabUrl('turmas')) ?>" class="<?= $tab === 'turmas' ? 'is-active' : '' ?>" <?= $tab === 'turmas' ? 'aria-current="page"' : '' ?>><i class="fas fa-users"></i>Por turma</a>
                <a href="<?= htmlspecialchars($tabUrl('disciplinas')) ?>" class="<?= $tab === 'disciplinas' ? 'is-active' : '' ?>" <?= $tab === 'disciplinas' ? 'aria-current="page"' : '' ?>><i class="fas fa-book"></i>Por disciplina</a>
                <a href="<?= htmlspecialchars($tabUrl('professores')) ?>" class="<?= $tab === 'professores' ? 'is-active' : '' ?>" <?= $tab === 'professores' ? 'aria-current="page"' : '' ?>><i class="fas fa-chalkboard-teacher"></i>Por professor</a>
            </nav>

            <?php if (!$summary['disciplineClasses']) { ?>

                <div class="card m-panel m-empty"><p>Nenhuma turma encontrada com esses filtros.</p></div>

            <?php } elseif ($tab === 'turmas') { ?>

                <div class="card m-panel">
                    <div class="m-obs-scroll"><table class="m-obs-table">
                        <thead>
                            <tr>
                                <th>Turma</th><th>Alunos</th><th>Média</th><th>Frequência</th><th>Em risco</th>
                                <th title="Aulas registradas em chamada ÷ aulas previstas pela grade até hoje">Chamadas</th>
                                <th>Notas pendentes</th><th>Ocorrências</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($summary['classes'] as $c) { ?>
                                <tr>
                                    <td data-label="Turma"><a href="<?= htmlspecialchars($classUrl($c['id'])) ?>" class="m-obs-link"><?= htmlspecialchars($c['label']) ?></a></td>
                                    <td data-label="Alunos"><?= $c['students'] ?></td>
                                    <td data-label="Média" class="<?= $low($c['average']) ?>"><?= obsGrade($c['average']) ?></td>
                                    <td data-label="Frequência" class="<?= $c['attendance'] !== null && $c['attendance'] < $rule['minAttendance'] ? 'is-low' : '' ?>"><?= obsPct($c['attendance']) ?></td>
                                    <td data-label="Em risco"><?= $c['atRisk'] ? '<span class="m-pill m-pill-bad">' . $c['atRisk'] . '</span>' : '0' ?></td>
                                    <td data-label="Chamadas"><?= obsPct($c['callShare']) ?></td>
                                    <td data-label="Notas pendentes"><?= $c['pendingGrades'] ? '<span class="m-pill m-pill-warn">' . $c['pendingGrades'] . '</span>' : '0' ?></td>
                                    <td data-label="Ocorrências"><?= $c['occurrences'] ?><?= $c['pendingAck'] ? ' <small class="m-obs-note">' . $c['pendingAck'] . ' sem ciência</small>' : '' ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table></div>
                </div>

            <?php } elseif ($tab === 'disciplinas') { ?>

                <?php foreach ($summary['disciplines'] as $d) { ?>
                    <details class="card m-panel m-obs-group">
                        <summary>
                            <strong><?= htmlspecialchars($d['name']) ?></strong>
                            <span class="m-obs-stats">
                                <span>Média <b class="<?= $low($d['average']) ?>"><?= obsGrade($d['average']) ?></b></span>
                                <span>Abaixo da média <b><?= obsPct($d['belowShare']) ?></b></span>
                                <span><?= count($d['classes']) ?> turma<?= count($d['classes']) === 1 ? '' : 's' ?></span>
                                <?php if ($d['pendingGrades']) { ?><span class="m-pill m-pill-warn"><?= $d['pendingGrades'] ?> notas pendentes</span><?php } ?>
                                <?php if ($d['missingExams']) { ?><span class="m-pill m-pill-info"><?= $d['missingExams'] ?> avaliações a criar</span><?php } ?>
                            </span>
                        </summary>
                        <div class="m-obs-scroll"><table class="m-obs-table">
                            <thead>
                                <tr><th>Turma</th><th>Professor</th><th>Média</th><th>Abaixo da média</th><th>Avaliações</th><th>Notas pendentes</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($d['classes'] as $t) { ?>
                                    <tr>
                                        <td data-label="Turma"><a href="<?= htmlspecialchars($classUrl($t['classId'])) ?>" class="m-obs-link"><?= htmlspecialchars($t['classLabel']) ?></a></td>
                                        <td data-label="Professor"><?= htmlspecialchars($t['teacher']) ?></td>
                                        <td data-label="Média" class="<?= $low($t['average']) ?>"><?= obsGrade($t['average']) ?></td>
                                        <td data-label="Abaixo da média"><?= obsPct($t['belowShare']) ?></td>
                                        <td data-label="Avaliações"><?= $t['exams'] ?> de <?= $t['expectedExams'] ?></td>
                                        <td data-label="Notas pendentes"><?= $t['pendingGrades'] ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table></div>
                    </details>
                <?php } ?>

            <?php } else { ?>

                <p class="m-obs-help">Chamadas = aulas registradas ÷ aulas previstas pela grade semanal até hoje (feriados não são descontados). Em dia: 90% ou mais e nenhuma nota pendente.</p>

                <?php foreach ($summary['teachers'] as $t) {
                    $status = ['em-dia' => ['m-pill-ok', 'Em dia'], 'atrasado' => ['m-pill-bad', 'Com atraso'], 'sem-grade' => ['m-pill-info', 'Sem grade de horários']][$t['status']];
                ?>
                    <details class="card m-panel m-obs-group">
                        <summary>
                            <strong><?= htmlspecialchars($t['name']) ?></strong>
                            <span class="m-obs-stats">
                                <span class="m-pill <?= $status[0] ?>"><?= $status[1] ?></span>
                                <span><?= count($t['classes']) ?> turma<?= count($t['classes']) === 1 ? '' : 's' ?> · <?= count($t['disciplines']) ?> disciplina<?= count($t['disciplines']) === 1 ? '' : 's' ?></span>
                                <span>Chamadas <b><?= obsPct($t['callShare']) ?></b></span>
                                <span>Última <b><?= $t['lastCall'] ?: '—' ?></b></span>
                                <?php if ($t['pendingGrades']) { ?><span class="m-pill m-pill-warn"><?= $t['pendingGrades'] ?> notas pendentes</span><?php } ?>
                            </span>
                        </summary>

                        <div class="m-obs-facts">
                            <span>Diário: <b><?= $t['diary'] ?></b> aula<?= $t['diary'] === 1 ? '' : 's' ?> registrada<?= $t['diary'] === 1 ? '' : 's' ?> em <?= $t['calls'] ?> chamada<?= $t['calls'] === 1 ? '' : 's' ?></span>
                            <span>Avaliações a criar: <b><?= $t['missingExams'] ?></b></span>
                            <span>Aulas como substituto: <b><?= $t['substitute'] ?></b></span>
                            <span>Aulas suas com troca: <b><?= $t['exceptions'] ?></b></span>
                            <button type="button" class="btn btn-light btn-sm" data-teacher-profile="<?= (int) $t['id'] ?>"><i class="fas fa-id-card mr-2"></i>Abrir perfil</button>
                        </div>

                        <div class="m-obs-scroll"><table class="m-obs-table">
                            <thead>
                                <tr><th>Turma</th><th>Disciplina</th><th>Chamadas</th><th>Última chamada</th><th>Diário</th><th>Avaliações</th><th>Notas pendentes</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($t['assignments'] as $a) { ?>
                                    <tr>
                                        <td data-label="Turma"><a href="<?= htmlspecialchars($classUrl($a['classId'])) ?>" class="m-obs-link"><?= htmlspecialchars($a['classLabel']) ?></a></td>
                                        <td data-label="Disciplina"><?= htmlspecialchars($a['discipline']) ?></td>
                                        <td data-label="Chamadas"><?= $a['lessons'] ?> de <?= $a['expected'] ?> <small class="m-obs-note"><?= obsPct($a['callShare']) ?></small></td>
                                        <td data-label="Última chamada"><?= $a['lastCall'] ?: '—' ?></td>
                                        <td data-label="Diário"><?= $a['diary'] ?></td>
                                        <td data-label="Avaliações"><?= $a['exams'] ?> de <?= $a['expectedExams'] ?></td>
                                        <td data-label="Notas pendentes"><?= $a['pendingGrades'] ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table></div>
                    </details>
                <?php } ?>

                <?php require __DIR__ . '/components/profileModals.php' ?>

            <?php } ?>

        </div>
    </div>
</section>
