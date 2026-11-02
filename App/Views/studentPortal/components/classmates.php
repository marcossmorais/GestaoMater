<?php
// Aba Turma do aluno: colegas só com nome e foto, sem matrícula, contato, faltas ou notas (LGPD); professores com o e-mail.
$classmates = $this->view->classmates ?? [];
?>

<div class="m-classmates">

    <div class="m-page-head">
        <div>
            <h5>Minha turma</h5>
            <p><?= htmlspecialchars($_SESSION['Student']['class']) ?> · <?= count($classmates) ?> alunos</p>
        </div>
    </div>

    <?php if (!$classmates) { ?>
        <div class="card m-panel m-empty">Nenhum colega encontrado nesta turma.</div>
    <?php } else { ?>
        <ul class="m-classmates-grid">
            <?php foreach ($classmates as $c) {
                $words = preg_split('/\s+/', trim($c->name));
                $initials = mb_strtoupper(mb_substr($words[0], 0, 1) . (count($words) > 1 ? mb_substr(end($words), 0, 1) : ''));
            ?>
                <li class="m-classmate <?= $c->is_me ? 'is-me' : '' ?>">
                    <span class="m-att-avatar"><?php if ($c->photo) { ?><img src="<?= $app_url ?>/assets/img/studentProfilePhotos/<?= htmlspecialchars($c->photo) ?>" alt="" loading="lazy"><?php } else { ?><span style="--h:<?= crc32($c->name) % 360 ?>"><?= htmlspecialchars($initials) ?></span><?php } ?></span>
                    <strong><?= htmlspecialchars($c->name) ?></strong>
                    <?php if ($c->is_me) { ?><small>Você</small><?php } ?>
                </li>
            <?php } ?>
        </ul>
    <?php } ?>

    <h5 class="mt-4 mb-3">Professores</h5>
    <ul class="m-classmates-grid m-class-teachers">
        <?php foreach ($this->view->listTeacher as $t) { ?>
            <li class="m-classmate">
                <img class="m-class-teacher-photo" src="<?= $app_url ?>/assets/img/teacherProfilePhotos/<?= htmlspecialchars($t->photo ?: 'foto-vazia.jpg') ?>" alt="" loading="lazy">
                <strong><?= htmlspecialchars($t->name) ?></strong>
                <small><?= htmlspecialchars(implode(' · ', $t->disciplines)) ?></small>
                <?php if ($t->email) { ?><a class="m-class-teacher-mail" href="mailto:<?= htmlspecialchars($t->email) ?>"><i class="fas fa-envelope mr-1" aria-hidden="true"></i><?= htmlspecialchars($t->email) ?></a><?php } ?>
            </li>
        <?php } ?>
    </ul>

</div>
