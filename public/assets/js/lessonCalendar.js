// Mater — Calendário do diário: o que foi dado em cada dia, por mês ou semana, e o planejamento das próximas aulas
(function () {

    const diary = window.MATER_DIARY;
    const config = window.MATER_LESSONS || {};
    const root = document.getElementById('lessons');
    const box = root && root.querySelector('[data-cal]');
    if (!diary || !box) return;

    const $ = (sel) => root.querySelector(sel);
    const { escapeHtml, toast, chip } = diary;

    const months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    const weekdays = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'];
    const eventTypes = { feriado: 'Feriado', recesso: 'Recesso', prova: 'Prova', reuniao: 'Reunião', evento: 'Evento', outro: 'Evento' };

    const errors = {
        'sem-permissao': 'Você não tem permissão nesta turma/disciplina.',
        'data-invalida': 'Escolha uma data válida.',
        'titulo-obrigatorio': 'Dê um título para o plano.',
        'quantidade-invalida': 'A quantidade de aulas vai de 1 a 10.',
        'codigo-bncc-invalido': 'Código BNCC inválido. Ex.: EF05MA01 ou EM13LGG101.',
        'texto-muito-longo': 'Texto longo demais.',
        'atividade-invalida': 'Atividade inválida.',
        'nao-autenticado': 'Sua sessão expirou. Entre novamente.',
        'falha-ao-salvar': 'Não foi possível salvar. Tente de novo.'
    };

    const storedView = (() => { try { return localStorage.getItem('mater-diario-visao'); } catch (e) { return null; } })();

    const state = {
        view: storedView === 'week' ? 'week' : 'month',
        anchor: parse(config.today),
        selected: config.today,
        filter: 0,
        data: { lessons: [], plans: [], exams: [], events: [], slots: [] },
        request: 0
    };

    // ---------- Datas (sempre ao meio-dia, para o horário de verão não trocar o dia)
    function parse(iso) { return new Date(`${iso}T12:00:00`); }
    function iso(date) {
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    }
    function addDays(date, days) {
        const copy = new Date(date);
        copy.setDate(copy.getDate() + days);
        return copy;
    }
    function monday(date) { return addDays(date, -((date.getDay() + 6) % 7)); }
    function weekday(date) { return ((date.getDay() + 6) % 7) + 1; }
    function short(isoDate) { return isoDate.split('-').reverse().slice(0, 2).join('/'); }

    function range() {
        if (state.view === 'week') {
            const start = monday(state.anchor);
            return { start, end: addDays(start, 6) };
        }
        const first = new Date(state.anchor.getFullYear(), state.anchor.getMonth(), 1, 12);
        const last = new Date(state.anchor.getFullYear(), state.anchor.getMonth() + 1, 0, 12);
        return { start: monday(first), end: addDays(monday(last), 6) };
    }

    // ---------- Dados
    function classOf(id) { return config.classes.find((c) => c.id === id); }
    function classLabel(id) {
        const c = classOf(id);
        return c ? `${c.label} · ${c.discipline}` : '';
    }

    const visible = (item) => !state.filter || item.dc === state.filter;

    function dayItems(date) {
        const d = state.data;
        const lessons = d.lessons.filter((l) => l.date === date && visible(l));
        const plans = d.plans.filter((p) => p.date === date && visible(p));
        const exams = d.exams.filter((e) => e.date === date && visible(e));
        const events = d.events.filter((e) => e.start <= date && e.finish >= date);
        const slots = d.slots.filter((s) => s.weekday === weekday(parse(date)) && visible(s));
        return { lessons, plans, exams, events, slots };
    }

    async function load() {
        const { start, end } = range();
        const request = ++state.request;
        try {
            const response = await fetch(`/portal-docente/diario/calendario?inicio=${iso(start)}&fim=${iso(end)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await response.json();
            if (request !== state.request) return;
            if (!response.ok) throw new Error(data.error);
            state.data = data;
        } catch (e) {
            if (request !== state.request) return;
            $('[data-cal-grid]').innerHTML = '<div class="m-empty">Não foi possível carregar o calendário. Recarregue a página.</div>';
            return;
        }
        render();
    }

    // ---------- Desenho
    const kinds = {
        lesson: ['fa-check', 'Aula dada'],
        plan: ['fa-pen', 'Planejada'],
        late: ['fa-exclamation', 'Sem registro'],
        open: ['fa-plus', 'Sem plano'],
        exam: ['fa-file-alt', 'Avaliação'],
        event: ['fa-flag', 'Evento da escola']
    };

    function pill(kind, text, title) {
        return `<span class="m-cal-pill is-${kind}" title="${escapeHtml(title || text)}"><i class="fas ${kinds[kind][0]}" aria-hidden="true"></i><span>${escapeHtml(text)}</span></span>`;
    }

    function openSlots(date, items) {
        if (date < config.today) return 0;
        return items.slots.filter((s) => !items.lessons.some((l) => l.dc === s.dc) && !items.plans.some((p) => p.dc === s.dc)).length;
    }

    function dayPills(date) {
        const items = dayItems(date);
        const pills = [];
        const label = (dc) => (classOf(dc) || {}).label || '';
        items.events.forEach((e) => pills.push(pill('event', e.title, `${eventTypes[e.type] || 'Evento'}: ${e.title}`)));
        items.exams.forEach((e) => pills.push(pill('exam', `${label(e.dc)}: ${e.description}`, `${classLabel(e.dc)}: ${e.description}`)));
        items.lessons.forEach((l) => {
            const plan = items.plans.find((p) => p.dc === l.dc && p.given);
            pills.push(pill('lesson', `${label(l.dc)}: ${plan ? plan.title : (classOf(l.dc) || {}).discipline || ''}`, `Aula dada: ${classLabel(l.dc)}${plan ? ` · ${plan.title}` : ''}`));
        });
        items.plans.filter((p) => !p.given).forEach((p) => pills.push(pill(date < config.today ? 'late' : 'plan', `${label(p.dc)}: ${p.title}`, `${date < config.today ? 'Planejada, sem registro' : 'Planejada'}: ${classLabel(p.dc)} · ${p.title}`)));
        const open = openSlots(date, items);
        if (open) pills.push(pill('open', `${open} aula${open > 1 ? 's' : ''} sem plano`));
        return { pills, items };
    }

    // legenda com o total de cada cor no período mostrado
    function renderLegend(counts) {
        $('[data-cal-legend]').innerHTML = Object.keys(kinds).map((kind) => `
            <span class="is-${kind}"><i class="fas ${kinds[kind][0]}" aria-hidden="true"></i>${kinds[kind][1]}<b>${counts[kind] || 0}</b></span>`).join('');
    }

    function countPills(date, counts) {
        dayPills(date).pills.forEach((p) => {
            const kind = p.match(/is-(\w+)/)[1];
            const match = kind === 'open' ? parseInt(p.match(/<span>(\d+)/)[1], 10) : 1;
            counts[kind] = (counts[kind] || 0) + match;
        });
    }

    function dayNumber(date, cell) {
        const today = date === config.today;
        return `<span class="m-cal-num${today ? ' is-today' : ''}" ${today ? 'aria-current="date"' : ''}>${cell.getDate()}</span>`;
    }

    function renderMonth() {
        const { start, end } = range();
        const month = state.anchor.getMonth();
        const cells = [];
        const counts = {};
        for (let day = start; day <= end; day = addDays(day, 1)) {
            const date = iso(day);
            const { pills } = dayPills(date);
            if (day.getMonth() === month) countPills(date, counts);
            cells.push(`
                <button type="button" class="m-cal-day${day.getMonth() !== month ? ' is-out' : ''}${date === state.selected ? ' is-selected' : ''}" data-cal-date="${date}" aria-label="${weekdays[weekday(day) - 1]}, ${short(date)}${pills.length ? `: ${pills.length} registro${pills.length > 1 ? 's' : ''}` : ''}">
                    ${dayNumber(date, day)}
                    <span class="m-cal-pills">${pills.join('')}</span>
                    <span class="m-cal-dots" aria-hidden="true">${pills.slice(0, 6).map((p) => `<i class="${p.match(/is-\w+/)[0]}"></i>`).join('')}</span>
                </button>`);
        }
        renderLegend(counts);
        return `
            <div class="m-cal-weekdays" aria-hidden="true">${weekdays.map((w) => `<span>${w.slice(0, 3)}</span>`).join('')}</div>
            <div class="m-cal-month">${cells.join('')}</div>`;
    }

    function renderWeek() {
        const { start } = range();
        const columns = [];
        const counts = {};
        for (let i = 0; i < 7; i++) {
            const day = addDays(start, i);
            const date = iso(day);
            const items = dayItems(date);
            countPills(date, counts);
            const rows = items.slots.map((s) => {
                const lesson = items.lessons.some((l) => l.dc === s.dc);
                const plan = items.plans.find((p) => p.dc === s.dc && !p.given);
                const state_ = lesson ? 'lesson' : plan ? (date < config.today ? 'late' : 'plan') : 'open';
                const given = items.plans.find((p) => p.dc === s.dc && p.given);
                const theme = plan || given;
                return `<span class="m-cal-slot is-${state_}"><b>${s.start}</b> ${escapeHtml(classLabel(s.dc))}${theme ? `<small>${escapeHtml(theme.title)}</small>` : ''}</span>`;
            });
            // aula dada ou planejada fora do horário fixo (reposição, troca) também aparece
            items.lessons.filter((l) => !items.slots.some((s) => s.dc === l.dc)).forEach((l) => rows.push(`<span class="m-cal-slot is-lesson">${escapeHtml(classLabel(l.dc))}</span>`));
            items.plans.filter((p) => !p.given && !items.slots.some((s) => s.dc === p.dc)).forEach((p) => rows.push(`<span class="m-cal-slot is-${date < config.today ? 'late' : 'plan'}">${escapeHtml(classLabel(p.dc))}<small>${escapeHtml(p.title)}</small></span>`));
            const { pills } = dayPills(date);
            const extras = pills.filter((p) => p.includes('is-event') || p.includes('is-exam')).join('');
            columns.push(`
                <button type="button" class="m-cal-col${date === state.selected ? ' is-selected' : ''}" data-cal-date="${date}">
                    <span class="m-cal-col-head">${weekdays[i].slice(0, 3)} ${dayNumber(date, day)}</span>
                    <span class="m-cal-pills">${extras}${rows.join('') || (extras ? '' : '<small class="m-cal-free">Sem aulas</small>')}</span>
                </button>`);
        }
        renderLegend(counts);
        return `<div class="m-cal-week">${columns.join('')}</div>`;
    }

    function renderTitle() {
        const { start } = range();
        if (state.view === 'month') {
            $('[data-cal-title]').textContent = `${months[state.anchor.getMonth()]} de ${state.anchor.getFullYear()}`;
        } else {
            $('[data-cal-title]').textContent = `Semana de ${short(iso(start))} a ${short(iso(addDays(start, 6)))}`;
        }
    }

    function detail(label, text) {
        return text ? `<div class="m-plan-field"><span class="m-att-label">${label}</span><p>${escapeHtml(text).replace(/\n/g, '<br>')}</p></div>` : '';
    }

    function lessonDetails(l) {
        const exception = l.substitute || l.taught
            ? `<div class="m-lesson-item-exc"><i class="fas fa-exchange-alt mr-1"></i>${escapeHtml([l.substitute ? `Substituto: ${l.substitute}` : '', l.taught ? `Matéria dada: ${l.taught}` : ''].filter(Boolean).join(' · '))}${l.reason ? ` — ${escapeHtml(l.reason)}` : ''}</div>`
            : '';
        return `
            ${l.bnccCode || l.bnccSkill ? `<p><span class="m-lesson-code-tag">${escapeHtml(l.bnccCode || 'BNCC')}</span> ${escapeHtml(l.bnccSkill || '')}</p>` : ''}
            ${l.activities.length ? `<div class="m-lesson-tags">${l.activities.map((a) => `<span>${escapeHtml(a)}</span>`).join('')}</div>` : ''}
            ${l.notes ? `<p class="m-lesson-notes">${escapeHtml(l.notes)}</p>` : ''}
            ${exception}`;
    }

    function planDetails(p) {
        const activities = p.activities.map((a) => config.activities[a] || a);
        return `
            ${p.bnccCode || p.bnccSkill ? `<p><span class="m-lesson-code-tag">${escapeHtml(p.bnccCode || 'BNCC')}</span> ${escapeHtml(p.bnccSkill || '')}</p>` : ''}
            ${detail('Objetivos', p.objectives)}
            ${detail('Passo a passo', p.development)}
            ${activities.length ? `<div class="m-lesson-tags">${activities.map((a) => `<span>${escapeHtml(a)}</span>`).join('')}</div>` : ''}
            ${detail('Recursos', p.resources)}
            ${detail('Como avaliar', p.assessment)}
            ${detail('Tarefa de casa', p.homework)}
            <div class="m-plan-actions">
                <button type="button" class="btn btn-light" data-cal-action="plan-edit" data-id="${p.id}"><i class="fas fa-pen mr-2"></i>Editar</button>
                <button type="button" class="btn btn-light" data-cal-action="plan-copy" data-id="${p.id}"><i class="fas fa-copy mr-2"></i>Copiar para outra turma ou data</button>
                <button type="button" class="btn btn-light" data-cal-action="plan-delete" data-id="${p.id}"><i class="fas fa-trash mr-2"></i>Excluir</button>
            </div>`;
    }

    // Uma linha por aula do dia: juntas a aula do horário, o plano e o registro
    function dayEntries(date, items) {
        const plans = items.plans.slice();
        const lessons = items.lessons.slice();
        const take = (list, dc) => {
            const i = list.findIndex((x) => x.dc === dc);
            return i < 0 ? null : list.splice(i, 1)[0];
        };
        const entries = items.slots.map((s) => ({ dc: s.dc, slot: s, plan: take(plans, s.dc), lesson: take(lessons, s.dc) }));
        // reposição ou troca: aula ou plano fora do horário fixo
        lessons.forEach((l) => entries.push({ dc: l.dc, slot: null, plan: take(plans, l.dc), lesson: l }));
        plans.forEach((p) => entries.push({ dc: p.dc, slot: null, plan: p, lesson: null }));
        return entries;
    }

    const statuses = {
        lesson: ['Aula dada', 'fa-check'],
        plan: ['Planejada', 'fa-pen'],
        late: ['Planejada, falta registrar', 'fa-exclamation'],
        missing: ['Sem registro', 'fa-minus'],
        open: ['Sem plano', 'fa-plus']
    };

    function entryCard(entry, date) {
        const c = classOf(entry.dc) || {};
        const { plan, lesson, slot } = entry;
        const past = date < config.today;
        const canRegister = date <= config.today;
        const status = lesson || (plan && plan.given) ? 'lesson' : plan ? (past ? 'late' : 'plan') : past ? 'missing' : 'open';

        const actions = [];
        if (!lesson && canRegister && !(plan && plan.given)) {
            actions.push(`<button type="button" class="btn btn-primary" data-cal-action="${plan ? 'lesson-from-plan' : 'lesson-new'}" data-id="${plan ? plan.id : ''}" data-dc="${entry.dc}"><i class="fas fa-check mr-2"></i>Registrar aula</button>`);
        }

        const quick = status === 'open' ? `
            <form class="m-cal-quick" data-cal-quick data-dc="${entry.dc}" data-lessons="${slot ? slot.count || 1 : 1}">
                <input type="text" class="form-control" maxlength="160" required placeholder="Qual o tema desta aula?" aria-label="Tema da aula de ${escapeHtml(classLabel(entry.dc))}">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <button type="button" class="btn btn-light" data-cal-action="plan-new" data-dc="${entry.dc}">Mais detalhes</button>
            </form>` : '';

        if (status === 'missing') actions.push(`<button type="button" class="btn btn-light" data-cal-action="plan-new" data-dc="${entry.dc}">Planejar</button>`);

        return `
            <li class="m-cal-entry is-${status}">
                <div class="m-cal-entry-time">${slot ? `<strong>${slot.start}</strong><span>${slot.finish}</span>` : '<span>Fora do horário</span>'}</div>
                <div class="m-cal-entry-body">
                    <div class="m-cal-entry-head">
                        <span class="m-att-class-badge m-badge-sm">${escapeHtml(c.label || '')}</span>
                        <strong>${escapeHtml(c.discipline || '')}</strong>
                        ${slot && slot.count > 1 ? `<small>${slot.count} aulas seguidas</small>` : ''}
                        <span class="m-cal-status"><i class="fas ${statuses[status][1]}" aria-hidden="true"></i>${statuses[status][0]}</span>
                    </div>
                    ${plan ? `<p class="m-cal-entry-theme">${escapeHtml(plan.title)}</p>` : ''}
                    ${quick}
                    ${actions.length ? `<div class="m-plan-actions">${actions.join('')}</div>` : ''}
                    ${lesson ? `<details class="m-cal-more-info"><summary>Ver o que foi feito</summary>${lessonDetails(lesson)}</details>` : ''}
                    ${plan ? `<details class="m-cal-more-info"><summary>Ver plano completo</summary>${planDetails(plan)}</details>` : ''}
                </div>
            </li>`;
    }

    function renderDay() {
        const date = state.selected;
        const day = parse(date);
        const items = dayItems(date);
        const dayName = date === config.today ? 'Hoje' : weekdays[weekday(day) - 1];
        const entries = dayEntries(date, items);

        const events = items.events.map((e) => `<li>${pill('event', eventTypes[e.type] || 'Evento')} ${escapeHtml(e.title)}</li>`).join('');
        const exams = items.exams.map((e) => `<li>${pill('exam', 'Avaliação')} ${escapeHtml(classLabel(e.dc))}: ${escapeHtml(e.description)}</li>`).join('');

        $('[data-cal-day]').innerHTML = `
            <div class="m-panel-head">
                <h6>${dayName} · ${short(date)} <small>${entries.length ? `${entries.length} aula${entries.length > 1 ? 's' : ''}` : ''}</small></h6>
                <button type="button" class="btn btn-light" data-cal-action="plan-new"><i class="fas fa-plus mr-2"></i>Planejar outra aula</button>
            </div>
            ${events || exams ? `<ul class="m-cal-notes">${events}${exams}</ul>` : ''}
            ${entries.length ? `<ul class="m-cal-entries">${entries.map((entry) => entryCard(entry, date)).join('')}</ul>` : ''}
            ${!entries.length && !events && !exams ? '<div class="m-empty">Nenhuma aula neste dia.</div>' : ''}`;
    }

    function renderFilter() {
        const filter = $('[data-cal-filter]');
        filter.hidden = config.classes.length < 2;
        filter.innerHTML = [{ id: 0, name: 'Todas' }].concat(config.classes.map((c) => ({ id: c.id, name: `${c.label} · ${c.discipline}` })))
            .map((c) => `<button type="button" class="m-att-subject${c.id === state.filter ? ' is-active' : ''}" data-cal-filter-value="${c.id}">${escapeHtml(c.name)}</button>`).join('');
    }

    function render() {
        renderTitle();
        renderFilter();
        box.querySelectorAll('[data-view]').forEach((b) => b.classList.toggle('is-active', b.dataset.view === state.view));
        $('[data-cal-grid]').innerHTML = state.view === 'month' ? renderMonth() : renderWeek();
        renderDay();
    }

    // ---------- Navegação
    function move(step) {
        if (state.view === 'week') {
            state.anchor = addDays(state.anchor, step * 7);
            state.selected = iso(monday(state.anchor));
        } else {
            state.anchor = new Date(state.anchor.getFullYear(), state.anchor.getMonth() + step, 1, 12);
            state.selected = iso(state.anchor);
        }
        load();
    }

    box.addEventListener('click', (event) => {
        const view = event.target.closest('[data-view]');
        if (view) {
            state.view = view.dataset.view;
            state.anchor = parse(state.selected);
            try { localStorage.setItem('mater-diario-visao', state.view); } catch (e) { /* preferência opcional */ }
            return load();
        }

        const nav = event.target.closest('[data-cal-move]');
        if (nav) return move(parseInt(nav.dataset.calMove, 10));

        if (event.target.closest('[data-cal-today]')) {
            state.anchor = parse(config.today);
            state.selected = config.today;
            return load();
        }

        const filter = event.target.closest('[data-cal-filter-value]');
        if (filter) {
            state.filter = parseInt(filter.dataset.calFilterValue, 10);
            return render();
        }

        const day = event.target.closest('[data-cal-date]');
        if (day) {
            state.selected = day.dataset.calDate;
            box.querySelectorAll('[data-cal-date]').forEach((b) => b.classList.toggle('is-selected', b.dataset.calDate === state.selected));
            renderDay();
            if (window.innerWidth < 768) $('[data-cal-day]').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });

    // ---------- Ações do dia
    const planOf = (id) => state.data.plans.find((p) => p.id === id);

    $('[data-cal-day]').addEventListener('click', async (event) => {
        const button = event.target.closest('[data-cal-action]');
        if (!button) return;
        const id = parseInt(button.dataset.id || '0', 10);
        const dc = parseInt(button.dataset.dc || '0', 10);

        switch (button.dataset.calAction) {
            case 'lesson-new': return diary.openLesson(dc, state.selected);
            case 'lesson-from-plan': { const plan = planOf(id); return diary.openLesson(plan.dc, plan.date, plan); }
            case 'plan-new': return openPlan({ dc: dc || state.filter, date: state.selected });
            case 'plan-edit': return openPlan(planOf(id));
            case 'plan-copy': { const plan = planOf(id); return openPlan(Object.assign({}, plan, { id: null, given: false }), true); }
            case 'plan-delete': return removePlan(id);
        }
    });

    // Tema direto na lista do dia: o jeito mais rápido de planejar
    $('[data-cal-day]').addEventListener('submit', async (event) => {
        const form = event.target.closest('[data-cal-quick]');
        if (!form) return;
        event.preventDefault();

        const input = form.querySelector('input');
        const title = input.value.trim();
        if (!title) return input.focus();

        const button = form.querySelector('[type="submit"]');
        button.disabled = true;
        try {
            const response = await fetch('/portal-docente/planejamento/salvar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ disciplineClass: parseInt(form.dataset.dc, 10), date: state.selected, title, lessons: parseInt(form.dataset.lessons, 10) || 1, activities: [] })
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(result.error || 'falha-ao-salvar');
            toast('Tema salvo');
            load();
        } catch (e) {
            button.disabled = false;
            toast(errors[e.message] || errors['falha-ao-salvar'], 'error');
        }
    });

    async function removePlan(id) {
        if (!window.confirm('Excluir este plano de aula?')) return;
        try {
            const response = await fetch('/portal-docente/planejamento/excluir', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ id })
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(result.error || 'falha-ao-salvar');
            toast('Plano excluído');
            load();
        } catch (e) {
            toast(errors[e.message] || errors['falha-ao-salvar'], 'error');
        }
    }

    // ---------- Formulário do plano
    const planForm = $('[data-ls-step="plan"]');
    const field = (name) => planForm.querySelector(`[name="${name}"]`);
    const planState = { id: null, activities: new Set(), saving: false };

    const planBncc = diary.bnccWidget(planForm.querySelector('.m-bncc'), {
        disciplineClass: () => parseInt(field('disciplineClass').value, 10),
        code: field('bnccCode'),
        skill: field('bnccSkill'),
        tag: planForm.querySelector('[data-plan-bncc-tag]')
    });

    function openPlan(plan, copying) {
        planState.id = plan.id || null;
        planState.activities = new Set(plan.activities || []);
        planForm.reset();
        field('disciplineClass').value = plan.dc || (config.classes[0] ? config.classes[0].id : '');
        field('date').value = plan.date;
        field('title').value = plan.title || '';
        field('lessons').value = plan.lessons || 1;
        ['objectives', 'development', 'resources', 'assessment', 'homework'].forEach((name) => { field(name).value = plan[name] || ''; });
        planBncc.reset(plan.bnccCode, plan.bnccSkill || '');
        planForm.querySelectorAll('[data-plan-activity]').forEach((b) => chip(b, planState.activities.has(b.dataset.planActivity)));
        planForm.querySelector('[data-plan-more]').open = ['bnccCode', 'bnccSkill', 'objectives', 'development', 'resources', 'assessment', 'homework'].some((name) => plan[name]);
        $('[data-plan-title]').textContent = copying ? 'Copiar plano de aula' : plan.id ? 'Editar plano de aula' : 'Planejar aula';
        diary.show('plan');
        (copying ? field('date') : field('title')).focus();
    }

    $('[data-plan-activities]').addEventListener('click', (event) => {
        const button = event.target.closest('[data-plan-activity]');
        if (!button) return;
        const key = button.dataset.planActivity;
        planState.activities.has(key) ? planState.activities.delete(key) : planState.activities.add(key);
        chip(button, planState.activities.has(key));
    });

    $('[data-plan-back]').addEventListener('click', () => diary.show('pick'));

    planForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (planState.saving) return;

        const payload = {
            id: planState.id,
            disciplineClass: parseInt(field('disciplineClass').value, 10),
            date: field('date').value,
            title: field('title').value.trim(),
            lessons: parseInt(field('lessons').value, 10) || 1,
            bnccCode: field('bnccCode').value.trim().toUpperCase(),
            activities: Array.from(planState.activities)
        };
        ['bnccSkill', 'objectives', 'development', 'resources', 'assessment', 'homework'].forEach((name) => { payload[name] = field(name).value.trim(); });

        if (!payload.title) {
            field('title').focus();
            toast(errors['titulo-obrigatorio'], 'error');
            return;
        }

        planState.saving = true;
        const save = $('[data-plan-save]');
        save.disabled = true;
        try {
            const response = await fetch('/portal-docente/planejamento/salvar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(payload)
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(result.error || 'falha-ao-salvar');
            state.selected = result.date;
            state.anchor = parse(result.date);
            diary.show('pick');
            toast('Plano salvo');
            load();
        } catch (e) {
            toast(errors[e.message] || errors['falha-ao-salvar'], 'error');
        } finally {
            planState.saving = false;
            save.disabled = false;
        }
    });

    diary.onLessonSaved = (date) => {
        state.selected = date;
        state.anchor = parse(date);
        load();
    };

    load();

})();
