<?php
// Testes das correções críticas e altas da auditoria de segurança. Uso: php tests/SecurityAuditFixesTest.php

require __DIR__ . '/../vendor/autoload.php';

use App\Auth\Clerk;
use App\Auth\PortalAccount;
use Firebase\JWT\JWT;

$fail = 0;
$total = 0;

function check($name, $ok, $got = null)
{
    global $fail, $total;
    $total++;
    echo ($ok ? 'ok   ' : 'FAIL ') . $name . ($ok || $got === null ? '' : ' → ' . json_encode($got, JSON_UNESCAPED_UNICODE)) . PHP_EOL;
    if (!$ok) $fail++;
}

// ---------- PortalAccount::normalizeEmail
check('e-mail normalizado em minúsculas e sem espaços', PortalAccount::normalizeEmail('  Prof.Silva@Escola.COM.br ') === 'prof.silva@escola.com.br');
check('e-mail vazio vira null', PortalAccount::normalizeEmail('   ') === null);
check('e-mail null vira null', PortalAccount::normalizeEmail(null) === null);
check('e-mail como array vira null', PortalAccount::normalizeEmail(['x@y.com']) === null);

// ---------- Clerk::verifySessionToken exige iss do domínio Clerk e azp autorizado (não aceita token sem azp)
$keyResource = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
$details = openssl_pkey_get_details($keyResource);
$privatePem = '';
openssl_pkey_export($keyResource, $privatePem);

$b64url = function ($bin) { return rtrim(strtr(base64_encode($bin), '+/', '-_'), '='); };
$jwks = [
    'keys' => [[
        'kty' => 'RSA',
        'kid' => 'test-key-1',
        'use' => 'sig',
        'alg' => 'RS256',
        'n' => $b64url($details['rsa']['n']),
        'e' => $b64url($details['rsa']['e']),
    ]],
];

$clerkDomain = 'clerk.mater.exemplo.com.br';
$pubKey = 'pk_live_' . rtrim(base64_encode($clerkDomain . '$'), '=');
$secKey = 'sk_live_test_audit_only';
$cacheFile = sys_get_temp_dir() . '/mater_clerk_jwks_' . md5($secKey) . '.json';
file_put_contents($cacheFile, json_encode($jwks));

putenv("CLERK_PUBLISHABLE_KEY=$pubKey");
putenv("CLERK_SECRET_KEY=$secKey");
putenv('CLERK_AUTHORIZED_PARTIES=https://portal.mater.com.br');

$mint = function (array $claims) use ($privatePem) {
    $now = time();
    $base = [
        'iss' => 'https://clerk.mater.exemplo.com.br',
        'sub' => 'user_2abc123',
        'iat' => $now - 5,
        'nbf' => $now - 5,
        'exp' => $now + 60,
        'azp' => 'https://portal.mater.com.br',
    ];
    return JWT::encode(array_replace($base, $claims), $privatePem, 'RS256', 'test-key-1');
};

$throws = function (callable $fn) {
    try {
        $fn();
        return false;
    } catch (\Throwable $e) {
        return true;
    }
};

$validClaims = Clerk::verifySessionToken($mint([]));
check('Clerk aceita token válido com iss e azp corretos', ($validClaims->sub ?? '') === 'user_2abc123');
check('Clerk recusa token sem claim azp', $throws(function () use ($mint) {
    $claims = ['azp' => null];
    $token = $mint($claims);
    Clerk::verifySessionToken($token);
}));
check('Clerk recusa token com azp de outra origem', $throws(function () use ($mint) {
    Clerk::verifySessionToken($mint(['azp' => 'https://outro-site.com']));
}));
check('Clerk recusa token com iss diferente do Frontend API', $throws(function () use ($mint) {
    Clerk::verifySessionToken($mint(['iss' => 'https://outro.clerk.accounts.dev']));
}));

@unlink($cacheFile);
putenv('CLERK_PUBLISHABLE_KEY');
putenv('CLERK_SECRET_KEY');
putenv('CLERK_AUTHORIZED_PARTIES');

// ---------- modalStudentProfile.php não interpola campos de texto cru sem esc(...) nas listas AJAX
$modal = (string) file_get_contents(__DIR__ . '/../App/Views/admin/student/components/modalStudentProfile.php');
check('modal escapa motivo e médico do atestado', strpos($modal, '${esc(c.motivo') !== false && strpos($modal, '${esc(c.medico_nome') !== false);
check('modal escapa título e descrição da ocorrência', strpos($modal, '${esc(o.titulo)}') !== false && strpos($modal, '${esc(o.descricao)}') !== false);
check('modal escapa nome e observação do responsável', strpos($modal, '${esc(g.nome)}') !== false && strpos($modal, '${esc(g.observacao_restricao)}') !== false);

echo PHP_EOL . ($total - $fail) . "/$total ok" . PHP_EOL;
exit($fail ? 1 : 0);
