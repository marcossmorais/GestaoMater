// Mater — pequenas melhorias de UX sem dependências (carregado depois do main.js)
(function () {

    // Alterna modo claro/escuro pela barra superior e mantém o interruptor de Configurações em sincronia
    document.addEventListener('click', function (event) {
        const button = event.target.closest('.m-theme-toggle');
        if (!button) return;

        const key = 'nightMode' + button.dataset.portal;
        const dark = document.documentElement.classList.toggle('nightMode');

        try {
            dark ? localStorage.setItem(key, 'true') : localStorage.removeItem(key);
        } catch (e) { /* armazenamento indisponível: vale só nesta página */ }

        const settingsSwitch = document.getElementById('nightMode');
        if (settingsSwitch) settingsSwitch.checked = dark;

        recolorCharts(dark);
    });

    // Os gráficos guardam a cor dos textos ao serem criados; atualiza sem recarregar a página
    function recolorCharts(dark) {
        if (!window.Chart || !Chart.instances) return;
        const color = dark ? '#94A3BD' : '#5B6B84';

        Object.values(Chart.instances).forEach(function (chart) {
            const o = chart.options;
            if (o.plugins && o.plugins.legend && o.plugins.legend.labels) o.plugins.legend.labels.color = color;
            if (o.plugins && o.plugins.title) o.plugins.title.color = color;
            ['x', 'y'].forEach(function (axis) {
                if (o.scales && o.scales[axis] && o.scales[axis].ticks) o.scales[axis].ticks.color = color;
            });
            chart.update('none');
        });
    }

    // Busca vinda da barra superior: /admin/aluno/lista?busca=Nome preenche o filtro e dispara a busca
    const query = new URLSearchParams(window.location.search).get('busca');
    const nameInput = document.querySelector("#seekStudent input[name='name']");

    if (query && nameInput) {
        nameInput.value = query;
        if (window.jQuery) window.jQuery(nameInput).trigger('keyup');
    }

    const topSearch = document.querySelector('.m-search input');
    if (topSearch && query) topSearch.value = query;

    // Alternância de modo (Timeline / Grade Completa) no Portal do Aluno
    document.addEventListener('click', function (event) {
        const btn = event.target.closest('[data-student-view-mode] button');
        if (!btn) return;
        const container = btn.closest('[data-student-view-mode]');
        container.querySelectorAll('button').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');

        const timelineView = document.getElementById('studentTimelineView');
        const gridView = document.getElementById('studentGridView');
        if (!timelineView || !gridView) return;

        if (btn.dataset.mode === 'timeline') {
            timelineView.style.display = 'block';
            gridView.style.display = 'none';
        } else {
            timelineView.style.display = 'none';
            gridView.style.display = 'block';
        }
    });

    // Alternância de dias no Quadro de Horários do Aluno
    document.addEventListener('click', function (event) {
        const tab = event.target.closest('[data-student-day-tabs] .m-day-tab');
        if (!tab) return;
        const tabsContainer = tab.closest('[data-student-day-tabs]');
        tabsContainer.querySelectorAll('.m-day-tab').forEach(t => t.classList.remove('is-active'));
        tab.classList.add('is-active');

        const targetDay = tab.dataset.day;
        const panels = document.querySelectorAll('[data-student-day-panel]');
        panels.forEach(panel => {
            if (panel.dataset.studentDayPanel === targetDay) {
                panel.classList.add('is-visible');
            } else {
                panel.classList.remove('is-visible');
            }
        });
    });

})();

// Menu "Mais" da barra inferior do docente (celular)
(function () {
    const toggle = document.querySelector('[data-more-toggle]');
    const menu = document.getElementById('teacherMoreMenu');
    if (!toggle || !menu) return;

    function set(open) {
        menu.hidden = !open;
        toggle.setAttribute('aria-expanded', open);
    }

    toggle.addEventListener('click', (event) => {
        event.stopPropagation();
        set(menu.hidden);
    });
    document.addEventListener('click', (event) => { if (!menu.hidden && !menu.contains(event.target)) set(false); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') set(false); });
})();
