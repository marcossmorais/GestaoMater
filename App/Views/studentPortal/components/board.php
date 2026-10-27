<?php
// Mural em destaque (fixados primeiro) e roteiro de provas — portal do aluno
$board = $this->view->board ?? [];
$guides = $this->view->guides ?? [];
$pinned = array_values(array_filter($board, function ($p) { return $p->pinned; }));
$others = array_values(array_filter($board, function ($p) { return !$p->pinned; }));
?>

<section class="m-board" id="studentBoard">

    <h5 class="title-mural"><i class="fas fa-thumbtack mr-2"></i>Mural da turma</h5>

    <?php if (!$board) { ?>
        <div class="card m-board-empty">Nenhum aviso da escola ou dos professores por enquanto.</div>
    <?php } ?>

    <?php foreach ([['items' => $pinned, 'pinned' => true], ['items' => array_slice($others, 0, 5), 'pinned' => false]] as $group) {
        foreach ($group['items'] as $post) { ?>
            <article class="card m-board-post<?= $group['pinned'] ? ' is-pinned' : '' ?><?= $post->read_by_me ? ' is-read' : '' ?>" data-board-post="<?= (int) $post->id ?>">
                <header>
                    <?php if ($group['pinned']) { ?><span class="m-pill m-pill-warn"><i class="fas fa-thumbtack mr-1"></i>Fixado</span><?php } ?>
                    <strong><?= htmlspecialchars($post->discipline) ?></strong>
                    <small><?= htmlspecialchars($post->teacher) ?> · <?= htmlspecialchars($post->posted_at) ?></small>
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
    <?php }
    } ?>

    <?php if ($guides) { ?>
        <h5 class="title-mural mt-4"><i class="fas fa-route mr-2"></i>Roteiro de provas</h5>
        <?php foreach ($guides as $g) { ?>
            <details class="card m-board-guide">
                <summary>
                    <span class="m-pill m-pill-info"><?= htmlspecialchars($g->date) ?></span>
                    <strong><?= htmlspecialchars($g->discipline) ?></strong>
                    <small><?= $g->recovery ? 'Recuperação · ' : '' ?><?= htmlspecialchars($g->description) ?> · <?= (int) $g->unity ?>º bimestre</small>
                </summary>
                <p><?= nl2br(htmlspecialchars($g->guide)) ?></p>
            </details>
        <?php } ?>
    <?php } ?>
</section>

<script>
    document.getElementById('studentBoard').addEventListener('click', async function (event) {
        const button = event.target.closest('[data-board-read]');
        if (!button) return;
        const post = button.closest('[data-board-post]');
        button.disabled = true;
        try {
            const response = await fetch('/portal-aluno/mural/lido', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: 'id=' + encodeURIComponent(post.dataset.boardPost)
            });
            if (!response.ok) throw new Error();
            post.classList.add('is-read');
            button.outerHTML = '<span class="m-board-read"><i class="fas fa-check-double mr-1"></i>Lido</span>';
        } catch (e) {
            button.disabled = false;
        }
    });
</script>
