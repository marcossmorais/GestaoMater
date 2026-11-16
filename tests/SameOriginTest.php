<?php
// Testes da checagem de origem das requisições de escrita. Uso: php tests/SameOriginTest.php (sai com código 1 se falhar)

require __DIR__ . '/../vendor/MF/Init/Bootstrap.php';
require __DIR__ . '/../App/Route.php';

use App\Route;

$fail = 0;
$total = 0;

function check($name, $ok)
{
    global $fail, $total;
    $total++;
    echo ($ok ? 'ok   ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) $fail++;
}

$host = ['HTTP_HOST' => 'portal.exemplo.com.br'];

// ---------- navegador atual (Sec-Fetch-Site manda)
check('same-origin aceita', Route::sameOrigin($host + ['HTTP_SEC_FETCH_SITE' => 'same-origin']));
check('none (digitado na barra) aceita', Route::sameOrigin($host + ['HTTP_SEC_FETCH_SITE' => 'none']));
check('cross-site recusa', !Route::sameOrigin($host + ['HTTP_SEC_FETCH_SITE' => 'cross-site']));
check('same-site (outro subdomínio) recusa', !Route::sameOrigin($host + ['HTTP_SEC_FETCH_SITE' => 'same-site']));
check('Sec-Fetch-Site vale mais que Origin forjado', !Route::sameOrigin($host + ['HTTP_SEC_FETCH_SITE' => 'cross-site', 'HTTP_ORIGIN' => 'https://portal.exemplo.com.br']));

// ---------- navegador antigo (Origin ou Referer)
check('Origin igual aceita', Route::sameOrigin($host + ['HTTP_ORIGIN' => 'https://portal.exemplo.com.br']));
check('Origin de outro site recusa', !Route::sameOrigin($host + ['HTTP_ORIGIN' => 'https://atacante.com']));
check('Origin com host parecido recusa', !Route::sameOrigin($host + ['HTTP_ORIGIN' => 'https://portal.exemplo.com.br.atacante.com']));
check('Origin "null" recusa', !Route::sameOrigin($host + ['HTTP_ORIGIN' => 'null']));
check('Referer do próprio site aceita', Route::sameOrigin($host + ['HTTP_REFERER' => 'https://portal.exemplo.com.br/portal-docente/perfil']));
check('Referer de outro site recusa', !Route::sameOrigin($host + ['HTTP_REFERER' => 'https://atacante.com/?https://portal.exemplo.com.br']));
check('Origin com porta igual aceita', Route::sameOrigin(['HTTP_HOST' => 'localhost:8000', 'HTTP_ORIGIN' => 'http://localhost:8000']));
check('Origin com porta diferente recusa', !Route::sameOrigin(['HTTP_HOST' => 'localhost:8000', 'HTTP_ORIGIN' => 'http://localhost:9999']));
check('Origin em maiúsculas aceita', Route::sameOrigin($host + ['HTTP_ORIGIN' => 'https://PORTAL.exemplo.com.br']));

// ---------- sem navegador (curl, smoke test): nada a forjar
check('sem cabeçalhos aceita', Route::sameOrigin($host));

echo PHP_EOL . ($total - $fail) . "/$total ok" . PHP_EOL;
exit($fail ? 1 : 0);
