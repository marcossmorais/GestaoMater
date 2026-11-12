<?php
// Mater — primeiro administrador real de um banco novo (produção). Roda uma vez, no servidor:
//
//   php database/create-admin.php --nome="Nome Completo" --email=pessoa@dominio.com
//
// Reaproveita o registro do admin fictício do 02_seed.sql (sem e-mail desde a migração 23): troca nome e e-mail,
// sorteia um código de acesso novo (o do seed é público no README) e mantém a conta ativa como Administrador.
// No primeiro login pelo Clerk com esse e-mail a conta se vincula. Os outros dados (CPF, nascimento, telefone,
// endereço) continuam os do seed até serem corrigidos em /admin/configuracoes.
// O e-mail é passado na hora: nunca fica em arquivo do repositório.

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (getenv('DB_HOST') === false) {
    Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__))->safeLoad();
}

function fail($msg) { fwrite(STDERR, 'ERRO: ' . $msg . PHP_EOL); exit(1); }

$opts = getopt('', ['nome:', 'email:']);
$name = trim(preg_replace('/\s+/', ' ', $opts['nome'] ?? ''));
$email = strtolower(trim($opts['email'] ?? ''));

if (mb_strlen($name) < 3 || mb_strlen($name) > 100) fail('informe --nome="Nome Completo" (3 a 100 caracteres)');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('informe --email=pessoa@dominio.com válido');

try {
    $db = new PDO(
        sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
            getenv('DB_HOST') ?: 'localhost', getenv('DB_PORT') ?: '5432', getenv('DB_NAME') ?: 'mater', getenv('DB_SSLMODE') ?: 'disable'),
        getenv('DB_USER') ?: 'postgres',
        getenv('DB_PASSWORD') ?: '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    fail('sem conexão com o banco: ' . $e->getMessage());
}

$db->beginTransaction();

// só serve para o primeiro admin: depois disso, novos admins são cadastrados pela tela do admin
$existing = $db->query(
    "SELECT count(*) FROM administrador
     WHERE email_administrador IS NOT NULL AND fk_id_situacao_conta_administrador = 1"
)->fetchColumn();

if ($existing > 0) fail('já existe administrador ativo com e-mail; cadastre os próximos pela tela /admin.');

$seed = $db->query(
    "SELECT id_administrador FROM administrador
     WHERE email_administrador IS NULL AND clerk_user_id IS NULL
     ORDER BY id_administrador LIMIT 1 FOR UPDATE"
)->fetchColumn();

if (!$seed) fail('admin do seed não encontrado (banco criado sem o 02_seed.sql?).');

$stmt = $db->prepare(
    "UPDATE administrador
     SET nome_administrador = :name, email_administrador = :email, codigo_acesso = :code,
         fk_id_administrador_hierarquia_funcao = 1, fk_id_situacao_conta_administrador = 1
     WHERE id_administrador = :id"
);
$stmt->execute([
    ':name' => $name,
    ':email' => $email,
    ':code' => random_int(100000000, 999999999), // só vale com o Clerk desligado; o do seed era público
    ':id' => (int) $seed,
]);

$db->commit();

echo "Administrador pronto: $name <$email> (id $seed)." . PHP_EOL;
echo "1. Entre em " . (getenv('APP_URL') ?: 'APP_URL') . "/admin pelo Clerk com esse e-mail (verificado)." . PHP_EOL;
echo "2. Em Configurações, corrija CPF, nascimento, telefone e endereço (ainda são os fictícios do seed)." . PHP_EOL;
