// Mater — Diário de aula (portal do docente)
// Escolhe a turma, registra habilidade BNCC, atividades, observação e exceções. Sem recarregar a página.
(function () {

    const config = window.MATER_LESSONS || { classes: [], classDisciplines: [], activities: {}, recent: [] };
    const root = document.getElementById('lessons');
    if (!root) return;

    const $ = (sel) => root.querySelector(sel);
    const $$ = (sel) => Array.from(root.querySelectorAll(sel));

    const steps = { pick: $('[data-ls-step="pick"]'), form: $('[data-ls-step="form"]'), done: $('[data-ls-step="done"]') };
    const form = steps.form;
    const saveButton = $('[data-ls-save]');

    const state = { cls: null, subjects: new Set(), activities: new Set(), saving: false };

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
        document.body.classList.toggle('m-att-marking', step === 'form');
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

    // ---------- Últimas aulas
    function renderRecent() {
        const box = $('[data-ls-recent]');
        if (!box) return;
        if (config.recent.length === 0) {
            box.innerHTML = '<div class="m-empty">Nenhuma aula registrada ainda.</div>';
            return;
        }
        box.innerHTML = config.recent.map((l) => {
            const exception = l.substitute || l.taught
                ? `<div class="m-lesson-item-exc"><i class="fas fa-exchange-alt mr-1"></i>${escapeHtml([l.substitute ? `Substituto: ${l.substitute}` : '', l.taught ? `Matéria dada: ${l.taught}` : ''].filter(Boolean).join(' · '))}${l.reason ? ` — ${escapeHtml(l.reason)}` : ''}</div>`
                : '';
            return `
                <div class="m-lesson-item">
                    <div class="m-lesson-item-head">
                        <strong>${escapeHtml(l.class)} · ${escapeHtml(l.discipline)}</strong>
                        <span class="m-pill m-pill-info">${escapeHtml(l.date)}</span>
                    </div>
                    ${l.bnccCode || l.bnccSkill ? `<p><span class="m-lesson-code-tag">${escapeHtml(l.bnccCode || 'BNCC')}</span> ${escapeHtml(l.bnccSkill || '')}</p>` : ''}
                    ${l.activities.length ? `<div class="m-lesson-tags">${l.activities.map((a) => `<span>${escapeHtml(a)}</span>`).join('')}</div>` : ''}
                    ${l.notes ? `<p class="m-lesson-notes">${escapeHtml(l.notes)}</p>` : ''}
                    ${exception}
                </div>`;
        }).join('');
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

        form.reset();
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
            reason: (data.get('reason') || '').trim()
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

            config.recent.unshift({
                class: state.cls.label,
                discipline: result.disciplines.join(', '),
                date: result.formattedDate.slice(0, 5),
                bnccCode: payload.bnccCode,
                bnccSkill: payload.bnccSkill,
                notes: payload.notes,
                activities: payload.activities.map((a) => config.activities[a] || a),
                substitute: payload.substitute ? form.querySelector('[name="substitute"]').selectedOptions[0].textContent : null,
                taught: payload.taughtDisciplineClass ? form.querySelector('[name="taughtDisciplineClass"]').selectedOptions[0].textContent : null,
                reason: payload.reason
            });
            renderRecent();

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

    renderRecent();

    // Atalho vindo da chamada: /portal-docente/diario?turma=ID&disciplinas=1,2
    const params = new URLSearchParams(window.location.search);
    const preselect = parseInt(params.get('turma') || '', 10);
    if (preselect) openClass(preselect, (params.get('disciplinas') || '').split(',').map((n) => parseInt(n, 10)).filter(Boolean));

})();
