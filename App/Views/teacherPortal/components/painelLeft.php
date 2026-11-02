<?php 

require __DIR__ . '../../../../config/variables.php';

?>

<div id="left-panel" class="row">

    <nav id="sidebar">

        <div class="col-lg-12 logo d-flex justify-content-center">
            <img class="logo-light" src="<?= $app_url ?>/assets/img/logo-components/logo-completa-480.png" alt="Mater">
            <img class="logo-dark" src="<?= $app_url ?>/assets/img/logo-components/logo-completa-branca-480.png" alt="Mater">
            <img class="logo-mini" src="<?= $app_url ?>/assets/img/app/icon-192.png" alt="Mater">
        </div>

        <ul class="sidebar-lists">

            <p>Navegação</p>

            <li id="minimize"><a  href="#"><i class="fas fa-chevron-circle-left text-white"></i><span class="link-name">Recolher</span></a></li>

            <li name="home"><a href="/portal-docente/home" title="Home"><i class="fas fa-home"></i><span class="link-name">Home</span></a></li>

            <li name="turmas"><a href="/portal-docente/turmas" title="Turmas"><i class="fas fa-boxes"></i><span class="link-name">Turmas</span></a></li>

            <li name="chamada"><a href="/portal-docente/chamada" title="Chamada"><i class="fas fa-clipboard-check"></i><span class="link-name">Chamada</span></a></li>

            <li name="diario"><a href="/portal-docente/diario" title="Diário"><i class="fas fa-book-open"></i><span class="link-name">Diário</span></a></li>

            <li name="notas"><a href="/portal-docente/notas" title="Notas"><i class="fas fa-marker"></i><span class="link-name">Notas</span></a></li>

            <li name="mural"><a href="/portal-docente/mural" title="Mural"><i class="fas fa-thumbtack"></i><span class="link-name">Mural</span></a></li>

            <li name="horario"><a href="/portal-docente/horario" title="Horários"><i class="fas fa-calendar-alt"></i><span class="link-name">Horários</span></a></li>

            <li name="perfil"><a href="/portal-docente/perfil" title="Perfil"><i class="fas fa-cog"></i><span class="link-name">Perfil</span></a></li>

            <li data-install-app hidden><a href="#" title="Instalar app"><i class="fas fa-download"></i><span class="link-name">Instalar app</span></a></li>

        </ul>

    </nav>

</div>