<?php
// Atalhos da gestão, na ordem em que a escola monta o ano letivo
$shortcuts = [
    ['/admin/gestao/periodo-letivo', 'far fa-calendar-alt', 'Período letivo', 'Defina o ano letivo atual, edite as datas ou finalize o período.'],
    ['/admin/gestao/sala', 'fas fa-door-open', 'Salas', 'Cadastre as salas de aula e a capacidade de cada uma.'],
    ['/admin/gestao/curso', 'fas fa-graduation-cap', 'Cursos', 'Defina os cursos ofertados pela escola.'],
    ['/admin/gestao/disciplina', 'fas fa-book', 'Disciplinas', 'Crie as disciplinas que os docentes vão ministrar.'],
    ['/admin/gestao/turma', 'fas fa-users', 'Turmas', 'Monte as turmas do período, vincule disciplinas e acompanhe notas.'],
    ['/admin/aluno/lista', 'fas fa-user-graduate', 'Alunos', 'Busque alunos, veja perfis, boletins e matrículas.'],
    ['/admin/professor/lista', 'fas fa-chalkboard-teacher', 'Docentes', 'Consulte e edite os cadastros dos professores.'],
];
?>

<section id="general-management">

    <div class="row main-container">

        <div class="col-lg-11 mx-auto">

            <div class="m-page-head">
                <div>
                    <h5>Gestão geral</h5>
                    <p>Tudo o que estrutura o ano letivo, na ordem de configuração.</p>
                </div>
            </div>

            <div class="m-shortcuts">
                <?php foreach ($shortcuts as $i => [$url, $icon, $title, $text]) { ?>
                    <a class="card m-shortcut" href="<?= $url ?>">
                        <div class="m-kpi-icon"><i class="<?= $icon ?>"></i></div>
                        <h6><?= $i < 5 ? ($i + 1) . '. ' : '' ?><?= $title ?></h6>
                        <p><?= $text ?></p>
                        <span class="m-go">Acessar <i class="fas fa-arrow-right ml-1"></i></span>
                    </a>
                <?php } ?>
            </div>

        </div>

    </div>

</section>
