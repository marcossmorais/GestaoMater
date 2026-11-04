<?php
// Abertura dos documentos A4 do aluno: pede $docTitle e $docHeading; o fechamento fica em documentFoot.php
require_once __DIR__ . '/../../config/variables.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($docTitle) ?></title>
    <link rel="stylesheet" href="<?= asset('/assets/css/print-sheet.css') ?>">
</head>
<body>
    <div class="actions"><button type="button" onclick="window.print()"><?= htmlspecialchars($docPrintLabel ?? 'Imprimir') ?></button></div>

    <article class="doc">
        <header>
            <div>
                <h1><?= htmlspecialchars($docHeading) ?></h1>
                <div><?= htmlspecialchars(getenv('SCHOOL_NAME') ?: 'MATER Colégio e Curso') ?></div>
            </div>
            <img src="<?= asset('/assets/img/logo-components/logo.png') ?>" alt="MATER">
        </header>
