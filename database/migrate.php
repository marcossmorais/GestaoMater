<?php
// Mater — executor de migrações (database/NN_nome.sql) para banco já existente, como o de produção.
// O docker-entrypoint-initdb.d do Postgres só roda na criação do volume; este script aplica o que faltar.
//
// Uso (no container do app):
//   php database/migrate.php                aplica as pendentes, em ordem, cada uma numa transação
//   php database/migrate.php --status       mostra aplicadas e pendentes, sem alterar nada
//   php database/migrate.php --baseline=22  banco criado pelo docker (initdb): registra 01..22 como já aplicadas, sem rodar
//
// Sai com código 1 em qualquer erro. database/dev/ (dados fictícios) nunca é executado aqui.

require_once dirname(__DIR__) . '/vendor/autoload.php';

// como no public/index.php: .env só quando roda fora do Docker
if (getenv('DB_HOST') === false) {
    Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__))->safeLoad();
}

const LOCK_ID = 7461001; // pg_advisory_lock: dois deploys ao mesmo tempo não aplicam a mesma migração

function out($msg) { fwrite(STDOUT, $msg . PHP_EOL); }
function fail($msg) { fwrite(STDERR, 'ERRO: ' . $msg . PHP_EOL); exit(1); }

$opts = getopt('', ['status', 'baseline:']);
$statusOnly = isset($opts['status']);
$baseline = isset($opts['baseline']) ? $opts['baseline'] : null;

if ($baseline !== null && !preg_match('/^\d{1,3}$/', $baseline)) fail('--baseline espera o número da última migração já aplicada, ex.: --baseline=22');

// ---------- arquivos: só NN_nome.sql na raiz de database/, em ordem numérica
$files = [];
foreach (glob(__DIR__ . '/*.sql') as $path) {
    $name = basename($path);
    if (!preg_match('/^(\d+)_[a-z0-9_]+\.sql$/', $name, $m)) fail("nome fora do padrão NN_nome.sql: $name");
    $number = (int) $m[1];
    if (isset($files[$number])) fail("número repetido: {$files[$number]['name']} e $name");
    $files[$number] = ['name' => $name, 'path' => $path, 'checksum' => hash_file('sha256', $path)];
}
ksort($files);

// ---------- conexão própria (sem o ator da auditoria da app)
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

$db->exec("SET TIME ZONE 'America/Sao_Paulo'");

$hasTable = function ($table) use ($db) {
    $stmt = $db->prepare('SELECT to_regclass(:t) IS NOT NULL');
    $stmt->execute([':t' => 'public.' . $table]);
    return (bool) $stmt->fetchColumn();
};

$applied = [];
if ($hasTable('schema_migrations')) {
    foreach ($db->query('SELECT arquivo, checksum FROM schema_migrations') as $row) $applied[$row['arquivo']] = $row['checksum'];
}

$pending = array_filter($files, function ($f) use ($applied) { return !isset($applied[$f['name']]); });

// ---------- avisos: migração aplicada não se edita (AGENTS.md) e não some do repositório
$onDisk = array_column($files, 'checksum', 'name');
foreach ($applied as $name => $checksum) {
    if (!isset($onDisk[$name])) out("AVISO: $name está registrada no banco mas não existe mais em database/");
    elseif ($onDisk[$name] !== $checksum) out("AVISO: $name foi alterada depois de aplicada (o banco não recebe a alteração; crie uma migração nova)");
}

if ($statusOnly) {
    if (!$hasTable('schema_migrations')) out('schema_migrations ainda não existe: nenhuma migração registrada.');
    foreach ($files as $f) out((isset($applied[$f['name']]) ? '[x] ' : '[ ] ') . $f['name']);
    out(count($pending) . ' pendente(s).');
    exit(0);
}

$db->query('SELECT pg_advisory_lock(' . LOCK_ID . ')');

$db->exec(
    "CREATE TABLE IF NOT EXISTS schema_migrations (
        arquivo varchar(255) PRIMARY KEY,
        checksum char(64) NOT NULL,
        aplicada_em timestamptz NOT NULL DEFAULT now()
    )"
);

// relido depois do lock: outro deploy pode ter aplicado algo enquanto este esperava
$applied = $db->query('SELECT arquivo, checksum FROM schema_migrations')->fetchAll(PDO::FETCH_KEY_PAIR);
$pending = array_filter($files, function ($f) use ($applied) { return !isset($applied[$f['name']]); });

$register = $db->prepare('INSERT INTO schema_migrations (arquivo, checksum) VALUES (:arquivo, :checksum)');

// ---------- baseline: banco criado pelo initdb do docker já tem as tabelas, mas nada registrado
if ($baseline !== null) {
    if ($applied) fail('--baseline só vale para banco sem nenhuma migração registrada.');
    if (!isset($files[(int) $baseline])) fail("não existe migração número $baseline em database/");

    $db->beginTransaction();
    foreach ($files as $number => $f) {
        if ($number > (int) $baseline) break;
        $register->execute([':arquivo' => $f['name'], ':checksum' => $f['checksum']]);
        out("registrada (baseline): {$f['name']}");
    }
    $db->commit();
    out('Baseline concluído. Rode de novo sem --baseline para aplicar o que ficou pendente.');
    exit(0);
}

// sem registro, mas com tabelas da app: rodar tudo de novo quebraria o banco
if (!$applied && $hasTable('aluno')) {
    fail("o banco já tem tabelas e nenhuma migração registrada (criado pelo docker?).\n"
        . "Confira até qual número ele está e rode: php database/migrate.php --baseline=NN");
}

if (!$pending) {
    out('Nada pendente: o banco está em dia.');
    exit(0);
}

foreach ($pending as $f) {
    $sql = file_get_contents($f['path']);
    $started = microtime(true);

    try {
        $db->beginTransaction();
        $db->exec($sql);
        $register->execute([':arquivo' => $f['name'], ':checksum' => $f['checksum']]);
        $db->commit();
    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        fail("{$f['name']} falhou e foi desfeita; as seguintes não foram aplicadas.\n" . $e->getMessage());
    }

    out(sprintf('aplicada: %s (%d ms)', $f['name'], (microtime(true) - $started) * 1000));
}

out(count($pending) . ' migração(ões) aplicada(s).');
