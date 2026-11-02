// Mater — Perfil do aluno no portal do docente: abas, notas por bimestre, gráfico e observações
(function () {

    const config = window.MATER_STUDENT || {};
    const root = document.getElementById('studentFile');
    if (!root) return;

    const notes = root.querySelector('[data-notes]');

    function escapeHtml(text) {
        return String(text).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    // ---------- Observação rápida: entra no histórico assim que salva
    const quick = root.querySelector('[data-quick]');
    if (quick) window.MaterQuickNote.bind(quick, {
        enrollment: () => config.enrollment,
        onSaved(text, data, option) {
            const discipline = option.textContent;
            const item = `<li class="${escapeHtml(option.dataset.subject || '')}" data-note-discipline="${escapeHtml(discipline)}"><p>${escapeHtml(text)}</p><small>${escapeHtml(data.time || '')} · ${escapeHtml(discipline)} · ${escapeHtml(config.teacher || '')}</small></li>`;
            const months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
            const [, month, year] = data.date.split('/');
            const monthLabel = `${months[parseInt(month, 10) - 1]} de ${year}`;
            const today = notes.querySelector(`[data-day="${data.date}"]`);
            if (today) {
                today.insertAdjacentHTML('afterend', item);
            } else {
                const dayHeader = `<li class="m-day-header" data-day="${data.date}">Hoje · ${data.date.slice(0, 5)}</li>${item}`;
                const first = notes.querySelector('[data-month]');
                if (first && first.textContent === monthLabel) first.insertAdjacentHTML('afterend', dayHeader);
                else notes.insertAdjacentHTML('afterbegin', `<li class="m-month-header" data-month>${monthLabel}</li>${dayHeader}`);
            }
            const empty = root.querySelector('[data-notes-empty]');
            if (empty) empty.remove();
        }
    });

    // ---------- Abas (?aba=notas abre direto) e bimestre das notas
    function openTab(tab) {
        root.querySelectorAll('[data-tab]').forEach((b) => b.classList.toggle('is-active', b.dataset.tab === tab));
        root.querySelectorAll('[data-tab-panel]').forEach((panel) => { panel.hidden = panel.dataset.tabPanel !== tab; });
        if (tab === 'resumo' && chartData) drawChart(chartUnit);
    }

    root.addEventListener('click', (event) => {
        const tab = event.target.closest('[data-tab]');
        if (tab) return openTab(tab.dataset.tab);

        const unit = event.target.closest('[data-grades-unit] [data-unit]');
        if (!unit) return;
        root.querySelectorAll('[data-grades-unit] button').forEach((b) => b.classList.toggle('is-active', b === unit));
        root.querySelectorAll('[data-grades-panel]').forEach((panel) => { panel.hidden = panel.dataset.gradesPanel !== unit.dataset.unit; });
    });

    // ---------- Filtro das observações por matéria (cabeçalhos de dia vazios somem)
    root.addEventListener('click', (event) => {
        const chipButton = event.target.closest('[data-notes-value]');
        if (!chipButton) return;
        const value = chipButton.dataset.notesValue;
        root.querySelectorAll('[data-notes-value]').forEach((b) => b.classList.toggle('is-active', b === chipButton));
        let dayHeader = null;
        let monthHeader = null;
        notes.querySelectorAll('li').forEach((li) => {
            if (li.hasAttribute('data-month')) {
                monthHeader = li;
                li.hidden = true;
            } else if (li.hasAttribute('data-day')) {
                dayHeader = li;
                li.hidden = true;
            } else {
                li.hidden = value !== '' && li.dataset.noteDiscipline !== value;
                if (!li.hidden) {
                    if (dayHeader) dayHeader.hidden = false;
                    if (monthHeader) monthHeader.hidden = false;
                }
            }
        });
    });

    // ---------- Solicitação de ocorrência à coordenação
    const occForm = root.querySelector('[data-occ-form]');
    if (occForm) {
        let category = 'Elogio';
        let sending = false;
        const occToast = (message, type) => {
            if (typeof tools !== 'undefined' && typeof tools.showToast === 'function') tools.showToast(message, type === 'error' ? 'bg-danger' : 'bg-success');
        };
        const occErrors = {
            'texto-obrigatorio': 'Preencha o título e o que aconteceu.',
            'data-invalida': 'Escolha uma data até hoje.',
            'sem-permissao': 'Você não tem permissão nesta disciplina.'
        };

        occForm.querySelector('[data-occ-category]').addEventListener('click', (event) => {
            const button = event.target.closest('[data-value]');
            if (!button) return;
            category = button.dataset.value;
            occForm.querySelectorAll('[data-occ-category] button').forEach((b) => b.classList.toggle('is-active', b === button));
            occForm.querySelector('[data-occ-severity]').hidden = category === 'Elogio';
        });

        occForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (sending) return;
            const data = new FormData(occForm);
            const payload = {
                enrollment: config.enrollment,
                disciplineClass: parseInt(data.get('disciplineClass'), 10),
                category,
                severity: category === 'Elogio' ? 'Baixa' : data.get('severity'),
                date: data.get('date'),
                title: (data.get('title') || '').trim(),
                description: (data.get('description') || '').trim()
            };
            if (!payload.title || !payload.description) return occToast(occErrors['texto-obrigatorio'], 'error');

            sending = true;
            try {
                const response = await fetch('/portal-docente/aluno/ocorrencia', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(result.error || 'falha');
                root.querySelector('[data-occ-list]').insertAdjacentHTML('afterbegin', `
                    <li>
                        <div><strong>${escapeHtml(payload.title)}</strong> <span class="m-pill m-pill-warn">Aguardando coordenação</span></div>
                        <small>${escapeHtml(category)} · ${payload.date.split('-').reverse().join('/')} · ${escapeHtml(config.teacher || '')}</small>
                        <p>${escapeHtml(payload.description).replace(/\n/g, '<br>')}</p>
                    </li>`);
                occForm.querySelector('[name="title"]').value = '';
                occForm.querySelector('[name="description"]').value = '';
                root.querySelector('[data-occ-box]').open = false;
                occToast('Solicitação enviada à coordenação');
            } catch (e) {
                occToast(occErrors[e.message] || 'Não foi possível enviar. Tente de novo.', 'error');
            } finally {
                sending = false;
            }
        });
    }

    // ---------- Gráfico: nota do aluno x média da turma em cada matéria, no bimestre escolhido
    const chartData = window.MATER_STUDENT_CHART;
    const canvas = document.getElementById('studentCompareChart');
    let chart = null;
    let chartUnit = chartData ? chartData.unity : 1;

    function drawChart(unit) {
        if (!chartData || !canvas || typeof Chart === 'undefined') return;
        const theme = MATER_CHART.theme();
        const text = theme.muted;
        const grid = theme.grid;
        const i = unit - 1;
        chartUnit = unit;
        const labels = chartData.subjects.map((s) => s.name.length > 12 ? s.name.slice(0, 11) + '…' : s.name);

        const data = {
            labels,
            datasets: [
                { label: 'Aluno', data: chartData.subjects.map((s) => s.student[i]), backgroundColor: chartData.subjects.map((s) => s.color), hoverBackgroundColor: chartData.subjects.map((s) => s.color), borderRadius: 8, maxBarThickness: 26 },
                { label: 'Média da turma', data: chartData.subjects.map((s) => s.class[i]), backgroundColor: grid, hoverBackgroundColor: grid, borderRadius: 8, maxBarThickness: 26 },
                { type: 'line', label: 'Média exigida', data: labels.map(() => chartData.passing), borderColor: '#E5484D', borderDash: [6, 4], borderWidth: 2, pointRadius: 0, pointHoverRadius: 0 }
            ]
        };

        if (chart) chart.destroy();
        chart = new Chart(canvas, {
            type: 'bar',
            data,
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: MATER_CHART.tooltip(theme, {
                        filter: (item) => item.dataset.type !== 'line',
                        callbacks: {
                            title: (items) => chartData.subjects[items[0].dataIndex].name,
                            label: (c) => `${c.dataset.label}: ${c.raw === null ? 'sem nota' : String(c.raw).replace('.', ',')}`
                        }
                    })
                },
                scales: {
                    y: { min: 0, max: 10, ticks: { stepSize: 2, color: text }, grid: { color: grid } },
                    x: { ticks: { color: text, font: { weight: '700' } }, grid: { display: false } }
                }
            }
        });
    }

    root.addEventListener('click', (event) => {
        const button = event.target.closest('[data-chart-unit] [data-unit]');
        if (!button) return;
        root.querySelectorAll('[data-chart-unit] button').forEach((b) => b.classList.toggle('is-active', b === button));
        drawChart(parseInt(button.dataset.unit, 10));
    });

    if (chartData) MATER_CHART.onThemeChange(() => { if (canvas.offsetParent) drawChart(chartUnit); });

    const startTab = new URLSearchParams(window.location.search).get('aba');
    openTab(['notas', 'observacoes'].includes(startTab) ? startTab : 'resumo');

})();
