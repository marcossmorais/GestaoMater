<?php
// Testes de tabela da regra de notas. Uso: php tests/GradeSimulatorTest.php (sai com código 1 se falhar)

require __DIR__ . '/../App/Tools/GradeSimulator.php';

use App\Tools\GradeSimulator;

$fail = 0;
$total = 0;

function check($name, $ok, $got)
{
    global $fail, $total;
    $total++;
    echo ($ok ? 'ok   ' : 'FAIL ') . $name . ($ok ? '' : ' → ' . json_encode($got)) . PHP_EOL;
    if (!$ok) $fail++;
}

// ---------- Nota do bimestre: média das 3 avaliações; abaixo de 7, a recuperação substitui
$bimester = [
    // [descrição, média, lançadas, recuperação, nota esperada, fechado, recuperou, precisa de recuperação]
    ['3 avaliações, média 8', 8.0, 3, null, 8.0, true, false, false],
    ['só 2 avaliações lançadas: ainda aberto', 6.0, 2, null, 6.0, false, false, false],
    ['média 7 exata não vai para recuperação', 7.0, 3, null, 7.0, true, false, false],
    ['média 5,5 sem recuperação lançada', 5.5, 3, null, 5.5, true, false, true],
    ['média 5,5 e recuperação 8: vale 8', 5.5, 3, 8.0, 8.0, true, true, false],
    ['média 5,5 e recuperação 4: substitui (vale 4)', 5.5, 3, 4.0, 4.0, true, true, false],
    ['média 8 com recuperação lançada: ignora', 8.0, 3, 5.0, 8.0, true, false, false],
    ['nenhuma nota', null, 0, null, null, false, false, false],
];

foreach ($bimester as [$name, $avg, $graded, $rec, $grade, $closed, $recovered, $needs]) {
    $r = GradeSimulator::bimester($avg, $graded, 3, $rec, 7);
    $ok = ($grade === null ? $r['grade'] === null : abs($r['grade'] - $grade) < 0.001)
        && $r['closed'] === $closed && $r['recovered'] === $recovered && $r['needsRecovery'] === $needs;
    check('bimestre: ' . $name, $ok, $r);
}

// ---------- Média final: 4 bimestres, média 7
$final = [
    // [descrição, bimestres, status esperado, needed esperado]
    ['nada fechado', [1 => null, 2 => null, 3 => null, 4 => null], 'possivel', 7.0],
    ['1º bimestre 6', [1 => 6, 2 => null, 3 => null, 4 => null], 'possivel', 7.4],
    ['3 bimestres 8, 7 e 6', [1 => 8, 2 => 7, 3 => 6, 4 => null], 'possivel', 7.0],
    ['já aprovado com 3 bimestres', [1 => 10, 2 => 10, 3 => 9, 4 => null], 'aprovado', null],
    ['impossível: precisa de 13', [1 => 3, 2 => 4, 3 => 8, 4 => null], 'impossivel', 13.0],
    ['4 fechados, média 6,75', [1 => 7, 2 => 7, 3 => 6, 4 => 7], 'impossivel', null],
    ['4 fechados, média 7 exata', [1 => 7, 2 => 7, 3 => 7, 4 => 7], 'aprovado', null],
    ['arredonda para cima em décimos', [1 => 6.05, 2 => null, 3 => null, 4 => null], 'possivel', 7.4],
];

foreach ($final as [$name, $units, $status, $needed]) {
    $r = GradeSimulator::simulate($units, 7, 4, 10);
    $ok = $r['status'] === $status && ($needed === null ? $r['needed'] === null : abs($r['needed'] - $needed) < 0.001);
    check('média final: ' . $name, $ok, $r);
}

echo ($total - $fail) . '/' . $total . ' casos' . PHP_EOL;
exit($fail ? 1 : 0);
