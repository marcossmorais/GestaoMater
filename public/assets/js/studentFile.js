// Mater — Ficha do aluno no portal do docente: observações rápidas (um toque) e texto livre
(function () {

    const config = window.MATER_STUDENT || {};
    const root = document.getElementById('studentFile');
    if (!root) return;

    const notes = root.querySelector('[data-notes]');
    let busy = false;

    function escapeHtml(text) {
        return String(text).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function toast(message, type) {
        if (typeof tools !== 'undefined' && typeof tools.showToast === 'function') {
            tools.showToast(message, type === 'error' ? 'bg-danger' : 'bg-success');
        }
    }

    async function save(box, text) {
        if (busy || !text) return false;
        busy = true;
        try {
            const response = await fetch('/portal-docente/aluno/observacao', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ enrollment: config.enrollment, disciplineClass: parseInt(box.dataset.dc, 10), text })
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data.error || 'falha');

            const discipline = box.closest('.card').querySelector('.m-panel-head h6').firstChild.textContent.trim();
            notes.insertAdjacentHTML('afterbegin', `<li><p>${escapeHtml(text)}</p><small>${escapeHtml(discipline)} · ${escapeHtml(config.teacher || '')} · ${data.date}</small></li>`);
            const empty = root.querySelector('[data-notes-empty]');
            if (empty) empty.remove();
            toast('Observação salva');
            return true;
        } catch (e) {
            toast(e.message === 'sem-permissao' ? 'Você não tem permissão nesta disciplina.' : 'Não foi possível salvar. Tente de novo.', 'error');
            return false;
        } finally {
            busy = false;
        }
    }

    root.addEventListener('click', async (event) => {
        const chip = event.target.closest('[data-quick-note]');
        if (!chip) return;
        chip.classList.add('is-active');
        const ok = await save(chip.closest('[data-quick]'), chip.dataset.quickNote);
        setTimeout(() => chip.classList.remove('is-active'), ok ? 600 : 0);
    });

    root.addEventListener('submit', async (event) => {
        const form = event.target.closest('[data-quick-form]');
        if (!form) return;
        event.preventDefault();
        const input = form.querySelector('input');
        if (await save(form.closest('[data-quick]'), input.value.trim())) input.value = '';
    });

})();
