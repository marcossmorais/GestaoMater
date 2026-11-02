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
            const item = `<li class="${escapeHtml(option.dataset.subject || '')}" data-note-discipline="${escapeHtml(discipline)}"><p>${escapeHtml(text)}</p><small>${escapeHtml(discipline)} · ${escapeHtml(config.teacher || '')}</small></li>`;
            const first = notes.querySelector('li');
            if (first && first.hasAttribute('data-day') && first.textContent.startsWith('Hoje')) {
                first.insertAdjacentHTML('afterend', item);
            } else {
                notes.insertAdjacentHTML('afterbegin', `<li class="m-day-header" data-day>Hoje · ${data.date.slice(0, 5)}</li>${item}`);
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
        let header = null;
        let visible = false;
        notes.querySelectorAll('li').forEach((li) => {
            if (li.hasAttribute('data-day')) {
                if (header) header.hidden = !visible;
                header = li;
                visible = false;
                return;
            }
            li.hidden = value !== '' && li.dataset.noteDiscipline !== value;
            if (!li.hidden) visible = true;
        });
        if (header) header.hidden = !visible;
    });

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
