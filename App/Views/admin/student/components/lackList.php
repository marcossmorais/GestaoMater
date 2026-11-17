<?php if (count($this->view->lackList) >= 1) {

    foreach ($this->view->lackList as $key => $lack) { ?>

        <tr id="lack<?= htmlspecialchars((string) $lack->lack_id) ?>">
            
            <td class="text-left"><?= htmlspecialchars((string) $lack->discipline_name) ?></td>
            <td><?= htmlspecialchars((string) $lack->unity) ?></td>
            <td><?= htmlspecialchars((string) $lack->total_lack) ?></td>

        </tr>

    <?php } ?>

    <tr class="mt-4">
        <td class="font-weight-bold" colspan="3" style="pointer-events:none"><?= count($this->view->lackList) ?> registros listados <i class="fas fa-history ml-2"></i></td>
    </tr>

<?php } else { ?>

    <tr class="mt-4">
        <td colspan="3" style="pointer-events:none">Nenhuma avaliação encrontada <i class="fas fa-history ml-2"></i></td>
    </tr>

<?php } ?>