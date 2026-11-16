<?php 

require __DIR__ . '../../../../../config/variables.php';

?>

<?php if (count($this->view->listTeacher) >= 1) { ?>

    <?php foreach ($this->view->listTeacher as $key => $teacher) { ?>

        <?php $photoDir =  "$app_url/assets/img/teacherProfilePhotos/" ?>

        <tr class="" id="teacher<?= htmlspecialchars((string) $teacher->id) ?>">

            <td class="text-right">
                <img class="miniature-photo" src='<?= $teacher->profile_photo == null ? $photoDir . "foto-vazia.jpg" : $photoDir . $teacher->profile_photo ?>' alt="" onerror='this.src="<?= $photoDir . "foto-vazia.jpg" ?>"'>
            </td>

            <td class="text-left"><?= htmlspecialchars((string) $teacher->name) ?></td>

            <?php if ($this->view->typeTeacherList != 'class' && isset($this->view->typeTeacherList)) { ?>

                <td><?= htmlspecialchars((string) $teacher->email) ?></td>
                <td><?= htmlspecialchars((string) $teacher->account_status) ?></td>
                <td><?= htmlspecialchars((string) $teacher->total_discipline) ?></td>

            <?php } else { ?>

                <td><?= htmlspecialchars((string) $teacher->email) ?></td>

                <td class=""><?= htmlspecialchars((string) $teacher->discipline_name) ?></td>

            <?php } ?>
        </tr>

    <?php } ?>

    <tr class="mt-4">
        <td class="font-weight-bold" colspan="6"  style="pointer-events:none"><?= count($this->view->listTeacher) ?> docentes listados <i class="fas fa-history ml-2"></i></td>
    </tr>

<?php } else { ?>

    <tr class="mt-4 thead-light">
        <td class="ml-3" colspan="5" style="pointer-events:none">Nenhum professor encontrado </td>
    </tr>

<?php } ?>