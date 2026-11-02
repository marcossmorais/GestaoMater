// Mater — Roteiro de Provas: turma, bimestre e avaliação; ao salvar, a coordenação recebe e o aluno vê no mural
(function () {

    const form = document.querySelector('[data-guide-form]');
    if (!form) return;

    const $ = (sel) => form.querySelector(sel);
    const field = (name) => form.querySelector(`[name="${name}"]`);

    const errors = {
        'sem-permissao': 'Você não tem permissão nesta turma/disciplina.',
        'unidade-fechada': 'Este bimestre está fechado.',
        'data-invalida': 'Escolha a data da avaliação.',
        'assuntos-obrigatorios': 'Escreva pelo menos um assunto.',
        'texto-muito-longo': 'Texto longo demais.',
        'recuperacao-ja-existe': 'A recuperação deste bimestre já existe.',
        'recuperacao-antes-das-avaliacoes': 'A recuperação vem depois das avaliações do bimestre.',
        'limite-avaliacoes': 'Este bimestre já tem todas as avaliações.',
        'falha': 'Não foi possível salvar. Tente de novo.'
    };

    const state = { dc: 0, unity: 0, exams: [], units: {}, perBimester: 3, slot: null, types: new Set(), busy: false };

    function toast(message, type) {
        if (typeof tools !== 'undefined' && typeof tools.showToast === 'function') tools.showToast(message, type === 'error' ? 'bg-danger' : 'bg-success');
    }

    function hasGuide(exam) { return Boolean(exam && exam.guideStatus); }

    // uma posição por avaliação do bimestre: as criadas, a próxima a criar e a recuperação
    function slots() {
        const exams = state.exams.filter((e) => e.unity === state.unity);
        const regular = exams.filter((e) => !e.recovery);
        const list = [];
        for (let i = 0; i < Math.max(state.perBimester, regular.length); i++) {
            list.push({ key: `r${i}`, label: `${i + 1}ª`, exam: regular[i] || null, recovery: false, disabled: i > regular.length });
        }
        const recovery = exams.find((e) => e.recovery) || null;
        list.push({ key: 'rec', label: 'Recuperação', exam: recovery, recovery: true, disabled: !recovery && regular.length < state.perBimester });
        return list;
    }

    function renderSlots() {
        const list = slots();
        if (!state.slot || !list.some((s) => s.key === state.slot && !s.disabled)) {
            const next = list.find((s) => !s.disabled && !hasGuide(s.exam)) || list.find((s) => !s.disabled);
            state.slot = next ? next.key : null;
        }
        $('[data-guide-slots]').innerHTML = list.map((s) => `
            <button type="button" data-slot="${s.key}" class="${s.key === state.slot ? 'is-active' : ''}" ${s.disabled ? 'disabled title="Crie as avaliações anteriores primeiro"' : ''}>
                ${hasGuide(s.exam) ? '<i class="fas fa-check-circle mr-1" aria-label="Com roteiro"></i>' : ''}${s.label}
            </button>`).join('');
        fill(list.find((s) => s.key === state.slot) || null);
    }

    function setTypes(types) {
        state.types = new Set(types);
        form.querySelectorAll('[data-type]').forEach((chip) => {
            const active = state.types.has(chip.dataset.type);
            chip.classList.toggle('is-active', active);
            chip.setAttribute('aria-pressed', active);
            chip.querySelector('i').className = `fas ${active ? 'fa-check-square' : 'fa-square'}`;
        });
    }

    function fill(slot) {
        const exam = slot ? slot.exam : null;
        const today = new Date().toLocaleDateString('en-CA');
        field('date').value = exam ? exam.date : today;
        field('date').disabled = Boolean(exam);
        field('topics').value = exam && exam.guideTopics ? exam.guideTopics : '';
        field('material').value = exam && exam.guideMaterial ? exam.guideMaterial : '';
        field('notes').value = exam && exam.guide ? exam.guide : '';
        setTypes(exam ? exam.types : []);

        const status = $('[data-guide-status]');
        const info = exam ? exam.guideStatus : null;
        status.hidden = !info;
        if (info) status.innerHTML = `<i class="fas ${info.seen ? 'fa-check-double' : 'fa-paper-plane'} mr-2"></i>Enviado em ${info.sentAt}${info.seen ? ' · visto pela coordenação' : ' · aguardando a coordenação ver'}`;

        $('[data-guide-hint]').textContent = exam ? (info ? 'Ao salvar de novo, a coordenação recebe a nova versão' : 'Avaliação já criada, ainda sem envio') : 'A avaliação será criada ao salvar';
        $('[data-guide-save]').disabled = !slot;
    }

    async function load() {
        $('[data-guide-save]').disabled = true;
        try {
            const response = await fetch(`/portal-docente/notas/avaliacoes?disciplineClass=${state.dc}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'falha');
            state.exams = data.exams;
            state.units = data.units;
            state.perBimester = data.perBimester;
            renderSlots();
        } catch (e) {
            toast(errors[e.message] || errors.falha, 'error');
        }
    }

    $('[data-guide-class]').addEventListener('change', (event) => {
        state.dc = parseInt(event.target.value, 10);
        state.slot = null;
        load();
    });

    $('[data-guide-unity]').addEventListener('click', (event) => {
        const button = event.target.closest('[data-value]');
        if (!button) return;
        state.unity = parseInt(button.dataset.value, 10);
        state.slot = null;
        form.querySelectorAll('[data-guide-unity] button').forEach((b) => b.classList.toggle('is-active', b === button));
        renderSlots();
    });

    $('[data-guide-slots]').addEventListener('click', (event) => {
        const button = event.target.closest('[data-slot]');
        if (!button || button.disabled) return;
        state.slot = button.dataset.slot;
        renderSlots();
    });

    $('[data-guide-types]').addEventListener('click', (event) => {
        const chip = event.target.closest('[data-type]');
        if (!chip) return;
        const types = new Set(state.types);
        types.has(chip.dataset.type) ? types.delete(chip.dataset.type) : types.add(chip.dataset.type);
        setTypes(Array.from(types));
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (state.busy) return;

        const slot = slots().find((s) => s.key === state.slot);
        if (!slot) return;

        if (!field('topics').value.trim()) {
            field('topics').focus();
            toast(errors['assuntos-obrigatorios'], 'error');
            return;
        }

        const payload = {
            topics: field('topics').value,
            material: field('material').value,
            notes: field('notes').value,
            types: Array.from(state.types)
        };
        if (slot.exam) {
            payload.exam = slot.exam.id;
        } else {
            payload.disciplineClass = state.dc;
            payload.unity = state.unity;
            payload.recovery = slot.recovery;
            payload.date = field('date').value;
        }

        state.busy = true;
        const save = $('[data-guide-save]');
        save.disabled = true;
        try {
            const response = await fetch('/portal-docente/roteiros/salvar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(payload)
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(result.error || 'falha');
            toast('Roteiro enviado à coordenação');
            await load();
        } catch (e) {
            toast(errors[e.message] || errors.falha, 'error');
            save.disabled = false;
        } finally {
            state.busy = false;
        }
    });

    // Início: turma pedida na URL (?turma=ID) ou a primeira; bimestre aberto mais recente
    const preselect = new URLSearchParams(window.location.search).get('turma');
    const select = $('[data-guide-class]');
    if (preselect && Array.from(select.options).some((o) => o.value === preselect)) select.value = preselect;
    state.dc = parseInt(select.value, 10);
    state.unity = parseInt($('[data-guide-unity] .is-active').dataset.value, 10);

    load();

})();
