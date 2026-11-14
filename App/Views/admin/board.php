<?php
use App\Models\TeacherStudent\ClassBoard;
use App\Models\TeacherStudent\GeneralBoard;

$types = ClassBoard::TYPES;
$audiences = GeneralBoard::AUDIENCES;
$posts = $this->view->posts;
$current = $this->view->audience;
?>

<section id="adminBoard" class="m-att">

    <div class="row main-container">

        <div class="col-lg-11 mx-auto">

            <div class="m-page-head">
                <div>
                    <h5>Mural geral</h5>
                    <p>Avisos da coordenação por público. Alunos e responsáveis veem no portal do aluno (a família entra com o login do aluno), acima dos avisos de cada matéria. Professores veem no início do portal docente. Até <?= GeneralBoard::MAX_PINNED ?> fixados no topo por público.</p>
                </div>
            </div>

            <form class="card m-panel m-post-form mb-3" data-admin-board-form novalidate>
                <div class="m-att-field">
                    <span class="m-att-label">Para quem</span>
                    <div class="m-board-types m-board-audiences" data-board-audiences role="group" aria-label="Público">
                        <?php foreach ($audiences as $key => $a) { ?>
                            <button type="button" class="m-board-type <?= $key === 'alunos' ? 'is-active' : '' ?>" data-audience="<?= $key ?>" aria-pressed="<?= $key === 'alunos' ? 'true' : 'false' ?>"><i class="fas <?= $a['icon'] ?>" aria-hidden="true"></i><?= htmlspecialchars($a['label']) ?></button>
                        <?php } ?>
                    </div>
                </div>
                <fieldset class="m-att-field m-class-picks" data-board-classes>
                    <legend class="m-att-label">Turmas <small>(nenhuma marcada = toda a escola)</small></legend>
                    <div class="m-class-picks-list">
                        <?php foreach ($this->view->classes as $c) { ?>
                            <label><input type="checkbox" name="classes" value="<?= (int) $c->id ?>"> <?= htmlspecialchars($c->label) ?></label>
                        <?php } ?>
                    </div>
                    <p class="m-class-picks-summary" data-board-classes-summary>Toda a escola</p>
                </fieldset>
                <p class="m-muted-note d-none" data-board-teachers-note>Vai para todos os professores ativos.</p>
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
                <div class="m-post-form-actions">
                    <label class="m-overview-low mb-0"><input type="checkbox" name="pinned"> Fixar no topo</label>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-2"></i>Publicar</button>
                </div>
            </form>

            <nav class="m-seg m-board-filter mb-3" aria-label="Filtrar por público">
                <a class="btn <?= $current === '' ? 'is-active' : '' ?>" href="/admin/mural">Todos</a>
                <?php foreach ($audiences as $key => $a) { ?>
                    <a class="btn <?= $current === $key ? 'is-active' : '' ?>" href="/admin/mural?publico=<?= $key ?>"><?= htmlspecialchars($a['label']) ?></a>
                <?php } ?>
            </nav>

            <?php if (!$posts) { ?><div class="card m-panel m-empty">Nenhum aviso geral publicado<?= $current ? ' para ' . htmlspecialchars(mb_strtolower($audiences[$current]['label'])) : '' ?>.</div><?php } ?>

            <?php foreach ($posts as $p) {
                $type = $types[$p->type] ?? $types['aviso'];
                $audience = $audiences[$p->audience] ?? $audiences['alunos'];
                $read = (int) $p->read_total;
                $of = (int) $p->audience_total;
                $target = $p->audience === 'professores' ? 'Todos os professores' : ($p->class_label ?: 'Toda a escola');
            ?>
                <article class="card m-board-post is-type-<?= htmlspecialchars($p->type) ?> <?= $p->pinned ? 'is-pinned' : '' ?>" data-admin-post="<?= (int) $p->id ?>">
                    <header>
                        <span class="m-board-tag"><i class="fas <?= $type['icon'] ?>" aria-hidden="true"></i><?= htmlspecialchars($type['label']) ?></span>
                        <span class="m-board-audience"><i class="fas <?= $audience['icon'] ?>" aria-hidden="true"></i><?= htmlspecialchars($audience['label']) ?></span>
                        <?php if ($p->pinned) { ?><span class="m-board-pin"><i class="fas fa-thumbtack mr-1"></i>Fixado</span><?php } ?>
                        <strong><?= htmlspecialchars($target) ?></strong>
                        <small><?= htmlspecialchars($p->posted_at) ?> · <?= $read ?> de <?= $of ?> <?= ['professores' => 'professores', 'responsaveis' => 'famílias', 'alunos' => 'alunos'][$p->audience] ?? 'alunos' ?> leram<?= $of ? ' (' . round($read * 100 / $of) . '%)' : '' ?></small>
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
        const classes = root.querySelector('[data-board-classes]');
        const summary = root.querySelector('[data-board-classes-summary]');
        const teachersNote = root.querySelector('[data-board-teachers-note]');
        let type = 'aviso';
        let audience = 'alunos';

        const errors = {
            'limite-fixados': 'Já há 3 avisos fixados para esse público. Desafixe um antes.',
            'texto-invalido': 'Escreva o aviso (até 2.000 caracteres).',
            'turma-invalida': 'Uma das turmas escolhidas não existe mais. Recarregue a página.'
        };

        async function post(url, body) {
            const response = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body) });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(result.error || 'falha');
        }

        function fail(e, fallback) {
            window.alert(errors[e.message] || fallback);
        }

        function pick(group, attr, button) {
            root.querySelectorAll('[' + group + '] [' + attr + ']').forEach((b) => {
                b.classList.toggle('is-active', b === button);
                b.setAttribute('aria-pressed', b === button);
            });
        }

        root.querySelector('[data-board-types]').addEventListener('click', (event) => {
            const button = event.target.closest('[data-type]');
            if (!button) return;
            type = button.dataset.type;
            pick('data-board-types', 'data-type', button);
        });

        root.querySelector('[data-board-audiences]').addEventListener('click', (event) => {
            const button = event.target.closest('[data-audience]');
            if (!button) return;
            audience = button.dataset.audience;
            pick('data-board-audiences', 'data-audience', button);
            classes.classList.toggle('d-none', audience === 'professores');
            teachersNote.classList.toggle('d-none', audience !== 'professores');
        });

        classes.addEventListener('change', () => {
            const checked = classes.querySelectorAll('input:checked').length;
            summary.textContent = checked ? checked + (checked === 1 ? ' turma escolhida' : ' turmas escolhidas') : 'Toda a escola';
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const data = new FormData(form);
            const text = (data.get('text') || '').trim();
            if (!text) return form.querySelector('[name="text"]').focus();
            const picked = audience === 'professores' ? [] : data.getAll('classes').map((v) => parseInt(v, 10));
            try {
                await post('/admin/mural/publicar', { text, type, audience, classes: picked, pinned: data.get('pinned') === 'on' });
                window.location.reload();
            } catch (e) {
                fail(e, 'Não foi possível publicar. Tente de novo.');
            }
        });

        root.addEventListener('click', async (event) => {
            const pin = event.target.closest('[data-admin-pin]');
            const del = event.target.closest('[data-admin-delete]');
            if (!pin && !del) return;
            if (del && !window.confirm('Excluir este aviso? Ele some para todos que recebem.')) return;
            const id = parseInt((pin || del).closest('[data-admin-post]').dataset.adminPost, 10);
            try {
                if (pin) await post('/admin/mural/fixar', { id, pinned: pin.dataset.adminPin === '1' });
                if (del) await post('/admin/mural/excluir', { id });
                window.location.reload();
            } catch (e) {
                fail(e, 'Não foi possível concluir. Tente de novo.');
            }
        });
    })();
</script>
