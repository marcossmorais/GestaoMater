<?php
// Testes da configuração mínima de produção. Uso: php tests/ProductionCheckTest.php (sai com código 1 se falhar)

require __DIR__ . '/../App/Tools/ProductionCheck.php';

use App\Tools\ProductionCheck;

$fail = 0;
$total = 0;

function check($name, $ok, $got)
{
    global $fail, $total;
    $total++;
    echo ($ok ? 'ok   ' : 'FAIL ') . $name . ($ok ? '' : ' → ' . json_encode($got, JSON_UNESCAPED_UNICODE)) . PHP_EOL;
    if (!$ok) $fail++;
}

$good = [
    'APP_ENV' => 'production',
    'APP_SIGNING_KEY' => '3f9a1c7e5b2d8f604a1e9c3b7d5f2a8e6c4b0d9f1a3e5c7b9d2f4a6c8e0b1d3f',
    'CLERK_PUBLISHABLE_KEY' => 'pk_live_abc',
    'CLERK_SECRET_KEY' => 'sk_live_abc',
    'APP_URL' => 'https://portal.exemplo.com.br',
];

$has = function (array $problems, $needle) {
    foreach ($problems as $p) if (stripos($p, $needle) !== false) return true;
    return false;
};

// ---------- fora de produção nada é exigido (dev roda sem Clerk e com a chave fixa)
check('dev: sem APP_ENV não exige nada', ProductionCheck::problems([]) === [], ProductionCheck::problems([]));
check('dev: APP_ENV=development não exige nada', ProductionCheck::problems(['APP_ENV' => 'development']) === [], null);

// ---------- produção completa
$p = ProductionCheck::problems($good);
check('produção completa: nenhum problema', $p === [], $p);

// ---------- chave de assinatura
$cases = [
    ['sem chave', '', 'curta'],
    ['chave curta', 'abc123', 'curta'],
    ['chave do docker-compose.yml de dev', ProductionCheck::DEV_SIGNING_KEY, 'desenvolvimento'],
    ['chave com change-me', 'change-me-' . str_repeat('ab12cd34', 4), 'desenvolvimento'],
    ['chave repetitiva', str_repeat('a', 40), 'variação'],
];
foreach ($cases as [$name, $key, $expect]) {
    $p = ProductionCheck::problems(['APP_SIGNING_KEY' => $key] + $good);
    check("chave: $name", count($p) === 1 && $has($p, $expect), $p);
}

// ---------- Clerk
$p = ProductionCheck::problems(['CLERK_SECRET_KEY' => ''] + $good);
check('Clerk sem secret key', count($p) === 1 && $has($p, 'Clerk'), $p);
$p = ProductionCheck::problems(['CLERK_PUBLISHABLE_KEY' => '', 'CLERK_SECRET_KEY' => ''] + $good);
check('Clerk desligado', count($p) === 1 && $has($p, 'Clerk'), $p);

// ---------- APP_URL
$p = ProductionCheck::problems(['APP_URL' => 'http://portal.exemplo.com.br'] + $good);
check('APP_URL em http', count($p) === 1 && $has($p, 'https'), $p);

// ---------- vários problemas juntos são todos listados
$p = ProductionCheck::problems(['APP_ENV' => 'production']);
check('produção vazia: 3 problemas', count($p) === 3, $p);

echo "$total casos" . ($fail ? ", $fail falha(s)" : '') . PHP_EOL;
exit($fail ? 1 : 0);
