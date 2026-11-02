<?php
use App\Models\TeacherStudent\ClassBoard;

$types = ClassBoard::TYPES;
$posts = $this->view->posts;
?>

<link rel="stylesheet" href="<?= asset('/assets/css/edu.css') ?>">

<section id="adminBoard" class="m-att">

    <div class="row main-container">

        <div class="col-lg-11 mx-auto">

            <div class="m-page-head">
                <div>
                    <h5>Mural geral</h5>
                    <p>O que você publica aqui aparece para os alunos acima dos avisos de cada matéria.</p>
                </div>
            </div>

            <form class="card m-panel m-post-form mb-3" data-admin-board-form novalidate>
                <div class="m-att-field">
                    <span class="m-att-label">Tipo de aviso</span>
                    <div class="m-board-types" data-board-types>
                        <?php foreach ($types as $key => $t) { ?>
                            <button type="button" class="m-board-type is-type-<?= $key ?> <?= $key === 'aviso' ? 'is-active' : '' ?>" data-type="<?= $key ?>" aria-pressed="<?= $key === 'aviso' ? 'true' : 'false' ?>"><i class="fas <?= $t['icon'] ?>" aria-hidden="true"></i><?= htmlspecialchars($t['label']) ?></button>
                        <?php } ?>
                    </div>
                </div>
                <label class="m-att-field">
                    <span class="m-att-label">Aviso</span>
                    <textarea class="form-control" name="text" rows="3" maxlength="2000" required placeholder="Ex.: Reunião de pais na quarta-feira, às 19h"></textarea>
                </label>
                <label class="m-att-field">
                    <span class="m-att-label">Para quem</span>
                    <select class="form-control" name="class">
                        <option value="0">Toda a escola</option>
                        <?php foreach ($this->view->classes as $c) { ?>
                            <option value="<?= (int) $c->id ?>"><?= htmlspecialchars($c->label) ?></option>
                        <?php } ?>
                    </select>
                </label>
                <div class="m-post-form-actions">
                    <label class="m-overview-low mb-0"><input type="checkbox" name="pinned"> Fixar no topo</label>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-2"></i>Publicar</button>
                </div>
            </form>

            <?php if (!$posts) { ?><div class="card m-panel m-empty">Nenhum aviso geral publicado.</div><?php } ?>

            <?php foreach ($posts as $p) { $type = $types[$p->type] ?? $types['aviso']; ?>
                <article class="card m-board-post is-type-<?= htmlspecialchars($p->type) ?> <?= $p->pinned ? 'is-pinned' : '' ?>" data-admin-post="<?= (int) $p->id ?>">
                    <header>
                        <span class="m-board-tag"><i class="fas <?= $type['icon'] ?>" aria-hidden="true"></i><?= htmlspecialchars($type['label']) ?></span>
                        <?php if ($p->pinned) { ?><span class="m-board-pin"><i class="fas fa-thumbtack mr-1"></i>Fixado</span><?php } ?>
                        <strong><?= $p->class_label ? htmlspecialchars($p->class_label) : 'Toda a escola' ?></strong>
                        <small><?= htmlspecialchars($p->posted_at) ?> · <?= (int) $p->read_total ?> leitura<?= (int) $p->read_total === 1 ? '' : 's' ?></small>
                    </header>
                    <p><?= nl2br(htmlspecialchars($p->text)) ?></p>
                    <footer class="m-post-actions">
                        <button type="button" class="btn btn-sm btn-light" data-admin-pin="<?= $p->pinned ? 0 : 1 ?>"><i class="fas fa-thumbtack mr-1"></i><?= $p->pinned ? 'Desafixar' : 'Fixar' ?></button>
                        <button type="button" class="btn btn-sm btn-light text-danger" data-admin-delete><i class="fas fa-trash-alt mr-1"></i>Excluir</button>
                    </footer>
                </article>
            <?php } ?>

        </div>
    </div>

</section>

<script>
    (function () {
        const root = document.getElementById('adminBoard');
        const form = root.querySelector('[data-admin-board-form]');
        let type = 'aviso';

        async function post(url, body) {
            const response = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body) });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(result.error || 'falha');
        }

        root.querySelector('[data-board-types]').addEventListener('click', (event) => {
            const button = event.target.closest('[data-type]');
            if (!button) return;
            type = button.dataset.type;
            root.querySelectorAll('[data-board-types] [data-type]').forEach((b) => {
                b.classList.toggle('is-active', b === button);
                b.setAttribute('aria-pressed', b === button);
            });
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const data = new FormData(form);
            const text = (data.get('text') || '').trim();
            if (!text) return form.querySelector('[name="text"]').focus();
            try {
                await post('/admin/mural/publicar', { text, type, class: parseInt(data.get('class'), 10), pinned: data.get('pinned') === 'on' });
                window.location.reload();
            } catch (e) {
                window.alert('Não foi possível publicar. Tente de novo.');
            }
        });

        root.addEventListener('click', async (event) => {
            const pin = event.target.closest('[data-admin-pin]');
            const del = event.target.closest('[data-admin-delete]');
            if (!pin && !del) return;
            if (del && !window.confirm('Excluir este aviso? Os alunos deixam de vê-lo.')) return;
            const id = parseInt((pin || del).closest('[data-admin-post]').dataset.adminPost, 10);
            try {
                if (pin) await post('/admin/mural/fixar', { id, pinned: pin.dataset.adminPin === '1' });
                if (del) await post('/admin/mural/excluir', { id });
                window.location.reload();
            } catch (e) {
                window.alert('Não foi possível concluir. Tente de novo.');
            }
        });
    })();
</script>
