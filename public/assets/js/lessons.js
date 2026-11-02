// Mater — Diário de aula (portal do docente)
// Escolhe a turma, registra habilidade BNCC, atividades, observação e exceções. Sem recarregar a página.
(function () {

    const config = window.MATER_LESSONS || { classes: [], classDisciplines: [], activities: {} };
    const root = document.getElementById('lessons');
    if (!root) return;

    const $ = (sel) => root.querySelector(sel);
    const $$ = (sel) => Array.from(root.querySelectorAll(sel));

    const steps = { pick: $('[data-ls-step="pick"]'), form: $('[data-ls-step="form"]'), plan: $('[data-ls-step="plan"]'), done: $('[data-ls-step="done"]') };
    const form = steps.form;
    const saveButton = $('[data-ls-save]');

    const state = { cls: null, subjects: new Set(), activities: new Set(), planId: null, saving: false };

    const errors = {
        'sem-permissao': 'Você não tem permissão para registrar aula nesta turma/disciplina.',
        'data-invalida': 'Data inválida (não pode ser futura).',
        'codigo-bncc-invalido': 'Código BNCC inválido. Ex.: EF05MA01 ou EM13LGG101.',
        'texto-muito-longo': 'Texto longo demais.',
        'atividade-invalida': 'Atividade inválida.',
        'aula-vazia': 'Preencha a habilidade, uma atividade ou a observação.',
        'substituto-invalido': 'Escolha um professor substituto válido.',
        'materia-invalida': 'A matéria trocada precisa ser outra disciplina da mesma turma.',
        'motivo-obrigatorio': 'Informe o motivo da troca.',
        'nao-autenticado': 'Sua sessão expirou. Entre novamente.',
        'falha-ao-salvar': 'Não foi possível salvar. Tente de novo.'
    };

    function show(step) {
        Object.keys(steps).forEach((k) => { steps[k].hidden = k !== step; });
        document.body.classList.toggle('m-att-marking', step === 'form' || step === 'plan');
        window.scrollTo(0, 0);
    }

    function escapeHtml(text) {
        return String(text).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function toast(message, type) {
        if (typeof tools !== 'undefined' && typeof tools.showToast === 'function') {
            tools.showToast(message, type === 'error' ? 'bg-danger' : 'bg-success');
        }
    }

    function chip(button, active) {
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active);
        button.querySelector('i').className = `fas ${active ? 'fa-check-square' : 'fa-square'}`;
    }

    // ---------- Passo 1: escolher turma
    root.addEventListener('click', (event) => {
        const card = event.target.closest('[data-ls-class]');
        if (card) openClass(parseInt(card.dataset.lsClass, 10));
    });

    function openClass(id, subjects) {
        const cls = config.classes.find((c) => c.id === id);
        if (!cls) return;

        state.cls = cls;
        state.subjects = new Set((subjects && subjects.length ? subjects : [cls.id]).filter((s) => config.classes.some((c) => c.id === s && c.classId === cls.classId)));
        if (!state.subjects.has(cls.id)) state.subjects.add(cls.id);
        state.activities = new Set();
        state.planId = null;

        form.reset();
        bncc.reset();
        $('[data-ls-plan-note]').hidden = true;
        $$('[data-activity]').forEach((b) => chip(b, false));
        $('[data-ls-exception]').open = false;

        const polyvalent = cls.stage === 'iniciais';
        $('[data-ls-title]').textContent = `${cls.label} · ${polyvalent ? 'Anos Iniciais' : cls.discipline}`;
        $('[data-ls-subtitle]').textContent = `${cls.course} · ${cls.shift}`;

        // matéria trocada: outras disciplinas da mesma turma
        $('[data-ls-taught]').innerHTML = '<option value="">Não houve troca</option>' + config.classDisciplines
            .filter((d) => Number(d.class_id) === cls.classId && Number(d.id) !== cls.id)
            .map((d) => `<option value="${Number(d.id)}">${escapeHtml(d.name)}</option>`).join('');

        renderSubjects();
        show('form');
    }

    // Anos Iniciais: em quais disciplinas a aula vale
    function renderSubjects() {
        const box = $('[data-ls-subjects]');
        const subjects = config.classes.filter((c) => c.classId === state.cls.classId);
        box.hidden = state.cls.stage !== 'iniciais' || subjects.length < 2;
        if (box.hidden) return;

        $('[data-ls-subjects-list]').innerHTML = subjects.map((c) => {
            const active = state.subjects.has(c.id);
            return `<button type="button" class="m-att-subject${active ? ' is-active' : ''}" data-subject="${c.id}" aria-pressed="${active}">
                <i class="fas ${active ? 'fa-check-square' : 'fa-square'}"></i>${escapeHtml(c.discipline)}</button>`;
        }).join('');
    }

    $('[data-ls-subjects]').addEventListener('click', (event) => {
        const button = event.target.closest('[data-subject]');
        if (!button) return;
        const id = parseInt(button.dataset.subject, 10);
        if (state.subjects.has(id) && state.subjects.size === 1) return;
        state.subjects.has(id) ? state.subjects.delete(id) : state.subjects.add(id);
        renderSubjects();
    });

    $('[data-ls-activities]').addEventListener('click', (event) => {
        const button = event.target.closest('[data-activity]');
        if (!button) return;
        const key = button.dataset.activity;
        state.activities.has(key) ? state.activities.delete(key) : state.activities.add(key);
        chip(button, state.activities.has(key));
    });

    // ---------- Busca de habilidade BNCC (filtrada pela série e disciplina da turma); usada no registro e no plano
    function bnccWidget(box, fields) {
        const input = box.querySelector('[data-bncc-search]');
        const results = box.querySelector('[data-bncc-results]');
        let timer = null;
        let skills = [];

        function set(code, text) {
            fields.code.value = code || '';
            fields.tag.hidden = !code;
            fields.tag.textContent = code || '';
            if (text !== undefined) fields.skill.value = text;
        }

        async function search() {
            const disciplineClass = fields.disciplineClass();
            if (!disciplineClass) return;
            try {
                const response = await fetch(`/portal-docente/bncc?turma=${disciplineClass}&q=${encodeURIComponent(input.value.trim())}`,
                    { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await response.json();
                skills = data.skills || [];
                results.innerHTML = skills.length
                    ? skills.map((s, i) => `<li><button type="button" data-bncc-pick="${i}"><span class="m-lesson-code-tag">${escapeHtml(s.code)}</span> ${escapeHtml(s.text)}</button></li>`).join('')
                    : '<li class="m-empty">Nenhuma habilidade encontrada para esta turma.</li>';
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
        input.addEventListener('keydown', (event) => { if (event.key === 'Escape') results.hidden = true; });

        results.addEventListener('click', (event) => {
            const pick = event.target.closest('[data-bncc-pick]');
            if (!pick) return;
            const skill = skills[parseInt(pick.dataset.bnccPick, 10)];
            set(skill.code, skill.text);
            input.value = '';
            results.hidden = true;
        });

        document.addEventListener('click', (event) => {
            if (!box.contains(event.target)) results.hidden = true;
        });

        // texto da habilidade editado à mão deixa de ser a habilidade oficial escolhida
        fields.skill.addEventListener('input', () => {
            const picked = skills.find((s) => s.code === fields.code.value);
            if (picked && picked.text !== fields.skill.value) set('');
        });

        return {
            set,
            reset(code, text) {
                set(code || '', text);
                input.value = '';
                skills = [];
                results.innerHTML = '';
                results.hidden = true;
            }
        };
    }

    const bncc = bnccWidget(form.querySelector('.m-bncc'), {
        disciplineClass: () => (state.cls ? state.cls.id : 0),
        code: form.querySelector('[name="bnccCode"]'),
        skill: form.querySelector('[name="bnccSkill"]'),
        tag: $('[data-bncc-tag]')
    });

    $('[data-ls-back]').addEventListener('click', () => show('pick'));
    $('[data-ls-again]').addEventListener('click', () => show('pick'));

    // ---------- Salvar
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (state.saving || !state.cls) return;

        const data = new FormData(form);
        const payload = {
            disciplineClass: state.cls.id,
            disciplineClasses: Array.from(state.subjects),
            date: data.get('date'),
            bnccCode: (data.get('bnccCode') || '').trim().toUpperCase(),
            bnccSkill: (data.get('bnccSkill') || '').trim(),
            notes: (data.get('notes') || '').trim(),
            activities: Array.from(state.activities),
            substitute: parseInt(data.get('substitute') || '0', 10) || null,
            taughtDisciplineClass: parseInt(data.get('taughtDisciplineClass') || '0', 10) || null,
            reason: (data.get('reason') || '').trim(),
            plan: state.planId
        };

        if (!payload.bnccCode && !payload.bnccSkill && !payload.notes && payload.activities.length === 0) {
            toast(errors['aula-vazia'], 'error');
            return;
        }
        if ((payload.substitute || payload.taughtDisciplineClass) && !payload.reason) {
            $('[data-ls-exception]').open = true;
            form.querySelector('[name="reason"]').focus();
            toast(errors['motivo-obrigatorio'], 'error');
            return;
        }

        state.saving = true;
        saveButton.disabled = true;
        saveButton.classList.add('is-loading');

        try {
            const response = await fetch('/portal-docente/diario/salvar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(payload)
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(result.error || 'falha-ao-salvar');

            if (window.MATER_DIARY.onLessonSaved) window.MATER_DIARY.onLessonSaved(result.isoDate);

            $('[data-ls-done-summary]').textContent = `${state.cls.label} · ${result.disciplines.join(', ')} — ${result.formattedDate}.`;
            show('done');
            toast('Aula registrada');
        } catch (e) {
            toast(errors[e.message] || errors['falha-ao-salvar'], 'error');
        } finally {
            state.saving = false;
            saveButton.disabled = false;
            saveButton.classList.remove('is-loading');
        }
    });

    // Usado pelo calendário (lessonCalendar.js)
    window.MATER_DIARY = {
        show,
        escapeHtml,
        toast,
        chip,
        bnccWidget,
        openLesson(id, date, plan) {
            openClass(id);
            if (!state.cls) return;
            form.querySelector('[name="date"]').value = date;
            if (!plan) return;
            state.planId = plan.id;
            bncc.reset(plan.bnccCode, plan.bnccSkill || '');
            form.querySelector('[name="notes"]').value = plan.title;
            plan.activities.forEach((key) => {
                state.activities.add(key);
                const button = $(`[data-activity="${key}"]`);
                if (button) chip(button, true);
            });
            const note = $('[data-ls-plan-note]');
            note.textContent = `Preenchido com o plano "${plan.title}". Ajuste o que mudou na aula.`;
            note.hidden = false;
        }
    };

    // Atalho vindo da chamada: /portal-docente/diario?turma=ID&disciplinas=1,2
    const params = new URLSearchParams(window.location.search);
    const preselect = parseInt(params.get('turma') || '', 10);
    if (preselect) {
        openClass(preselect, (params.get('disciplinas') || '').split(',').map((n) => parseInt(n, 10)).filter(Boolean));
        if (/^\d{4}-\d{2}-\d{2}$/.test(params.get('data') || '')) form.querySelector('[name="date"]').value = params.get('data');

        // vindo direto da chamada: confirma a chamada aqui mesmo e segue para o registro da aula
        let saved = null;
        try { saved = JSON.parse(sessionStorage.getItem('mater-chamada-salva') || 'null'); sessionStorage.removeItem('mater-chamada-salva'); } catch (e) { saved = null; }
        if (saved && state.cls) {
            form.querySelector('.m-att-head').insertAdjacentHTML('afterend', `
                <div class="m-saved-banner">
                    <i class="fas fa-check-circle"></i>
                    <span><strong>Chamada salva.</strong> ${escapeHtml(saved.summary)}${saved.absent && saved.absent.length ? `<small>Ausentes: ${escapeHtml(saved.absent.join(', '))}</small>` : ''}</span>
                    <a href="/portal-docente/chamada">Outra chamada</a>
                </div>`);
        }
    }

})();
