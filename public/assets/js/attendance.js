// Mater — Chamada rápida (aba Chamada da tela da turma)
// Todos começam presentes; um toque no card marca/desmarca a falta. Sem recarregar a página.
(function () {

    const config = window.MATER_ATTENDANCE || { classes: [], current: 0, daily: null, photoBase: '' };
    const root = document.getElementById('attendance');
    if (!root) return;

    // Fund. 1: chamada do dia da turma (titular). Uma por dia; salvar de novo corrige a lista.
    const daily = config.daily || null;
    const absenceLabel = daily ? 'dias de falta no bimestre' : 'faltas no bimestre';

    const $ = (sel) => root.querySelector(sel);
    const $$ = (sel) => Array.from(root.querySelectorAll(sel));

    const steps = { mark: $('[data-att-step="mark"]'), done: $('[data-att-step="done"]') };
    const grid = $('[data-att-grid]');
    const saveButton = $('[data-att-save]');
    const searchInput = $('[data-att-search]');

    const state = { cls: null, unity: null, date: null, lessons: 1, students: [], absent: new Set(), subjects: new Set(), saving: false, finished: false, dirty: false };

    const errors = {
        'turma-nao-encontrada': 'Turma não encontrada para o seu usuário.',
        'sem-permissao': daily ? 'Só o professor titular faz a chamada desta turma.' : 'Você não tem permissão para lançar nesta turma/disciplina.',
        'chamada-diaria': 'Nesta turma a chamada é a do dia, feita pelo professor titular.',
        'unidade-fechada': 'Este bimestre está fechado para lançamentos.',
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

    // ---------- Abrir a turma/disciplina
    async function openClass(id, keepUnity) {
        const cls = daily
            ? { id: daily.classId, classId: daily.classId, stage: 'iniciais', label: daily.label, discipline: 'chamada do dia', students: daily.students }
            : config.classes.find((c) => c.id === id);
        if (!cls) return;

        state.cls = cls;
        state.absent = new Set();
        state.dirty = false;
        state.lessons = 1;
        const dateInput = $('[data-att-date]');
        state.date = dateInput ? dateInput.value : new Date().toISOString().slice(0, 10);
        if (!keepUnity) state.unity = parseInt(($('[data-att-unity] .is-active') || {}).dataset?.value || '1', 10);

        if (!keepUnity) state.subjects = new Set([cls.id]);
        renderSubjects();
        const lessonsValue = $('[data-att-lessons-value]');
        if (lessonsValue) lessonsValue.textContent = '1';
        searchInput.value = '';

        grid.innerHTML = skeleton(Math.min(cls.students || 8, 12));
        show('mark');
        updateCounters();

        try {
            const url = daily
                ? `/portal-docente/chamada-diaria/alunos?classId=${daily.classId}&unity=${state.unity}&date=${encodeURIComponent(state.date)}`
                : `/portal-docente/chamada/alunos?disciplineClass=${cls.id}&unity=${state.unity}&date=${encodeURIComponent(state.date)}`;
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'erro');

            state.unity = data.unity;
            state.students = data.students;
            setActiveUnity(data.unity);

            // chamada do dia já feita: abre com as faltas salvas, para corrigir
            if (daily) state.absent = new Set(data.students.filter((s) => s.absentOnDate).map((s) => s.id));

            const warning = $('[data-att-warning]');
            if (data.doneTodayAt) {
                const todayIso = new Date().toISOString().slice(0, 10);
                const dayLabel = state.date === todayIso ? 'hoje' : `no dia ${state.date.split('-').reverse().join('/')}`;
                warning.innerHTML = daily
                    ? `<i class="fas fa-info-circle mr-2"></i>A chamada de ${dayLabel} já foi salva às <strong>${escapeHtml(data.doneTodayAt)}</strong>. As faltas marcadas abaixo são as salvas; salvar de novo substitui a lista do dia.`
                    : `<i class="fas fa-info-circle mr-2"></i>Você já fez a chamada desta turma ${dayLabel} às <strong>${escapeHtml(data.doneTodayAt)}</strong>. Salvar de novo lança as faltas de mais uma aula.`;
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
        box.hidden = daily !== null || state.cls.stage !== 'iniciais' || subjects.length < 2;
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

    // ---------- Carômetro
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
                    <small class="m-att-ring-label">${absenceLabel}</small>
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
        state.dirty = true;
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
        const keepDirty = state.dirty;
        state.unity = unity;
        setActiveUnity(unity);
        openClass(state.cls.id, true).then(() => { state.absent = keepAbsent; state.dirty = keepDirty; render(); });
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
            // chamada do dia: outro dia abre com as faltas salvas daquele dia
            if (daily) {
                openClass(state.cls.id, true);
                return;
            }
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
    $('[data-att-reset]').addEventListener('click', () => { state.absent.clear(); state.dirty = true; render(); });

    // Trocar de turma ou de aba com faltas marcadas: o navegador pede confirmação
    // (na chamada do dia, só se algo mudou: ela já abre com as faltas salvas)
    window.addEventListener('beforeunload', (event) => {
        if (state.finished || (daily ? !state.dirty : state.absent.size === 0)) return;
        event.preventDefault();
        event.returnValue = '';
    });

    // ---------- Salvar
    saveButton.addEventListener('click', async () => {
        if (state.saving || !state.cls) return;
        state.saving = true;
        saveButton.classList.add('is-loading');
        updateCounters();

        try {
            const body = daily
                ? { classId: daily.classId, unity: state.unity, date: state.date, absent: Array.from(state.absent) }
                : {
                    disciplineClass: state.cls.id,
                    disciplineClasses: Array.from(state.subjects),
                    unity: state.unity,
                    date: state.date,
                    lessons: state.lessons,
                    absent: Array.from(state.absent)
                };
            const response = await fetch(daily ? '/portal-docente/chamada-diaria/salvar' : '/portal-docente/chamada/salvar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(body)
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
        state.finished = true;

        const lessonsText = data.lessons > 1 ? ` (${data.lessons} aulas)` : '';
        const dateStr = data.formattedDate || (state.date ? state.date.split('-').reverse().join('/') : '');
        const subject = (data.disciplines && data.disciplines.length) ? data.disciplines.join(', ') : cls.discipline;
        const corrected = data.corrected ? ' Chamada do dia corrigida.' : '';
        $('[data-att-done-summary]').textContent = data.absent === 0
            ? `${cls.label} · ${subject} (${dateStr}): todos os ${data.present} alunos presentes${lessonsText}.${corrected}`
            : `${cls.label} · ${subject} (${dateStr}): ${data.present} presentes e ${data.absent} ausente${data.absent > 1 ? 's' : ''}${lessonsText} no ${data.unity}º bimestre.${corrected}`;

        const absentNames = state.students.filter((s) => state.absent.has(s.id));
        const unit = daily ? ['dia de falta', 'dias de falta'] : ['falta', 'faltas'];
        $('[data-att-done-list]').innerHTML = absentNames.map((s) => {
            const total = s.totalLack + data.lessons;
            return `<li><span class="m-att-dot"></span>${escapeHtml(s.name)}<small>${total} ${unit[total === 1 ? 0 : 1]}</small></li>`;
        }).join('');

        // chamada e diário em sequência: segue direto para o registro da aula, com a mesma turma, disciplinas e data
        // (chamada do dia: o diário do dia da turma, na aba ao lado)
        const lessonSubjects = Array.from(state.subjects);
        const lessonUrl = daily
            ? `/portal-docente/turma?id=${daily.classId}&ver=diario&data=${encodeURIComponent(data.date || state.date || '')}`
            : `/portal-docente/diario?turma=${cls.id}&disciplinas=${lessonSubjects.join(',')}${state.date ? `&data=${state.date}` : ''}`;
        const lessonLink = $('[data-att-lesson]');
        lessonLink.hidden = !lessonUrl;
        if (lessonUrl) lessonLink.href = lessonUrl;
        if (!lessonUrl) {
            show('done');
            toast('Chamada salva');
            return;
        }

        try {
            sessionStorage.setItem('mater-chamada-salva', JSON.stringify({
                summary: $('[data-att-done-summary]').textContent,
                absent: absentNames.map((s) => s.name)
            }));
            window.location.href = lessonUrl;
            return;
        } catch (e) {
            // sem armazenamento da sessão: mostra a confirmação aqui, com o botão para o diário
        }

        show('done');
        toast('Chamada salva');
    }

    $('[data-att-again]').addEventListener('click', () => {
        state.finished = false;
        openClass(state.cls.id);
    });

    openClass(daily ? daily.classId : config.current);

})();
