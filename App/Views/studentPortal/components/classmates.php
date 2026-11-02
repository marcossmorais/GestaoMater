<?php
// Aba Turma do aluno: só os colegas, com nome e foto. Sem matrícula, contato, faltas ou notas (LGPD).
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

</div>
