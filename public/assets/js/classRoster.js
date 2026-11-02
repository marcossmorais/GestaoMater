// Mater — Carômetro da turma: busca por nome e observação rápida sem sair da tela
(function () {

    const root = document.getElementById('classOverview');
    const grid = root && root.querySelector('[data-roster]');
    if (!grid) return;

    const normalize = (text) => text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

    const search = root.querySelector('[data-roster-search]');
    search.addEventListener('input', () => {
        const term = normalize(search.value.trim());
        grid.querySelectorAll('[data-name]').forEach((card) => { card.hidden = !normalize(card.dataset.name).includes(term); });
    });

    const sheet = root.querySelector('[data-note-sheet]');
    if (!sheet) return;

    const form = sheet.querySelector('[data-quick]');
    let opener = null;
    let enrollment = 0;

    function close() {
        sheet.hidden = true;
        document.documentElement.style.overflow = '';
        if (opener) opener.focus();
    }

    const quick = window.MaterQuickNote.bind(form, {
        enrollment: () => enrollment,
        onSaved: close
    });

    grid.addEventListener('click', (event) => {
        const button = event.target.closest('[data-note-student]');
        if (!button) return;
        opener = button;
        enrollment = parseInt(button.dataset.noteStudent, 10);
        sheet.querySelector('[data-note-name]').textContent = button.dataset.noteName;
        quick.reset();
        sheet.hidden = false;
        document.documentElement.style.overflow = 'hidden';
        sheet.querySelector('[data-note-close]').focus();
    });

    sheet.addEventListener('click', (event) => {
        if (event.target === sheet || event.target.closest('[data-note-close]')) close();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !sheet.hidden) close();
    });

})();
