// Mater — Perfil do professor (/portal-docente/perfil): editar os próprios dados e mostrar o código de acesso
(function () {

    const form = document.getElementById('teacherProfileForm');
    if (!form) return;

    const fields = () => form.querySelectorAll('.form-control');
    const editButton = document.querySelector('[data-profile-edit]');
    const bar = form.querySelector('[data-profile-bar]');
    const save = form.querySelector('[data-profile-save]');

    function toast(message, type) {
        if (typeof tools !== 'undefined' && typeof tools.showToast === 'function') tools.showToast(message, type);
    }

    function toggle(editing) {
        fields().forEach((field) => { field.disabled = !editing; });
        editButton.hidden = editing;
        bar.hidden = !editing;
        document.body.classList.toggle('m-att-marking', editing);
        if (editing) form.querySelector('[name="email"]').focus();
    }

    if (window.jQuery && jQuery.fn.mask) {
        jQuery('#zipCode').mask('00000-000');
        jQuery('#telephoneNumber').mask('(00) 00000-0000');
    }

    editButton.addEventListener('click', () => toggle(true));
    form.querySelector('[data-profile-cancel]').addEventListener('click', () => { form.reset(); toggle(false); });

    form.querySelector('[data-reveal]').addEventListener('click', (event) => {
        const input = form.querySelector('[name="accessCode"]');
        input.type = input.type === 'password' ? 'text' : 'password';
        event.currentTarget.querySelector('i').className = `fas ${input.type === 'password' ? 'fa-eye' : 'fa-eye-slash'}`;
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        save.disabled = true;
        try {
            const response = await fetch('/portal-docente/perfil/atualizar', {
                method: 'POST',
                body: new URLSearchParams(new FormData(form)),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) throw new Error();
            toggle(false);
            toast('Dados atualizados', 'bg-success');
        } catch (e) {
            toast('Não foi possível salvar. Tente de novo.', 'bg-danger');
        } finally {
            save.disabled = false;
        }
    });

})();
