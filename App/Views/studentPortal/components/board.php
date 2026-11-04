<?php
// Mural: área geral da coordenação no topo e avisos separados por matéria — portal do aluno
$board = $this->view->board ?? [];
$general = $this->view->generalBoard ?? [];
$types = \App\Models\TeacherStudent\ClassBoard::TYPES;
$disciplines = [];
foreach ($board as $p) $disciplines[$p->discipline] = ($disciplines[$p->discipline] ?? 0) + ($p->read_by_me ? 0 : 1);
ksort($disciplines);

$postCard = function ($post, $title, $meta, $readAttr) use ($types) {
    $type = $types[$post->type] ?? $types['aviso'];
    ob_start(); ?>
    <article class="card m-board-post is-type-<?= htmlspecialchars($post->type) ?><?= $post->pinned ? ' is-pinned' : '' ?><?= $post->read_by_me ? ' is-read' : '' ?>" <?= $readAttr ?>="<?= (int) $post->id ?>" data-discipline="<?= htmlspecialchars($title) ?>">
        <header>
            <span class="m-board-tag"><i class="fas <?= $type['icon'] ?>" aria-hidden="true"></i><?= htmlspecialchars($type['label']) ?></span>
            <?php if ($post->pinned) { ?><span class="m-board-pin"><i class="fas fa-thumbtack mr-1"></i>Fixado</span><?php } ?>
            <strong><?= htmlspecialchars($title) ?></strong>
            <small><?= htmlspecialchars($meta) ?></small>
        </header>
        <p><?= nl2br(htmlspecialchars($post->text)) ?></p>
        <footer>
            <?php if ($post->read_by_me) { ?>
                <span class="m-board-read"><i class="fas fa-check-double mr-1"></i>Lido</span>
            <?php } else { ?>
                <button type="button" class="btn btn-sm btn-outline-primary" data-board-read><i class="fas fa-check mr-1"></i>Marcar como lido</button>
            <?php } ?>
        </footer>
    </article>
    <?php return ob_get_clean();
};
?>

<section class="m-board" id="studentBoard">

    <h5 class="title-mural"><i class="fas fa-school mr-2"></i>Geral da escola</h5>

    <?php if (!$general) { ?>
        <div class="card m-board-empty">Nenhum aviso da coordenação por enquanto.</div>
    <?php } ?>

    <?php foreach ($general as $post) echo $postCard($post, 'Coordenação', $post->posted_at, 'data-board-general'); ?>

    <h5 class="title-mural mt-4"><i class="fas fa-thumbtack mr-2"></i>Por matéria</h5>

    <?php if (!$board) { ?>
        <div class="card m-board-empty">Nenhum aviso dos professores por enquanto.</div>
    <?php } else { ?>
        <div class="m-filter-chips" data-board-filter role="group" aria-label="Filtrar por matéria">
            <button type="button" class="m-att-subject is-active" data-board-value="">Todas</button>
            <?php foreach ($disciplines as $name => $unread) { ?>
                <button type="button" class="m-att-subject" data-board-value="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?><?php if ($unread) { ?> <b class="m-board-count"><?= (int) $unread ?></b><?php } ?></button>
            <?php } ?>
        </div>
        <?php foreach ($board as $post) echo $postCard($post, $post->discipline, $post->teacher . ' · ' . $post->posted_at, 'data-board-post'); ?>
    <?php } ?>
</section>

<script>
    document.getElementById('studentBoard').addEventListener('click', async function (event) {
        const chip = event.target.closest('[data-board-value]');
        if (chip) {
            this.querySelectorAll('[data-board-value]').forEach((b) => b.classList.toggle('is-active', b === chip));
            this.querySelectorAll('[data-board-post]').forEach((p) => { p.hidden = chip.dataset.boardValue !== '' && p.dataset.discipline !== chip.dataset.boardValue; });
            return;
        }

        const button = event.target.closest('[data-board-read]');
        if (!button) return;
        const post = button.closest('[data-board-post], [data-board-general]');
        const general = post.hasAttribute('data-board-general');
        button.disabled = true;
        try {
            const response = await fetch(general ? '/portal-aluno/mural/geral/lido' : '/portal-aluno/mural/lido', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: 'id=' + encodeURIComponent(general ? post.dataset.boardGeneral : post.dataset.boardPost)
            });
            if (!response.ok) throw new Error();
            post.classList.add('is-read');
            if (!general) {
                const count = this.querySelector(`[data-board-value="${CSS.escape(post.dataset.discipline)}"] .m-board-count`);
                if (count) {
                    const left = parseInt(count.textContent, 10) - 1;
                    if (left > 0) count.textContent = left; else count.remove();
                }
            }
            button.outerHTML = '<span class="m-board-read"><i class="fas fa-check-double mr-1"></i>Lido</span>';
        } catch (e) {
            button.disabled = false;
        }
    });
</script>
