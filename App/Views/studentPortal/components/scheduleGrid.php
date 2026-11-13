<?php
// Quadro de horários organizado por aula e dia: usado na tela e no documento impresso
$dayNames = $this->view->dayNames;
$grid = [];
$slots = [];
$days = [2, 3, 4, 5, 6];
foreach ($this->view->classSchedule ?? [] as $s) {
    $grid[(int) $s->ordem_aula][(int) $s->dia_semana] = $s;
    $slots[(int) $s->ordem_aula] = ['start' => $s->hora_inicio, 'end' => $s->hora_fim];
    if (!in_array((int) $s->dia_semana, $days, true)) $days[] = (int) $s->dia_semana;
}
// Fund. 1: todas as faixas da turma entram; horário sem especialista é aula do titular
$homeroom = $this->view->homeroomTeacher ?? null;
if ($homeroom) {
    foreach ($this->view->scheduleSlots ?? [] as $s) $slots[(int) $s->position] = ['start' => $s->start, 'end' => $s->finish];
}
$cellFor = function ($order, $day) use ($grid, $homeroom) {
    if (isset($grid[$order][$day])) return $grid[$order][$day];
    return $homeroom ? (object) ['nome_disciplina' => 'Professor(a) titular', 'nome_professor' => $homeroom, 'homeroom' => true] : null;
};
ksort($slots);
sort($days);
$room = !empty($this->view->classSchedule) ? $this->view->classSchedule[0]->sala : '';
$today = (int) (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->format('N') + 1;

// o intervalo aparece onde a grade tem uma folga entre duas aulas
$gapBefore = function ($order) use ($slots) {
    $previous = null;
    foreach ($slots as $o => $slot) {
        if ($o === $order) return $previous !== null && $slots[$previous]['end'] !== $slot['start'] ? $slots[$previous]['end'] . ' às ' . $slot['start'] : null;
        $previous = $o;
    }
    return null;
};
