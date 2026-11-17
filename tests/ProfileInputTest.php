<?php
// Testes da validação do perfil editado pelo próprio usuário. Uso: php tests/ProfileInputTest.php (sai com código 1 se falhar)

require __DIR__ . '/../App/Tools/ProfileInput.php';

use App\Tools\ProfileInput;

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
    'address' => 'Rua do Rosário 395',
    'district' => 'Perpétuo Socorro',
    'county' => 'Paulo Afonso',
    'uf' => 'ba',
    'zipCode' => '48600-613',
    'telephoneNumber' => '(75) 98422-7932',
    'email' => 'Prof@Exemplo.com',
    'accessCode' => '320436',
    'bloodType' => '6',
    'pcd' => '1',
];

$with = function (array $changes) use ($good) { return ProfileInput::validate(array_merge($good, $changes)); };
$rejected = function ($r) { return isset($r['error']) && !isset($r['data']); };

// ---------- dado válido sai limpo
$r = ProfileInput::validate($good);
check('válido: aceita', isset($r['data']), $r);
check('válido: UF em maiúscula', ($r['data']['uf'] ?? null) === 'BA', $r);
check('válido: CEP só números', ($r['data']['zipCode'] ?? null) === '48600613', $r);
check('válido: telefone só números', ($r['data']['telephoneNumber'] ?? null) === '75984227932', $r);
check('válido: e-mail em minúscula', ($r['data']['email'] ?? null) === 'prof@exemplo.com', $r);
check('válido: ids viram inteiros', ($r['data']['bloodType'] ?? null) === 6 && ($r['data']['pcd'] ?? null) === 1, $r);

// ---------- XSS e marcação são recusados
check('XSS no e-mail', $rejected($with(['email' => 'x"><img src=x onerror=alert(1)>'])), null);
check('e-mail com aspas', $rejected($with(['email' => "a'b@exemplo.com"])), null);
check('tag no endereço', $rejected($with(['address' => '<script>alert(1)</script>'])), null);
check('tag no bairro', $rejected($with(['district' => 'Centro <b>'])), null);
check('caractere de controle no município', $rejected($with(['county' => "Paulo\x00Afonso"])), null);

// ---------- formatos e tamanhos
check('e-mail inválido', $rejected($with(['email' => 'sem-arroba'])), null);
check('e-mail longo demais', $rejected($with(['email' => str_repeat('a', 95) . '@x.com'])), null);
$isNull = function ($r, $key) { return isset($r['data']) && array_key_exists($key, $r['data']) && $r['data'][$key] === null; };
check('e-mail vazio vira null', $isNull($with(['email' => '']), 'email'), null);
check('endereço acima de 45', $rejected($with(['address' => str_repeat('a', 46)])), null);
check('endereço com 45 aceita', isset($with(['address' => str_repeat('á', 45)])['data']), null);
check('endereço vazio', $rejected($with(['address' => '   '])), null);
check('UF inexistente', $rejected($with(['uf' => 'XX'])), null);
check('CEP curto', $rejected($with(['zipCode' => '4860'])), null);
check('telefone curto', $rejected($with(['telephoneNumber' => '1234'])), null);
check('telefone vazio vira null', array_key_exists('telephoneNumber', $with(['telephoneNumber' => ''])['data'] ?? []) && $with(['telephoneNumber' => ''])['data']['telephoneNumber'] === null, null);
check('código com letras', $rejected($with(['accessCode' => '12ab56'])), null);
check('código curto', $rejected($with(['accessCode' => '123'])), null);
check('código acima de bigint', $rejected($with(['accessCode' => str_repeat('9', 19)])), null);
check('tipo sanguíneo não numérico', $rejected($with(['bloodType' => '6 OR 1=1'])), null);
check('campo como array vira vazio', $isNull($with(['email' => ['a@b.com']]), 'email'), null);
check('campo faltando', $rejected(ProfileInput::validate([])), null);

echo PHP_EOL . ($total - $fail) . "/$total ok" . PHP_EOL;
exit($fail ? 1 : 0);
