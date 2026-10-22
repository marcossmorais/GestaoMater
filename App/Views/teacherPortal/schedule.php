<?php
require __DIR__ . '/../../config/variables.php';

$scheduleByDay = $this->view->scheduleByDay;
$dayNames = $this->view->dayNames;
$shortDayNames = $this->view->shortDayNames;
$totalClasses = $this->view->totalClasses;

// Dia atual (2 = Segunda ... 6 = Sexta; se for fim de semana, padrão 2)
$currentDayOfWeek = (int) date('N') + 1;
if ($currentDayOfWeek < 2 || $currentDayOfWeek > 6) $currentDayOfWeek = 2;

// Ordem dos horários padrão para a matriz semanal
$timeSlots = [
    1 => ['start' => '07:30', 'end' => '08:20', 'label' => '1ª aula (Manhã)'],
    2 => ['start' => '08:20', 'end' => '09:10', 'label' => '2ª aula (Manhã)'],
    // Intervalo das 09:10 às 09:30
    3 => ['start' => '09:30', 'end' => '10:20', 'label' => '3ª aula (Manhã)'],
    4 => ['start' => '10:20', 'end' => '11:10', 'label' => '4ª aula (Manhã)'],
    5 => ['start' => '11:10', 'end' => '12:00', 'label' => '5ª aula (Manhã)'],
    6 => ['start' => '13:00', 'end' => '13:50', 'label' => '1ª aula (Tarde)'],
    7 => ['start' => '13:50', 'end' => '14:40', 'label' => '2ª aula (Tarde)'],
    8 => ['start' => '15:00', 'end' => '15:50', 'label' => '3ª aula (Tarde)'],
];
?>

<section id="schedule" class="m-schedule-view">

    <div class="row main-container">

        <div class="col-lg-11 mx-auto">

            <!-- Cabeçalho limpo com estilo Sophia / Superapp -->
            <div class="m-page-head">
                <div>
                    <div class="m-breadcrumb">
                        <a href="/portal-docente/home"><i class="fas fa-arrow-left mr-1"></i> Voltar para o início</a>
                        <span class="m-sep">/</span>
                        <span class="text-muted">Horários</span>
                    </div>
                    <h5>Quadro de horários</h5>
                    <p>Cronograma semanal das suas aulas, turmas e salas.</p>
                </div>

                <div class="m-actions align-items-center">
                    <span class="m-pill m-pill-info"><i class="fas fa-calendar-check mr-1"></i> <?= $totalClasses ?> aulas na semana</span>
                    
                    <div class="m-view-switch m-seg" data-schedule-view-mode>
                        <button type="button" class="is-active" data-mode="timeline"><i class="fas fa-list-ul mr-1"></i> Por dia</button>
                        <button type="button" data-mode="grid"><i class="fas fa-table mr-1"></i> Grade completa</button>
                    </div>
                </div>
            </div>

            <!-- MODO 1: VISÃO POR DIA (TIMELINE ESPAÇOSA E LIMPA) -->
            <div id="scheduleTimelineView">

                <!-- Seletor de Dias em Abas Grandes -->
                <div class="m-day-tabs" data-schedule-day-tabs>
                    <?php for ($d = 2; $d <= 6; $d++) { 
                        $count = count($scheduleByDay[$d] ?? []);
                        $isActive = ($d === $currentDayOfWeek);
                    ?>
                        <button type="button" class="m-day-tab <?= $isActive ? 'is-active' : '' ?>" data-day="<?= $d ?>">
                            <span class="m-day-tab-name"><?= $dayNames[$d] ?></span>
                            <span class="m-day-tab-badge"><?= $count ?> aula<?= $count !== 1 ? 's' : '' ?></span>
                        </button>
                    <?php } ?>
                </div>

                <!-- Lista de Cards do Dia Selecionado -->
                <?php for ($d = 2; $d <= 6; $d++) { 
                    $slots = $scheduleByDay[$d] ?? [];
                    $isShown = ($d === $currentDayOfWeek);
                ?>
                    <div class="m-day-content <?= $isShown ? 'is-visible' : '' ?>" data-day-panel="<?= $d ?>">

                        <?php if (count($slots) > 0) { ?>

                            <div class="m-timeline-list">
                                <?php foreach ($slots as $slot) { ?>
                                    <div class="card m-timeline-card">
                                        
                                        <div class="m-timeline-time">
                                            <span class="m-time-pill"><?= $slot->hora_inicio ?> – <?= $slot->hora_fim ?></span>
                                            <small class="m-ordem-text"><?= $slot->ordem_aula ?>ª aula · <?= $slot->shift ?></small>
                                        </div>

                                        <div class="m-timeline-info">
                                            <div class="m-timeline-tags">
                                                <span class="m-tag m-tag-class"><?= $slot->series_acronym ?>ª <?= $slot->ballot ?></span>
                                                <span class="m-tag m-tag-course"><?= $slot->course ?></span>
                                                <span class="m-tag m-tag-room"><i class="fas fa-map-marker-alt mr-1"></i><?= $slot->sala ?></span>
                                            </div>
                                            <h6 class="m-timeline-title"><?= htmlspecialchars($slot->nome_disciplina) ?></h6>
                                        </div>

                                        <div class="m-timeline-action">
                                            <a href="/portal-docente/chamada" class="btn btn-primary btn-sm">
                                                <i class="fas fa-clipboard-check mr-1"></i> Fazer chamada
                                            </a>
                                        </div>

                                    </div>
                                <?php } ?>
                            </div>

                        <?php } else { ?>

                            <div class="card m-panel m-empty py-5">
                                <img src="<?= $app_url ?>/assets/img/illustrations/calendar.svg" alt="" style="max-width: 140px;">
                                <h6>Nenhuma aula agendada para <?= $dayNames[$d] ?></h6>
                                <p class="text-muted">Aproveite para planejar suas atividades ou revisar notas.</p>
                            </div>

                        <?php } ?>

                    </div>
                <?php } ?>

            </div>

            <!-- MODO 2: GRADE COMPLETA (MATRIZ SEMANAL VISUAL) -->
            <div id="scheduleGridView" style="display: none;">
                <div class="card m-panel p-0 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-bordered m-schedule-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 120px;" class="text-center">Horário</th>
                                    <?php for ($d = 2; $d <= 6; $d++) { ?>
                                        <th class="text-center <?= $d === $currentDayOfWeek ? 'bg-primary-soft' : '' ?>">
                                            <?= $shortDayNames[$d] ?>
                                        </th>
                                    <?php } ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Exibir linhas para os horários
                                foreach ($timeSlots as $tKey => $tInfo) { 
                                ?>
                                    <tr>
                                        <td class="m-matrix-time text-center">
                                            <strong><?= $tInfo['start'] ?></strong>
                                            <small><?= $tInfo['end'] ?></small>
                                        </td>

                                        <?php for ($d = 2; $d <= 6; $d++) { 
                                            // Encontra se o professor tem aula neste dia e horário
                                            $matched = null;
                                            foreach ($scheduleByDay[$d] ?? [] as $s) {
                                                if ($s->hora_inicio === $tInfo['start']) {
                                                    $matched = $s;
                                                    break;
                                                }
                                            }
                                        ?>
                                            <td class="m-matrix-cell <?= $matched ? 'is-occupied' : 'is-free' ?>">
                                                <?php if ($matched) { ?>
                                                    <div class="m-cell-content">
                                                        <span class="m-cell-badge"><?= $matched->series_acronym ?>ª <?= $matched->ballot ?></span>
                                                        <strong class="m-cell-disc"><?= htmlspecialchars($matched->nome_disciplina) ?></strong>
                                                        <small class="m-cell-room"><?= $matched->sala ?></small>
                                                    </div>
                                                <?php } else { ?>
                                                    <span class="m-cell-empty">—</span>
                                                <?php } ?>
                                            </td>
                                        <?php } ?>
                                    </tr>

                                    <?php if ($tKey === 2) { ?>
                                        <tr class="m-recreio-row">
                                            <td colspan="6" class="text-center">
                                                <i class="fas fa-coffee mr-2"></i> 09:10 às 09:30 — Intervalo / Recreio (Manhã)
                                            </td>
                                        </tr>
                                    <?php } ?>

                                    <?php if ($tKey === 5) { ?>
                                        <tr class="m-recreio-row m-turno-split">
                                            <td colspan="6" class="text-center font-weight-bold">
                                                <i class="fas fa-sun mr-2"></i> Início do Turno Vespertino
                                            </td>
                                        </tr>
                                    <?php } ?>

                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    </div>

</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Alternância entre Visão Por Dia (Timeline) e Grade Completa
        const modeButtons = document.querySelectorAll('[data-schedule-view-mode] button');
        const timelineView = document.getElementById('scheduleTimelineView');
        const gridView = document.getElementById('scheduleGridView');

        modeButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                modeButtons.forEach(b => b.classList.remove('is-active'));
                this.classList.add('is-active');
                const mode = this.dataset.mode;
                if (mode === 'timeline') {
                    timelineView.style.display = 'block';
                    gridView.style.display = 'none';
                } else {
                    timelineView.style.display = 'none';
                    gridView.style.display = 'block';
                }
            });
        });

        // Alternância de Abas de Dias
        const dayTabs = document.querySelectorAll('[data-schedule-day-tabs] .m-day-tab');
        const dayPanels = document.querySelectorAll('[data-day-panel]');

        dayTabs.forEach(tab => {
            tab.addEventListener('click', function () {
                dayTabs.forEach(t => t.classList.remove('is-active'));
                this.classList.add('is-active');
                const targetDay = this.dataset.day;

                dayPanels.forEach(panel => {
                    if (panel.dataset.dayPanel === targetDay) {
                        panel.classList.add('is-visible');
                    } else {
                        panel.classList.remove('is-visible');
                    }
                });
            });
        });
    });
</script>
