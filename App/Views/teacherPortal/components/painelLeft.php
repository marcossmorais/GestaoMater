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

            <li name="home"><a href="/portal-docente/home" title="Painel"><i class="fas fa-home"></i><span class="link-name">Painel</span></a></li>

            <li name="turmas"><a href="/portal-docente/turmas" title="Turmas"><i class="fas fa-boxes"></i><span class="link-name">Turmas</span></a></li>


            <li name="diario"><a href="/portal-docente/diario" title="Planejamento e Horário"><i class="fas fa-book-open"></i><span class="link-name">Planejamento e Horário</span></a></li>

            <li name="notas"><a href="/portal-docente/notas" title="Notas"><i class="fas fa-marker"></i><span class="link-name">Notas</span></a></li>

            <li name="roteiros"><a href="/portal-docente/roteiros" title="Roteiro de Provas"><i class="fas fa-route"></i><span class="link-name">Roteiro de Provas</span></a></li>

            <li name="mural"><a href="/portal-docente/mural" title="Mural"><i class="fas fa-thumbtack"></i><span class="link-name">Mural</span></a></li>


            <li name="perfil"><a href="/portal-docente/perfil" title="Perfil"><i class="fas fa-cog"></i><span class="link-name">Perfil</span></a></li>

            <li data-install-app hidden><a href="#" title="Instalar app"><i class="fas fa-download"></i><span class="link-name">Instalar app</span></a></li>

        </ul>

    </nav>

</div>