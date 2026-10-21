<?php require __DIR__ . '/../../config/variables.php'; ?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mater — Sistema de Gestão Escolar</title>
    <link rel="preload" href="<?= $app_url ?>/assets/fonts/inter/inter-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= $app_url ?>/assets/css/auth.css">
    <link rel="icon" type="image/png" href="<?= $app_url ?>/favicon.png">
</head>

<body class="brand-bg">

    <div class="home">

        <header>
            <img src="<?= $app_url ?>/assets/img/logo-components/logo-completa-branca.png" alt="Mater — O seu futuro está aqui!" width="230" height="75">
        </header>

        <main>
            <h1>Bem-vindo ao Mater</h1>
            <p class="lead">Escolha o seu tipo de acesso</p>

            <div class="portals">

                <a class="portal-card" href="/admin">
                    <div class="art"><img src="<?= $app_url ?>/assets/img/illustrations/admin.svg" alt="" loading="lazy"></div>
                    <h2>Administração</h2>
                    <p>Secretaria e direção: turmas, matrículas, professores e configurações.</p>
                    <span class="btn">Acessar</span>
                </a>

                <a class="portal-card" href="/portal-docente">
                    <div class="art"><img src="<?= $app_url ?>/assets/img/illustrations/teacher.svg" alt="" loading="lazy"></div>
                    <h2>Docente</h2>
                    <p>Avaliações, notas, faltas e avisos das suas turmas.</p>
                    <span class="btn">Acessar</span>
                </a>

                <a class="portal-card" href="/portal-aluno">
                    <div class="art"><img src="<?= $app_url ?>/assets/img/illustrations/students.svg" alt="" loading="lazy"></div>
                    <h2>Aluno</h2>
                    <p>Boletim, notas, faltas, avisos da turma e rematrícula.</p>
                    <span class="btn">Acessar</span>
                </a>

            </div>
        </main>

        <footer>© <?= date('Y') ?> Mater — Colégio e Curso</footer>

    </div>

</body>

</html>
