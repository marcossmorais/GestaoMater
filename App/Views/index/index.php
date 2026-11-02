<?php require __DIR__ . '/../../config/variables.php'; ?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mater — Colégio e Curso</title>
    <meta name="description" content="Portal do Colégio Mater: chamada, diário, notas e boletim para professores e alunos.">
    <meta name="theme-color" content="#2F6FED">
    <link rel="preload" href="<?= $app_url ?>/assets/fonts/nunito/nunito-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= $app_url ?>/node_modules/@fortawesome/fontawesome-free/css/all.css">
    <link rel="stylesheet" href="<?= asset('/assets/css/auth.css') ?>">
    <link rel="icon" type="image/png" href="<?= $app_url ?>/favicon.png">
</head>

<body class="lp">

    <header class="lp-top">
        <a href="/" class="lp-logo"><img src="<?= $app_url ?>/assets/img/logo-components/logo-completa-480.png" alt="Mater — O seu futuro está aqui" width="170" height="55"></a>
        <nav class="lp-nav" aria-label="Seções">
            <a href="#professor">Para professores</a>
            <a href="#aluno">Para alunos</a>
            <a class="lp-btn lp-btn-sm" href="#entrar">Entrar</a>
        </nav>
    </header>

    <main>

        <section class="lp-hero">
            <div class="lp-hero-text">
                <span class="lp-sticker lp-rot-l"><i class="fas fa-school"></i> Colégio Mater · <?= date('Y') ?></span>
                <h1>A escola inteira na <span class="lp-mark">palma da mão</span></h1>
                <p>Chamada em segundos, notas por bimestre, diário com BNCC, mural da turma e boletim sempre em dia. Para quem ensina e para quem aprende.</p>
                <div class="lp-cta" id="entrar">
                    <a class="lp-btn" href="/portal-docente"><i class="fas fa-chalkboard-teacher"></i> Sou professor</a>
                    <a class="lp-btn lp-btn-amber" href="/portal-aluno"><i class="fas fa-user-graduate"></i> Sou aluno</a>
                </div>
            </div>
            <div class="lp-hero-art">
                <div class="lp-blob"><img src="<?= $app_url ?>/assets/img/illustrations/students.svg" alt=""></div>
                <span class="lp-float lp-float-1"><i class="fas fa-check-circle"></i> Chamada feita</span>
                <span class="lp-float lp-float-2"><i class="fas fa-star"></i> Média 8,5</span>
                <span class="lp-float lp-float-3"><i class="fas fa-thumbtack"></i> Prova na sexta!</span>
            </div>
        </section>

        <section class="lp-features" aria-label="O que tem no Mater">
            <article class="lp-feature lp-green"><span><i class="fas fa-clipboard-check"></i></span><h3>Chamada rápida</h3><p>Todos começam presentes. O professor toca só em quem faltou.</p></article>
            <article class="lp-feature lp-amber"><span><i class="fas fa-star"></i></span><h3>Notas por bimestre</h3><p>1ª, 2ª e 3ª avaliação, de 0 a 10, e recuperação para quem precisa.</p></article>
            <article class="lp-feature lp-coral"><span><i class="fas fa-book-open"></i></span><h3>Diário com BNCC</h3><p>Conteúdo da aula com a habilidade certa, escolhida numa busca.</p></article>
            <article class="lp-feature lp-purple"><span><i class="fas fa-thumbtack"></i></span><h3>Mural da turma</h3><p>Avisos e roteiros de prova chegam direto para os alunos.</p></article>
        </section>

        <section class="lp-who">
            <article class="lp-who-card lp-blue" id="professor">
                <img src="<?= $app_url ?>/assets/img/illustrations/teacher.svg" alt="">
                <div>
                    <h2>Para quem ensina</h2>
                    <ul>
                        <li><i class="fas fa-check"></i> Aulas do dia e o que falta fazer, logo na entrada</li>
                        <li><i class="fas fa-check"></i> Chamada e diário em sequência, sem papel</li>
                        <li><i class="fas fa-check"></i> Alunos que precisam de atenção, antes do fechamento</li>
                    </ul>
                    <a class="lp-btn" href="/portal-docente">Entrar como professor</a>
                </div>
            </article>
            <article class="lp-who-card lp-amber" id="aluno">
                <img src="<?= $app_url ?>/assets/img/illustrations/students.svg" alt="">
                <div>
                    <h2>Para quem aprende</h2>
                    <ul>
                        <li><i class="fas fa-check"></i> Boletim e faltas de cada matéria</li>
                        <li><i class="fas fa-check"></i> Quanto falta para fechar a média</li>
                        <li><i class="fas fa-check"></i> Horário, avisos e roteiros de prova</li>
                    </ul>
                    <a class="lp-btn lp-btn-amber" href="/portal-aluno">Entrar como aluno</a>
                </div>
            </article>
        </section>

    </main>

    <footer class="lp-foot">
        <span>© <?= date('Y') ?> Mater — Colégio e Curso</span>
        <nav aria-label="Outros acessos">
            <a href="/validar"><i class="fas fa-shield-alt"></i> Validar documento</a>
            <a href="/admin"><i class="fas fa-lock"></i> Acesso da secretaria</a>
        </nav>
    </footer>

</body>

</html>
