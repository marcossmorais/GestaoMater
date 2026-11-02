<?php
// Aba Frequência do aluno: resumo, dias de aula perdidos e envio de atestado ou justificativa
use App\Models\Student\StudentAttendance;
use App\Tools\Subject;

$rows = $this->view->performance ?? [];
$missed = $this->view->missedDays ?? [];
$submissions = $this->view->submissions ?? [];
$pct = function ($n) { return $n === null ? '–' : str_replace('.', ',', (string) round((float) $n, 1)) . '%'; };

$lessons = array_sum(array_column($rows, 'lessons'));
$absences = array_sum(array_column($rows, 'absences'));
$excused = array_sum(array_column($rows, 'excused'));
$overall = $lessons > 0 ? round(100 * max(0, $lessons - max(0, $absences - $excused)) / $lessons, 1) : null;
$minimum = 75;
$dated = array_sum(array_map(function ($m) { return (int) $m->lessons; }, $missed));

$monthNames = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
$weekNames = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
$byDay = [];
foreach ($missed as $m) $byDay[$m->date][] = $m;
$certificate = [
    'Aprovado' => ['m-pill-ok', 'Abonada'],
    'Pendente' => ['m-pill-info', 'Em análise'],
    'Recusado' => ['m-pill-bad', 'Atestado recusado'],
];
$submissionStatus = [
    'Aprovado' => ['m-pill-ok', 'Aceito'],
    'Pendente' => ['m-pill-warn', 'Aguardando a coordenação'],
    'Recusado' => ['m-pill-bad', 'Recusado'],
];
?>

<section class="m-frequency" id="studentFrequency">
    <div class="col-lg-11 mx-auto">

        <div class="m-page-head">
            <div>
                <h5>Frequência</h5>
                <p>Sua presença em cada matéria e os dias de aula perdidos. O mínimo é <?= $minimum ?>%.</p>
            </div>
        </div>

        <div class="m-freq-tiles">
            <div class="card m-freq-tile <?= $overall !== null && $overall < $minimum ? 'is-low' : '' ?>"><strong><?= $pct($overall) ?></strong><small>Frequência geral</small></div>
            <div class="card m-freq-tile"><strong><?= (int) $absences ?></strong><small>Faltas</small></div>
            <div class="card m-freq-tile"><strong><?= (int) $excused ?></strong><small>Abonadas</small></div>
            <div class="card m-freq-tile"><strong><?= count($byDay) ?></strong><small>Dias perdidos</small></div>
        </div>

        <div class="m-freq-grid">

            <div class="card m-panel">
                <div class="m-panel-head"><h6><i class="fas fa-paper-plane mr-2"></i>Enviar atestado ou justificativa</h6></div>
                <form class="m-lesson-form mb-0" data-freq-form novalidate>
                    <div class="m-att-field">
                        <span class="m-att-label">O que você vai enviar</span>
                        <div class="m-seg" data-freq-type>
                            <?php foreach (StudentAttendance::TYPES as $key => $label) { ?>
                                <button type="button" data-value="<?= $key ?>" class="<?= $key === 'atestado' ? 'is-active' : '' ?>"><?= htmlspecialchars($label) ?></button>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="m-lesson-row">
                        <label class="m-att-field">
                            <span class="m-att-label">Data da falta</span>
                            <input type="date" class="form-control" name="start" max="<?= date('Y-m-d') ?>" required>
                        </label>
                        <label class="m-att-field">
                            <span class="m-att-label">Até <small>(se faltou mais de um dia)</small></span>
                            <input type="date" class="form-control" name="end" max="<?= date('Y-m-d') ?>">
                        </label>
                    </div>
                    <label class="m-att-field">
                        <span class="m-att-label">Motivo</span>
                        <textarea class="form-control" name="reason" rows="2" maxlength="255" required placeholder="Ex.: consulta médica"></textarea>
                    </label>
                    <label class="m-att-field">
                        <span class="m-att-label"><span data-freq-file-label>Arquivo do atestado</span> <small>(PDF, JPG ou PNG, até 5 MB)</small></span>
                        <input type="file" class="form-control" name="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                    </label>
                    <div class="m-actions"><button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-2"></i>Enviar à coordenação</button></div>
                </form>

                <?php if ($submissions) { ?>
                    <h6 class="m-data-sub">Meus envios</h6>
                    <ul class="m-requests">
                        <?php foreach ($submissions as $s) {
                            $state = $submissionStatus[$s->status] ?? ['m-pill-info', $s->status];
                            $period = $s->start_date === $s->end_date ? $s->start_date : $s->start_date . ' a ' . $s->end_date;
                        ?>
                            <li>
                                <div><strong><?= htmlspecialchars(StudentAttendance::TYPES[$s->type] ?? $s->type) ?></strong> <span class="m-pill <?= $state[0] ?>"><?= $state[1] ?></span></div>
                                <small><?= htmlspecialchars($period) ?><?= $s->excused ? ' · ' . (int) $s->excused . ' falta' . ((int) $s->excused === 1 ? '' : 's') . ' abonada' . ((int) $s->excused === 1 ? '' : 's') : '' ?></small>
                                <p><?= htmlspecialchars($s->reason) ?></p>
                                <?php if ($s->answer) { ?><p class="m-muted-note mb-0">Coordenação: <?= htmlspecialchars($s->answer) ?></p><?php } ?>
                                <?php if ($s->has_file && $s->private_file) { ?><a href="/portal-aluno/frequencia/anexo?id=<?= (int) $s->id ?>" target="_blank" rel="noopener"><i class="fas fa-paperclip mr-1"></i>Ver arquivo</a><?php } ?>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } ?>
            </div>

            <div class="card m-panel">
                <div class="m-panel-head"><h6><i class="fas fa-chart-pie mr-2"></i>Por matéria</h6></div>
                <ul class="m-freq-list">
                    <?php foreach ($rows as $d) { $low = $d['attendance'] !== null && $d['attendance'] < $minimum; ?>
                        <li class="<?= Subject::css($d['discipline']) ?>">
                            <div><strong><?= htmlspecialchars($d['discipline']) ?></strong><span class="<?= $low ? 'is-low' : '' ?>"><?= $pct($d['attendance']) ?></span></div>
                            <i class="m-index-track"><i style="width:<?= (int) ($d['attendance'] ?? 0) ?>%"></i></i>
                            <small><?= (int) $d['absences'] ?> falta<?= (int) $d['absences'] === 1 ? '' : 's' ?><?= $d['excused'] ? ' · ' . (int) $d['excused'] . ' abonada' . ((int) $d['excused'] === 1 ? '' : 's') : '' ?></small>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </div>

        <div class="card m-panel mt-3">
            <div class="m-panel-head"><h6><i class="fas fa-calendar-times mr-2"></i>Dias de aula perdidos</h6></div>

            <?php if (!$byDay) { ?>
                <p class="m-empty mb-0">Nenhuma falta com data registrada.</p>
            <?php } ?>

            <?php if ($absences > $dated) { ?>
                <p class="m-muted-note"><?= (int) ($absences - $dated) ?> falta<?= $absences - $dated === 1 ? '' : 's' ?> lançada<?= $absences - $dated === 1 ? '' : 's' ?> sem a data da aula. Na dúvida, fale com a secretaria.</p>
            <?php } ?>

            <ul class="m-freq-days">
                <?php
                $lastMonth = null;
                foreach ($byDay as $date => $items) {
                    $day = new DateTime($date);
                    $month = $monthNames[(int) $day->format('n') - 1] . ' de ' . $day->format('Y');
                    $state = $certificate[$items[0]->certificate] ?? null;
                ?>
                    <?php if ($month !== $lastMonth) { $lastMonth = $month; ?><li class="m-freq-month"><?= $month ?></li><?php } ?>
                    <li class="m-freq-day">
                        <span class="m-freq-date"><strong><?= $day->format('d') ?></strong><small><?= $weekNames[(int) $day->format('w')] ?></small></span>
                        <span class="m-freq-subjects">
                            <?php foreach ($items as $m) { ?><span><?= htmlspecialchars($m->discipline) ?> <small>(<?= (int) $m->lessons ?> aula<?= (int) $m->lessons === 1 ? '' : 's' ?>)</small></span><?php } ?>
                        </span>
                        <?php if ($state && $items[0]->certificate !== 'Recusado') { ?>
                            <span class="m-pill <?= $state[0] ?>"><?= $state[1] ?></span>
                        <?php } else { ?>
                            <button type="button" class="btn btn-light" data-freq-justify="<?= htmlspecialchars($date) ?>">Justificar</button>
                        <?php } ?>
                    </li>
                <?php } ?>
            </ul>
        </div>

    </div>
</section>

<script>
    (function () {
        const root = document.getElementById('studentFrequency');
        const form = root.querySelector('[data-freq-form]');
        let type = 'atestado';
        let sending = false;

        const messages = {
            'data-invalida': 'Escolha uma data de falta até hoje.',
            'periodo-longo': 'O período pode ter até 30 dias.',
            'motivo-obrigatorio': 'Escreva o motivo.',
            'arquivo-obrigatorio': 'Anexe o arquivo do atestado.',
            'arquivo-grande': 'O arquivo passa de 5 MB.',
            'arquivo-invalido': 'Use um arquivo PDF, JPG ou PNG.',
            'ja-enviado': 'Já existe um envio em análise para este período.'
        };

        function toast(message, kind) {
            if (typeof tools !== 'undefined' && typeof tools.showToast === 'function') tools.showToast(message, kind === 'error' ? 'bg-danger' : 'bg-success');
        }

        root.querySelector('[data-freq-type]').addEventListener('click', (event) => {
            const button = event.target.closest('[data-value]');
            if (!button) return;
            type = button.dataset.value;
            root.querySelectorAll('[data-freq-type] button').forEach((b) => b.classList.toggle('is-active', b === button));
            root.querySelector('[data-freq-file-label]').textContent = type === 'atestado' ? 'Arquivo do atestado' : 'Arquivo (opcional)';
        });

        root.addEventListener('click', (event) => {
            const button = event.target.closest('[data-freq-justify]');
            if (!button) return;
            form.querySelector('[name="start"]').value = button.dataset.freqJustify;
            form.querySelector('[name="end"]').value = '';
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            form.querySelector('[name="reason"]').focus({ preventScroll: true });
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (sending) return;
            const data = new FormData(form);
            data.append('type', type);

            sending = true;
            try {
                const response = await fetch('/portal-aluno/frequencia/atestado', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: data });
                const result = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(result.error || 'falha');
                toast('Enviado à coordenação');
                window.location.href = '/portal-aluno/home?aba=frequencia';
            } catch (e) {
                toast(messages[e.message] || 'Não foi possível enviar. Tente de novo.', 'error');
                sending = false;
            }
        });
    })();
</script>
