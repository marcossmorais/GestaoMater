// Mater — Gráficos da home do professor (Chart.js, carregado no layout)
(function () {

    const data = window.MATER_HOME;
    if (!data || typeof Chart === 'undefined' || !window.MATER_CHART) return;

    const number = (n) => String(n).replace('.', ',');
    const labels = data.classes.map((c) => c.label);
    let charts = [];
    let activeClass = null;

    // Rosca: ao passar o cursor, o miolo mostra a fatia; ao sair, volta ao resumo
    function bindCenter(canvas) {
        const box = canvas.parentElement.querySelector('.m-donut-center');
        if (!box) return () => {};
        const strong = box.querySelector('strong');
        const small = box.querySelector('small');
        // guarda o resumo uma vez só: o gráfico é redesenhado ao trocar o tema
        if (!box.dataset.value) {
            box.dataset.value = strong.textContent;
            box.dataset.label = small.textContent;
        }
        const reset = () => { strong.textContent = box.dataset.value; small.textContent = box.dataset.label; };
        canvas.onmouseleave = reset;
        return (value, label) => {
            if (value === null) return reset();
            strong.textContent = value;
            small.textContent = label;
        };
    }

    function round(theme, id, type, names, values, colors, cutout, unit) {
        const canvas = document.getElementById(id);
        if (!canvas) return;
        const empty = values.every((v) => !v);
        const total = values.reduce((a, b) => a + b, 0);
        const setCenter = cutout ? bindCenter(canvas) : null;
        const share = (v) => (total ? Math.round((100 * v) / total) : 0);

        charts.push(new Chart(canvas, {
            type,
            data: {
                labels: empty ? ['Sem dados'] : names,
                datasets: [{
                    data: empty ? [1] : values,
                    backgroundColor: empty ? [theme.grid] : colors,
                    hoverBackgroundColor: empty ? [theme.grid] : colors,
                    borderColor: theme.surface,
                    hoverBorderColor: theme.surface,
                    borderWidth: 3,
                    hoverOffset: 6
                }]
            },
            options: {
                maintainAspectRatio: false,
                cutout,
                layout: { padding: 8 },
                onHover: (event, items) => {
                    event.native.target.style.cursor = items.length && !empty ? 'pointer' : 'default';
                    if (setCenter) setCenter(items.length && !empty ? values[items[0].index] : null, items.length ? names[items[0].index] : '');
                },
                plugins: {
                    legend: { display: false },
                    tooltip: MATER_CHART.tooltip(theme, {
                        enabled: !empty && !cutout,
                        callbacks: { label: (c) => `${c.raw} ${unit} (${share(c.raw)}%)` }
                    })
                }
            }
        }));
    }

    function scales(theme, extraY) {
        return {
            x: { stacked: !!(extraY && extraY.stacked), ticks: { color: theme.muted, font: { weight: '700' } }, grid: { display: false } },
            y: Object.assign({ beginAtZero: true, ticks: { color: theme.muted, precision: 0 }, grid: { color: theme.grid } }, extraY || {})
        };
    }

    // Barra: mantém a cor ao passar o cursor
    const bar = (label, values, color) => ({ label, data: values, backgroundColor: color, hoverBackgroundColor: color, borderRadius: 8, maxBarThickness: 44 });

    function filterAttention(turma) {
        const list = document.querySelector('[data-attention]');
        if (!list) return;
        let count = 0;
        list.querySelectorAll('li[data-turma]').forEach((li) => {
            li.hidden = !!turma && li.dataset.turma !== turma;
            if (!li.hidden) count++;
        });
        list.querySelector('[data-attention-title]').textContent = turma ? `${count} alunos da ${turma} (toque na barra de novo para ver todos)` : `Ver os ${count} alunos`;
        list.open = !!turma;
    }

    function draw() {
        charts.forEach((c) => c.destroy());
        charts = [];
        const theme = MATER_CHART.theme();

        round(theme, 'chartStatus', 'doughnut', data.status.map((s) => s.label), data.status.map((s) => s.value), data.status.map((s) => s.color), '68%', 'alunos');
        round(theme, 'chartBands', 'pie', data.bands.map((b) => b[0]), data.bands.map((b) => b[1]), data.bands.map((b) => b[2]), undefined, 'notas');
        round(theme, 'chartReads', 'doughnut', ['Leram', 'Ainda não leram'], [data.reads.read, Math.max(0, data.reads.expected - data.reads.read)], ['#2F6FED', theme.grid], '72%', 'leituras');

        const average = document.getElementById('chartClassAverage');
        if (average) charts.push(new Chart(average, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    bar('Média', data.classes.map((c) => c.average), data.classes.map((c) => (c.average !== null && c.average < data.passing ? '#F2643D' : '#2F6FED'))),
                    { type: 'line', label: 'Média exigida', data: labels.map(() => data.passing), borderColor: '#E5484D', borderDash: [6, 4], borderWidth: 2, pointRadius: 0, pointHoverRadius: 0 }
                ]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: MATER_CHART.tooltip(theme, {
                        filter: (item) => item.dataset.type !== 'line',
                        callbacks: { label: (c) => `Média ${c.raw === null ? 'sem nota' : number(c.raw)}` }
                    })
                },
                scales: scales(theme, { max: 10 })
            }
        }));

        const absences = document.getElementById('chartAbsences');
        if (absences) charts.push(new Chart(absences, {
            type: 'bar',
            data: { labels, datasets: [bar('Faltas', data.classes.map((c) => c.absences), '#0EA5E9')] },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: MATER_CHART.tooltip(theme, { callbacks: { label: (c) => `${c.raw} faltas` } }) },
                scales: scales(theme)
            }
        }));

        // Atenção: tocar na barra filtra a lista de alunos daquela turma
        const attention = document.getElementById('chartAttention');
        if (attention) charts.push(new Chart(attention, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    bar('Aguardam recuperação', data.classes.map((c) => c.recovery), '#F2643D'),
                    bar('Média parcial baixa', data.classes.map((c) => c.partial), '#0EA5E9'),
                    bar('Só por faltas', data.classes.map((c) => Math.max(0, c.attention - c.recovery - c.partial)), '#7B61FF')
                ]
            },
            options: {
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: true },
                plugins: {
                    legend: { position: 'bottom', labels: { color: theme.muted, boxWidth: 12, usePointStyle: true, font: { weight: '700' } } },
                    tooltip: MATER_CHART.tooltip(theme, {
                        filter: (item) => item.raw > 0,
                        callbacks: { label: (c) => `${c.dataset.label}: ${c.raw}` }
                    })
                },
                scales: scales(theme, { stacked: true }),
                onHover: (event, items) => { event.native.target.style.cursor = items.length ? 'pointer' : 'default'; },
                onClick: (event, items) => {
                    if (!items.length) return;
                    const turma = data.classes[items[0].index].turma;
                    activeClass = activeClass === turma ? null : turma;
                    filterAttention(activeClass);
                }
            }
        }));
    }

    draw();
    MATER_CHART.onThemeChange(draw);

})();
