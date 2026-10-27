<?php
require __DIR__ . '/../../config/variables.php';

$result = $this->view->result;
$code = $this->view->code;

$states = [
    'valid' => ['Documento válido', 'Emitido pelo sistema do MATER e sem alterações.', 'ok', 'fa-check-circle'],
    'revoked' => ['Documento cancelado', 'Este documento foi cancelado pela secretaria e não vale mais.', 'warn', 'fa-ban'],
    'tampered' => ['Documento inválido', 'O registro deste código não confere com a assinatura. Procure a secretaria.', 'bad', 'fa-exclamation-triangle'],
    'not_found' => ['Código não encontrado', 'Confira se digitou o código exatamente como está no documento.', 'bad', 'fa-question-circle'],
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Validar documento | MATER</title>
    <link href="<?= $app_url ?>/node_modules/@fortawesome/fontawesome-free/css/all.css" rel="stylesheet">
    <style>
        :root { --bg: #F4F7FC; --card: #fff; --text: #0F1B33; --muted: #5B6B85; --border: #DCE3EF; --primary: #1868E8;
                --ok: #16A34A; --warn: #B45309; --bad: #DC2626; }
        @media (prefers-color-scheme: dark) {
            :root { --bg: #0B1220; --card: #111A2E; --text: #E6ECF7; --muted: #9AA8C2; --border: #24324D; --primary: #5B9BFF; --warn: #FBBF24; }
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: var(--bg); color: var(--text); font: 16px/1.5 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
               display: flex; align-items: flex-start; justify-content: center; padding: 48px 16px; }
        main { width: 100%; max-width: 440px; }
        .brand { text-align: center; margin-bottom: 20px; }
        .brand img { height: 48px; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 24px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        p { margin: 0 0 16px; color: var(--muted); }
        form { display: flex; gap: 8px; }
        input { flex: 1; min-width: 0; min-height: 48px; padding: 0 14px; border-radius: 10px; border: 1px solid var(--border); background: var(--bg); color: var(--text);
                font-size: 17px; letter-spacing: .08em; text-transform: uppercase; }
        button { min-height: 48px; padding: 0 18px; border: 0; border-radius: 10px; background: var(--primary); color: #fff; font-weight: 700; font-size: 15px; cursor: pointer; }
        .result { margin-top: 20px; padding-top: 20px; border-top: 1px dashed var(--border); }
        .result h2 { display: flex; align-items: center; gap: 8px; font-size: 18px; margin: 0 0 4px; }
        .is-ok h2 { color: var(--ok); } .is-warn h2 { color: var(--warn); } .is-bad h2 { color: var(--bad); }
        dl { display: grid; grid-template-columns: auto 1fr; gap: 6px 14px; margin: 12px 0 0; font-size: 15px; }
        dt { color: var(--muted); } dd { margin: 0; font-weight: 600; overflow-wrap: anywhere; }
    </style>
</head>
<body>
    <main>
        <div class="brand"><img src="<?= $app_url ?>/assets/img/logo-components/logo.png" alt="MATER"></div>
        <div class="card">
            <h1>Validar documento</h1>
            <p>Digite o código impresso no rodapé da declaração ou do boletim, ou leia o QR code.</p>
            <form method="get" action="/validar">
                <input type="text" name="codigo" value="<?= htmlspecialchars($code) ?>" placeholder="XXXX-XXXX-XXXX" maxlength="20" autocomplete="off" aria-label="Código do documento" required>
                <button type="submit">Validar</button>
            </form>

            <?php if ($result) { [$title, $text, $tone, $icon] = $states[$result['status']]; ?>
                <section class="result is-<?= $tone ?>" aria-live="polite">
                    <h2><i class="fas <?= $icon ?>"></i><?= $title ?></h2>
                    <p><?= $text ?></p>
                    <?php if (in_array($result['status'], ['valid', 'revoked'], true)) { ?>
                        <dl>
                            <dt>Documento</dt><dd><?= htmlspecialchars($result['type']) ?></dd>
                            <dt>Aluno(a)</dt><dd><?= htmlspecialchars($result['student']) ?></dd>
                            <dt>Emitido em</dt><dd><?= htmlspecialchars($result['issuedAt']) ?></dd>
                            <dt>Código</dt><dd><?= htmlspecialchars($result['code']) ?></dd>
                        </dl>
                    <?php } ?>
                </section>
            <?php } ?>
        </div>
    </main>
</body>
</html>
