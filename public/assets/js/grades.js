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
        'unidade-fechada': 'Esta unidade está fechada para lançamentos.',
        'descricao-invalida': 'Informe a descrição da avaliação.',
        'valor-invalido': 'Informe o valor da avaliação.',
        'data-invalida': 'Data inválida.',
        'pontos-excedidos': 'A unidade passaria de 10 pontos.',
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
            if (back.dataset.grBack === 'exams' && state.dirty && !window.confirm('Descartar as notas não salvas?')) return;
            show(back.dataset.grBack);
        }
    });

    async function openClass(id) {
        const cls = config.classes.find((c) => c.id === id);
        if (!cls) return;
        state.cls = cls;
        $('[data-gr-title]').textContent = `${cls.label} · ${cls.discipline}`;
        $('[data-gr-subtitle]').textContent = `${cls.course} · ${cls.shift} · ${cls.students} alunos`;
        $('[data-gr-exams]').innerHTML = '<div class="m-empty">Carregando…</div>';
        $('[data-gr-new]').open = false;
        show('exams');
        await loadExams();
    }

    async function loadExams() {
        try {
            const data = await api(`/portal-docente/notas/avaliacoes?disciplineClass=${state.cls.id}`);
            state.exams = data.exams;

            $('[data-gr-units]').innerHTML = Object.entries(data.unitPoints).map(([u, pts]) =>
                `<span class="m-pill ${pts >= data.maxUnitPoints ? 'm-pill-ok' : 'm-pill-info'}">${u}ª un.: ${fmt(pts)}/${data.maxUnitPoints} pts distribuídos</span>`
            ).join('');

            $('[data-gr-exams]').innerHTML = state.exams.length ? state.exams.map((e) => `
                <button type="button" class="m-exam" data-exam="${e.id}">
                    <span class="m-att-class-badge">${e.unity}ª</span>
                    <span class="m-exam-body">
                        <strong>${escapeHtml(e.description)}</strong>
                        <small>${fmt(e.value)} pts · ${e.date.split('-').reverse().join('/')} · ${e.graded}/${state.cls.students} notas${e.guide ? ' · com roteiro' : ''}</small>
                    </span>
                    <i class="fas fa-chevron-right m-att-class-go"></i>
                </button>`).join('') : '<div class="m-empty">Nenhuma avaliação nesta disciplina.</div>';
        } catch (e) {
            $('[data-gr-exams]').innerHTML = `<div class="m-empty">${escapeHtml(errors[e.message] || 'Não foi possível carregar.')}</div>`;
        }
    }

    // ---------- Nova avaliação
    $('[data-gr-exam-form]').addEventListener('submit', async (event) => {
        event.preventDefault();
        if (state.busy) return;
        const data = new FormData(event.target);
        state.busy = true;
        try {
            await api('/portal-docente/notas/avaliacao/salvar', {
                disciplineClass: state.cls.id,
                description: (data.get('description') || '').trim(),
                unity: parseInt(data.get('unity'), 10),
                value: parseFloat(String(data.get('value') || '0').replace(',', '.')),
                date: data.get('date'),
                guide: (data.get('guide') || '').trim()
            });
            event.target.reset();
            $('[data-gr-new]').open = false;
            toast('Avaliação criada');
            await loadExams();
        } catch (e) {
            const extra = e.message === 'pontos-excedidos' && e.data ? ` Restam ${fmt(e.data.available)} pts.` : '';
            toast((errors[e.message] || errors['falha-ao-salvar']) + extra, 'error');
        } finally {
            state.busy = false;
        }
    });

    // ---------- Passo 3: notas
    $('[data-gr-exams]').addEventListener('click', (event) => {
        const button = event.target.closest('[data-exam]');
        if (button) openExam(parseInt(button.dataset.exam, 10));
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
            $('[data-gr-exam-subtitle]').textContent = `${state.cls.label} · ${state.cls.discipline} · vale ${fmt(data.exam.value)} pts · ${data.exam.unity}ª unidade`;
            render();
        } catch (e) {
            list.innerHTML = `<div class="m-empty">${escapeHtml(errors[e.message] || 'Não foi possível carregar.')}</div>`;
        }
    }

    function render() {
        list.innerHTML = state.students.map((s, i) => `
            <label class="m-grade-row" data-student="${s.id}">
                <span>${i + 1}. ${escapeHtml(s.name)}</span>
                <input type="text" inputmode="decimal" class="form-control" value="${s.grade === null ? '' : String(s.grade).replace('.', ',')}"
                    ${state.editable ? '' : 'disabled'} aria-label="Nota de ${escapeHtml(s.name)}" autocomplete="off">
            </label>`).join('') || '<div class="m-empty">Nenhum aluno na turma.</div>';
        updateCount();
    }

    function parseGrade(text) {
        const value = String(text).trim().replace(',', '.');
        if (value === '') return null;
        return /^\d+(\.\d{1,2})?$/.test(value) ? parseFloat(value) : NaN;
    }

    list.addEventListener('input', (event) => {
        const row = event.target.closest('[data-student]');
        if (!row) return;
        const grade = parseGrade(event.target.value);
        row.classList.toggle('is-invalid', Number.isNaN(grade) || (grade !== null && grade > state.exam.value));
        state.dirty = true;
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
        const inputs = $$('[data-gr-list] input');
        const filled = inputs.filter((i) => i.value.trim() !== '').length;
        $('[data-gr-count]').textContent = `${filled}/${inputs.length} notas`;
        saveButton.disabled = !state.editable || state.busy;
    }

    saveButton.addEventListener('click', async () => {
        if (state.busy || !state.editable) return;

        const grades = {};
        let invalid = false;
        $$('[data-gr-list] [data-student]').forEach((row) => {
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

})();
