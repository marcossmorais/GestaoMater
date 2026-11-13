<?php
// Fund. 1: diário do dia da turma (titular). Usa $this->view->logDate, log, recentLogs e daily.
$log = $this->view->log;
$logDate = $this->view->logDate;
$weekdayNames = [1 => 'segunda', 2 => 'terça', 3 => 'quarta', 4 => 'quinta', 5 => 'sexta', 6 => 'sábado', 7 => 'domingo'];
$logLink = function ($date) { return '/portal-docente/turma?id=' . (int) $this->view->classId . '&ver=diario&data=' . rawurlencode($date); };
?>

<div class="m-dlog" data-dlog data-class-id="<?= (int) $this->view->classId ?>" data-date="<?= htmlspecialchars($logDate) ?>"
     data-codes="<?= htmlspecialchars(json_encode($log ? $log->codes : [])) ?>">

    <form class="card m-panel m-lesson-form" data-dlog-form novalidate>

        <div class="m-lesson-row">
            <label class="m-att-field">
                <span class="m-att-label">Dia</span>
                <input type="date" class="form-control" name="date" value="<?= htmlspecialchars($logDate) ?>" max="<?= date('Y-m-d') ?>" <?= $this->view->daily['from'] !== null ? 'min="' . htmlspecialchars($this->view->daily['from']) . '"' : '' ?> data-dlog-date required>
            </label>
            <p class="m-dlog-status" data-dlog-status>
                <?php if ($log) { ?>
                    <i class="fas fa-check-circle"></i> Registrado por <?= htmlspecialchars($log->teacher) ?> em <?= htmlspecialchars($log->updated_at) ?>. Salvar de novo corrige.
                <?php } else { ?>
                    <i class="far fa-circle"></i> Ainda sem diário neste dia.
                <?php } ?>
            </p>
        </div>

        <label class="m-att-field">
            <span class="m-att-label">O que foi trabalhado no dia</span>
            <textarea class="form-control" name="text" rows="7" maxlength="<?= \App\Models\TeacherStudent\DailyLog::MAX_TEXT ?>" required
                placeholder="Ex.: Português — leitura do conto e reconto oral; Matemática — problemas de adição com reagrupamento; Ciências — ciclo da água com experimento."><?= htmlspecialchars($log ? $log->text : '') ?></textarea>
        </label>

        <div class="m-att-field m-bncc">
            <label class="m-att-label" for="dailyBnccSearch">Habilidades BNCC <small>(opcional, até <?= \App\Models\TeacherStudent\DailyLog::MAX_CODES ?>; busque por palavra ou código)</small></label>
            <input type="search" id="dailyBnccSearch" class="form-control" data-bncc-search placeholder="Ex.: leitura, adição, EF03MA…" autocomplete="off" aria-controls="dailyBnccResults">
            <ul class="m-bncc-results" id="dailyBnccResults" data-bncc-results hidden></ul>
            <div class="m-att-subjects-list m-dlog-codes" data-dlog-codes aria-live="polite"></div>
        </div>

        <div class="m-att-bar">
            <div class="m-att-bar-count"><span class="m-att-count" data-dlog-hint>Escreva o que foi trabalhado no dia</span></div>
            <button type="submit" class="btn btn-primary m-att-save" data-dlog-save>
                <i class="fas fa-check mr-2"></i><span>Salvar diário</span>
            </button>
        </div>
    </form>

    <div class="card m-panel mt-3">
        <div class="m-panel-head"><h6>Últimos 14 dias</h6></div>
        <?php if (!$this->view->recentLogs) { ?>
            <div class="m-empty">Nenhuma chamada ou diário nos últimos 14 dias.</div>
        <?php } else { ?>
            <ul class="m-todo">
                <?php foreach ($this->view->recentLogs as $day) { $done = $day->text !== null; ?>
                    <li><a href="<?= htmlspecialchars($logLink($day->date)) ?>" <?= $day->date === $logDate ? 'aria-current="date"' : '' ?>>
                        <i class="fas <?= $done ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                        <span>
                            <strong><?= htmlspecialchars($day->short_date) ?> · <?= $weekdayNames[(int) $day->weekday] ?></strong>
                            <small><?= $done ? htmlspecialchars(mb_strimwidth($day->text, 0, 90, '…')) : 'Chamada feita, sem diário' ?></small>
                        </span>
                    </a></li>
                <?php } ?>
            </ul>
        <?php } ?>
    </div>
</div>
