<?php
// Testes de tabela do simulador. Uso: php tests/GradeSimulatorTest.php (sai com código 1 se falhar)

require __DIR__ . '/../App/Tools/GradeSimulator.php';

use App\Tools\GradeSimulator;

$cases = [
    // [descrição, unidades, média, total, máx, status esperado, needed esperado]
    ['nada lançado', [1 => null, 2 => null, 3 => null], 6, 3, 10, 'possivel', 6.0],
    ['1ª unidade 4, faltam 2', [1 => 4, 2 => null, 3 => null], 6, 3, 10, 'possivel', 7.0],
    ['1ª 4,5 e 2ª 5,5, falta 1', [1 => 4.5, 2 => 5.5, 3 => null], 6, 3, 10, 'possivel', 8.0],
    ['já aprovado com 2 unidades', [1 => 10, 2 => 9, 3 => null], 6, 3, 10, 'aprovado', null],
    ['impossível: precisa de 11', [1 => 2, 2 => 5, 3 => null], 6, 3, 10, 'impossivel', 11.0],
    ['todas lançadas, reprovado', [1 => 5, 2 => 5, 3 => 5], 6, 3, 10, 'impossivel', null],
    ['todas lançadas, aprovado no limite', [1 => 6, 2 => 6, 3 => 6], 6, 3, 10, 'aprovado', null],
    ['arredonda para cima em décimos', [1 => 5.05, 2 => null, 3 => null], 6, 3, 10, 'possivel', 6.5],
    ['nota acima do máximo é limitada', [1 => 15, 2 => null, 3 => null], 6, 3, 10, 'possivel', 4.0],
    ['regra 7,0 em 4 unidades', [1 => 6, 2 => 7, 3 => null, 4 => null], 7, 4, 10, 'possivel', 7.5],
];

$fail = 0;

foreach ($cases as [$name, $units, $passing, $total, $max, $status, $needed]) {
    $r = GradeSimulator::simulate($units, $passing, $total, $max);
    $ok = $r['status'] === $status && ($needed === null ? $r['needed'] === null : abs($r['needed'] - $needed) < 0.001);
    echo ($ok ? 'ok   ' : 'FAIL ') . $name . ($ok ? '' : ' → ' . json_encode($r)) . PHP_EOL;
    if (!$ok) $fail++;
}

echo count($cases) - $fail . '/' . count($cases) . ' casos' . PHP_EOL;
exit($fail ? 1 : 0);
