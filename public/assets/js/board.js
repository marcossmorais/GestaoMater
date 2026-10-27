// Mater — Mural da turma (portal do docente)
(function () {

    const config = window.MATER_BOARD || { classes: [] };
    const root = document.getElementById('board');
    if (!root) return;

    const $ = (sel) => root.querySelector(sel);
    const steps = { pick: $('[data-bd-step="pick"]'), list: $('[data-bd-step="list"]') };
    const form = $('[data-bd-form]');
    const postsBox = $('[data-bd-posts]');
    const MAX_PINNED = 3;

    const state = { classId: null, posts: [], busy: false };

    const errors = {
        'sem-permissao': 'Você não tem permissão para isso nesta turma.',
        'texto-invalido': 'Escreva o aviso (até 2000 caracteres).',
        'limite-fixados': 'A turma já tem 3 avisos fixados. Desafixe um antes.',
        'nao-autenticado': 'Sua sessão expirou. Entre novamente.'
    };

    function escapeHtml(text) {
        return String(text).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function toast(message, type) {
        if (typeof tools !== 'undefined' && typeof tools.showToast === 'function') {
            tools.showToast(message, type === 'error' ? 'bg-danger' : 'bg-success');
        }
    }

    function show(step) {
        Object.keys(steps).forEach((k) => { steps[k].hidden = k !== step; });
        window.scrollTo(0, 0);
    }

    async function api(url, body) {
        const response = await fetch(url, body ? {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(body)
        } : { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.error || 'erro');
        return data;
    }

    root.addEventListener('click', (event) => {
        const card = event.target.closest('[data-bd-class]');
        if (card) openClass(parseInt(card.dataset.bdClass, 10));
    });

    $('[data-bd-back]').addEventListener('click', () => show('pick'));

    async function openClass(classId) {
        const disciplines = config.classes.filter((c) => c.classId === classId);
        if (!disciplines.length) return;

        state.classId = classId;
        const first = disciplines[0];
        $('[data-bd-title]').textContent = `${first.label} · Mural`;
        $('[data-bd-subtitle]').textContent = `${first.course} · ${first.shift} · ${first.students} alunos`;

        const select = $('[data-bd-discipline]');
        select.innerHTML = disciplines.map((c) => `<option value="${c.id}"${c.own ? ' selected' : ''}>${escapeHtml(c.discipline)}</option>`).join('');
        $('[data-bd-discipline-field]').hidden = disciplines.length < 2;

        form.reset();
        postsBox.innerHTML = '<div class="m-empty">Carregando…</div>';
        show('list');
        await load();
    }

    async function load() {
        try {
            const data = await api(`/portal-docente/mural/avisos?turma=${state.classId}`);
            state.posts = data.posts;
            render();
        } catch (e) {
            postsBox.innerHTML = `<div class="m-empty">${escapeHtml(errors[e.message] || 'Não foi possível carregar os avisos.')}</div>`;
        }
    }

    function render() {
        const pinned = state.posts.filter((p) => p.pinned).length;
        $('[data-bd-pinned-count]').textContent = `(${pinned}/${MAX_PINNED})`;

        if (!state.posts.length) {
            postsBox.innerHTML = '<div class="card m-panel m-empty">Nenhum aviso publicado nesta turma.</div>';
            return;
        }

        postsBox.innerHTML = state.posts.map((p) => `
            <article class="card m-board-post${p.pinned ? ' is-pinned' : ''}" data-post="${p.id}">
                <header>
                    ${p.pinned ? '<span class="m-pill m-pill-warn"><i class="fas fa-thumbtack mr-1"></i>Fixado</span>' : ''}
                    <strong>${escapeHtml(p.discipline)}</strong>
                    <small>${escapeHtml(p.teacher)} · ${escapeHtml(p.postedAt)} · ${p.readTotal} leitura${p.readTotal === 1 ? '' : 's'}</small>
                </header>
                <p>${escapeHtml(p.text).replace(/\n/g, '<br>')}</p>
                ${p.canManage ? `
                <footer class="m-post-actions">
                    <button type="button" class="btn btn-sm btn-light" data-pin="${p.pinned ? 0 : 1}"><i class="fas fa-thumbtack mr-1"></i>${p.pinned ? 'Desafixar' : 'Fixar'}</button>
                    <button type="button" class="btn btn-sm btn-light text-danger" data-delete><i class="fas fa-trash-alt mr-1"></i>Excluir</button>
                </footer>` : ''}
            </article>`).join('');
    }

    postsBox.addEventListener('click', async (event) => {
        const post = event.target.closest('[data-post]');
        if (!post || state.busy) return;
        const id = parseInt(post.dataset.post, 10);
        const pin = event.target.closest('[data-pin]');
        const del = event.target.closest('[data-delete]');
        if (!pin && !del) return;
        if (del && !window.confirm('Excluir este aviso? Os alunos deixam de vê-lo.')) return;

        state.busy = true;
        try {
            if (pin) await api('/portal-docente/mural/fixar', { id, pinned: pin.dataset.pin === '1' });
            if (del) await api('/portal-docente/mural/excluir', { id });
            await load();
        } catch (e) {
            toast(errors[e.message] || 'Não foi possível concluir.', 'error');
        } finally {
            state.busy = false;
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (state.busy) return;

        const data = new FormData(form);
        const text = (data.get('text') || '').trim();
        if (!text) {
            toast(errors['texto-invalido'], 'error');
            return;
        }

        state.busy = true;
        $('[data-bd-submit]').disabled = true;
        try {
            await api('/portal-docente/mural/publicar', {
                disciplineClass: parseInt(data.get('disciplineClass'), 10),
                text,
                pinned: data.get('pinned') === 'on'
            });
            form.reset();
            toast('Aviso publicado');
            await load();
        } catch (e) {
            toast(errors[e.message] || 'Não foi possível publicar.', 'error');
        } finally {
            state.busy = false;
            $('[data-bd-submit]').disabled = false;
        }
    });

})();
