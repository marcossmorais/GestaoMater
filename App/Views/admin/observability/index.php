<?php
require __DIR__ . '/components/helpers.php';

$summary = $this->view->summary;
$rule = $this->view->rule;
$tab = $this->view->tab;
$query = $this->view->query;
$passing = $rule['passing'];

$filterAction = '/admin/observabilidade';
$filterHidden = array_filter(['aba' => $tab, 'etapa' => $this->view->filters['stage']]);
$stageUrl = function ($stage) use ($query, $tab) {
    $q = $query;
    unset($q['etapa']);
    return '/admin/observabilidade?' . http_build_query($q + array_filter(['etapa' => $stage, 'aba' => $tab]));
};
$charts = $summary['charts'];

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

            <?php require __DIR__ . '/components/stages.php' ?>

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

            <?php if (!$this->view->filters['stage'] && count($summary['stages']) > 1) { ?>
                <div class="m-obs-stage-cards">
                    <?php foreach ($summary['stages'] as $st) { ?>
                        <a class="card m-panel m-obs-stage-card" href="<?= htmlspecialchars($stageUrl($st['key'])) ?>">
                            <div class="m-panel-head"><h6><?= htmlspecialchars($st['label']) ?></h6><i class="fas fa-chevron-right" aria-hidden="true"></i></div>
                            <dl class="m-obs-stage-stats">
                                <div><dt>Turmas</dt><dd><?= (int) $st['classes'] ?></dd></div>
                                <div><dt>Alunos</dt><dd><?= (int) $st['students'] ?></dd></div>
                                <div><dt>Média</dt><dd class="<?= $low($st['average']) ?>"><?= obsGrade($st['average']) ?></dd></div>
                                <div><dt>Frequência</dt><dd class="<?= $st['attendance'] !== null && $st['attendance'] < $rule['minAttendance'] ? 'is-low' : '' ?>"><?= obsPct($st['attendance']) ?></dd></div>
                                <div><dt>Em risco</dt><dd><?= $st['atRisk'] ? '<span class="m-pill m-pill-bad">' . (int) $st['atRisk'] . '</span>' : '0' ?></dd></div>
                                <div><dt>Notas pendentes</dt><dd><?= (int) $st['pendingGrades'] ?></dd></div>
                            </dl>
                        </a>
                    <?php } ?>
                </div>
            <?php } ?>

            <?php if ($charts && $summary['students']) {
                $status = $charts['status'];
                $statusLegend = [
                    ['Na média e frequentes', $status['ok'], '#22B07D'],
                    ['Abaixo da média em alguma disciplina', $status['grade'], '#F2643D'],
                    ['Só a frequência baixa', $status['attendance'], '#0EA5E9'],
                    ['Sem nota ainda', $status['none'], '#B8C5DC'],
                ];
                $bandColors = ['#F2643D', '#0EA5E9', '#2F6FED', '#22B07D'];
                $chartData = [
                    'passing' => $charts['passing'],
                    'status' => array_map(function ($s) { return ['label' => $s[0], 'value' => (int) $s[1], 'color' => $s[2]]; }, $statusLegend),
                    'bands' => array_map(function ($label, $count, $color) { return [$label, (int) $count, $color]; }, array_keys($charts['bands']), $charts['bands'], $bandColors),
                    'classes' => $charts['classes'],
                    'disciplines' => $charts['disciplines'],
                    'classUrl' => '/admin/observabilidade/turma?' . http_build_query($query + ['id' => '']),
                ];
            ?>
                <div class="m-charts m-obs-charts">
                    <div class="card m-panel m-chart-card">
                        <div class="m-panel-head"><h6>Situação dos alunos</h6></div>
                        <div class="m-donut"><canvas id="obsChartStatus" aria-label="Situação dos alunos"></canvas><div class="m-donut-center"><strong><?= $students ?></strong><small>alunos</small></div></div>
                        <ul class="m-legend">
                            <?php foreach ($statusLegend as [$label, $count, $color]) { ?>
                                <li><i style="background: <?= $color ?>"></i><span><?= $label ?></span><b><?= (int) $count ?></b></li>
                            <?php } ?>
                        </ul>
                    </div>

                    <div class="card m-panel m-chart-card">
                        <div class="m-panel-head"><h6>Faixas da média geral</h6></div>
                        <div class="m-donut"><canvas id="obsChartBands" aria-label="Alunos por faixa de média"></canvas></div>
                        <ul class="m-legend">
                            <?php $i = 0; foreach ($charts['bands'] as $label => $count) { ?>
                                <li><i style="background: <?= htmlspecialchars((string) $bandColors[$i++]) ?>"></i><span><?= $label ?></span><b><?= (int) $count ?></b></li>
                            <?php } ?>
                        </ul>
                    </div>

                    <div class="card m-panel m-chart-card m-chart-wide">
                        <div class="m-panel-head"><h6>Média por turma</h6><small class="m-muted-note m-0">toque na barra para abrir · linha: média <?= obsGrade($passing) ?></small></div>
                        <div class="m-chart-box"><canvas id="obsChartClassAverage" aria-label="Média de cada turma"></canvas></div>
                    </div>

                    <div class="card m-panel m-chart-card m-chart-wide">
                        <div class="m-panel-head"><h6>Alunos em risco por turma</h6><span class="m-pill m-pill-bad"><?= $atRisk ?></span></div>
                        <div class="m-chart-box"><canvas id="obsChartAtRisk" aria-label="Alunos em risco por turma"></canvas></div>
                    </div>

                    <div class="card m-panel m-chart-card m-chart-wide">
                        <div class="m-panel-head"><h6>Média por disciplina</h6><small class="m-muted-note m-0">todas as turmas juntas</small></div>
                        <div class="m-chart-box"><canvas id="obsChartDisciplines" aria-label="Média de cada disciplina"></canvas></div>
                    </div>
                </div>
                <script>window.MATER_OBS = <?= json_encode($chartData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;</script>
                <script src="<?= asset('/assets/js/adminObservability.js') ?>" defer></script>
            <?php } ?>

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
                                    <td data-label="Turma"><a href="<?= htmlspecialchars($classUrl($c['id'])) ?>" class="m-obs-link"><?= htmlspecialchars($c['label']) ?></a><?= !$this->view->filters['stage'] && isset($this->view->stages[$c['stage']]) ? ' <small class="m-obs-note">' . htmlspecialchars($this->view->stages[$c['stage']]['short']) . '</small>' : '' ?></td>
                                    <td data-label="Alunos"><?= htmlspecialchars((string) $c['students']) ?></td>
                                    <td data-label="Média" class="<?= $low($c['average']) ?>"><?= obsGrade($c['average']) ?></td>
                                    <td data-label="Frequência" class="<?= $c['attendance'] !== null && $c['attendance'] < $rule['minAttendance'] ? 'is-low' : '' ?>"><?= obsPct($c['attendance']) ?></td>
                                    <td data-label="Em risco"><?= $c['atRisk'] ? '<span class="m-pill m-pill-bad">' . $c['atRisk'] . '</span>' : '0' ?></td>
                                    <td data-label="Chamadas"><?= obsPct($c['callShare']) ?></td>
                                    <td data-label="Notas pendentes"><?= $c['pendingGrades'] ? '<span class="m-pill m-pill-warn">' . $c['pendingGrades'] . '</span>' : '0' ?></td>
                                    <td data-label="Ocorrências"><?= htmlspecialchars((string) $c['occurrences']) ?><?= $c['pendingAck'] ? ' <small class="m-obs-note">' . $c['pendingAck'] . ' sem ciência</small>' : '' ?></td>
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
                                <?php if ($d['pendingGrades']) { ?><span class="m-pill m-pill-warn"><?= htmlspecialchars((string) $d['pendingGrades']) ?> notas pendentes</span><?php } ?>
                                <?php if ($d['missingExams']) { ?><span class="m-pill m-pill-info"><?= htmlspecialchars((string) $d['missingExams']) ?> avaliações a criar</span><?php } ?>
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
                                        <td data-label="Avaliações"><?= htmlspecialchars((string) $t['exams']) ?> de <?= htmlspecialchars((string) $t['expectedExams']) ?></td>
                                        <td data-label="Notas pendentes"><?= htmlspecialchars((string) $t['pendingGrades']) ?></td>
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
                                <span class="m-pill <?= htmlspecialchars((string) $status[0]) ?>"><?= htmlspecialchars((string) $status[1]) ?></span>
                                <span><?= count($t['classes']) ?> turma<?= count($t['classes']) === 1 ? '' : 's' ?> · <?= count($t['disciplines']) ?> disciplina<?= count($t['disciplines']) === 1 ? '' : 's' ?></span>
                                <span>Chamadas <b><?= obsPct($t['callShare']) ?></b></span>
                                <span>Última <b><?= $t['lastCall'] ?: '—' ?></b></span>
                                <?php if ($t['pendingGrades']) { ?><span class="m-pill m-pill-warn"><?= htmlspecialchars((string) $t['pendingGrades']) ?> notas pendentes</span><?php } ?>
                            </span>
                        </summary>

                        <div class="m-obs-facts">
                            <span>Diário: <b><?= htmlspecialchars((string) $t['diary']) ?></b> aula<?= $t['diary'] === 1 ? '' : 's' ?> registrada<?= $t['diary'] === 1 ? '' : 's' ?> em <?= htmlspecialchars((string) $t['calls']) ?> chamada<?= $t['calls'] === 1 ? '' : 's' ?></span>
                            <span>Avaliações a criar: <b><?= htmlspecialchars((string) $t['missingExams']) ?></b></span>
                            <span>Aulas como substituto: <b><?= htmlspecialchars((string) $t['substitute']) ?></b></span>
                            <span>Aulas suas com troca: <b><?= htmlspecialchars((string) $t['exceptions']) ?></b></span>
                            <button type="button" class="btn btn-light btn-sm" data-teacher-profile="<?= (int) $t['id'] ?>"><i class="fas fa-id-card mr-2"></i>Abrir perfil</button>
                            <a class="btn btn-light btn-sm" href="/admin/mural?professor=<?= (int) $t['id'] ?>"><i class="fas fa-envelope mr-2"></i>Mensagem direta</a>
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
                                        <td data-label="Chamadas"><?= htmlspecialchars((string) $a['lessons']) ?> de <?= htmlspecialchars((string) $a['expected']) ?> <small class="m-obs-note"><?= obsPct($a['callShare']) ?></small></td>
                                        <td data-label="Última chamada"><?= $a['lastCall'] ?: '—' ?></td>
                                        <td data-label="Diário"><?= htmlspecialchars((string) $a['diary']) ?></td>
                                        <td data-label="Avaliações"><?= htmlspecialchars((string) $a['exams']) ?> de <?= htmlspecialchars((string) $a['expectedExams']) ?></td>
                                        <td data-label="Notas pendentes"><?= htmlspecialchars((string) $a['pendingGrades']) ?></td>
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
