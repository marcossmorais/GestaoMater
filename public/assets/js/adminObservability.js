// Mater — Gráficos da observabilidade do admin (os da home do professor, da escola toda). Chart.js vem do layout.
(function () {

    const data = window.MATER_OBS;
    if (!data || typeof Chart === 'undefined' || !window.MATER_CHART) return;

    const number = (n) => String(n).replace('.', ',');
    const labels = data.classes.map((c) => c.label.split(' · ')[0]);
    let charts = [];

    function round(theme, id, type, names, values, colors, cutout, unit) {
        const canvas = document.getElementById(id);
        if (!canvas) return;
        const empty = values.every((v) => !v);
        const total = values.reduce((a, b) => a + b, 0);
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
                plugins: {
                    legend: { display: false },
                    tooltip: MATER_CHART.tooltip(theme, {
                        enabled: !empty,
                        callbacks: { label: (c) => `${c.raw} ${unit} (${share(c.raw)}%)` }
                    })
                }
            }
        }));
    }

    function scales(theme, extraY) {
        return {
            x: { ticks: { color: theme.muted, font: { weight: '700' } }, grid: { display: false } },
            y: Object.assign({ beginAtZero: true, ticks: { color: theme.muted, precision: 0 }, grid: { color: theme.grid } }, extraY || {})
        };
    }

    const bar = (label, values, color) => ({ label, data: values, backgroundColor: color, hoverBackgroundColor: color, borderRadius: 8, maxBarThickness: 44 });
    const passingLine = (count) => ({ type: 'line', label: 'Média exigida', data: Array(count).fill(data.passing), borderColor: '#E5484D', borderDash: [6, 4], borderWidth: 2, pointRadius: 0, pointHoverRadius: 0 });
    const tone = (v) => (v !== null && v < data.passing ? '#F2643D' : '#2F6FED');

    // tocar na barra de uma turma abre a turma
    const openClass = (event, items) => {
        if (items.length) window.location.href = data.classUrl + data.classes[items[0].index].id;
    };
    const pointer = (event, items) => { event.native.target.style.cursor = items.length ? 'pointer' : 'default'; };

    function draw() {
        charts.forEach((c) => c.destroy());
        charts = [];
        const theme = MATER_CHART.theme();

        round(theme, 'obsChartStatus', 'doughnut', data.status.map((s) => s.label), data.status.map((s) => s.value), data.status.map((s) => s.color), '68%', 'alunos');
        round(theme, 'obsChartBands', 'pie', data.bands.map((b) => b[0]), data.bands.map((b) => b[1]), data.bands.map((b) => b[2]), undefined, 'alunos');

        const average = document.getElementById('obsChartClassAverage');
        if (average) charts.push(new Chart(average, {
            type: 'bar',
            data: { labels, datasets: [bar('Média', data.classes.map((c) => c.average), data.classes.map((c) => tone(c.average))), passingLine(labels.length)] },
            options: {
                maintainAspectRatio: false,
                onHover: pointer,
                onClick: openClass,
                plugins: {
                    legend: { display: false },
                    tooltip: MATER_CHART.tooltip(theme, {
                        filter: (item) => item.dataset.type !== 'line',
                        callbacks: {
                            title: (items) => data.classes[items[0].dataIndex].label,
                            label: (c) => `Média ${c.raw === null ? 'sem nota' : number(c.raw)}`
                        }
                    })
                },
                scales: scales(theme, { max: 10 })
            }
        }));

        const risk = document.getElementById('obsChartAtRisk');
        if (risk) charts.push(new Chart(risk, {
            type: 'bar',
            data: { labels, datasets: [bar('Em risco', data.classes.map((c) => c.atRisk), '#F2643D')] },
            options: {
                maintainAspectRatio: false,
                onHover: pointer,
                onClick: openClass,
                plugins: {
                    legend: { display: false },
                    tooltip: MATER_CHART.tooltip(theme, {
                        callbacks: {
                            title: (items) => data.classes[items[0].dataIndex].label,
                            label: (c) => `${c.raw} de ${data.classes[c.dataIndex].students} alunos em risco`
                        }
                    })
                },
                scales: scales(theme)
            }
        }));

        const disciplines = document.getElementById('obsChartDisciplines');
        if (disciplines) charts.push(new Chart(disciplines, {
            type: 'bar',
            data: {
                labels: data.disciplines.map((d) => d.name),
                datasets: [bar('Média', data.disciplines.map((d) => d.average), data.disciplines.map((d) => tone(d.average))), passingLine(data.disciplines.length)]
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
    }

    draw();
    MATER_CHART.onThemeChange(draw);

})();
