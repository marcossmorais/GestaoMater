<?php 

require __DIR__ . '../../../../config/variables.php';

?>

<nav class="navbar fixed-bottom navbar-expand p-2 rounded-0 navbarBottomTeacher" id="navbarBottom" style="border-radius: 0px">

    <div class="collapse navbar-collapse" id="navbarSupportedContent">

        <ul class="navbar-nav">

            <li class="nav-item active">
                <a name="home" class="nav-link" href="/portal-docente/home"><i class="fas fa-home"></i> <span>Home</span></a>
            </li>

            <li class="nav-item">
                <a name="chamada" class="nav-link" href="/portal-docente/chamada"><i class="fas fa-clipboard-check"></i> <span>Chamada</span></a>
            </li>

            <li class="nav-item">
                <a name="diario" class="nav-link" href="/portal-docente/diario"><i class="fas fa-book-open"></i> <span>Diário</span></a>
            </li>

            <li class="nav-item">
                <a name="notas" class="nav-link" href="/portal-docente/notas"><i class="fas fa-marker"></i> <span>Notas</span></a>
            </li>

            <li class="nav-item">
                <button type="button" class="nav-link m-more-toggle" data-more-toggle aria-expanded="false" aria-controls="teacherMoreMenu"><i class="fas fa-ellipsis-h"></i> <span>Mais</span></button>
            </li>

        </ul>

    </div>

</nav>

<?php $photo = ($_SESSION['Teacher']['profilePhoto'] ? $_SESSION['Teacher']['profilePhoto'] : 'foto-vazia.jpg') ?>

<!-- Menu "Mais" (celular) -->
<div class="m-more-menu" id="teacherMoreMenu" hidden>
    <a href="/portal-docente/turmas"><i class="fas fa-users"></i>Turmas</a>
    <a href="/portal-docente/mural"><i class="fas fa-thumbtack"></i>Mural da turma</a>
    <a href="/portal-docente/horario"><i class="fas fa-calendar-alt"></i>Horários</a>
    <a href="#" data-install-app hidden><i class="fas fa-download"></i>Instalar app no celular</a>
    <a href="#" id="settingsTeacherPortal"><img class="foto-perfil" src="<?= $app_url ?>/assets/img/teacherProfilePhotos/<?= $photo ?>" alt="">Meu perfil</a>
    <a href="/portal-docente/sair" class="text-danger"><i class="fas fa-sign-out-alt"></i>Sair da conta</a>
</div>