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

})();
