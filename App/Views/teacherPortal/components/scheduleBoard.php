<?php
// Aba Horário de Planejamento e Horário. Entrada: $this->view->scheduleByDay, dayNames, shortDayNames, totalClasses.

use App\Tools\Subject;

$scheduleByDay = $this->view->scheduleByDay;
$dayNames = $this->view->dayNames;
$shortDayNames = $this->view->shortDayNames;
$totalClasses = $this->view->totalClasses;

// semana mostrada: a atual (no fim de semana, a próxima); dia_semana 2 = segunda … 6 = sexta
$tz = new DateTimeZone('America/Sao_Paulo');
$now = new DateTime('now', $tz);
$weekday = (int) $now->format('N');
$monday = (clone $now)->modify($weekday > 5 ? 'next monday' : 'monday this week');
$today = $weekday <= 5 ? $weekday + 1 : null;
$selected = $today ?? 2;
$time = $now->format('H:i');

$dates = [];
for ($d = 2; $d <= 6; $d++) $dates[$d] = (clone $monday)->modify('+' . ($d - 2) . ' day')->format('d/m');

// estado da aula de hoje: já foi, agora, próxima
$state = function ($slot, $d) use ($today, $time) {
    if ($d !== $today) return '';
    if ($time > $slot->hora_fim) return 'is-past';
    if ($time >= $slot->hora_inicio) return 'is-now';
    return '';
};
$nextMarked = false;

$slotCard = function ($slot, $d, $compact) use ($state, $today, $time, &$nextMarked) {
    $st = $state($slot, $d);
    $next = !$nextMarked && $d === $today && $time < $slot->hora_inicio;
    if ($next) $nextMarked = true;
    ob_start(); ?>
    <article class="m-slot <?= Subject::css($slot->nome_disciplina) ?> <?= $st ?>">
        <span class="m-slot-ico"><i class="fas <?= Subject::icon($slot->nome_disciplina) ?>" aria-hidden="true"></i></span>
        <div class="m-slot-body">
            <span class="m-slot-time"><?= $slot->hora_inicio ?> – <?= $slot->hora_fim ?>
                <?php if ($st === 'is-now') { ?><span class="m-pill m-pill-info">agora</span><?php } elseif ($next) { ?><span class="m-pill m-pill-warn">próxima</span><?php } ?>
            </span>
            <strong><?= htmlspecialchars($slot->nome_disciplina) ?></strong>
            <small><?= htmlspecialchars($slot->series_acronym) ?>ª <?= htmlspecialchars($slot->ballot) ?> · <?= htmlspecialchars($slot->course) ?> · sala <?= htmlspecialchars($slot->sala) ?></small>
        </div>
        <?php if (!$compact) { ?>
            <div class="m-slot-actions">
                <a class="btn btn-primary btn-sm" href="/portal-docente/chamada?turma=<?= (int) $slot->discipline_class_id ?>"><i class="fas fa-clipboard-check mr-1"></i>Chamada</a>
                <a class="btn btn-light btn-sm" href="/portal-docente/diario?turma=<?= (int) $slot->discipline_class_id ?>"><i class="fas fa-book-open mr-1"></i>Diário</a>
            </div>
        <?php } ?>
    </article>
    <?php return ob_get_clean();
};
?>

<section id="schedule" class="m-schedule-view">

            <div class="m-page-head">
                <div>
                    <h6 class="m-0">Meu horário</h6>
                    <p>Semana de <?= $dates[2] ?> a <?= $dates[6] ?> · <?= (int) $totalClasses ?> aula<?= $totalClasses == 1 ? '' : 's' ?> por semana</p>
                </div>
                <div class="m-seg" data-schedule-mode role="group" aria-label="Modo de exibição">
                    <button type="button" data-mode="week" class="is-active"><i class="fas fa-th-large mr-1"></i>Semana</button>
                    <button type="button" data-mode="day"><i class="fas fa-list-ul mr-1"></i>Dia</button>
                </div>
            </div>

            <!-- Semana: uma coluna por dia -->
            <div class="m-week" data-view="week">
                <?php for ($d = 2; $d <= 6; $d++) { $slots = $scheduleByDay[$d] ?? []; ?>
                    <div class="m-week-col <?= $d === $today ? 'is-today' : '' ?>">
                        <div class="m-week-head">
                            <strong><?= htmlspecialchars($shortDayNames[$d]) ?></strong>
                            <span><?= $dates[$d] ?></span>
                            <?php if ($d === $today) { ?><em>hoje</em><?php } ?>
                        </div>
                        <?php if (!$slots) { ?>
                            <div class="m-week-free"><i class="fas fa-mug-hot"></i>Livre</div>
                        <?php } ?>
                        <?php foreach ($slots as $slot) echo $slotCard($slot, $d, true); ?>
                    </div>
                <?php } ?>
            </div>

            <!-- Dia: abas e cartões com ações -->
            <div data-view="day" hidden>
                <div class="m-day-chips" role="tablist">
                    <?php for ($d = 2; $d <= 6; $d++) { $count = count($scheduleByDay[$d] ?? []); ?>
                        <button type="button" role="tab" class="m-day-chip <?= $d === $selected ? 'is-active' : '' ?>" data-day="<?= $d ?>" aria-selected="<?= $d === $selected ? 'true' : 'false' ?>">
                            <strong><?= htmlspecialchars($shortDayNames[$d]) ?></strong>
                            <span><?= $dates[$d] ?></span>
                            <small><?= $count ?> aula<?= $count === 1 ? '' : 's' ?></small>
                        </button>
                    <?php } ?>
                </div>

                <?php for ($d = 2; $d <= 6; $d++) { $slots = $scheduleByDay[$d] ?? []; ?>
                    <div class="m-day-list" data-day-panel="<?= $d ?>" <?= $d === $selected ? '' : 'hidden' ?>>
                        <?php if (!$slots) { ?>
                            <div class="card m-panel m-empty">
                                <img src="<?= $app_url ?>/assets/img/illustrations/calendar.svg" alt="" style="max-width: 140px;">
                                <p>Nenhuma aula na <?= htmlspecialchars(mb_strtolower($dayNames[$d])) ?>.</p>
                            </div>
                        <?php } ?>
                        <?php foreach ($slots as $slot) echo $slotCard($slot, $d, false); ?>
                    </div>
                <?php } ?>
            </div>

</section>

<script>
    (function () {
        const root = document.getElementById('schedule');
        const views = root.querySelectorAll('[data-view]');
        const modeButtons = root.querySelectorAll('[data-schedule-mode] button');

        function setMode(mode) {
            views.forEach((v) => { v.hidden = v.dataset.view !== mode; });
            modeButtons.forEach((b) => b.classList.toggle('is-active', b.dataset.mode === mode));
        }

        modeButtons.forEach((b) => b.addEventListener('click', () => setMode(b.dataset.mode)));

        root.querySelectorAll('[data-day]').forEach((chip) => chip.addEventListener('click', () => {
            root.querySelectorAll('[data-day]').forEach((c) => {
                c.classList.toggle('is-active', c === chip);
                c.setAttribute('aria-selected', c === chip ? 'true' : 'false');
            });
            root.querySelectorAll('[data-day-panel]').forEach((p) => { p.hidden = p.dataset.dayPanel !== chip.dataset.day; });
        }));

        // no celular a semana inteira não cabe: começa no dia
        if (window.matchMedia('(max-width: 767.98px)').matches) setMode('day');
    })();
</script>
