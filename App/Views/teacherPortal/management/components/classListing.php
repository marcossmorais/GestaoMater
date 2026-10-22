<?php if (count($this->view->teacherClasses) > 0) { ?>

    <?php foreach ($this->view->teacherClasses as $key => $class) { ?>

        <?php $shift = substr($class->shift, 0, 1) ?>

        <tr id="class<?= $class->class_id ?>">
            <td class="m-td-title"><?= $class->course ?>-<?= $class->series_acronym ?><?= $shift ?>-<?= $class->ballot ?></td>
            <td data-label="Sala"><?= $class->classroom_number ?></td>
            <td data-label="Período letivo"><?= $class->school_term ?></td>
            <td data-label="Disciplinas"><?= $class->discipline_total ?></td>
            <td data-label="Alunos"><?= $class->student_total ?></td>
            <td class="m-td-action text-right">
                <a href="/portal-docente/chamada" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation()"><i class="fas fa-clipboard-check mr-1"></i>Chamada</a>
            </td>
        </tr>

    <?php } ?>

    <tr class="mt-4 m-tr-summary">
        <td colspan="6" class="font-weight-bold" style="pointer-events:none"><?= count($this->view->teacherClasses) ?> turmas listadas <i class="fas fa-history ml-2"></i></td>
    </tr>

<?php } else { ?>

    <tr class="mt-4 m-tr-summary">
        <td colspan="6" style="pointer-events:none">Nenhuma turma encontrada <i class="fas fa-history ml-2"></i></td>
    </tr>

<?php } ?>
