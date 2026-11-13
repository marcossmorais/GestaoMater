<?php
// Quadro de horários da turma, como o admin publicou: grade semanal para consultar e imprimir — portal do aluno
use App\Tools\Subject;

require __DIR__ . '/scheduleGrid.php';
?>

<div class="row">
    <div class="col-lg-11 mx-auto">

        <?php if ($slots) { ?>
            <div class="m-doc-tools">
                <a class="btn btn-primary" data-print href="/portal-aluno/horarios/documento?imprimir=1" target="_blank" rel="noopener"><i class="fas fa-print mr-2" aria-hidden="true"></i>Imprimir</a>
            </div>
        <?php } ?>

        <div class="m-doc">
            <div class="m-doc-head">
                <?php if (getenv('SCHOOL_NAME')) { ?><small><?= htmlspecialchars(getenv('SCHOOL_NAME')) ?></small><?php } ?>
                <h5>Quadro de horários</h5>
                <p>Turma <?= htmlspecialchars($_SESSION['Student']['class']) ?><?= $room !== '' ? ' · Sala ' . htmlspecialchars($room) : '' ?></p>
            </div>

            <?php if (!$slots) { ?>
                <p class="m-doc-none">O quadro de horários da sua turma ainda não foi publicado.</p>
            <?php } else { ?>

                <table class="m-doc-table m-sched-table">
                    <thead>
                        <tr>
                            <th class="m-sched-time">Horário</th>
                            <?php foreach ($days as $d) { ?><th class="<?= $d === $today ? 'is-today' : '' ?>"><?= htmlspecialchars($dayNames[$d]) ?></th><?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($slots as $order => $slot) { ?>
                            <?php if ($gap = $gapBefore($order)) { ?>
                                <tr class="m-sched-gap"><td colspan="<?= count($days) + 1 ?>">Intervalo · <?= $gap ?></td></tr>
                            <?php } ?>
                            <tr>
                                <td class="m-sched-time"><strong><?= $slot['start'] ?></strong><small><?= $slot['end'] ?></small></td>
                                <?php foreach ($days as $d) { $lesson = $cellFor($order, $d); ?>
                                    <td class="<?= $lesson ? Subject::css($lesson->nome_disciplina) : '' ?>">
                                        <?php if ($lesson) { ?>
                                            <strong><?= htmlspecialchars($lesson->nome_disciplina) ?></strong>
                                            <small><?= htmlspecialchars($lesson->nome_professor) ?></small>
                                        <?php } else { ?>
                                            <span class="m-doc-muted">—</span>
                                        <?php } ?>
                                    </td>
                                <?php } ?>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>

                <div class="m-sched-days">
                    <?php foreach ($days as $d) { ?>
                        <section class="<?= $d === $today ? 'is-today' : '' ?>">
                            <h6><?= htmlspecialchars($dayNames[$d]) ?></h6>
                            <?php foreach ($slots as $order => $slot) { $lesson = $cellFor($order, $d); ?>
                                <?php if ($gap = $gapBefore($order)) { ?><p class="m-sched-gap">Intervalo · <?= $gap ?></p><?php } ?>
                                <?php if ($lesson) { ?>
                                    <div class="m-sched-lesson <?= Subject::css($lesson->nome_disciplina) ?>">
                                        <span><?= $slot['start'] ?>–<?= $slot['end'] ?></span>
                                        <div><strong><?= htmlspecialchars($lesson->nome_disciplina) ?></strong><small><?= htmlspecialchars($lesson->nome_professor) ?></small></div>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                        </section>
                    <?php } ?>
                </div>

            <?php } ?>
        </div>

    </div>
</div>
