<?php
// Testes do leitor de importação (CSV/JSON e validações). Uso: php tests/ImportParserTest.php (sai com código 1 se falhar)

require __DIR__ . '/../App/Tools/ImportParser.php';

use App\Tools\ImportParser;

$fail = 0;
$total = 0;

function check($name, $ok, $got)
{
    global $fail, $total;
    $total++;
    echo ($ok ? 'ok   ' : 'FAIL ') . $name . ($ok ? '' : ' → ' . json_encode($got, JSON_UNESCAPED_UNICODE)) . PHP_EOL;
    if (!$ok) $fail++;
}

// ---------- CSV
$r = ImportParser::parse("Nome;CPF;Data de Nascimento\nAna Souza;529.982.247-25;05/03/2010\n\n", 'csv');
check('csv com ponto e vírgula e cabeçalho normalizado', $r['error'] === null && $r['rows'] === [['nome' => 'Ana Souza', 'cpf' => '529.982.247-25', 'data_de_nascimento' => '05/03/2010']], $r);

$r = ImportParser::parse("nome,turma\n\"Silva, João\",1ª A · EM · Matutino\n", 'csv');
check('csv com vírgula e campo entre aspas', ($r['rows'][0]['nome'] ?? null) === 'Silva, João' && ($r['rows'][0]['turma'] ?? '') === '1ª A · EM · Matutino', $r);

$r = ImportParser::parse("\xEF\xBB\xBFnome;sexo\nJoão;M\n", 'csv');
check('csv com BOM', isset($r['rows'][0]['nome']) && $r['rows'][0]['nome'] === 'João', $r);

$r = ImportParser::parse(mb_convert_encoding("nome;naturalidade\nJoão;São Paulo\n", 'Windows-1252', 'UTF-8'), 'csv');
check('csv do Excel em Windows-1252 vira UTF-8', ($r['rows'][0]['naturalidade'] ?? '') === 'São Paulo', $r);

$r = ImportParser::parse("nome;cpf\n", 'csv');
check('csv só com cabeçalho dá erro', $r['error'] !== null, $r);

$r = ImportParser::parse("", 'csv');
check('arquivo vazio dá erro', $r['error'] !== null, $r);

// ---------- JSON
$r = ImportParser::parse('[{"Nome": "Ana", "CPF": 52998224725}]', 'json');
check('json lista de objetos', $r['rows'] === [['nome' => 'Ana', 'cpf' => '52998224725']], $r);

$r = ImportParser::parse('{"alunos": [{"nome": "Ana"}]}', 'json');
check('json com a lista dentro de um objeto', $r['rows'] === [['nome' => 'Ana']], $r);

$r = ImportParser::parse('{"nome": "Ana"', 'json');
check('json quebrado dá erro', $r['error'] !== null, $r);

$r = ImportParser::parse('[1, 2]', 'json');
check('json sem objetos dá erro', $r['error'] !== null, $r);

// ---------- Validações
check('cpf válido com máscara', ImportParser::cpf('529.982.247-25') === '52998224725', ImportParser::cpf('529.982.247-25'));
check('cpf com dígito errado', ImportParser::cpf('529.982.247-26') === null, ImportParser::cpf('529.982.247-26'));
check('cpf repetido', ImportParser::cpf('111.111.111-11') === null, null);
check('cpf vazio', ImportParser::cpf('') === null, null);
check('data dd/mm/aaaa', ImportParser::date('05/03/2010') === '2010-03-05', ImportParser::date('05/03/2010'));
check('data aaaa-mm-dd', ImportParser::date('2010-03-05') === '2010-03-05', null);
check('data inexistente', ImportParser::date('31/02/2010') === null, null);
check('data no futuro', ImportParser::date('01/01/' . ((int) date('Y') + 1)) === null, null);
check('e-mail válido em minúsculas', ImportParser::email('Ana@Escola.COM') === 'ana@escola.com', null);
check('e-mail inválido', ImportParser::email('ana@') === null, null);
check('cabeçalho com acento', ImportParser::key('Tipo sanguíneo') === 'tipo_sanguineo' && ImportParser::key('Município') === 'municipio', ImportParser::key('Tipo sanguíneo'));
check('nome de turma compactado', ImportParser::compact('1ª A · EM · Matutino') === ImportParser::compact('1a a em matutino'), ImportParser::compact('1ª A · EM · Matutino'));

echo PHP_EOL . ($total - $fail) . "/$total ok" . PHP_EOL;
exit($fail ? 1 : 0);
