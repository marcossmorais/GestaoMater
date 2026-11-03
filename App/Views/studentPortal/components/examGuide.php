<?php
// Roteiro de provas: folha padrão por bimestre, com destaques e um bloco por matéria — portal do aluno
use App\Models\TeacherStudent\Gradebook;
use App\Tools\Subject;

$units = (int) $this->view->performanceRule['units'];
$sheets = array_fill(1, $units, []);
$labels = [];
$subjects = [];
foreach ($this->view->examSheet ?? [] as $e) {
    $e->label = $e->recovery ? 'Recuperação' : $e->description;
    $sheets[(int) $e->unity][$e->discipline][] = $e;
    $labels[$e->label] = true;
    $subjects[$e->discipline] = true;
}
ksort($labels);
ksort($subjects);

// abre no bimestre da próxima avaliação; sem nenhuma pela frente, no último com avaliações
$today = (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
$start = 1;
foreach ($sheets as $u => $blocks) if ($blocks) $start = $u;
foreach (array_reverse($sheets, true) as $u => $blocks) {
    foreach ($blocks as $exams) foreach ($exams as $e) if ($e->date >= $today) $start = $u;
}

$weekdays = ['domingo', 'segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado'];
?>

<div class="row">
    <div class="col-lg-11 mx-auto">

        <div class="m-doc-tools">
            <div class="m-seg" data-guide-unit role="group" aria-label="Bimestre">
                <?php for ($u = 1; $u <= $units; $u++) { ?><button type="button" class="<?= $u === $start ? 'is-active' : '' ?>" data-unit="<?= $u ?>"><?= $u ?>º bim.</button><?php } ?>
            </div>
            <?php if ($labels) { ?>
                <div class="m-seg" data-guide-filter role="group" aria-label="Avaliação">
                    <button type="button" class="is-active" data-group="">Todas</button>
                    <?php foreach (array_keys($labels) as $label) { ?><button type="button" data-group="<?= htmlspecialchars($label) ?>"><?= htmlspecialchars($label) ?></button><?php } ?>
                </div>
                <select class="form-control m-rt-select" data-guide-subject aria-label="Matéria">
                    <option value="">Todas as matérias</option>
                    <?php foreach (array_keys($subjects) as $name) { ?><option value="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?></option><?php } ?>
                </select>
                <a class="btn btn-primary" data-print data-guide-print href="/portal-aluno/roteiro/documento?bimestre=<?= $start ?>&amp;imprimir=1" target="_blank" rel="noopener"><i class="fas fa-print mr-2" aria-hidden="true"></i>Imprimir</a>
            <?php } ?>
        </div>

        <?php foreach ($sheets as $u => $blocks) {
            $all = $blocks ? array_merge(...array_values($blocks)) : [];
            $summary = Gradebook::examSummary($all, $today);
            $withGuide = $summary['withGuide'];
            $nextDate = $summary['next'];
            $nextNames = $summary['nextNames'];
        ?>
            <div class="m-doc m-rt" data-guide-sheet="<?= $u ?>" <?= $u === $start ? '' : 'hidden' ?>>

                <div class="m-rt-head">
                    <div class="m-rt-head-title">
                        <?php if (getenv('SCHOOL_NAME')) { ?><small><?= htmlspecialchars(getenv('SCHOOL_NAME')) ?></small><?php } ?>
                        <h5>Roteiro de provas</h5>
                    </div>
                    <dl>
                        <div><dt>Turma</dt><dd><?= htmlspecialchars($_SESSION['Student']['class']) ?></dd></div>
                        <div><dt>Período</dt><dd><?= $u ?>º bimestre</dd></div>
                    </dl>
                </div>

                <?php if ($all) { ?>
                    <div class="m-rt-highlights">
                        <div class="<?= $nextDate ? 'is-next' : '' ?>">
                            <small>Próxima avaliação</small>
                            <?php if ($nextDate) { ?>
                                <strong><?= date('d/m', strtotime($nextDate)) ?> · <?= $weekdays[(int) date('w', strtotime($nextDate))] ?></strong>
                                <span><?= htmlspecialchars(implode(', ', $nextNames)) ?></span>
                            <?php } else { ?>
                                <strong>Nenhuma pela frente</strong>
                                <span>As provas deste bimestre já passaram.</span>
                            <?php } ?>
                        </div>
                        <div>
                            <small>Período das provas</small>
                            <strong><?= date('d/m', strtotime($summary['first'])) ?> a <?= date('d/m', strtotime($summary['last'])) ?></strong>
                            <span><?= count($all) ?> avaliaç<?= count($all) === 1 ? 'ão' : 'ões' ?> em <?= count($blocks) ?> matéria<?= count($blocks) === 1 ? '' : 's' ?></span>
                        </div>
                        <div class="<?= $withGuide < count($all) ? 'is-pending' : '' ?>">
                            <small>Assuntos divulgados</small>
                            <strong><?= $withGuide ?> de <?= count($all) ?></strong>
                            <span><?= $withGuide < count($all) ? (count($all) - $withGuide) . ' ainda a divulgar' : 'Todas com assuntos' ?></span>
                        </div>
                    </div>
                <?php } ?>

                <p class="m-doc-none" data-guide-none <?= $all ? 'hidden' : '' ?>>Nenhuma avaliação lançada aqui ainda.</p>

                <?php foreach ($blocks as $discipline => $exams) { ?>
                    <section class="m-rt-subject <?= Subject::css($discipline) ?>" data-subject="<?= htmlspecialchars($discipline) ?>">
                        <h6>
                            <span><i class="fas <?= Subject::icon($discipline) ?> mr-2" aria-hidden="true"></i><?= htmlspecialchars($discipline) ?></span>
                            <small><?= htmlspecialchars($exams[0]->teacher) ?></small>
                        </h6>
                        <table class="m-doc-table m-rt-table">
                            <thead>
                                <tr><th class="m-rt-exam">Avaliação</th><th class="m-rt-date">Data</th><th>Conteúdo</th><th class="m-rt-where">Onde estudar</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($exams as $e) {
                                    $time = strtotime($e->date);
                                    $topics = Gradebook::guideTopics($e->guide_topics);
                                    $types = Gradebook::typeLabels($e->types);
                                    $state = $e->date === $nextDate ? 'is-next' : ($e->date < $today ? 'is-past' : '');
                                ?>
                                    <tr class="<?= $state ?> <?= $e->recovery ? 'is-recovery' : '' ?>" data-group="<?= htmlspecialchars($e->label) ?>">
                                        <td class="m-rt-exam" data-label="Avaliação">
                                            <strong><?= htmlspecialchars($e->label) ?></strong>
                                            <?php if ($types !== '') { ?><small><?= htmlspecialchars($types) ?></small><?php } ?>
                                        </td>
                                        <td class="m-rt-date" data-label="Data">
                                            <strong><?= date('d/m', $time) ?></strong>
                                            <small><?= $weekdays[(int) date('w', $time)] ?></small>
                                            <?php if ($state === 'is-next') { ?><b class="m-rt-tag"><?= $e->date === $today ? 'Hoje' : 'Próxima' ?></b><?php } ?>
                                        </td>
                                        <td data-label="Conteúdo">
                                            <?php if ($topics) { ?>
                                                <ul><?php foreach ($topics as $topic) { ?><li><?= htmlspecialchars($topic) ?></li><?php } ?></ul>
                                            <?php } else { ?>
                                                <b class="m-rt-pending">A divulgar</b>
                                            <?php } ?>
                                            <?php if ($e->guide) { ?><p class="m-rt-note"><i class="fas fa-exclamation-circle mr-1" aria-hidden="true"></i><?= nl2br(htmlspecialchars($e->guide)) ?></p><?php } ?>
                                        </td>
                                        <td class="m-rt-where" data-label="Onde estudar"><?= htmlspecialchars((string) $e->guide_material) ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </section>
                <?php } ?>

            </div>
        <?php } ?>

    </div>
</div>
