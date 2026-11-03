<?php
// Roteiro de provas em folha A4: uma tabela por bimestre, com colunas fixas e cada matéria inteira na mesma folha
use App\Models\TeacherStudent\Gradebook;

$unit = $this->view->unit;
$examFilter = $this->view->examFilter;
$subjectFilter = $this->view->subjectFilter;

$blocks = [];
$all = [];
foreach ($this->view->examSheet as $e) {
    if ((int) $e->unity !== $unit) continue;
    $e->label = $e->recovery ? 'Recuperação' : $e->description;
    if ($examFilter !== '' && $e->label !== $examFilter) continue;
    if ($subjectFilter !== '' && $e->discipline !== $subjectFilter) continue;
    $blocks[$e->discipline][] = $e;
    $all[] = $e;
}

$today = (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
$summary = Gradebook::examSummary($all, $today);
$weekdays = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];

$docTitle = 'Roteiro de provas — ' . $unit . 'º bimestre';
$docHeading = 'Roteiro de provas · ' . $unit . 'º bimestre';
require __DIR__ . '/documentHead.php';
?>

        <div class="meta">
            <div><span>Turma</span><strong><?= htmlspecialchars($_SESSION['Student']['class']) ?></strong></div>
            <?php if ($all) { ?>
                <div><span>Período das provas</span><strong><?= date('d/m', strtotime($summary['first'])) ?> a <?= date('d/m', strtotime($summary['last'])) ?></strong></div>
                <div><span>Próxima avaliação</span><strong><?= $summary['next'] ? date('d/m', strtotime($summary['next'])) . ' · ' . htmlspecialchars(implode(', ', $summary['nextNames'])) : 'Nenhuma pela frente' ?></strong></div>
                <div><span>Assuntos divulgados</span><strong><?= $summary['withGuide'] ?> de <?= $summary['total'] ?></strong></div>
            <?php } ?>
        </div>

        <?php if (!$all) { ?>
            <p class="none">Nenhuma avaliação lançada aqui ainda.</p>
        <?php } else { ?>
            <table class="sheet-table sheet-exams">
                <colgroup><col class="c-subject"><col class="c-exam"><col class="c-date"><col class="c-topics"><col class="c-where"></colgroup>
                <thead>
                    <tr><th>Matéria</th><th>Avaliação</th><th>Data</th><th>Conteúdo</th><th>Onde estudar</th></tr>
                </thead>
                <?php foreach ($blocks as $discipline => $exams) { ?>
                    <tbody>
                        <?php foreach ($exams as $i => $e) {
                            $topics = Gradebook::guideTopics($e->guide_topics);
                            $types = Gradebook::typeLabels($e->types);
                            $time = strtotime($e->date);
                        ?>
                            <tr class="<?= $e->date === $summary['next'] ? 'is-next' : '' ?>">
                                <?php if ($i === 0) { ?>
                                    <th scope="rowgroup" rowspan="<?= count($exams) ?>"><?= htmlspecialchars($discipline) ?><small><?= htmlspecialchars($e->teacher) ?></small></th>
                                <?php } ?>
                                <td class="<?= $e->recovery ? 'is-rec' : '' ?>"><strong><?= htmlspecialchars($e->label) ?></strong><?php if ($types !== '') { ?><small><?= htmlspecialchars($types) ?></small><?php } ?></td>
                                <td class="date"><strong><?= date('d/m', $time) ?></strong> <?= $weekdays[(int) date('w', $time)] ?></td>
                                <td>
                                    <?= $topics ? htmlspecialchars(implode('; ', $topics)) : '<em>A divulgar</em>' ?>
                                    <?php if ($e->guide) { ?><small class="note"><?= nl2br(htmlspecialchars($e->guide)) ?></small><?php } ?>
                                </td>
                                <td><?= htmlspecialchars((string) $e->guide_material) ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                <?php } ?>
            </table>
            <p class="rule">Fundo amarelo: próxima avaliação. Datas e assuntos podem mudar; confira no portal. Emitido em <?= date('d/m/Y', strtotime($today)) ?>.</p>
        <?php } ?>

<?php require __DIR__ . '/documentFoot.php'; ?>
