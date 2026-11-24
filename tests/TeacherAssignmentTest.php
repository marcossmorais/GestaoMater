<?php
// Atuação do professor no cadastro: polivalente vira titular com as disciplinas de base; especialista ganha as turmas
// marcadas. Uso: php tests/TeacherAssignmentTest.php (sai com código 1 se falhar)

require __DIR__ . '/../vendor/autoload.php';

use App\Models\Teacher\TeacherAssignment as A;

$fail = 0;
$total = 0;

function check($name, $ok)
{
    global $fail, $total;
    $total++;
    echo ($ok ? 'ok   ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) $fail++;
}

$disciplines = [1 => 'Língua Portuguesa', 2 => 'Matemática', 3 => 'História', 4 => 'Geografia', 13 => 'Ciências', 8 => 'Língua Inglesa', 5 => 'Física'];
$classes = [
    9 => ['label' => '3º C · EF1 · Vespertino', 'stage' => 'iniciais', 'homeroom' => null],
    11 => ['label' => '4º A · EF1 · Matutino', 'stage' => 'iniciais', 'homeroom' => 'Maria'],
    10 => ['label' => '6º A · EF2 · Vespertino', 'stage' => 'finais', 'homeroom' => null],
    1 => ['label' => '1ª A · EM · Matutino', 'stage' => 'medio', 'homeroom' => null],
];
$in = function ($role, $subjects = [], $homeroom = 0, $links = []) {
    return ['role' => $role, 'subjects' => $subjects, 'homeroomClass' => $homeroom, 'links' => $links];
};

// ---------- definir depois
$p = A::plan($in(''), $classes, $disciplines, []);
check('sem atuação não grava nada', $p['error'] === null && $p['role'] === null && !$p['links']);
check('atuação desconhecida é recusada', A::plan($in('diretor'), $classes, $disciplines, [])['error'] !== null);

// ---------- polivalente
$base = [1, 2, 3, 4, 13];
$p = A::plan($in('polivalente', $base), $classes, $disciplines, []);
check('polivalente sem turma: só as matérias', $p['error'] === null && $p['subjects'] === [1, 2, 3, 4, 13] && !$p['links'] && $p['homeroom'] === null);

$p = A::plan($in('polivalente', $base, 9), $classes, $disciplines, []);
check('polivalente com turma vira titular', $p['homeroom'] === 9);
check('polivalente ganha as 5 de base na turma', $p['links'] === [[9, 1], [9, 2], [9, 3], [9, 4], [9, 13]]);
check('polivalente sem Inglês marcado não fica com Inglês', !in_array([9, 8], $p['links'], true));

$p = A::plan($in('polivalente', [1, 2, 8], 9), $classes, $disciplines, []);
check('polivalente com matéria a mais leva ela para a turma', $p['links'] === [[9, 1], [9, 2], [9, 8]]);

check('turma do Médio não aceita titular', A::plan($in('polivalente', $base, 1), $classes, $disciplines, [])['error'] !== null);
check('turma fora do período não aceita titular', A::plan($in('polivalente', $base, 99), $classes, $disciplines, [])['error'] !== null);
check('turma que já tem titular é recusada', strpos((string) A::plan($in('polivalente', $base, 11), $classes, $disciplines, [])['error'], 'Maria') !== false);

$p = A::plan($in('polivalente', $base, 9), $classes, $disciplines, ['9-2' => 'João']);
check('matéria de base já de outro professor trava tudo', strpos((string) $p['error'], 'Matemática na 3º C · EF1 · Vespertino já é de João') !== false && !$p['links']);

check('polivalente sem matéria é recusado', A::plan($in('polivalente', [], 9), $classes, $disciplines, [])['error'] !== null);
check('as de base vêm marcadas na tela', A::isBaseSubject(' Língua Portuguesa ') && A::isBaseSubject('CIÊNCIAS') && !A::isBaseSubject('Língua Inglesa'));

// ---------- especialista
check('especialista sem matéria é recusado', A::plan($in('especialista'), $classes, $disciplines, [])['error'] !== null);

$p = A::plan($in('especialista', [2, 5]), $classes, $disciplines, []);
check('especialista sem turmas: só as matérias', $p['error'] === null && $p['subjects'] === [2, 5] && !$p['links']);

$p = A::plan($in('especialista', [2, 5], 0, [[10, 2], [1, 2], [1, 5], [1, 5]]), $classes, $disciplines, []);
check('especialista ganha cada matéria nas turmas marcadas (sem repetir)', $p['links'] === [[10, 2], [1, 2], [1, 5]] && $p['homeroom'] === null);

$p = A::plan($in('especialista', [8], 0, [[9, 8], [10, 2]]), $classes, $disciplines, []);
check('turma de matéria desmarcada fica de fora', $p['links'] === [[9, 8]]);
check('especialista pode dar aula no Fund. 1 sem virar titular', $p['homeroom'] === null && $p['error'] === null);

check('matéria inexistente é ignorada', A::plan($in('especialista', [2, 77]), $classes, $disciplines, [])['subjects'] === [2]);
check('turma fora do período é recusada', A::plan($in('especialista', [2], 0, [[99, 2]]), $classes, $disciplines, [])['error'] !== null);
check('turma que já tem a matéria é recusada', strpos((string) A::plan($in('especialista', [2], 0, [[1, 2]]), $classes, $disciplines, ['1-2' => 'Ana'])['error'], 'já é de Ana') !== false);

// ---------- leitura do formulário
$i = A::input(['atuacao' => 'especialista', 'materias' => ['2', '5'], 'turmas' => ['2' => ['10', '1'], '5' => ['1']]]);
check('formulário vira matérias e pares turma/matéria', $i['subjects'] === [2, 5] && $i['links'] === [[10, 2], [1, 2], [1, 5]]);
$i = A::input(['atuacao' => 'polivalente', 'materiasTitular' => ['1', '8'], 'materias' => ['5'], 'turmaTitular' => '9']);
check('polivalente usa a própria lista (não a do especialista)', $i['subjects'] === [1, 8] && $i['homeroomClass'] === 9);
check('formulário vazio = definir depois', A::input([])['role'] === '' && A::input(['atuacao' => ['x']])['role'] === '');

echo PHP_EOL . ($total - $fail) . "/$total ok" . PHP_EOL;
exit($fail ? 1 : 0);
