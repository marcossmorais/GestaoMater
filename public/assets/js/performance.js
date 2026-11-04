// Mater — Simulador de notas, folha do roteiro e impressão (portal do aluno)
(function () {

    const rule = window.MATER_PERFORMANCE || { units: 4, maxPerUnit: 10, passing: 7, perBimester: 3 };

    function fmt(n) {
        return Number(n).toLocaleString('pt-BR', { maximumFractionDigits: 1 });
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
        const target = rule.passing * rule.units;
        const remaining = target - sum;
        const average = sum / rule.units;
        const total = sum;
        if (remaining <= 0.0001) return { status: 'aprovado', needed: null, average, total, target, missing };
        if (missing === 0) return { status: 'impossivel', needed: null, average, total, target, missing };
        const needed = Math.ceil(Math.round(remaining / missing * 10000) / 10000 * 10) / 10;
        return { status: needed > rule.maxPerUnit ? 'impossivel' : 'possivel', needed, average, total, target, missing };
    }

    // ---------- Simulador do bimestre: notas lançadas + as que o aluno digitar nas avaliações que faltam
    function simulateBimester(box) {
        const base = JSON.parse(box.dataset.bim);
        const per = rule.perBimester;
        const result = box.querySelector('[data-bim-result]');
        const grades = base.grades.slice();
        let invalid = false;

        box.querySelectorAll('[data-bim-slot]').forEach((field) => {
            const raw = field.value.trim().replace(',', '.');
            if (raw === '') return field.classList.remove('is-invalid');
            const value = Number(raw);
            const bad = !/^\d+(\.\d+)?$/.test(raw) || value > 10;
            field.classList.toggle('is-invalid', bad);
            if (bad) invalid = true;
            else grades.push(value);
        });

        if (invalid) {
            result.textContent = 'Use notas de 0 a 10.';
            return;
        }

        const sum = grades.reduce((a, b) => a + b, 0);
        const missing = per - grades.length;

        if (missing > 0) {
            const need = Math.ceil(((rule.passing * per - sum) / missing) * 10) / 10;
            if (need <= 0) result.innerHTML = `<i class="fas fa-check-circle mr-1"></i>Você já fecha o ${base.unit}º bimestre com média ${fmt(rule.passing)}, qualquer que seja a nota.`;
            else if (need > 10) result.innerHTML = `<i class="fas fa-redo mr-1"></i>Mesmo com 10 não fecha ${fmt(rule.passing)}: vai para a recuperação, onde precisa tirar ${fmt(rule.passing)}.`;
            else result.innerHTML = `<i class="fas fa-bullseye mr-1"></i>Precisa de <strong>${fmt(need)}</strong> ${missing > 1 ? `em cada uma das ${missing} avaliações que faltam` : 'na avaliação que falta'} para fechar ${fmt(rule.passing)}.`;
            return;
        }

        const average = Math.round((sum / per) * 100) / 100;
        result.innerHTML = average >= rule.passing
            ? `<i class="fas fa-check-circle mr-1"></i>Média do ${base.unit}º bimestre: <strong>${fmt(average)}</strong>. Fechou!`
            : `<i class="fas fa-redo mr-1"></i>Média do ${base.unit}º bimestre: <strong>${fmt(average)}</strong>. Vai para a recuperação, onde precisa tirar ${fmt(rule.passing)}.`;
    }

    // o Boletim chega depois, por AJAX: os eventos ficam no documento
    document.querySelectorAll('[data-bim-sim]').forEach(simulateBimester);
    document.addEventListener('toggle', (event) => {
        if (event.target.matches && event.target.matches('[data-bim-sim]')) simulateBimester(event.target);
    }, true);
    document.addEventListener('input', (event) => {
        const box = event.target.closest('[data-bim-sim]');
        if (box) simulateBimester(box);
    });

    // ---------- Boletim: expande a matéria (o conteúdo chega por AJAX)
    function setRow(button, open) {
        button.setAttribute('aria-expanded', open);
        button.closest('tr').nextElementSibling.hidden = !open;
    }

    document.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-bul-toggle]');
        if (toggle) return setRow(toggle, toggle.getAttribute('aria-expanded') !== 'true');

        const all = event.target.closest('[data-bul-all]');
        if (!all) return;
        const open = all.getAttribute('aria-expanded') !== 'true';
        all.setAttribute('aria-expanded', open);
        all.querySelector('span').textContent = open ? 'Recolher todas' : 'Expandir todas';
        document.querySelectorAll('[data-bul-toggle]').forEach((button) => setRow(button, open));
    });

    // ---------- Simulador da média do ano, por disciplina
    {
        document.addEventListener('input', (event) => {
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
                result.innerHTML = `<i class="fas fa-check-circle mr-1"></i>Com essas notas sua média do ano ficaria em pelo menos <strong>${fmt(rule.passing)}</strong>: aprovado(a)!`;
            } else if (sim.needed !== null) {
                result.innerHTML = `Ainda faltaria <strong>${fmt(sim.needed)}</strong> em cada bimestre restante${sim.status === 'impossivel' ? ' (acima do máximo)' : ''}.`;
            } else {
                result.innerHTML = `Sua média do ano seria <strong>${fmt(sim.average)}</strong>, abaixo de ${fmt(rule.passing)}.`;
            }
        });
    }

    // ---------- Roteiro de provas: uma folha por bimestre, filtrada por avaliação e matéria
    const guideUnits = document.querySelector('[data-guide-unit]');
    if (guideUnits) {
        guideUnits.addEventListener('click', (event) => {
            const button = event.target.closest('[data-unit]');
            if (!button) return;
            guideUnits.querySelectorAll('[data-unit]').forEach((b) => b.classList.toggle('is-active', b === button));
            document.querySelectorAll('[data-guide-sheet]').forEach((sheet) => { sheet.hidden = sheet.dataset.guideSheet !== button.dataset.unit; });
        });
    }

    const guideFilter = document.querySelector('[data-guide-filter]');
    const guideSubject = document.querySelector('[data-guide-subject]');
    function filterGuide() {
        const group = guideFilter.querySelector('.is-active').dataset.group;
        document.querySelectorAll('[data-guide-sheet]').forEach((sheet) => {
            let shown = 0;
            sheet.querySelectorAll('[data-subject]').forEach((block) => {
                let rows = 0;
                block.querySelectorAll('tr[data-group]').forEach((row) => {
                    row.hidden = group !== '' && row.dataset.group !== group;
                    if (!row.hidden) rows++;
                });
                block.hidden = rows === 0 || (guideSubject.value !== '' && block.dataset.subject !== guideSubject.value);
                if (!block.hidden) shown++;
            });
            sheet.querySelector('[data-guide-none]').hidden = shown > 0;
        });
    }
    if (guideFilter) {
        guideFilter.addEventListener('click', (event) => {
            const button = event.target.closest('[data-group]');
            if (!button) return;
            guideFilter.querySelectorAll('[data-group]').forEach((b) => b.classList.toggle('is-active', b === button));
            filterGuide();
        });
        guideSubject.addEventListener('change', filterGuide);
    }

    // ---------- Atestado: mostra o nome do arquivo escolhido
    document.addEventListener('change', (event) => {
        if (!event.target.matches('[data-freq-file]')) return;
        const name = event.target.closest('.m-file-pick').querySelector('[data-freq-file-name]');
        name.textContent = event.target.files.length ? event.target.files[0].name : 'Nenhum arquivo escolhido';
    });

    // ---------- Roteiro: o documento impresso sai com o bimestre e os filtros da tela
    document.addEventListener('click', (event) => {
        const link = event.target.closest('[data-guide-print]');
        if (!link) return;
        const params = new URLSearchParams({
            bimestre: guideUnits.querySelector('.is-active').dataset.unit,
            avaliacao: guideFilter.querySelector('.is-active').dataset.group,
            materia: guideSubject.value,
            imprimir: 1,
        });
        link.href = link.pathname + '?' + params;
    });

})();
