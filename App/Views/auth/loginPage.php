<?php
// Tela de login compartilhada pelos 3 portais. Espera $loginPortal = 'admin' | 'docente' | 'aluno'

if (session_status() === PHP_SESSION_NONE) session_start();

$homes = ['Admin' => '/admin/home', 'Teacher' => '/portal-docente/home', 'Student' => '/portal-aluno/home'];
foreach ($homes as $sessionKey => $home) {
    if (isset($_SESSION[$sessionKey])) {
        header("Location: $home");
        exit;
    }
}

require __DIR__ . '/../../config/variables.php';

$portals = [
    'admin' => [
        'tab' => 'Admin', 'url' => '/admin', 'action' => '/admin/login', 'title' => 'Portal da administração',
        'art' => 'admin.svg', 'headline' => 'Gestão da escola em um só lugar',
        'text' => 'Turmas, matrículas, professores, períodos letivos e rematrícula.',
    ],
    'docente' => [
        'tab' => 'Docente', 'url' => '/portal-docente', 'action' => '/portal-docente/login', 'title' => 'Portal do docente',
        'art' => 'teacher.svg', 'headline' => 'Suas turmas, sem papelada',
        'text' => 'Lance avaliações, notas e faltas e publique avisos para os alunos.',
    ],
    'aluno' => [
        'tab' => 'Aluno', 'url' => '/portal-aluno', 'action' => '/portal-aluno/login', 'title' => 'Portal do aluno',
        'art' => 'students.svg', 'headline' => 'O seu futuro está aqui',
        'text' => 'Acompanhe boletim, notas, faltas e os avisos da sua turma.',
    ],
];
$portal = $portals[$loginPortal];
$clerkEnabled = \App\Auth\Clerk::enabled();
$error = isset($_GET['error']) ? ucfirst(str_replace('-', ' ', $_GET['error'])) : null;
?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar — <?= $portal['title'] ?> | Mater</title>
    <link rel="preload" href="<?= $app_url ?>/assets/fonts/inter/inter-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= asset('/assets/css/auth.css') ?>">
    <link rel="icon" type="image/png" href="<?= $app_url ?>/favicon.png">
</head>

<body>

    <div class="auth">

        <aside class="auth-side brand-bg">
            <a class="logo" href="/"><img src="<?= $app_url ?>/assets/img/logo-components/logo-completa-branca.png" alt="Mater" width="200" height="65"></a>

            <div>
                <h1><?= $portal['headline'] ?></h1>
                <p><?= $portal['text'] ?></p>
            </div>

            <div class="art"><img src="<?= $app_url ?>/assets/img/illustrations/<?= $portal['art'] ?>" alt=""></div>

            <small>© <?= date('Y') ?> Mater — Colégio e Curso</small>
        </aside>

        <main class="auth-main">
            <div class="auth-box">

                <a class="back" href="/">&larr; Voltar ao início</a>

                <nav class="tabs" aria-label="Portais">
                    <?php foreach ($portals as $key => $p) { ?>
                        <a href="<?= $p['url'] ?>" class="<?= $key === $loginPortal ? 'active' : '' ?>"><?= $p['tab'] ?></a>
                    <?php } ?>
                </nav>

                <h2>Entrar</h2>
                <p class="sub"><?= $portal['title'] ?></p>

                <?php if ($error) { ?>
                    <div class="alert" role="alert"><?= htmlspecialchars($error) ?></div>
                <?php } ?>

                <?php if ($clerkEnabled) { ?>

                    <?php $clerkPortal = $loginPortal; require __DIR__ . '/clerkSignIn.php'; ?>

                <?php } else { ?>

                    <span class="dev-badge">Modo desenvolvimento</span>

                    <form action="<?= $portal['action'] ?>" method="POST">
                        <div class="field">
                            <label for="name">Nome completo</label>
                            <input type="text" id="name" name="name" autocomplete="name" required>
                        </div>
                        <div class="field">
                            <label for="accessCode">Código de acesso</label>
                            <input type="password" id="accessCode" name="accessCode" inputmode="numeric" maxlength="30" required>
                        </div>
                        <button class="btn" type="submit">Entrar</button>
                    </form>

                    <p class="hint">Esqueceu o código? Fale com a secretaria da escola.</p>

                <?php } ?>

            </div>
        </main>

    </div>

</body>

</html>
