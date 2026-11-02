<?php
// Roteiro de provas: uma folha por bimestre, com data, assuntos e material de cada avaliação — portal do aluno
use App\Models\TeacherStudent\Gradebook;
use App\Tools\Subject;

$units = (int) $this->view->performanceRule['units'];
$sheets = array_fill(1, $units, []);
$labels = [];
foreach ($this->view->examSheet ?? [] as $e) {
    $label = $e->recovery ? 'Recuperação' : $e->description;
    $sheets[(int) $e->unity][$label][] = $e;
    $labels[$label] = true;
}

// abre no bimestre da próxima avaliação; sem nenhuma pela frente, no último com avaliações
$today = (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
$start = 1;
foreach ($sheets as $u => $groups) if ($groups) $start = $u;
foreach (array_reverse($sheets, true) as $u => $groups) {
    foreach ($groups as $exams) foreach ($exams as $e) if ($e->date >= $today) $start = $u;
}

$weekdays = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];
$lines = function ($text) { return array_filter(array_map('trim', explode("\n", (string) $text)), 'strlen'); };
?>

<div class="row">
    <div class="col-lg-11 mx-auto">

        <div class="m-doc-tools m-no-print">
            <div class="m-seg" data-guide-unit role="group" aria-label="Bimestre">
                <?php for ($u = 1; $u <= $units; $u++) { ?><button type="button" class="<?= $u === $start ? 'is-active' : '' ?>" data-unit="<?= $u ?>"><?= $u ?>º bim.</button><?php } ?>
            </div>
            <?php if ($labels) { ?>
                <div class="m-seg" data-guide-filter role="group" aria-label="Avaliação">
                    <button type="button" class="is-active" data-group="">Todas</button>
                    <?php foreach (array_keys($labels) as $label) { ?><button type="button" data-group="<?= htmlspecialchars($label) ?>"><?= htmlspecialchars($label) ?></button><?php } ?>
                </div>
                <button type="button" class="btn btn-primary" data-print><i class="fas fa-print mr-2" aria-hidden="true"></i>Imprimir</button>
            <?php } ?>
        </div>

        <?php foreach ($sheets as $u => $groups) { ?>
            <div class="m-doc" data-guide-sheet="<?= $u ?>" <?= $u === $start ? '' : 'hidden' ?>>
                <div class="m-doc-head">
                    <?php if (getenv('SCHOOL_NAME')) { ?><small><?= htmlspecialchars(getenv('SCHOOL_NAME')) ?></small><?php } ?>
                    <h5>Roteiro de provas · <?= $u ?>º bimestre</h5>
                    <p>Turma <?= htmlspecialchars($_SESSION['Student']['class']) ?></p>
                </div>

                <p class="m-doc-none" data-guide-none <?= $groups ? 'hidden' : '' ?>>Nenhuma avaliação lançada aqui ainda.</p>

                <?php if ($groups) { ?>
                    <table class="m-doc-table m-guide-table" data-guide-table>
                        <thead>
                            <tr><th class="m-guide-when">Data</th><th class="m-guide-subject">Matéria</th><th>Assuntos</th><th class="m-guide-study">Material e observações</th></tr>
                        </thead>
                        <?php foreach ($groups as $label => $exams) { ?>
                            <tbody data-guide-group="<?= htmlspecialchars($label) ?>">
                                <tr class="m-guide-group"><th colspan="4" scope="colgroup"><?= htmlspecialchars($label) ?></th></tr>
                                <?php foreach ($exams as $e) {
                                    $time = strtotime($e->date);
                                    $topics = $lines($e->guide_topics);
                                    $types = Gradebook::typeLabels($e->types);
                                ?>
                                    <tr class="<?= Subject::css($e->discipline) ?>">
                                        <td class="m-guide-when" data-label="Data"><strong><?= date('d/m', $time) ?></strong> <small><?= $weekdays[(int) date('w', $time)] ?></small></td>
                                        <td class="m-guide-subject" data-label="Matéria"><strong><?= htmlspecialchars($e->discipline) ?></strong><?php if ($types !== '') { ?><small><?= htmlspecialchars($types) ?></small><?php } ?></td>
                                        <td data-label="Assuntos">
                                            <?php if ($topics) { ?>
                                                <ul><?php foreach ($topics as $topic) { ?><li><?= htmlspecialchars($topic) ?></li><?php } ?></ul>
                                            <?php } else { ?>
                                                <span class="m-doc-muted">A divulgar</span>
                                            <?php } ?>
                                        </td>
                                        <td class="m-guide-study" data-label="Material e observações"><?php if ($e->guide_material) { ?><div><?= htmlspecialchars($e->guide_material) ?></div><?php } ?><?php if ($e->guide) { ?><div class="m-doc-muted"><?= nl2br(htmlspecialchars($e->guide)) ?></div><?php } ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        <?php } ?>
                    </table>
                <?php } ?>
            </div>
        <?php } ?>

    </div>
</div>
