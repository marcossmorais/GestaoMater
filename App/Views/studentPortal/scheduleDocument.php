<?php
// Quadro de horários em folha A4, como o admin publicou
require __DIR__ . '/components/scheduleGrid.php';

$docTitle = 'Quadro de horários';
$docHeading = 'Quadro de horários';
require __DIR__ . '/documentHead.php';
?>

        <div class="meta">
            <div><span>Turma</span><strong><?= htmlspecialchars($_SESSION['Student']['class']) ?></strong></div>
            <?php if ($room !== '') { ?><div><span>Sala</span><strong><?= htmlspecialchars($room) ?></strong></div><?php } ?>
        </div>

        <?php if (!$slots) { ?>
            <p class="none">O quadro de horários da sua turma ainda não foi publicado.</p>
        <?php } else { ?>
            <table class="sheet-table sheet-schedule">
                <thead>
                    <tr>
                        <th class="time">Horário</th>
                        <?php foreach ($days as $d) { ?><th><?= htmlspecialchars($dayNames[$d]) ?></th><?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($slots as $order => $slot) { ?>
                        <?php if ($gap = $gapBefore($order)) { ?>
                            <tr class="gap"><td colspan="<?= count($days) + 1 ?>">Intervalo · <?= $gap ?></td></tr>
                        <?php } ?>
                        <tr>
                            <td class="time"><strong><?= $slot['start'] ?></strong><small><?= $slot['end'] ?></small></td>
                            <?php foreach ($days as $d) { $lesson = $grid[$order][$d] ?? null; ?>
                                <td><?php if ($lesson) { ?><strong><?= htmlspecialchars($lesson->nome_disciplina) ?></strong><small><?= htmlspecialchars($lesson->nome_professor) ?></small><?php } else { ?>–<?php } ?></td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } ?>

<?php require __DIR__ . '/documentFoot.php'; ?>
