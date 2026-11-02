<?php
require __DIR__ . '../../../config/variables.php';

$teacher = $this->view->Data[0];
$photoDir = "$app_url/assets/img/teacherProfilePhotos/";
$photo = $teacher->profile_photo ? $photoDir . $teacher->profile_photo : $photoDir . 'foto-vazia.jpg';
$cpf = preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', str_pad((string) $teacher->cpf, 11, '0', STR_PAD_LEFT));
$birth = $teacher->birth_date ? date('d/m/Y', strtotime($teacher->birth_date)) : '–';
?>

<div class="m-profile" id="teacherProfile">

    <aside class="m-profile-side">
        <img class="m-profile-photo" src="<?= htmlspecialchars($photo) ?>" alt="" onerror="this.src='<?= $photoDir ?>foto-vazia.jpg'">
        <h5><?= htmlspecialchars($teacher->name) ?></h5>
        <span class="m-pill m-pill-info"><i class="fas fa-chalkboard-teacher mr-1"></i><?= htmlspecialchars($teacher->hierarchy_function) ?></span>
        <nav class="m-profile-nav" aria-label="Seções do perfil">
            <button type="button" class="is-active" data-profile-tab="dados"><i class="fas fa-user"></i>Meus dados</button>
            <button type="button" data-profile-tab="aparencia"><i class="fas fa-palette"></i>Aparência</button>
        </nav>
    </aside>

    <div class="m-profile-main">

        <form id="teacherProfileForm" data-profile-panel="dados" novalidate>

            <section class="m-profile-card">
                <header><h6><i class="fas fa-id-card"></i>Dados pessoais</h6><small>Para corrigir, fale com a secretaria.</small></header>
                <dl class="m-data-list m-data-list-3">
                    <div><dt>Nome</dt><dd><?= htmlspecialchars($teacher->name) ?></dd></div>
                    <div><dt>CPF</dt><dd><?= htmlspecialchars($cpf) ?></dd></div>
                    <div><dt>Nascimento</dt><dd><?= htmlspecialchars($birth) ?></dd></div>
                    <div><dt>Sexo</dt><dd><?= htmlspecialchars($teacher->sex) ?></dd></div>
                    <div><dt>Nacionalidade</dt><dd><?= htmlspecialchars($teacher->nationality) ?></dd></div>
                    <div><dt>Naturalidade</dt><dd><?= htmlspecialchars($teacher->naturalness) ?></dd></div>
                </dl>
            </section>

            <section class="m-profile-card">
                <header><h6><i class="fas fa-address-book"></i>Contato e endereço</h6></header>
                <div class="m-form-grid">
                    <label class="m-att-field m-span-2"><span class="m-att-label">E-mail</span><input class="form-control" name="email" type="email" value="<?= htmlspecialchars($teacher->email) ?>" disabled></label>
                    <label class="m-att-field"><span class="m-att-label">Telefone</span><input class="form-control" id="telephoneNumber" name="telephoneNumber" value="<?= htmlspecialchars($teacher->telephone_number) ?>" disabled inputmode="tel"></label>
                    <label class="m-att-field"><span class="m-att-label">CEP</span><input class="form-control" id="zipCode" name="zipCode" value="<?= htmlspecialchars($teacher->zip_code) ?>" disabled inputmode="numeric"></label>
                    <label class="m-att-field m-span-2"><span class="m-att-label">Endereço</span><input class="form-control" name="address" value="<?= htmlspecialchars($teacher->address) ?>" disabled></label>
                    <label class="m-att-field"><span class="m-att-label">Bairro</span><input class="form-control" name="district" value="<?= htmlspecialchars($teacher->district) ?>" disabled></label>
                    <label class="m-att-field"><span class="m-att-label">Município</span><input class="form-control" name="county" value="<?= htmlspecialchars($teacher->county) ?>" disabled></label>
                    <label class="m-att-field"><span class="m-att-label">UF</span><input class="form-control" name="uf" value="<?= htmlspecialchars($teacher->uf) ?>" maxlength="2" disabled></label>
                </div>
            </section>

            <section class="m-profile-card">
                <header><h6><i class="fas fa-heartbeat"></i>Saúde e acessibilidade</h6></header>
                <div class="m-form-grid">
                    <label class="m-att-field"><span class="m-att-label">Tipo sanguíneo</span>
                        <select class="form-control custom-select" name="bloodType" disabled>
                            <option value="<?= (int) $teacher->blood_type_id ?>"><?= htmlspecialchars($teacher->blood_type) ?></option>
                            <?php foreach ($this->view->bloodType as $b) { if ($b->option_value == $teacher->blood_type_id) continue; ?><option value="<?= (int) $b->option_value ?>"><?= htmlspecialchars($b->option_text) ?></option><?php } ?>
                        </select>
                    </label>
                    <label class="m-att-field"><span class="m-att-label">Pessoa com deficiência</span>
                        <select class="form-control custom-select" name="pcd" disabled>
                            <option value="<?= (int) $teacher->pcd_id ?>"><?= htmlspecialchars($teacher->pcd) ?></option>
                            <?php foreach ($this->view->pcd as $p) { if ($p->option_value == $teacher->pcd_id) continue; ?><option value="<?= (int) $p->option_value ?>"><?= htmlspecialchars($p->option_text) ?></option><?php } ?>
                        </select>
                    </label>
                </div>
            </section>

            <section class="m-profile-card">
                <header><h6><i class="fas fa-key"></i>Acesso ao portal</h6></header>
                <label class="m-att-field m-code-field"><span class="m-att-label">Código de acesso</span>
                    <span class="m-code-input">
                        <input class="form-control" name="accessCode" type="password" value="<?= htmlspecialchars($teacher->access_code) ?>" maxlength="30" disabled autocomplete="off">
                        <button type="button" class="m-icon-btn" data-reveal aria-label="Mostrar código"><i class="fas fa-eye"></i></button>
                    </span>
                </label>
            </section>

            <div class="m-profile-bar">
                <button type="button" class="btn btn-primary" data-profile-edit><i class="fas fa-pen mr-2"></i>Editar meus dados</button>
                <button type="submit" class="btn btn-primary" data-profile-save hidden><i class="fas fa-check mr-2"></i>Salvar</button>
                <button type="button" class="btn btn-light" data-profile-cancel hidden>Cancelar</button>
            </div>
        </form>

        <section class="m-profile-card" data-profile-panel="aparencia" hidden>
            <header><h6><i class="fas fa-palette"></i>Aparência</h6></header>
            <div class="custom-control custom-switch m-switch">
                <input type="checkbox" class="custom-control-input" id="nightMode" portal="Teacher">
                <label class="custom-control-label" for="nightMode">Modo escuro</label>
            </div>
            <p class="m-muted-note mt-2 mb-0">Fica salvo neste aparelho.</p>
        </section>

    </div>
</div>

<script>
    (function () {
        const root = document.getElementById('teacherProfile');
        const form = document.getElementById('teacherProfileForm');
        const fields = () => form.querySelectorAll('.form-control');
        const toggle = (editing) => {
            fields().forEach((f) => { f.disabled = !editing; });
            form.querySelector('[data-profile-edit]').hidden = editing;
            form.querySelector('[data-profile-save]').hidden = !editing;
            form.querySelector('[data-profile-cancel]').hidden = !editing;
            if (editing) form.querySelector('[name="email"]').focus();
        };

        root.querySelectorAll('[data-profile-tab]').forEach((tab) => tab.addEventListener('click', () => {
            root.querySelectorAll('[data-profile-tab]').forEach((t) => t.classList.toggle('is-active', t === tab));
            root.querySelectorAll('[data-profile-panel]').forEach((p) => { p.hidden = p.dataset.profilePanel !== tab.dataset.profileTab; });
        }));

        form.querySelector('[data-profile-edit]').addEventListener('click', () => toggle(true));
        form.querySelector('[data-profile-cancel]').addEventListener('click', () => { form.reset(); toggle(false); });
        form.querySelector('[data-reveal]').addEventListener('click', (e) => {
            const input = form.querySelector('[name="accessCode"]');
            input.type = input.type === 'password' ? 'text' : 'password';
            e.currentTarget.querySelector('i').className = `fas ${input.type === 'password' ? 'fa-eye' : 'fa-eye-slash'}`;
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const body = new URLSearchParams(new FormData(form));
            try {
                const response = await fetch('/portal-docente/perfil/atualizar', { method: 'POST', body, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!response.ok) throw new Error();
                toggle(false);
                if (typeof tools !== 'undefined') tools.showToast('Dados atualizados', 'bg-success');
            } catch (e) {
                if (typeof tools !== 'undefined') tools.showToast('Não foi possível salvar. Tente de novo.', 'bg-danger');
            }
        });
    })();
</script>
