<?php
// Testes de tabela do índice pedagógico. Uso: php tests/PedagogicalIndexTest.php (sai com código 1 se falhar)

require __DIR__ . '/../App/Tools/PedagogicalIndex.php';

use App\Tools\PedagogicalIndex;

$fail = 0;
$total = 0;

function check($name, $ok, $got)
{
    global $fail, $total;
    $total++;
    echo ($ok ? 'ok   ' : 'FAIL ') . $name . ($ok ? '' : ' → ' . json_encode($got)) . PHP_EOL;
    if (!$ok) $fail++;
}

$cases = [
    // [descrição, notas, frequência, positivas, negativas, ocorrências, índice, faixa]
    ['nenhum dado', [], [], 0, 0, [], null, 'none'],
    ['só notas: vale 100% das notas', [8.0, 6.0], [], 0, 0, [], 70, 'ok'],
    ['sem registro de comportamento: peso vai para notas e frequência', [8.0], [100.0], 0, 0, [], 89, 'high'],
    ['todas as partes', [7.0], [90.0], 2, 0, [], 79, 'ok'],
    ['marcações negativas puxam para baixo', [5.0], [70.0], 0, 4, [], 56, 'mid'],
    ['elogio soma 10', [7.0], [90.0], 0, 0, [['category' => 'Elogio', 'severity' => 'Baixa']], 79, 'ok'],
    ['ocorrência gravíssima tira 20', [7.0], [90.0], 0, 0, [['category' => 'Disciplinar', 'severity' => 'Gravíssima']], 70, 'ok'],
    ['saúde não conta como comportamento ruim', [7.0], [90.0], 0, 0, [['category' => 'Saúde', 'severity' => 'Alta']], 76, 'ok'],
    ['comportamento nunca passa de 100', [10.0], [100.0], 40, 0, [], 100, 'high'],
    ['comportamento nunca fica abaixo de 0', [3.0], [50.0], 0, 40, [], 27, 'low'],
];

foreach ($cases as [$name, $grades, $attendance, $positive, $negative, $occurrences, $score, $band]) {
    $got = PedagogicalIndex::compute($grades, $attendance, $positive, $negative, $occurrences);
    check($name, $got['score'] === $score && $got['band'] === $band, $got);
}

$tones = PedagogicalIndex::countTones([
    'Participou bem da aula · Ajudou os colegas',
    'Não fez a tarefa · Sem material. Veio sem o livro',
    'Dificuldade no conteúdo',
    'Texto livre sem marcação',
]);
check('conta as marcações e ignora texto livre', $tones === ['positive' => 2, 'negative' => 2], $tones);

$got = PedagogicalIndex::compute([7.0], [], 0, 0);
check('parte sem dados vem nula', $got['parts']['attendance'] === null && $got['parts']['behavior'] === null && $got['parts']['grades'] === 70, $got);

echo "$total casos" . ($fail ? ", $fail falhas" : '') . PHP_EOL;
exit($fail ? 1 : 0);
