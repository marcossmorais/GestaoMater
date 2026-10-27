<?php 

require __DIR__ . '../../../../config/variables.php';

?>

<div id="left-panel" class="row">

    <nav id="sidebar">

        <div class="col-lg-12 logo d-flex justify-content-center"><img src="<?= $app_url ?>/assets/img/logo-components/logo-completa-branca.png" alt=""></div> 

        <ul class="sidebar-lists">

            <p>Navegação</p>

            <li id="minimize"><a  href="#"><i class="fas fa-chevron-circle-left text-white"></i><span class="link-name">Minimilizar</span></a></li>

            <li name="home"><a href="/portal-docente/home"><i class="fas fa-home"></i><span class="link-name">Home</span></a></li>

            <li name="turmas"><a href="/portal-docente/turmas"><i class="fas fa-boxes"></i><span class="link-name">Turmas</span></a></li>

            <li name="chamada"><a href="/portal-docente/chamada"><i class="fas fa-clipboard-check"></i><span class="link-name">Chamada rápida</span></a></li>

            <li name="diario"><a href="/portal-docente/diario"><i class="fas fa-book-open"></i><span class="link-name">Diário de aula</span></a></li>

            <li name="notas"><a href="/portal-docente/notas"><i class="fas fa-marker"></i><span class="link-name">Notas e provas</span></a></li>

            <li name="mural"><a href="/portal-docente/mural"><i class="fas fa-thumbtack"></i><span class="link-name">Mural da turma</span></a></li>

            <li name="horario"><a href="/portal-docente/horario"><i class="fas fa-calendar-alt"></i><span class="link-name">Horários</span></a></li>

            <li><a href="#" id="settingsTeacherPortal"><i class="fas fa-cog"></i><span class="link-name">Configurações</span></a></li>

        </ul>

    </nav>

</div>