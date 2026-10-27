// Mater — Desempenho, simulador de notas e cronograma (portal do aluno)
(function () {

    const rule = window.MATER_PERFORMANCE || { units: 3, maxPerUnit: 10, passing: 6, minAttendance: 75 };

    function fmt(n) {
        return Number(n).toLocaleString('pt-BR', { maximumFractionDigits: 1 });
    }

    function escapeHtml(text) {
        return String(text).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    // Mesma conta de App/Tools/GradeSimulator.php
    function simulate(units) {
        let sum = 0;
        let missing = 0;
        for (let u = 1; u <= rule.units; u++) {
            const grade = units[u];
            if (grade === null || grade === undefined) missing++;
            else sum += Math.min(grade, rule.maxPerUnit);
        }
        const remaining = rule.passing * rule.units - sum;
        const average = sum / rule.units;
        if (remaining <= 0.0001) return { status: 'aprovado', needed: null, average, missing };
        if (missing === 0) return { status: 'impossivel', needed: null, average, missing };
        const needed = Math.ceil(Math.round(remaining / missing * 10000) / 10000 * 10) / 10;
        return { status: needed > rule.maxPerUnit ? 'impossivel' : 'possivel', needed, average, missing };
    }

    // ---------- Simulador por disciplina
    const performance = document.getElementById('studentPerformance');
    if (performance) {
        performance.addEventListener('input', (event) => {
            const input = event.target.closest('[data-try-unit]');
            if (!input) return;
            const card = input.closest('[data-perf]');
            const base = JSON.parse(card.dataset.perf).units;
            const units = {};
            Object.keys(base).forEach((u) => { units[u] = base[u]; });

            let invalid = false;
            let typed = 0;
            card.querySelectorAll('[data-try-unit]').forEach((field) => {
                const raw = field.value.trim().replace(',', '.');
                if (raw === '') return;
                const value = Number(raw);
                const bad = !/^\d+(\.\d+)?$/.test(raw) || value > rule.maxPerUnit;
                field.classList.toggle('is-invalid', bad);
                if (bad) invalid = true;
                else { units[field.dataset.tryUnit] = value; typed++; }
            });

            const result = card.querySelector('[data-try-result]');
            if (invalid) {
                result.textContent = `Use notas de 0 a ${fmt(rule.maxPerUnit)}.`;
                return;
            }
            if (typed === 0) {
                result.textContent = 'Digite as notas que você espera tirar.';
                return;
            }

            const sim = simulate(units);
            if (sim.status === 'aprovado') {
                result.innerHTML = `<i class="fas fa-check-circle mr-1"></i>Com essas notas sua média seria <strong>${fmt(sim.average)}</strong>: aprovado(a)!`;
            } else if (sim.needed !== null) {
                result.innerHTML = `Ainda faltaria <strong>${fmt(sim.needed)}</strong> em cada unidade restante${sim.status === 'impossivel' ? ' (acima do máximo)' : ''}.`;
            } else {
                result.innerHTML = `Sua média seria <strong>${fmt(sim.average)}</strong>, abaixo de ${fmt(rule.passing)}.`;
            }
        });
    }

    // ---------- Cronograma
    const agenda = document.getElementById('studentAgenda');
    if (agenda) {
        const events = window.MATER_AGENDA || [];
        const list = agenda.querySelector('[data-agenda-list]');
        const types = {
            evento: ['Evento', 'fa-star'], feriado: ['Feriado', 'fa-umbrella-beach'], prova: ['Prova', 'fa-file-alt'],
            reuniao: ['Reunião', 'fa-users'], recesso: ['Recesso', 'fa-pause-circle'], outro: ['Aviso', 'fa-info-circle'],
            avaliacao: ['Avaliação', 'fa-pen']
        };
        const weekdays = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];

        const iso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        const parse = (s) => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };

        function range(kind) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            if (kind === 'day') return [today, today];
            if (kind === 'week') {
                const start = new Date(today);
                start.setDate(today.getDate() - today.getDay());
                const end = new Date(start);
                end.setDate(start.getDate() + 6);
                return [start, end];
            }
            if (kind === 'month') return [new Date(today.getFullYear(), today.getMonth(), 1), new Date(today.getFullYear(), today.getMonth() + 1, 0)];
            return [new Date(today.getFullYear(), 0, 1), new Date(today.getFullYear(), 11, 31)];
        }

        function render(kind) {
            const [from, to] = range(kind);
            const a = iso(from);
            const b = iso(to);
            const todayIso = iso(new Date());
            const items = events.filter((e) => e.end >= a && e.start <= b);

            if (!items.length) {
                list.innerHTML = '<div class="card m-board-empty">Nada no cronograma neste período.</div>';
                return;
            }

            list.innerHTML = items.map((e) => {
                const [label, icon] = types[e.type] || types.outro;
                const start = parse(e.start);
                const period = e.end !== e.start ? ` até ${parse(e.end).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })}` : '';
                return `
                    <article class="card m-agenda-item${e.start <= todayIso && e.end >= todayIso ? ' is-today' : ''}${e.end < todayIso ? ' is-past' : ''}">
                        <div class="m-agenda-date"><strong>${String(start.getDate()).padStart(2, '0')}</strong><small>${start.toLocaleDateString('pt-BR', { month: 'short' }).replace('.', '')} · ${weekdays[start.getDay()]}</small></div>
                        <div class="m-agenda-body">
                            <span class="m-agenda-type m-agenda-${escapeHtml(e.type)}"><i class="fas ${icon} mr-1"></i>${label}${period}</span>
                            <strong>${escapeHtml(e.title)}</strong>
                            ${e.description ? `<p>${escapeHtml(e.description)}</p>` : ''}
                        </div>
                    </article>`;
            }).join('');
        }

        agenda.querySelector('[data-agenda-range]').addEventListener('click', (event) => {
            const button = event.target.closest('[data-range]');
            if (!button) return;
            agenda.querySelectorAll('[data-range]').forEach((b) => b.classList.toggle('is-active', b === button));
            render(button.dataset.range);
        });

        render('week');
    }

})();
