// Mater — Chamada rápida (portal do docente)
// Todos começam presentes; um toque no card marca/desmarca a falta. Sem recarregar a página.
(function () {

    const config = window.MATER_ATTENDANCE || { classes: [], photoBase: '' };
    const root = document.getElementById('attendance');
    if (!root) return;

    const $ = (sel) => root.querySelector(sel);
    const $$ = (sel) => Array.from(root.querySelectorAll(sel));

    const steps = { pick: $('[data-att-step="pick"]'), mark: $('[data-att-step="mark"]'), done: $('[data-att-step="done"]') };
    const grid = $('[data-att-grid]');
    const saveButton = $('[data-att-save]');
    const searchInput = $('[data-att-search]');

    const state = { cls: null, unity: null, date: null, lessons: 1, students: [], absent: new Set(), subjects: new Set(), saving: false };

    const errors = {
        'turma-nao-encontrada': 'Turma não encontrada para o seu usuário.',
        'sem-permissao': 'Você não tem permissão para lançar nesta turma/disciplina.',
        'unidade-fechada': 'Esta unidade está fechada para lançamentos.',
        'aluno-fora-da-turma': 'A lista de alunos mudou. Abra a turma novamente.',
        'nao-autenticado': 'Sua sessão expirou. Entre novamente.',
        'falha-ao-salvar': 'Não foi possível salvar. Tente de novo.'
    };

    // ---------- Navegação entre passos
    function show(step) {
        Object.keys(steps).forEach((k) => { steps[k].hidden = k !== step; });
        document.body.classList.toggle('m-att-marking', step === 'mark');
        window.scrollTo(0, 0);
    }

    // ---------- Utilitários
    function escapeHtml(text) {
        return String(text).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function initials(name) {
        const parts = name.trim().split(/\s+/);
        return ((parts[0] || '')[0] + ((parts.length > 1 ? parts[parts.length - 1] : '')[0] || '')).toUpperCase();
    }

    function hue(text) {
        let h = 0;
        for (let i = 0; i < text.length; i++) h = (h * 31 + text.charCodeAt(i)) % 360;
        return h;
    }

    function normalize(text) {
        return text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    }

    function toast(message, type) {
        // `tools` é declarado com const no main.js (escopo global, mas fora de window)
        if (typeof tools !== 'undefined' && typeof tools.showToast === 'function') {
            tools.showToast(message, type === 'error' ? 'bg-danger' : 'bg-success');
        }
    }

    // ---------- Passo 1: escolher turma
    root.addEventListener('click', (event) => {
        const card = event.target.closest('[data-att-class]');
        if (card) openClass(parseInt(card.dataset.attClass, 10));
    });

    async function openClass(id, keepUnity) {
        const cls = config.classes.find((c) => c.id === id);
        if (!cls) return;

        state.cls = cls;
        state.absent = new Set();
        state.lessons = 1;
        const dateInput = $('[data-att-date]');
        state.date = dateInput ? dateInput.value : new Date().toISOString().slice(0, 10);
        if (!keepUnity) state.unity = parseInt(($('[data-att-unity] .is-active') || {}).dataset?.value || '1', 10);

        const polyvalent = cls.stage === 'iniciais';
        if (!keepUnity) state.subjects = new Set([cls.id]);
        $('[data-att-title]').textContent = `${cls.label} · ${polyvalent ? 'Anos Iniciais' : cls.discipline}`;
        $('[data-att-subtitle]').textContent = `${cls.course} · ${cls.shift} · ${cls.students} alunos`;
        renderSubjects();
        $('[data-att-lessons-value]').textContent = '1';
        searchInput.value = '';

        grid.innerHTML = skeleton(Math.min(cls.students || 8, 12));
        show('mark');
        updateCounters();

        try {
            const response = await fetch(`/portal-docente/chamada/alunos?disciplineClass=${cls.id}&unity=${state.unity}&date=${encodeURIComponent(state.date)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'erro');

            state.unity = data.unity;
            state.students = data.students;
            setActiveUnity(data.unity);

            const warning = $('[data-att-warning]');
            if (data.doneTodayAt) {
                const todayIso = new Date().toISOString().slice(0, 10);
                const dayLabel = state.date === todayIso ? 'hoje' : `no dia ${state.date.split('-').reverse().join('/')}`;
                warning.innerHTML = `<i class="fas fa-info-circle mr-2"></i>Você já fez a chamada desta turma ${dayLabel} às <strong>${escapeHtml(data.doneTodayAt)}</strong>. Salvar de novo lança as faltas de mais uma aula.`;
                warning.hidden = false;
            } else {
                warning.hidden = true;
            }

            render();
        } catch (e) {
            grid.innerHTML = `<div class="m-empty">${escapeHtml(errors[e.message] || 'Não foi possível carregar os alunos.')}</div>`;
        }
    }

    // Anos Iniciais: o polivalente marca em quais disciplinas da turma a chamada vale
    function renderSubjects() {
        const box = $('[data-att-subjects]');
        const subjects = config.classes.filter((c) => c.classId === state.cls.classId);
        box.hidden = state.cls.stage !== 'iniciais' || subjects.length < 2;
        if (box.hidden) return;

        $('[data-att-subjects-list]').innerHTML = subjects.map((c) => {
            const active = state.subjects.has(c.id);
            return `<button type="button" class="m-att-subject${active ? ' is-active' : ''}" data-subject="${c.id}" aria-pressed="${active}">
                <i class="fas ${active ? 'fa-check-square' : 'fa-square'}"></i>${escapeHtml(c.discipline)}</button>`;
        }).join('');
    }

    $('[data-att-subjects]').addEventListener('click', (event) => {
        const button = event.target.closest('[data-subject]');
        if (!button) return;
        const id = parseInt(button.dataset.subject, 10);
        if (state.subjects.has(id) && state.subjects.size === 1) return; // ao menos uma
        state.subjects.has(id) ? state.subjects.delete(id) : state.subjects.add(id);
        renderSubjects();
    });

    function skeleton(n) {
        return Array.from({ length: n }, () => '<div class="m-att-student is-loading"><span class="m-att-avatar"></span><span class="m-att-skel"></span><span class="m-att-skel m-att-skel-sm"></span></div>').join('');
    }

    // ---------- Passo 2: carômetro
    function render() {
        if (state.students.length === 0) {
            grid.innerHTML = '<div class="m-empty">Nenhum aluno matriculado nesta turma.</div>';
            updateCounters();
            return;
        }

        grid.innerHTML = state.students.map((s, index) => {
            const absent = state.absent.has(s.id);
            const avatar = s.photo
                ? `<img src="${escapeHtml(config.photoBase + s.photo)}" alt="" loading="lazy">`
                : `<span style="--h:${hue(s.name)}">${escapeHtml(initials(s.name))}</span>`;
            const lack = s.totalLack + (absent ? state.lessons : 0);
            const ring = Math.min(lack, 20) / 20 * 100;
            const ringClass = lack >= 10 ? 'is-high' : lack >= 5 ? 'is-mid' : '';

            const certBadge = s.hasCertificate
                ? `<span class="m-att-cert-badge" title="Atestado: ${escapeHtml(s.certificateReason || 'Médico')} (${escapeHtml(s.certificateDates || '')})"><i class="fas fa-file-medical mr-1"></i> Atestado</span>`
                : '';

            return `
                <button type="button" class="m-att-student${absent ? ' is-absent' : ''}" data-student="${s.id}" data-name="${escapeHtml(normalize(s.name))}" aria-pressed="${absent}">
                    <span class="m-att-num">${index + 1}</span>
                    <span class="m-att-avatar">${avatar}<span class="m-att-mark"><i class="fas ${absent ? 'fa-times' : 'fa-check'}"></i></span></span>
                    <span class="m-att-name">${escapeHtml(s.name)}${certBadge}</span>
                    <span class="m-att-status">${absent ? 'Faltou' : 'Presente'}</span>
                    <span class="m-att-ring ${ringClass}" style="--p:${ring}">
                        <strong>${lack}</strong>
                    </span>
                    <small class="m-att-ring-label">faltas na unidade</small>
                </button>`;
        }).join('');

        applySearch();
        updateCounters();
    }

    grid.addEventListener('click', (event) => {
        const card = event.target.closest('[data-student]');
        if (!card) return;

        const id = parseInt(card.dataset.student, 10);
        state.absent.has(id) ? state.absent.delete(id) : state.absent.add(id);
        if (navigator.vibrate) navigator.vibrate(12);
        renderOne(card, id);
        updateCounters();
    });

    // Atualiza só o card tocado (resposta instantânea, sem redesenhar a grade)
    function renderOne(card, id) {
        const student = state.students.find((s) => s.id === id);
        const absent = state.absent.has(id);
        const lack = student.totalLack + (absent ? state.lessons : 0);

        card.classList.toggle('is-absent', absent);
        card.setAttribute('aria-pressed', absent);
        card.querySelector('.m-att-status').textContent = absent ? 'Faltou' : 'Presente';
        card.querySelector('.m-att-mark i').className = `fas ${absent ? 'fa-times' : 'fa-check'}`;

        const ring = card.querySelector('.m-att-ring');
        ring.style.setProperty('--p', Math.min(lack, 20) / 20 * 100);
        ring.classList.toggle('is-high', lack >= 10);
        ring.classList.toggle('is-mid', lack >= 5 && lack < 10);
        ring.querySelector('strong').textContent = lack;
    }

    function updateCounters() {
        const total = state.students.length;
        const absent = state.absent.size;
        $('[data-att-present]').textContent = total - absent;
        $('[data-att-absent]').textContent = absent;
        saveButton.disabled = total === 0 || state.saving;
        saveButton.querySelector('span').textContent = absent === 0 ? 'Salvar · todos presentes' : `Salvar chamada · ${absent} falta${absent > 1 ? 's' : ''}`;
    }

    // Unidade
    $('[data-att-unity]').addEventListener('click', (event) => {
        const button = event.target.closest('button[data-value]');
        if (!button || !state.cls) return;
        const unity = parseInt(button.dataset.value, 10);
        if (unity === state.unity) return;
        const keepAbsent = new Set(state.absent);
        state.unity = unity;
        setActiveUnity(unity);
        openClass(state.cls.id, true).then(() => { state.absent = keepAbsent; render(); });
    });

    function setActiveUnity(unity) {
        $$('[data-att-unity] button').forEach((b) => b.classList.toggle('is-active', parseInt(b.dataset.value, 10) === unity));
    }

    // Data da aula
    const dateInput = $('[data-att-date]');
    if (dateInput) {
        dateInput.addEventListener('change', () => {
            if (!state.cls) return;
            state.date = dateInput.value || new Date().toISOString().slice(0, 10);
            const keepAbsent = new Set(state.absent);
            openClass(state.cls.id, true).then(() => {
                state.absent = keepAbsent;
                render();
            });
        });
    }

    // Aulas seguidas
    $$('[data-att-lessons]').forEach((button) => button.addEventListener('click', () => {
        state.lessons = Math.max(1, Math.min(6, state.lessons + parseInt(button.dataset.attLessons, 10)));
        $('[data-att-lessons-value]').textContent = state.lessons;
        render();
    }));

    // Busca
    searchInput.addEventListener('input', applySearch);

    function applySearch() {
        const term = normalize(searchInput.value.trim());
        $$('[data-student]').forEach((card) => { card.hidden = term !== '' && !card.dataset.name.includes(term); });
    }

    // Todos presentes
    $('[data-att-reset]').addEventListener('click', () => { state.absent.clear(); render(); });

    // Voltar
    $('[data-att-back]').addEventListener('click', () => {
        if (state.absent.size > 0 && !window.confirm('Descartar as faltas marcadas?')) return;
        show('pick');
    });

    // ---------- Salvar
    saveButton.addEventListener('click', async () => {
        if (state.saving || !state.cls) return;
        state.saving = true;
        saveButton.classList.add('is-loading');
        updateCounters();

        try {
            const response = await fetch('/portal-docente/chamada/salvar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({
                    disciplineClass: state.cls.id,
                    disciplineClasses: Array.from(state.subjects),
                    unity: state.unity,
                    date: state.date,
                    lessons: state.lessons,
                    absent: Array.from(state.absent)
                })
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data.error || 'falha-ao-salvar');

            finish(data);
        } catch (e) {
            toast(errors[e.message] || errors['falha-ao-salvar'], 'error');
        } finally {
            state.saving = false;
            saveButton.classList.remove('is-loading');
            updateCounters();
        }
    });

    function finish(data) {
        const cls = state.cls;
        cls.doneTodayAt = data.savedAt;

        const pill = root.querySelector(`[data-att-class="${cls.id}"] [data-att-done]`);
        if (pill) {
            pill.className = 'm-pill m-pill-ok';
            pill.innerHTML = `<i class="fas fa-check mr-1"></i>${escapeHtml(data.savedAt)}`;
        }

        const lessonsText = data.lessons > 1 ? ` (${data.lessons} aulas)` : '';
        const dateStr = data.formattedDate || (state.date ? state.date.split('-').reverse().join('/') : '');
        const subject = (data.disciplines && data.disciplines.length) ? data.disciplines.join(', ') : cls.discipline;
        $('[data-att-done-summary]').textContent = data.absent === 0
            ? `${cls.label} · ${subject} (${dateStr}): todos os ${data.present} alunos presentes${lessonsText}.`
            : `${cls.label} · ${subject} (${dateStr}): ${data.present} presentes e ${data.absent} ausente${data.absent > 1 ? 's' : ''}${lessonsText} na ${data.unity}ª unidade.`;

        const absentNames = state.students.filter((s) => state.absent.has(s.id));
        $('[data-att-done-list]').innerHTML = absentNames.map((s) =>
            `<li><span class="m-att-dot"></span>${escapeHtml(s.name)}<small>${s.totalLack + data.lessons} faltas</small></li>`
        ).join('');

        show('done');
        toast('Chamada salva');
    }

    $('[data-att-again]').addEventListener('click', () => show('pick'));

    // Atalho: /portal-docente/chamada?turma=ID abre direto a turma
    const preselect = parseInt(new URLSearchParams(window.location.search).get('turma') || '', 10);
    if (preselect) openClass(preselect);

})();
