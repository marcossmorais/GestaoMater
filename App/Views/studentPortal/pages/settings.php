<?php
require __DIR__ . '../../../../config/variables.php';

// Perfil do aluno: só leitura (correções de cadastro passam pela secretaria), matrícula vinculada e aparência
$student = $this->view->studentProfile[0];
$photoDir = "$app_url/assets/img/studentProfilePhotos/";
$photo = $student->profile_photo ? $photoDir . $student->profile_photo : $photoDir . 'foto-vazia.jpg';
$birth = $student->birth_date ? date('d/m/Y', strtotime($student->birth_date)) : '–';
$enrollments = $this->view->studentEnrollment ?? [];
?>

<div class="m-profile m-profile-student" id="studentProfile">

    <aside class="m-profile-side">
        <img class="m-profile-photo" src="<?= htmlspecialchars($photo) ?>" alt="" onerror="this.src='<?= $photoDir ?>foto-vazia.jpg'">
        <h5><?= htmlspecialchars($student->name) ?></h5>
        <span class="m-sticker"><i class="fas fa-star"></i><?= htmlspecialchars($_SESSION['Student']['class']) ?></span>
        <?php if (count($enrollments) > 1) { ?>
            <nav class="m-profile-nav" aria-label="Seções do perfil">
                <button type="button" class="is-active" data-profile-tab="dados"><i class="fas fa-user"></i>Meus dados</button>
                <button type="button" data-profile-tab="matricula"><i class="fas fa-school"></i>Matrículas</button>
            </nav>
        <?php } ?>
    </aside>

    <div class="m-profile-main">

        <div data-profile-panel="dados">
            <section class="m-profile-card">
                <header><h6><i class="fas fa-id-card"></i>Sobre você</h6><small>Algo errado? Fale com a secretaria.</small></header>
                <dl class="m-data-list m-data-list-3">
                    <div><dt>Nome</dt><dd><?= htmlspecialchars($student->name) ?></dd></div>
                    <div><dt>Nascimento</dt><dd><?= htmlspecialchars($birth) ?></dd></div>
                    <div><dt>Situação</dt><dd><?= htmlspecialchars($student->general_situation ?? '–') ?></dd></div>
                    <div><dt>Turma</dt><dd><?= htmlspecialchars($_SESSION['Student']['class']) ?></dd></div>
                    <div><dt>Mãe</dt><dd><?= htmlspecialchars($student->mother ?: '–') ?></dd></div>
                    <div><dt>Pai</dt><dd><?= htmlspecialchars($student->father ?: '–') ?></dd></div>
                </dl>
            </section>

            <section class="m-profile-card">
                <header><h6><i class="fas fa-address-book"></i>Contato</h6></header>
                <dl class="m-data-list m-data-list-3">
                    <div><dt>E-mail</dt><dd><?= htmlspecialchars($student->email ?: '–') ?></dd></div>
                    <div><dt>Telefone</dt><dd><?= htmlspecialchars($student->telephone_number ?: '–') ?></dd></div>
                    <div><dt>Cidade</dt><dd><?= htmlspecialchars(trim(($student->county ?? '') . ' / ' . ($student->uf ?? ''), ' /') ?: '–') ?></dd></div>
                </dl>
            </section>

            <section class="m-profile-card">
                <header><h6><i class="fas fa-key"></i>Acesso ao portal</h6></header>
                <span class="m-att-label">Código de acesso</span>
                <span class="m-code-input">
                    <input class="form-control" type="password" value="<?= htmlspecialchars($student->access_code) ?>" readonly aria-label="Código de acesso">
                    <button type="button" class="m-icon-btn" data-reveal aria-label="Mostrar código"><i class="fas fa-eye"></i></button>
                </span>
                <p class="m-muted-note mt-2 mb-0">Não conte seu código para ninguém.</p>
            </section>
        </div>

        <?php if (count($enrollments) > 1) { ?>
            <section class="m-profile-card" data-profile-panel="matricula" hidden>
                <header><h6><i class="fas fa-school"></i>Matrículas</h6><small>Escolha qual ano ver no portal.</small></header>
                <form id="formSettingsStudent" class="m-enrollments">
                    <?php foreach ($enrollments as $e) { $current = (int) $e->enrollment_id === (int) $_SESSION['Student']['enrollmentId']; ?>
                        <label class="m-enrollment <?= $current ? 'is-current' : '' ?>">
                            <input type="radio" name="enrollmentId" value="<?= (int) $e->enrollment_id ?>" <?= $current ? 'checked' : '' ?>>
                            <span><strong><?= htmlspecialchars($e->school_year) ?></strong><small><?= htmlspecialchars($e->acronym_series) ?>ª <?= htmlspecialchars($e->ballot) ?> · <?= htmlspecialchars($e->course) ?> · <?= htmlspecialchars($e->shift) ?></small></span>
                        </label>
                    <?php } ?>
                    <button type="submit" class="btn btn-primary mt-2">Ver esta matrícula</button>
                </form>
            </section>
        <?php } ?>

    </div>
</div>

<script>
    (function () {
        const root = document.getElementById('studentProfile');
        root.querySelectorAll('[data-profile-tab]').forEach((tab) => tab.addEventListener('click', () => {
            root.querySelectorAll('[data-profile-tab]').forEach((t) => t.classList.toggle('is-active', t === tab));
            root.querySelectorAll('[data-profile-panel]').forEach((p) => { p.hidden = p.dataset.profilePanel !== tab.dataset.profileTab; });
        }));
        root.querySelector('[data-reveal]').addEventListener('click', (e) => {
            const input = e.currentTarget.previousElementSibling;
            input.type = input.type === 'password' ? 'text' : 'password';
            e.currentTarget.querySelector('i').className = `fas ${input.type === 'password' ? 'fa-eye' : 'fa-eye-slash'}`;
        });
        const form = document.getElementById('formSettingsStudent');
        if (form) form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const response = await fetch('/portal-aluno/configuracoes/atualizar', { method: 'POST', body: new URLSearchParams(new FormData(form)), headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (response.ok) location.reload();
            else if (typeof tools !== 'undefined') tools.showToast('Não foi possível trocar a matrícula.', 'bg-danger');
        });
    })();
</script>
