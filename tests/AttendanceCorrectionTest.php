<?php
// Chamada por disciplina: salvar de novo corrige (não soma) e a data é validada. Uso: php tests/AttendanceCorrectionTest.php (sai com código 1 se falhar)

require __DIR__ . '/../vendor/autoload.php';

use App\Models\TeacherStudent\Attendance;
use App\Controllers\TeacherPortal\TeacherPortalController;

$fail = 0;
$total = 0;

function check($name, $ok)
{
    global $fail, $total;
    $total++;
    echo ($ok ? 'ok   ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) $fail++;
}

$prev = function ($unity, $lessons, array $absent) {
    return (object) ['unity' => $unity, 'lessons' => $lessons, 'absent' => $absent];
};

// ---------- primeira chamada do dia: cada ausente recebe as aulas
check('primeira chamada lança as faltas', Attendance::absenceDelta(null, 1, 2, [10, 11]) === [1 => [10 => 2, 11 => 2]]);
check('primeira chamada sem ausentes não grava nada', Attendance::absenceDelta(null, 1, 1, []) === []);

// ---------- reenvio igual (toque duplo): nada muda
check('reenvio igual não soma de novo', Attendance::absenceDelta($prev(1, 2, [10, 11]), 1, 2, [10, 11]) === []);

// ---------- correção: só quem muda
check('aluno que deixou de faltar perde as faltas', Attendance::absenceDelta($prev(1, 1, [10, 11]), 1, 1, [10]) === [1 => [11 => -1]]);
check('aluno que passou a faltar ganha as faltas', Attendance::absenceDelta($prev(1, 1, [10]), 1, 1, [10, 12]) === [1 => [12 => 1]]);
check('mais uma aula no dia soma só a diferença', Attendance::absenceDelta($prev(1, 1, [10]), 1, 2, [10]) === [1 => [10 => 1]]);
check('todos presentes tira as faltas da chamada', Attendance::absenceDelta($prev(1, 3, [10, 11]), 1, 3, []) === [1 => [10 => -3, 11 => -3]]);

// ---------- bimestre errado: as faltas mudam de bimestre
check('troca de bimestre move as faltas', Attendance::absenceDelta($prev(1, 2, [10]), 2, 2, [10]) === [1 => [10 => -2], 2 => [10 => 2]]);

// ---------- data da aula
$term = (object) ['start' => '2026-02-02', 'finish' => '2026-12-18'];
$today = '2026-11-21';
check('hoje vale', TeacherPortalController::lessonDateError('2026-11-21', $today, $term) === null);
check('dia passado dentro do período vale', TeacherPortalController::lessonDateError('2026-03-10', $today, $term) === null);
check('amanhã é recusado', TeacherPortalController::lessonDateError('2026-11-22', $today, $term) === 'data-invalida');
check('data inexistente é recusada', TeacherPortalController::lessonDateError('2026-02-30', $today, $term) === 'data-invalida');
check('formato errado é recusado', TeacherPortalController::lessonDateError('21/11/2026', $today, $term) === 'data-invalida');
check('antes do período letivo é recusado', TeacherPortalController::lessonDateError('2025-12-15', $today, $term) === 'data-fora-do-periodo');
check('primeiro dia do período vale', TeacherPortalController::lessonDateError('2026-02-02', $today, $term) === null);
check('turma sem período não trava', TeacherPortalController::lessonDateError('2025-12-15', $today, null) === null);

echo PHP_EOL . ($total - $fail) . "/$total ok" . PHP_EOL;
exit($fail ? 1 : 0);
