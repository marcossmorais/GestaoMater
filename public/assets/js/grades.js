// Mater — Avaliações, roteiro de provas e notas (portal do docente)
(function () {

    const config = window.MATER_GRADES || { classes: [] };
    const root = document.getElementById('grades');
    if (!root) return;

    const $ = (sel) => root.querySelector(sel);
    const $$ = (sel) => Array.from(root.querySelectorAll(sel));
    const steps = { pick: $('[data-gr-step="pick"]'), exams: $('[data-gr-step="exams"]'), grades: $('[data-gr-step="grades"]') };
    const list = $('[data-gr-list]');
    const saveButton = $('[data-gr-save]');

    const state = { cls: null, exams: [], exam: null, students: [], editable: false, dirty: false, busy: false };

    const errors = {
        'sem-permissao': 'Você não tem permissão nesta turma/disciplina.',
        'unidade-fechada': 'Este bimestre está fechado para lançamentos.',
        'limite-avaliacoes': 'Este bimestre já tem as 3 avaliações.',
        'recuperacao-ja-existe': 'Este bimestre já tem recuperação.',
        'recuperacao-antes-das-avaliacoes': 'A recuperação só abre depois das 3 avaliações do bimestre.',
        'sem-recuperacao': 'Só lança recuperação quem fechou o bimestre abaixo de 7.',
        'descricao-invalida': 'Informe a descrição da avaliação.',
        'valor-invalido': 'Informe o valor da avaliação.',
        'data-invalida': 'Data inválida.',
        'texto-muito-longo': 'Roteiro longo demais.',
        'nota-invalida': 'Há uma nota inválida.',
        'nota-acima-do-valor': 'Há nota acima do valor da avaliação.',
        'aluno-fora-da-turma': 'A lista de alunos mudou. Abra a avaliação novamente.',
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

    function show(step) {
        Object.keys(steps).forEach((k) => { steps[k].hidden = k !== step; });
        document.body.classList.toggle('m-att-marking', step === 'grades');
        window.scrollTo(0, 0);
    }

    function fmt(n) {
        return Number(n).toLocaleString('pt-BR', { maximumFractionDigits: 2 });
    }

    async function api(url, body) {
        const response = await fetch(url, body ? {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(body)
        } : { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error(data.error || 'falha-ao-salvar');
            error.data = data;
            throw error;
        }
        return data;
    }

    // ---------- Passo 1
    root.addEventListener('click', (event) => {
        const card = event.target.closest('[data-gr-class]');
        if (card) openClass(parseInt(card.dataset.grClass, 10));
        const back = event.target.closest('[data-gr-back]');
        if (back) {
            if (back.dataset.grBack === 'exams' && state.dirty) {
                if (!window.confirm('Descartar as notas não salvas?')) return;
                clearDraft();
                state.dirty = false;
            }
            show(back.dataset.grBack);
        }
    });

    async function openClass(id) {
        const cls = config.classes.find((c) => c.id === id);
        if (!cls) return;
        state.cls = cls;
        $('[data-gr-title]').textContent = `${cls.label} · ${cls.discipline}`;
        $('[data-gr-subtitle]').textContent = `${cls.course} · ${cls.shift} · ${cls.students} alunos`;
        $('[data-gr-bimesters]').innerHTML = '<div class="m-empty">Carregando…</div>';
        closeNew();
        show('exams');
        await loadExams();
    }

    // Cada bimestre tem vagas fixas: 1ª, 2ª e 3ª avaliação e a recuperação (liberada depois das 3)
    function slot(exam, title, badge, emptyText, creatable, unity, recovery) {
        if (exam) {
            const custom = exam.description !== title ? ` · ${escapeHtml(exam.description)}` : '';
            return `
                <button type="button" class="m-exam" data-exam="${exam.id}">
                    <span class="m-att-class-badge">${badge}</span>
                    <span class="m-exam-body">
                        <strong>${title}</strong>
                        <small>${exam.date.split('-').reverse().join('/')} · ${exam.graded} nota${exam.graded === 1 ? '' : 's'}${exam.guide ? ' · com roteiro' : ''}${custom}</small>
                    </span>
                    <i class="fas fa-chevron-right m-att-class-go"></i>
                </button>`;
        }
        return `
            <button type="button" class="m-exam ${creatable ? 'is-empty' : 'is-locked'}" ${creatable ? `data-new-unity="${unity}" data-new-recovery="${recovery ? 1 : 0}" data-new-title="${title}"` : 'disabled'}>
                <span class="m-att-class-badge">${badge}</span>
                <span class="m-exam-body">
                    <strong>${title}</strong>
                    <small>${emptyText}</small>
                </span>
                <i class="fas ${creatable ? 'fa-plus' : 'fa-lock'} m-att-class-go"></i>
            </button>`;
    }

    function renderBimesters(data) {
        const per = data.perBimester;
        const open = data.openUnits;
        const units = Array.from(new Set(open.concat(state.exams.map((e) => e.unity)))).sort((a, b) => b - a);

        $('[data-gr-bimesters]').innerHTML = units.map((u) => {
            const editable = open.includes(u);
            const regular = state.exams.filter((e) => e.unity === u && !e.recovery);
            const recovery = state.exams.find((e) => e.unity === u && e.recovery);
            const slots = [];

            for (let i = 0; i < Math.max(per, regular.length); i++) {
                const title = `${i + 1}ª avaliação`;
                const next = i === regular.length;
                slots.push(slot(regular[i], title, `${i + 1}ª`,
                    !editable ? 'Bimestre fechado' : next ? 'Toque para criar' : `Depois da ${i}ª avaliação`,
                    editable && next, u, false));
            }

            const complete = regular.length >= per;
            slots.push(slot(recovery, 'Recuperação', '<i class="fas fa-redo"></i>',
                !editable ? 'Bimestre fechado' : complete ? `Para quem fechou o bimestre abaixo de ${fmt(data.passing)} · toque para criar` : `Liberada depois da ${per}ª avaliação`,
                editable && complete, u, true));

            return `
                <div class="card m-panel mb-3">
                    <div class="m-panel-head">
                        <h6>${u}º bimestre</h6>
                        <span class="m-pill ${complete ? 'm-pill-ok' : 'm-pill-info'}">${Math.min(regular.length, per)}/${per} avaliações</span>
                    </div>
                    <div class="m-exam-list">${slots.join('')}</div>
                </div>`;
        }).join('');

        const upcoming = [];
        for (let u = Math.max(...units) + 1; u <= 4; u++) upcoming.push(`${u}º`);
        if (upcoming.length) {
            $('[data-gr-bimesters]').insertAdjacentHTML('beforeend',
                `<p class="m-muted-note"><i class="fas fa-lock mr-2"></i>${upcoming.join(', ')} bimestre: ainda não aberto pela coordenação.</p>`);
        }
    }

    async function loadExams() {
        try {
            const data = await api(`/portal-docente/notas/avaliacoes?disciplineClass=${state.cls.id}`);
            state.exams = data.exams;
            renderBimesters(data);
        } catch (e) {
            $('[data-gr-bimesters]').innerHTML = `<div class="m-empty">${escapeHtml(errors[e.message] || 'Não foi possível carregar.')}</div>`;
        }
    }

    // ---------- Nova avaliação (a partir da vaga vazia)
    const newBox = $('[data-gr-new]');
    const newForm = $('[data-gr-exam-form]');

    function closeNew() {
        newBox.hidden = true;
        newForm.reset();
        state.newExam = null;
    }

    $('[data-gr-bimesters]').addEventListener('click', (event) => {
        const exam = event.target.closest('[data-exam]');
        if (exam) return openExam(parseInt(exam.dataset.exam, 10));

        const empty = event.target.closest('[data-new-unity]');
        if (!empty) return;
        state.newExam = { unity: parseInt(empty.dataset.newUnity, 10), recovery: empty.dataset.newRecovery === '1' };
        $('[data-gr-new-title]').textContent = `${empty.dataset.newTitle} · ${state.newExam.unity}º bimestre`;
        newBox.hidden = false;
        newBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        newForm.querySelector('[name="date"]').focus();
    });

    $('[data-gr-new-cancel]').addEventListener('click', closeNew);

    newForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (state.busy || !state.newExam) return;
        const data = new FormData(event.target);
        state.busy = true;
        try {
            const created = await api('/portal-docente/notas/avaliacao/salvar', {
                disciplineClass: state.cls.id,
                unity: state.newExam.unity,
                recovery: state.newExam.recovery,
                date: data.get('date'),
                guide: (data.get('guide') || '').trim()
            });
            closeNew();
            toast('Avaliação criada');
            await loadExams();
            openExam(created.id);
        } catch (e) {
            toast(errors[e.message] || errors['falha-ao-salvar'], 'error');
        } finally {
            state.busy = false;
        }
    });

    async function openExam(id) {
        const meta = state.exams.find((e) => e.id === id);
        state.dirty = false;
        list.innerHTML = '<div class="m-empty">Carregando…</div>';
        $('[data-gr-exam-title]').textContent = meta ? meta.description : 'Avaliação';
        $('[data-gr-exam-subtitle]').textContent = `${state.cls.label} · ${state.cls.discipline}`;
        $('[data-gr-guide]').value = meta && meta.guide ? meta.guide : '';
        $('[data-gr-guide-box]').open = false;
        show('grades');

        try {
            const data = await api(`/portal-docente/notas/alunos?exam=${id}`);
            state.exam = data.exam;
            state.students = data.students;
            state.editable = data.editable;
            $('[data-gr-closed]').hidden = data.editable;
            $('[data-gr-exam-subtitle]').textContent = `${state.cls.label} · ${state.cls.discipline} · ${data.exam.recovery ? 'recuperação · ' : ''}nota de 0 a ${fmt(data.exam.value)} · ${data.exam.unity}º bimestre`;
            state.passing = data.passing;
            state.perBimester = data.perBimester;
            render();
            restoreDraft();
        } catch (e) {
            list.innerHTML = `<div class="m-empty">${escapeHtml(errors[e.message] || 'Não foi possível carregar.')}</div>`;
        }
    }

    function initials(name) {
        const parts = name.trim().split(/\s+/);
        return ((parts[0] || '')[0] + ((parts.length > 1 ? parts[parts.length - 1] : '')[0] || '')).toUpperCase();
    }

    // Linha de apoio: média do bimestre ao vivo e quanto falta na última avaliação para fechar a média
    function hint(s, grade) {
        if (state.exam.recovery) {
            return `média do bimestre ${s.bimesterAverage === null ? '–' : fmt(s.bimesterAverage)}${s.eligible ? '' : ' · sem recuperação'}`;
        }
        const valid = grade !== null && !Number.isNaN(grade);
        const count = s.othersCount + (valid ? 1 : 0);
        const per = state.perBimester;
        const passing = state.passing;

        if (!valid && s.othersCount === per - 1) {
            const need = passing * per - s.othersSum;
            if (need <= 0) return '<b class="is-ok">já fecha a média com qualquer nota</b>';
            if (need > 10) return '<b class="is-low">não fecha a média · vai para a recuperação</b>';
            return `precisa de <b>${fmt(Math.ceil(need * 10) / 10)}</b> para fechar ${fmt(passing)}`;
        }
        if (count === 0) return '';
        const average = (s.othersSum + (valid ? grade : 0)) / count;
        if (count >= per) {
            return average < passing
                ? `<b class="is-low">média ${fmt(Math.round(average * 100) / 100)} · recuperação</b>`
                : `<b class="is-ok">média ${fmt(Math.round(average * 100) / 100)}</b>`;
        }
        return `média parcial ${fmt(Math.round(average * 100) / 100)} (${count} de ${per})`;
    }

    function render() {
        // na recuperação, quem não ficou abaixo da média aparece só para consulta
        list.innerHTML = state.students.map((s, i) => `
            <label class="m-grade-row${s.eligible ? '' : ' is-muted'}" data-student="${s.id}">
                <span class="m-grade-avatar">${s.photo ? `<img src="/assets/img/studentProfilePhotos/${escapeHtml(s.photo)}" alt="" loading="lazy">` : escapeHtml(initials(s.name))}</span>
                <span>${i + 1}. ${escapeHtml(s.name)}<small data-gr-hint>${hint(s, s.grade)}</small></span>
                <input type="text" inputmode="decimal" class="form-control" value="${s.grade === null ? '' : String(s.grade).replace('.', ',')}"
                    ${state.editable && s.eligible ? '' : 'disabled'} aria-label="Nota de ${escapeHtml(s.name)}" autocomplete="off">
            </label>`).join('') || '<div class="m-empty">Nenhum aluno na turma.</div>';
        updateCount();
    }

    // ---------- Rascunho no aparelho: nada se perde se a tela fechar antes de salvar
    const draftKey = () => `mater-notas-${state.exam.id}`;

    function saveDraft() {
        const draft = {};
        $$('[data-gr-list] [data-student]').forEach((row) => { draft[row.dataset.student] = row.querySelector('input').value; });
        try { localStorage.setItem(draftKey(), JSON.stringify(draft)); } catch (e) { /* sem armazenamento: segue sem rascunho */ }
    }

    function clearDraft() {
        try { localStorage.removeItem(draftKey()); } catch (e) { /* idem */ }
    }

    function restoreDraft() {
        let draft = null;
        try { draft = JSON.parse(localStorage.getItem(draftKey()) || 'null'); } catch (e) { draft = null; }
        if (!draft || !state.editable) return;

        let changed = 0;
        $$('[data-gr-list] [data-student]').forEach((row) => {
            const input = row.querySelector('input');
            const value = draft[row.dataset.student];
            if (value === undefined || input.disabled || value === input.value) return;
            input.value = value;
            refreshRow(row);
            changed++;
        });
        if (changed) {
            state.dirty = true;
            updateCount();
            toast(`Rascunho recuperado: ${changed} nota${changed > 1 ? 's' : ''} ainda não salva${changed > 1 ? 's' : ''}`);
        } else {
            clearDraft();
        }
    }

    function refreshRow(row) {
        const grade = parseGrade(row.querySelector('input').value);
        row.classList.toggle('is-invalid', Number.isNaN(grade) || (grade !== null && grade > state.exam.value));
        const student = state.students.find((s) => String(s.id) === row.dataset.student);
        if (student) row.querySelector('[data-gr-hint]').innerHTML = hint(student, grade);
    }

    window.addEventListener('beforeunload', (event) => {
        if (!state.dirty) return;
        event.preventDefault();
        event.returnValue = '';
    });

    function parseGrade(text) {
        const value = String(text).trim().replace(',', '.');
        if (value === '') return null;
        return /^\d+(\.\d{1,2})?$/.test(value) ? parseFloat(value) : NaN;
    }

    list.addEventListener('input', (event) => {
        const row = event.target.closest('[data-student]');
        if (!row) return;
        refreshRow(row);
        state.dirty = true;
        saveDraft();
        updateCount();
    });

    // Enter vai para o próximo aluno (lançamento rápido pelo teclado numérico)
    list.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        const inputs = $$('[data-gr-list] input');
        const next = inputs[inputs.indexOf(event.target) + 1];
        if (next) next.focus();
    });

    function updateCount() {
        const inputs = $$('[data-gr-list] [data-student]:not(.is-muted) input');
        const filled = inputs.filter((i) => i.value.trim() !== '').length;
        $('[data-gr-count]').textContent = `${filled}/${inputs.length} notas`;
        saveButton.disabled = !state.editable || state.busy;
    }

    saveButton.addEventListener('click', async () => {
        if (state.busy || !state.editable) return;

        const grades = {};
        let invalid = false;
        $$('[data-gr-list] [data-student]:not(.is-muted)').forEach((row) => {
            const grade = parseGrade(row.querySelector('input').value);
            if (Number.isNaN(grade) || (grade !== null && grade > state.exam.value)) invalid = true;
            grades[row.dataset.student] = grade;
        });
        if (invalid) {
            toast(`Corrija as notas em vermelho (de 0 a ${fmt(state.exam.value)}).`, 'error');
            return;
        }

        state.busy = true;
        saveButton.classList.add('is-loading');
        updateCount();
        try {
            await api('/portal-docente/notas/salvar', { exam: state.exam.id, grades });
            state.dirty = false;
            clearDraft();
            toast('Notas salvas');
            await loadExams();
        } catch (e) {
            toast(errors[e.message] || errors['falha-ao-salvar'], 'error');
        } finally {
            state.busy = false;
            saveButton.classList.remove('is-loading');
            updateCount();
        }
    });

    $('[data-gr-guide-save]').addEventListener('click', async () => {
        try {
            await api('/portal-docente/notas/avaliacao/salvar', { exam: state.exam.id, guide: $('[data-gr-guide]').value.trim() });
            toast('Roteiro salvo');
            await loadExams();
        } catch (e) {
            toast(errors[e.message] || errors['falha-ao-salvar'], 'error');
        }
    });

    // Atalho vindo da home: /portal-docente/notas?turma=ID&avaliacao=ID abre direto a avaliação
    const params = new URLSearchParams(window.location.search);
    const preselect = parseInt(params.get('turma') || '', 10);
    const preselectExam = parseInt(params.get('avaliacao') || '', 10);
    if (preselect) openClass(preselect).then(() => { if (preselectExam) openExam(preselectExam); });

})();
