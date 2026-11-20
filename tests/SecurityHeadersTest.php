<?php
// Content-Security-Policy das páginas. Uso: php tests/SecurityHeadersTest.php (sai com código 1 se falhar)

require __DIR__ . '/../App/Tools/SecurityHeaders.php';

use App\Tools\SecurityHeaders;

$fail = 0;
$total = 0;

function check($name, $ok)
{
    global $fail, $total;
    $total++;
    echo ($ok ? 'ok   ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) $fail++;
}

$clerk = 'clerk.colegio.com.br';

// ---------- telas internas: política completa aplicada
$app = SecurityHeaders::forPath('/admin/home', $clerk);
$csp = $app['Content-Security-Policy'] ?? '';
check('tela interna aplica a política', $csp !== '' && !isset($app['Content-Security-Policy-Report-Only']));
check('script só do próprio site', strpos($csp, "script-src 'self' 'unsafe-inline';") !== false);
check('sem CDN de terceiros', strpos($csp, 'jsdelivr') === false && strpos($csp, 'cdnjs') === false);
check('Clerk fora das telas internas', strpos($csp, $clerk) === false);
check('ViaCEP liberado só para chamadas', strpos($csp, 'connect-src \'self\' https://viacep.com.br') !== false);
check('sem plugin (object-src none)', strpos($csp, "object-src 'none'") !== false);
check('não abre em iframe de outro site', strpos($csp, "frame-ancestors 'self'") !== false);
check('base-uri travado', strpos($csp, "base-uri 'self'") !== false);
check('formulário só para o próprio site', strpos($csp, "form-action 'self'") !== false);
check('portal do docente também', isset(SecurityHeaders::forPath('/portal-docente/home', $clerk)['Content-Security-Policy']));
check('página inicial também', strpos(SecurityHeaders::forPath('/', $clerk)['Content-Security-Policy'], "default-src 'self'") !== false);

// ---------- login: só o básico aplicado, política do Clerk em observação
foreach (['/admin', '/portal-docente/', '/portal-aluno', '/portal-aluno/login'] as $path) {
    $h = SecurityHeaders::forPath($path, $clerk);
    check("$path: aplica só o básico", ($h['Content-Security-Policy'] ?? '') === "object-src 'none'; base-uri 'self'; frame-ancestors 'self'");
    check("$path: política completa em Report-Only", strpos($h['Content-Security-Policy-Report-Only'] ?? '', "script-src 'self' 'unsafe-inline' https://$clerk https://challenges.cloudflare.com") !== false);
}

$login = SecurityHeaders::forPath('/admin', $clerk)['Content-Security-Policy-Report-Only'];
check('login: Clerk liberado nas chamadas', strpos($login, "connect-src 'self' https://$clerk") !== false);
check('login: imagens do Clerk', strpos($login, 'https://img.clerk.com') !== false);

// ---------- host do Clerk estranho não entra na política
$bad = SecurityHeaders::loginPolicy("x.com; script-src *");
check('host com ; é ignorado', strpos($bad, 'x.com') === false && strpos($bad, 'script-src *') === false);
check('sem Clerk configurado não quebra', strpos(SecurityHeaders::loginPolicy(''), "script-src 'self' 'unsafe-inline' https://challenges.cloudflare.com") !== false);

echo PHP_EOL . ($total - $fail) . "/$total ok" . PHP_EOL;
exit($fail ? 1 : 0);
