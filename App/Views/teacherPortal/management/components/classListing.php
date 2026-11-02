<?php if (count($this->view->teacherClasses) > 0) { ?>

    <div class="m-att-classes">
        <?php foreach ($this->view->teacherClasses as $class) { ?>
            <a href="/portal-docente/turma?id=<?= (int) $class->class_id ?>" class="card m-att-class">
                <span class="m-att-class-badge"><?= htmlspecialchars($class->series_acronym) ?>ª <?= htmlspecialchars($class->ballot) ?></span>
                <span class="m-att-class-body">
                    <strong><?= htmlspecialchars($class->course) ?> · <?= htmlspecialchars($class->shift) ?></strong>
                    <small>Sala <?= htmlspecialchars($class->classroom_number) ?> · <?= (int) $class->student_total ?> alunos · <?= (int) $class->discipline_total ?> disciplina<?= $class->discipline_total == 1 ? '' : 's' ?> sua<?= $class->discipline_total == 1 ? '' : 's' ?></small>
                </span>
                <i class="fas fa-chevron-right m-att-class-go"></i>
            </a>
        <?php } ?>
    </div>

<?php } else { ?>

    <div class="card m-panel m-empty">Nenhuma turma vinculada no período letivo ativo.</div>

<?php } ?>
