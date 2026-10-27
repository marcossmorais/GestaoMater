<?php
// Turmas/disciplinas do professor para os cards de escolha (chamada e diário).
// Entrada: $disciplineClasses. Saída: $classesJson (uma linha por disciplina) e $cards (Anos Iniciais agrupados por turma).

$classesJson = array_map(function ($c) {
    return [
        'id' => (int) $c->discipline_class_id,
        'classId' => (int) $c->class_id,
        'stage' => $c->stage,
        'own' => (bool) $c->own,
        'label' => $c->series_acronym . 'ª ' . $c->ballot,
        'course' => $c->course,
        'shift' => $c->shift,
        'discipline' => $c->discipline_name,
        'students' => (int) $c->student_total,
        'doneTodayAt' => $c->done_today_at,
    ];
}, $disciplineClasses);

// Anos Iniciais: um card por turma (o polivalente escolhe as disciplinas depois); demais: um card por disciplina
$cards = [];
foreach ($classesJson as $c) {
    if ($c['stage'] !== 'iniciais') {
        $cards[] = $c;
        continue;
    }
    $key = 'turma-' . $c['classId'];
    if (!isset($cards[$key])) {
        $cards[$key] = $c + ['count' => 0];
    }
    $cards[$key]['count']++;
    $cards[$key]['discipline'] = 'Polivalente · ' . $cards[$key]['count'] . ' disciplina' . ($cards[$key]['count'] > 1 ? 's' : '');
    if ($c['own'] && !$cards[$key]['own']) $cards[$key]['id'] = $c['id'];
    $cards[$key]['own'] = $cards[$key]['own'] || $c['own'];
    if ($c['doneTodayAt'] && $c['doneTodayAt'] > (string) $cards[$key]['doneTodayAt']) $cards[$key]['doneTodayAt'] = $c['doneTodayAt'];
}
