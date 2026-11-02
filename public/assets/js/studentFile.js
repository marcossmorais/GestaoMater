// Mater — Ficha do aluno no portal do docente: observações rápidas (um toque) e texto livre
(function () {

    const config = window.MATER_STUDENT || {};
    const root = document.getElementById('studentFile');
    if (!root) return;

    const notes = root.querySelector('[data-notes]');
    let busy = false;

    function escapeHtml(text) {
        return String(text).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function toast(message, type) {
        if (typeof tools !== 'undefined' && typeof tools.showToast === 'function') {
            tools.showToast(message, type === 'error' ? 'bg-danger' : 'bg-success');
        }
    }

    async function save(box, text) {
        if (busy || !text) return false;
        busy = true;
        try {
            const response = await fetch('/portal-docente/aluno/observacao', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ enrollment: config.enrollment, disciplineClass: parseInt(box.dataset.dc, 10), text })
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data.error || 'falha');

            const discipline = box.closest('.card').querySelector('.m-panel-head h6').firstChild.textContent.trim();
            const item = `<li class="${box.closest('.card').className.match(/m-subj m-subj-\w+/)?.[0] || ''}" data-note-discipline="${escapeHtml(discipline)}"><p>${escapeHtml(text)}</p><small>${escapeHtml(discipline)} · ${escapeHtml(config.teacher || '')}</small></li>`;
            const first = notes.querySelector('li');
            if (first && first.hasAttribute('data-day') && first.textContent.startsWith('Hoje')) {
                first.insertAdjacentHTML('afterend', item);
            } else {
                notes.insertAdjacentHTML('afterbegin', `<li class="m-day-header" data-day>Hoje · ${data.date.slice(0, 5)}</li>${item}`);
            }
            const empty = root.querySelector('[data-notes-empty]');
            if (empty) empty.remove();
            toast('Observação salva');
            return true;
        } catch (e) {
            toast(e.message === 'sem-permissao' ? 'Você não tem permissão nesta disciplina.' : 'Não foi possível salvar. Tente de novo.', 'error');
            return false;
        } finally {
            busy = false;
        }
    }

    root.addEventListener('click', async (event) => {
        const chip = event.target.closest('[data-quick-note]');
        if (!chip) return;
        chip.classList.add('is-active');
        const ok = await save(chip.closest('[data-quick]'), chip.dataset.quickNote);
        setTimeout(() => chip.classList.remove('is-active'), ok ? 600 : 0);
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

    if (chartData) {
        drawChart(chartData.unity);
        MATER_CHART.onThemeChange(() => drawChart(chartUnit));
    }

    root.addEventListener('submit', async (event) => {
        const form = event.target.closest('[data-quick-form]');
        if (!form) return;
        event.preventDefault();
        const input = form.querySelector('input');
        if (await save(form.closest('[data-quick]'), input.value.trim())) input.value = '';
    });

})();
