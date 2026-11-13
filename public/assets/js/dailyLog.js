// Mater — Diário do dia do Fund. 1 (aba da turma, só o titular): texto único do dia + códigos BNCC opcionais.
(function () {

    const root = document.querySelector('[data-dlog]');
    if (!root) return;

    const $ = (sel) => root.querySelector(sel);
    const form = $('[data-dlog-form]');
    const classId = parseInt(root.dataset.classId, 10);
    const MAX_CODES = 10;
    const CODE = /^(EI|EF|EM)\d{2}[A-Z]{2,3}\d{2,3}$/;

    // barra de salvar fixa no rodapé, como na chamada (esconde o menu inferior no celular)
    document.body.classList.add('m-att-marking');

    const state = { codes: [], saving: false, dirty: false };
    try { state.codes = JSON.parse(root.dataset.codes || '[]'); } catch (e) { state.codes = []; }

    const errors = {
        'sem-permissao': 'Só o professor titular registra o diário desta turma.',
        'data-invalida': 'Data inválida (não pode ser futura).',
        'diario-vazio': 'Escreva o que foi trabalhado no dia.',
        'texto-muito-longo': 'Texto longo demais.',
        'codigo-bncc-invalido': 'Código BNCC inválido. Ex.: EF03MA01.',
        'codigos-demais': `No máximo ${MAX_CODES} habilidades por dia.`,
        'nao-autenticado': 'Sua sessão expirou. Entre novamente.',
        'falha-ao-salvar': 'Não foi possível salvar. Tente de novo.'
    };

    function escapeHtml(text) {
        return String(text).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function toast(message, type) {
        if (typeof tools !== 'undefined' && typeof tools.showToast === 'function') {
            tools.showToast(message, type === 'error' ? 'bg-danger' : 'bg-success');
        }
    }

    function link(date) {
        return `/portal-docente/turma?id=${classId}&ver=diario&data=${encodeURIComponent(date)}`;
    }

    // ---------- códigos escolhidos
    const codesBox = $('[data-dlog-codes]');

    function renderCodes() {
        codesBox.innerHTML = state.codes.map((code) =>
            `<button type="button" class="m-att-subject is-active" data-dlog-remove="${escapeHtml(code)}" aria-label="Tirar ${escapeHtml(code)}">${escapeHtml(code)}<i class="fas fa-times ml-2" aria-hidden="true"></i></button>`
        ).join('');
    }

    function addCode(code) {
        code = String(code || '').replace(/\s+/g, '').toUpperCase();
        if (!CODE.test(code)) return toast(errors['codigo-bncc-invalido'], 'error');
        if (state.codes.includes(code)) return;
        if (state.codes.length >= MAX_CODES) return toast(errors['codigos-demais'], 'error');
        state.codes.push(code);
        state.dirty = true;
        renderCodes();
    }

    codesBox.addEventListener('click', (event) => {
        const button = event.target.closest('[data-dlog-remove]');
        if (!button) return;
        state.codes = state.codes.filter((c) => c !== button.dataset.dlogRemove);
        state.dirty = true;
        renderCodes();
    });

    // ---------- busca BNCC (todos os componentes do ano da turma)
    const input = $('[data-bncc-search]');
    const results = $('[data-bncc-results]');
    const box = $('.m-bncc');
    let skills = [];
    let timer = null;

    async function search() {
        try {
            const response = await fetch(`/portal-docente/diario-do-dia/bncc?turma=${classId}&q=${encodeURIComponent(input.value.trim())}`,
                { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await response.json();
            skills = data.skills || [];
            results.innerHTML = skills.length
                ? skills.map((s, i) => `<li><button type="button" data-bncc-pick="${i}"><span class="m-lesson-code-tag">${escapeHtml(s.code)}</span> ${escapeHtml(s.text)}</button></li>`).join('')
                : '<li class="m-empty">Nenhuma habilidade encontrada para este ano.</li>';
            results.hidden = false;
        } catch (e) {
            results.hidden = true;
        }
    }

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(search, 250);
    });
    input.addEventListener('focus', () => { if (!results.innerHTML) search(); else results.hidden = false; });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') results.hidden = true;
        // código digitado inteiro + Enter entra direto
        if (event.key === 'Enter') {
            event.preventDefault();
            if (CODE.test(input.value.replace(/\s+/g, '').toUpperCase())) {
                addCode(input.value);
                input.value = '';
                results.hidden = true;
            }
        }
    });

    results.addEventListener('click', (event) => {
        const pick = event.target.closest('[data-bncc-pick]');
        if (!pick) return;
        addCode(skills[parseInt(pick.dataset.bnccPick, 10)].code);
        input.value = '';
        results.hidden = true;
    });

    document.addEventListener('click', (event) => {
        if (!box.contains(event.target)) results.hidden = true;
    });

    // ---------- trocar o dia
    const text = form.querySelector('[name="text"]');
    text.addEventListener('input', () => { state.dirty = true; });

    $('[data-dlog-date]').addEventListener('change', (event) => {
        const date = event.target.value;
        if (!date) return;
        if (state.dirty && !window.confirm('Há alterações não salvas neste dia. Trocar de dia mesmo assim?')) {
            event.target.value = root.dataset.date;
            return;
        }
        window.location.href = link(date);
    });

    window.addEventListener('beforeunload', (event) => {
        if (state.dirty && !state.saving) { event.preventDefault(); event.returnValue = ''; }
    });

    // ---------- salvar
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (state.saving) return;

        const body = { classId, date: root.dataset.date, text: text.value.trim(), codes: state.codes };
        if (!body.text) {
            toast(errors['diario-vazio'], 'error');
            text.focus();
            return;
        }

        const button = $('[data-dlog-save]');
        state.saving = true;
        button.disabled = true;

        try {
            const response = await fetch('/portal-docente/diario-do-dia/salvar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(body)
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data.error || 'falha-ao-salvar');

            state.dirty = false;
            try { sessionStorage.setItem('mater-diario-salvo', data.corrected ? 'Diário corrigido' : 'Diário salvo'); } catch (e) { /* sem armazenamento: só recarrega */ }
            window.location.href = link(body.date);
        } catch (e) {
            toast(errors[e.message] || errors['falha-ao-salvar'], 'error');
            state.saving = false;
            button.disabled = false;
        }
    });

    // ---------- avisos vindos da chamada do dia ou do salvamento anterior
    let saved = null;
    let message = null;
    try {
        saved = JSON.parse(sessionStorage.getItem('mater-chamada-salva') || 'null');
        sessionStorage.removeItem('mater-chamada-salva');
        message = sessionStorage.getItem('mater-diario-salvo');
        sessionStorage.removeItem('mater-diario-salvo');
    } catch (e) { /* sem armazenamento da sessão */ }

    if (saved) {
        root.insertAdjacentHTML('afterbegin', `
            <div class="m-saved-banner">
                <i class="fas fa-check-circle"></i>
                <span><strong>Chamada salva.</strong> ${escapeHtml(saved.summary)}${saved.absent && saved.absent.length ? `<small>Ausentes: ${escapeHtml(saved.absent.join(', '))}</small>` : ''} Agora registre o diário do dia.</span>
            </div>`);
    }
    if (message) toast(message);

    renderCodes();

})();
