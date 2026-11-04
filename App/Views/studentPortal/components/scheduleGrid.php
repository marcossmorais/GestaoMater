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
