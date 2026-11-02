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
            <details class="card m-board-guide <?= \App\Tools\Subject::css($g->discipline) ?>">
                <summary>
                    <span class="m-guide-date"><strong><?= htmlspecialchars(substr($g->date, 0, 2)) ?></strong><small>/<?= htmlspecialchars(substr($g->date, 3, 2)) ?></small></span>
                    <span class="m-guide-title">
                        <strong><i class="fas <?= \App\Tools\Subject::icon($g->discipline) ?> m-subj-ico"></i><?= htmlspecialchars($g->discipline) ?></strong>
                        <small><?= $g->recovery ? 'Recuperação' : htmlspecialchars($g->description) ?> · <?= (int) $g->unity ?>º bimestre · <?= htmlspecialchars($g->teacher) ?></small>
                        <?php if ($types = \App\Models\TeacherStudent\Gradebook::typeLabels($g->types)) { ?><small class="m-guide-types"><i class="fas fa-tasks mr-1"></i><?= htmlspecialchars($types) ?></small><?php } ?>
                    </span>
                </summary>
                <div class="m-guide-body">
                    <?php if ($g->guide_topics) { ?>
                        <h6>O que estudar</h6>
                        <ul class="m-guide-topics">
                            <?php foreach (explode("\n", $g->guide_topics) as $topic) { ?><li><i class="fas fa-check-circle"></i><?= htmlspecialchars($topic) ?></li><?php } ?>
                        </ul>
                    <?php } ?>
                    <?php if ($g->guide_material) { ?><p class="m-guide-material"><i class="fas fa-book mr-2"></i><?= htmlspecialchars($g->guide_material) ?></p><?php } ?>
                    <?php if ($g->guide) { ?><p class="m-guide-notes"><i class="fas fa-info-circle mr-2"></i><?= nl2br(htmlspecialchars($g->guide)) ?></p><?php } ?>
                </div>
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
