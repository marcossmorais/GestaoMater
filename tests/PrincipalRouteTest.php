<?php
// Rotas só do Administrador (hierarquia 1). Uso: php tests/PrincipalRouteTest.php (sai com código 1 se falhar)

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

// carrega a tabela de rotas sem despachar a requisição
$router = new class extends Route {
    public function __construct() { $this->initRoutes(); }
};

$roles = [];
foreach ($router->getRoutes() as $route) {
    $roles[$route['route']] = $route['role'] ?? null;
}

// ---------- importação, virada de ano e escrita do período letivo: só o Administrador
foreach ([
    '/admin/importacao', '/admin/importacao/modelo', '/admin/importacao/previa', '/admin/importacao/confirmar',
    '/admin/importacao/codigos', '/admin/importacao/virada/confirmar',
    '/admin/gestao/periodo-letivo/inserir', '/admin/gestao/periodo-letivo/atualizar',
] as $url) {
    check("$url é só do Administrador", ($roles[$url] ?? null) === 'principal');
}

// ---------- o Coordenador continua vendo o período letivo e usando o resto da gestão
foreach (['/admin/gestao/periodo-letivo', '/admin/gestao/periodo-letivo/lista', '/admin/gestao/turma', '/admin/aluno/lista'] as $url) {
    check("$url continua aberta ao Coordenador", array_key_exists($url, $roles) && $roles[$url] === null);
}

// rota sem método no model (dava erro fatal): removida
check('excluir período letivo não existe mais', !array_key_exists('/admin/gestao/periodo-letivo/deletar', $roles));

// ---------- quem é Administrador
check('hierarquia 1 é Administrador', Route::isPrincipal(['Admin' => ['hierarchyFunction' => 1]]));
check('hierarquia "1" (texto do banco) é Administrador', Route::isPrincipal(['Admin' => ['hierarchyFunction' => '1']]));
check('Coordenador (2) não é', !Route::isPrincipal(['Admin' => ['hierarchyFunction' => 2]]));
check('sem hierarquia não é', !Route::isPrincipal(['Admin' => []]));
check('docente não é', !Route::isPrincipal(['Teacher' => ['hierarchyFunction' => 1]]));
check('sem sessão não é', !Route::isPrincipal([]));

echo PHP_EOL . ($total - $fail) . "/$total ok" . PHP_EOL;
exit($fail ? 1 : 0);
