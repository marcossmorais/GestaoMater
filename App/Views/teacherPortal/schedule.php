<?php
require __DIR__ . '/../../config/variables.php';

$scheduleByDay = $this->view->scheduleByDay;
$dayNames = $this->view->dayNames;
$shortDayNames = $this->view->shortDayNames;
$totalClasses = $this->view->totalClasses;

// Dia atual (2 = Segunda ... 6 = Sexta; se for fim de semana, padrão 2)
$currentDayOfWeek = (int) date('N') + 1; // ISO-8601 (1=Segunda ... 7=Domingo) -> 2=Segunda
if ($currentDayOfWeek < 2 || $currentDayOfWeek > 6) $currentDayOfWeek = 2;
?>

<section id="schedule" class="m-schedule">

    <div class="row main-container">

        <div class="col-lg-11 mx-auto">

            <div class="m-page-head">
                <div>
                    <h5>Quadro de horários</h5>
                    <p>Seu cronograma semanal de aulas, turmas e salas.</p>
                </div>
                <div class="m-actions">
                    <span class="m-pill m-pill-info"><i class="fas fa-calendar-alt mr-1"></i> <?= $totalClasses ?> aulas na semana</span>
                    <a href="/portal-docente/chamada" class="btn btn-primary"><i class="fas fa-clipboard-check mr-2"></i>Fazer chamada</a>
                </div>
            </div>

            <!-- Seletor de dia para telas menores -->
            <div class="m-schedule-tabs mb-3 d-flex d-md-none">
                <div class="m-seg w-100 justify-content-between" data-schedule-tabs>
                    <?php for ($d = 2; $d <= 6; $d++) { ?>
                        <button type="button" data-day="<?= $d ?>" class="<?= $d === $currentDayOfWeek ? 'is-active' : '' ?>">
                            <?= substr($shortDayNames[$d], 0, 3) ?>
                        </button>
                    <?php } ?>
                </div>
            </div>

            <!-- Grade semanal: 5 colunas no desktop, filtra no mobile -->
            <div class="m-schedule-grid" data-schedule-grid>

                <?php for ($d = 2; $d <= 6; $d++) {
                    $slots = $scheduleByDay[$d] ?? [];
                    $isToday = ($d === $currentDayOfWeek);
                ?>
                    <div class="card m-schedule-col <?= $isToday ? 'is-today' : '' ?>" data-day-col="<?= $d ?>">

                        <div class="m-schedule-col-head">
                            <div>
                                <h6><?= $dayNames[$d] ?></h6>
                                <small class="text-muted"><?= count($slots) ?> aula<?= count($slots) !== 1 ? 's' : '' ?></small>
                            </div>
                            <?php if ($isToday) { ?>
                                <span class="badge badge-primary">Hoje</span>
                            <?php } ?>
                        </div>

                        <div class="m-schedule-col-body">

                            <?php if (count($slots) > 0) { ?>

                                <?php foreach ($slots as $slot) { ?>
                                    <div class="m-schedule-card">
                                        <div class="m-schedule-card-time">
                                            <span class="m-time-range"><?= $slot->hora_inicio ?> – <?= $slot->hora_fim ?></span>
                                            <span class="m-ordem-tag"><?= $slot->ordem_aula ?>ª aula</span>
                                        </div>

                                        <div class="m-schedule-card-body">
                                            <strong class="m-schedule-discipline"><?= htmlspecialchars($slot->nome_disciplina) ?></strong>
                                            <div class="m-schedule-meta">
                                                <span class="m-class-tag"><i class="fas fa-users mr-1"></i><?= $slot->series_acronym ?>ª <?= $slot->ballot ?> (<?= $slot->shift ?>)</span>
                                                <span class="m-room-tag"><i class="fas fa-map-marker-alt mr-1"></i><?= $slot->sala ?></span>
                                            </div>
                                        </div>

                                        <div class="m-schedule-card-action">
                                            <a href="/portal-docente/chamada" class="btn btn-sm btn-light" title="Fazer chamada">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php } ?>

                            <?php } else { ?>

                                <div class="m-schedule-empty">
                                    <i class="fas fa-mug-hot"></i>
                                    <span>Sem aulas</span>
                                </div>

                            <?php } ?>

                        </div>

                    </div>
                <?php } ?>

            </div>

        </div>

    </div>

</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabs = document.querySelectorAll('[data-schedule-tabs] button');
        const cols = document.querySelectorAll('[data-schedule-grid] [data-day-col]');

        function setDay(day) {
            tabs.forEach(t => t.classList.toggle('is-active', t.dataset.day === String(day)));
            if (window.innerWidth < 768) {
                cols.forEach(col => {
                    col.style.display = (col.dataset.dayCol === String(day)) ? 'block' : 'none';
                });
            } else {
                cols.forEach(col => col.style.display = '');
            }
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', function () {
                setDay(this.dataset.day);
            });
        });

        window.addEventListener('resize', function () {
            const activeTab = document.querySelector('[data-schedule-tabs] button.is-active');
            setDay(activeTab ? activeTab.dataset.day : '2');
        });

        // Inicializa para o dia ativo
        const initial = document.querySelector('[data-schedule-tabs] button.is-active');
        if (initial) setDay(initial.dataset.day);
    });
</script>
