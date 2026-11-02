// Mater — Observação rápida do aluno: marca uma ou mais opções, escreve se quiser e salva tudo como uma observação
window.MaterQuickNote = {

    /**
     * @param {HTMLFormElement} form componente teacherPortal/components/quickNote.php
     * @param {{enrollment: function(): number, onSaved: function(string, object, HTMLOptionElement): void}} options
     */
    bind(form, options) {

        const select = form.querySelector('[data-quick-discipline]');
        const input = form.querySelector('[data-quick-text]');
        const submit = form.querySelector('[type="submit"]');
        let busy = false;

        function toast(message, type) {
            if (typeof tools !== 'undefined' && typeof tools.showToast === 'function') {
                tools.showToast(message, type === 'error' ? 'bg-danger' : 'bg-success');
            }
        }

        function mark(chip, active) {
            chip.classList.toggle('is-active', active);
            chip.setAttribute('aria-pressed', active);
            chip.querySelector('i').className = `fas ${active ? 'fa-check-square' : 'fa-square'}`;
        }

        function reset() {
            form.querySelectorAll('[data-quick-note]').forEach((chip) => mark(chip, false));
            input.value = '';
        }

        form.addEventListener('click', (event) => {
            const chip = event.target.closest('[data-quick-note]');
            if (chip) mark(chip, !chip.classList.contains('is-active'));
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (busy) return;

            const marked = Array.from(form.querySelectorAll('[data-quick-note].is-active')).map((chip) => chip.dataset.quickNote);
            const text = [marked.join(' · '), input.value.trim()].filter(Boolean).join('. ');
            if (!text) {
                toast('Marque uma opção ou escreva a observação.', 'error');
                return;
            }

            busy = true;
            submit.disabled = true;
            try {
                const response = await fetch('/portal-docente/aluno/observacao', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ enrollment: options.enrollment(), disciplineClass: parseInt(select.value, 10), text })
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(data.error || 'falha');
                const option = select.selectedOptions[0];
                reset();
                toast('Observação salva');
                options.onSaved(text, data, option);
            } catch (e) {
                toast(e.message === 'sem-permissao' ? 'Você não tem permissão nesta disciplina.' : (e.message === 'texto-invalido' ? 'Observação longa demais.' : 'Não foi possível salvar. Tente de novo.'), 'error');
            } finally {
                busy = false;
                submit.disabled = false;
            }
        });

        return { reset };
    }
};
