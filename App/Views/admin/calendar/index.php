<?php
// Cronograma escolar: visões Ano, Mês, Semana e Dia; cadastro de eventos e Notificar no mural (#21)
use App\Controllers\Admin\AdminCalendarController;
use App\Models\GeneralManagement\SchoolCalendar;
use App\Models\TeacherStudent\GeneralBoard;

$cal = $this->view->calendar;
$events = $this->view->events;
$types = SchoolCalendar::TYPES;
$view = $cal['view'];
$from = $cal['from'];
$to = $cal['to'];
$today = $cal['today'];

$url = function (array $change = []) use ($cal) {
    $q = array_filter(['visao' => $cal['view'], 'data' => $cal['date'], 'turma' => $cal['class']] + [], 'strlen');
    return '/admin/cronograma?' . http_build_query(array_filter($change + $q, 'strlen'));
};

// dia (Y-m-d) => eventos daquele dia, dentro do período mostrado
$byDay = [];
foreach ($events as $e) {
    $d = max($e->start, $from->format('Y-m-d'));
    $last = min($e->finish, $to->format('Y-m-d'));
    while ($d <= $last) {
        $byDay[$d][] = $e;
        $d = date('Y-m-d', strtotime("$d +1 day"));
    }
}

$fmt = function ($ymd) { return date('d/m', strtotime($ymd)); };
$when = function ($e) use ($fmt) { return $e->start === $e->finish ? $fmt($e->start) : $fmt($e->start) . ' a ' . $fmt($e->finish); };
$monthNames = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
$weekNames = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
$pill = function ($e) use ($types) {
    return '<a class="m-cal-pill is-ev-' . htmlspecialchars($e->type) . '" href="#evento-' . (int) $e->id . '" title="' . htmlspecialchars($e->title) . '">' . htmlspecialchars($e->title) . '</a>';
};
?>

<section id="adminCalendar" class="m-att">
    <div class="row main-container">
        <div class="col-lg-11 mx-auto">

            <div class="m-page-head">
                <div>
                    <h5>Cronograma escolar</h5>
                    <p>Eventos, provas, reuniões, feriados e recessos da escola ou de uma turma. Os professores veem no planejamento; para avisar alunos, famílias ou professores, use "Notificar no mural".</p>
                </div>
                <button type="button" class="btn btn-primary" data-cal-new><i class="fas fa-plus mr-2"></i>Novo evento</button>
            </div>

            <form class="card m-panel m-post-form mb-3 m-cal-form" data-cal-form hidden novalidate>
                <h6 class="m-cal-form-title" data-cal-form-title>Novo evento</h6>
                <input type="hidden" name="id" value="0">
                <label class="m-att-field">
                    <span class="m-att-label">Título</span>
                    <input class="form-control" name="title" maxlength="120" required placeholder="Ex.: Feira de Ciências">
                </label>
                <div class="m-att-field">
                    <span class="m-att-label">Tipo</span>
                    <div class="m-board-types" data-cal-types role="group" aria-label="Tipo">
                        <?php foreach ($types as $key => $t) { ?>
                            <button type="button" class="m-board-type is-ev-<?= $key ?> <?= $key === 'evento' ? 'is-active' : '' ?>" data-type="<?= $key ?>" aria-pressed="<?= $key === 'evento' ? 'true' : 'false' ?>"><i class="fas <?= $t['icon'] ?>" aria-hidden="true"></i><?= htmlspecialchars($t['label']) ?></button>
                        <?php } ?>
                    </div>
                </div>
                <div class="m-cal-form-row">
                    <label class="m-att-field"><span class="m-att-label">Início</span><input type="date" class="form-control" name="start" required></label>
                    <label class="m-att-field"><span class="m-att-label">Fim <small>(se durar mais de um dia)</small></span><input type="date" class="form-control" name="finish"></label>
                    <label class="m-att-field"><span class="m-att-label">Para</span>
                        <select class="form-control" name="class">
                            <option value="0">Toda a escola</option>
                            <?php foreach ($this->view->classes as $c) { ?><option value="<?= (int) $c->id ?>"><?= htmlspecialchars($c->label) ?></option><?php } ?>
                        </select>
                    </label>
                </div>
                <label class="m-att-field">
                    <span class="m-att-label">Descrição <small>(opcional)</small></span>
                    <textarea class="form-control" name="description" rows="2" maxlength="2000" placeholder="Ex.: Apresentação dos projetos no pátio."></textarea>
                </label>
                <div class="m-post-form-actions">
                    <button type="button" class="btn btn-light" data-cal-cancel>Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check mr-2"></i>Salvar</button>
                </div>
            </form>

            <div class="card m-panel m-cal m-admin-cal">
                <div class="m-cal-head">
                    <div class="m-cal-nav">
                        <a class="btn btn-light" href="<?= htmlspecialchars($url(['data' => $cal['prev']])) ?>" aria-label="Anterior"><i class="fas fa-chevron-left"></i></a>
                        <a class="btn btn-light" href="<?= htmlspecialchars($url(['data' => $today])) ?>">Hoje</a>
                        <a class="btn btn-light" href="<?= htmlspecialchars($url(['data' => $cal['next']])) ?>" aria-label="Próximo"><i class="fas fa-chevron-right"></i></a>
                    </div>
                    <h6><?= htmlspecialchars($cal['title']) ?></h6>
                    <nav class="m-seg m-board-filter" aria-label="Visão">
                        <?php foreach (AdminCalendarController::VIEWS as $k => $label) { ?>
                            <a class="btn <?= $view === $k ? 'is-active' : '' ?>" href="<?= htmlspecialchars($url(['visao' => $k])) ?>" <?= $view === $k ? 'aria-current="page"' : '' ?>><?= $label ?></a>
                        <?php } ?>
                    </nav>
                </div>

                <form class="m-cal-filter" method="get" action="/admin/cronograma">
                    <input type="hidden" name="visao" value="<?= htmlspecialchars($view) ?>">
                    <input type="hidden" name="data" value="<?= htmlspecialchars($cal['date']) ?>">
                    <label>Mostrar
                        <select class="form-control" name="turma" onchange="this.form.submit()">
                            <option value="">Todos os eventos</option>
                            <option value="escola" <?= $cal['class'] === 'escola' ? 'selected' : '' ?>>Só os da escola toda</option>
                            <?php foreach ($this->view->classes as $c) { ?>
                                <option value="<?= (int) $c->id ?>" <?= $cal['class'] === (string) $c->id ? 'selected' : '' ?>>Escola + <?= htmlspecialchars($c->label) ?></option>
                            <?php } ?>
                        </select>
                    </label>
                    <noscript><button class="btn btn-light">Filtrar</button></noscript>
                </form>

                <?php if ($view === 'ano') { ?>
                    <div class="m-cal-year">
                        <?php for ($m = 1; $m <= 12; $m++) {
                            $ym = $from->format('Y') . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
                            $monthEvents = array_filter($events, function ($e) use ($ym) { return substr($e->start, 0, 7) <= $ym && substr($e->finish, 0, 7) >= $ym; });
                        ?>
                            <div class="m-cal-year-month">
                                <a class="m-cal-year-name" href="<?= htmlspecialchars($url(['visao' => 'mes', 'data' => $ym . '-01'])) ?>"><?= $monthNames[$m] ?> <small><?= count($monthEvents) ?: '' ?></small></a>
                                <?php if (!$monthEvents) { ?><p class="m-cal-free">Sem eventos</p><?php } ?>
                                <ul>
                                    <?php foreach ($monthEvents as $e) { ?>
                                        <li><span><?= $when($e) ?></span> <?= $pill($e) ?></li>
                                    <?php } ?>
                                </ul>
                            </div>
                        <?php } ?>
                    </div>

                <?php } elseif ($view === 'mes') {
                    $gridStart = $from->modify('-' . ((int) $from->format('N') - 1) . ' days');
                    $gridEnd = $to->modify('+' . (7 - (int) $to->format('N')) . ' days');
                ?>
                    <div class="m-cal-weekdays" aria-hidden="true"><?php foreach ($weekNames as $w) { ?><span><?= $w ?></span><?php } ?></div>
                    <div class="m-cal-month">
                        <?php for ($d = $gridStart; $d <= $gridEnd; $d = $d->modify('+1 day')) {
                            $ymd = $d->format('Y-m-d');
                            $dayEvents = $byDay[$ymd] ?? [];
                            $out = $d->format('m') !== $from->format('m');
                        ?>
                            <div class="m-cal-day <?= $out ? 'is-out' : '' ?>">
                                <a class="m-cal-num <?= $ymd === $today ? 'is-today' : '' ?>" href="<?= htmlspecialchars($url(['visao' => 'dia', 'data' => $ymd])) ?>" aria-label="<?= $d->format('d/m') ?><?= $dayEvents ? ', ' . count($dayEvents) . ' evento(s)' : '' ?>"><?= $d->format('j') ?></a>
                                <?php if (!$out && $dayEvents) { ?>
                                    <div class="m-cal-pills"><?php foreach (array_slice($dayEvents, 0, 3) as $e) echo $pill($e); ?><?php if (count($dayEvents) > 3) { ?><span class="m-cal-more">+<?= count($dayEvents) - 3 ?></span><?php } ?></div>
                                    <div class="m-cal-dots"><?php foreach (array_slice($dayEvents, 0, 4) as $e) { ?><i class="is-ev-<?= htmlspecialchars($e->type) ?>"></i><?php } ?></div>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>

                <?php } elseif ($view === 'semana') { ?>
                    <div class="m-cal-week">
                        <?php for ($d = $from, $i = 0; $d <= $to; $d = $d->modify('+1 day'), $i++) { $ymd = $d->format('Y-m-d'); ?>
                            <div class="m-cal-col">
                                <div class="m-cal-col-head"><a href="<?= htmlspecialchars($url(['visao' => 'dia', 'data' => $ymd])) ?>"><?= $weekNames[$i] ?> <?= $d->format('d/m') ?></a><?php if ($ymd === $today) { ?><span class="m-cal-num is-today" aria-label="hoje">•</span><?php } ?></div>
                                <?php if (empty($byDay[$ymd])) { ?><span class="m-cal-free">Sem eventos</span><?php } ?>
                                <?php foreach ($byDay[$ymd] ?? [] as $e) { ?>
                                    <a class="m-cal-slot is-ev-<?= htmlspecialchars($e->type) ?>" href="#evento-<?= (int) $e->id ?>"><b><?= htmlspecialchars($e->title) ?></b><small><?= htmlspecialchars($types[$e->type]['label'] ?? '') ?> · <?= htmlspecialchars($e->class_label ?: 'Escola') ?></small></a>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>

                <div class="m-cal-legend" aria-hidden="true">
                    <?php foreach ($types as $key => $t) { ?><span><i class="is-ev-<?= $key ?>"></i><?= htmlspecialchars($t['label']) ?></span><?php } ?>
                </div>
            </div>

            <h5 class="m-section-title mt-4"><?= $view === 'dia' ? 'Eventos do dia' : 'Eventos do período' ?> <span class="m-pill m-pill-info"><?= count($events) ?></span></h5>

            <?php if (!$events) { ?><div class="card m-panel m-empty">Nenhum evento neste período.</div><?php } ?>

            <?php foreach ($events as $e) { $t = $types[$e->type] ?? $types['outro']; ?>
                <article class="card m-board-post m-cal-event is-ev-<?= htmlspecialchars($e->type) ?>" id="evento-<?= (int) $e->id ?>" data-cal-event='<?= htmlspecialchars(json_encode(['id' => (int) $e->id, 'title' => $e->title, 'description' => (string) $e->description, 'type' => $e->type, 'start' => $e->start, 'finish' => $e->finish, 'class' => (int) $e->class_id], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>'>
                    <header>
                        <span class="m-board-tag"><i class="fas <?= $t['icon'] ?>" aria-hidden="true"></i><?= htmlspecialchars($t['label']) ?></span>
                        <strong><?= htmlspecialchars($e->title) ?></strong>
                        <small><?= $when($e) ?> · <?= htmlspecialchars($e->class_label ?: 'Toda a escola') ?></small>
                        <?php if ($e->changed_after_notice) { ?>
                            <span class="m-cal-status is-warn"><i class="fas fa-exclamation-circle mr-1"></i>Alterado depois do aviso</span>
                        <?php } elseif ($e->notified_at) { ?>
                            <span class="m-cal-status"><i class="fas fa-check mr-1"></i>Notificado em <?= htmlspecialchars($e->notified_at) ?></span>
                        <?php } ?>
                    </header>
                    <?php if ($e->description) { ?><p><?= nl2br(htmlspecialchars($e->description)) ?></p><?php } ?>
                    <footer class="m-post-actions">
                        <button type="button" class="btn btn-sm btn-light" data-cal-edit><i class="fas fa-pen mr-1"></i>Editar</button>
                        <button type="button" class="btn btn-sm btn-light" data-cal-notify-open aria-expanded="false"><i class="fas fa-bullhorn mr-1"></i><?= $e->notified_at ? 'Notificar de novo' : 'Notificar no mural' ?></button>
                        <button type="button" class="btn btn-sm btn-light text-danger" data-cal-delete><i class="fas fa-trash-alt mr-1"></i>Excluir</button>
                    </footer>
                    <div class="m-cal-notify" data-cal-notify hidden>
                        <p class="m-cal-notify-preview"><?= nl2br(htmlspecialchars(SchoolCalendar::noticeText($e, (bool) $e->changed_after_notice))) ?></p>
                        <fieldset>
                            <legend class="m-att-label">Publicar no mural para <small>(<?= htmlspecialchars($e->class_label ? 'turma ' . $e->class_label : 'toda a escola') ?>; professores: todos)</small></legend>
                            <?php foreach (GeneralBoard::AUDIENCES as $key => $a) { ?>
                                <label><input type="checkbox" name="audiences" value="<?= $key ?>" <?= $key === 'alunos' ? 'checked' : '' ?>> <?= htmlspecialchars($a['label']) ?></label>
                            <?php } ?>
                        </fieldset>
                        <button type="button" class="btn btn-primary btn-sm" data-cal-notify-send><i class="fas fa-paper-plane mr-1"></i>Publicar aviso</button>
                    </div>
                </article>
            <?php } ?>

        </div>
    </div>
</section>

<script>
    (function () {
        const root = document.getElementById('adminCalendar');
        const form = root.querySelector('[data-cal-form]');
        let type = 'evento';

        const errors = {
            'titulo-invalido': 'Escreva o título (até 120 caracteres).',
            'data-invalida': 'Informe a data de início.',
            'fim-antes-do-inicio': 'O fim não pode ser antes do início.',
            'turma-invalida': 'Essa turma não existe mais. Recarregue a página.',
            'publico-invalido': 'Escolha pelo menos um público.',
            'evento-nao-encontrado': 'Esse evento não existe mais. Recarregue a página.'
        };

        async function post(url, body) {
            const response = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body) });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(result.error || 'falha');
            return result;
        }

        function setType(value) {
            type = value;
            root.querySelectorAll('[data-cal-types] [data-type]').forEach((b) => {
                b.classList.toggle('is-active', b.dataset.type === value);
                b.setAttribute('aria-pressed', b.dataset.type === value);
            });
        }

        function openForm(data) {
            form.hidden = false;
            form.querySelector('[data-cal-form-title]').textContent = data.id ? 'Editar evento' : 'Novo evento';
            form.elements.id.value = data.id || 0;
            form.elements.title.value = data.title || '';
            form.elements.start.value = data.start || '';
            form.elements.finish.value = data.finish && data.finish !== data.start ? data.finish : '';
            form.elements.class.value = data.class || 0;
            form.elements.description.value = data.description || '';
            setType(data.type || 'evento');
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            form.elements.title.focus({ preventScroll: true });
        }

        root.querySelector('[data-cal-new]').addEventListener('click', () => openForm({ start: <?= json_encode($view === 'dia' ? $cal['date'] : '') ?> }));
        root.querySelector('[data-cal-cancel]').addEventListener('click', () => { form.hidden = true; });

        root.querySelector('[data-cal-types]').addEventListener('click', (event) => {
            const button = event.target.closest('[data-type]');
            if (button) setType(button.dataset.type);
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const f = form.elements;
            if (!f.title.value.trim()) return f.title.focus();
            if (!f.start.value) return f.start.focus();
            try {
                await post('/admin/cronograma/salvar', {
                    id: parseInt(f.id.value, 10), title: f.title.value.trim(), description: f.description.value.trim(),
                    type, start: f.start.value, finish: f.finish.value, class: parseInt(f.class.value, 10)
                });
                window.location.reload();
            } catch (e) {
                window.alert(errors[e.message] || 'Não foi possível salvar. Tente de novo.');
            }
        });

        root.addEventListener('click', async (event) => {
            const card = event.target.closest('[data-cal-event]');
            if (!card) return;
            const data = JSON.parse(card.dataset.calEvent);

            if (event.target.closest('[data-cal-edit]')) return openForm(data);

            const toggle = event.target.closest('[data-cal-notify-open]');
            if (toggle) {
                const box = card.querySelector('[data-cal-notify]');
                box.hidden = !box.hidden;
                toggle.setAttribute('aria-expanded', !box.hidden);
                return;
            }

            const send = event.target.closest('[data-cal-notify-send]');
            if (send) {
                const audiences = [...card.querySelectorAll('[name="audiences"]:checked')].map((i) => i.value);
                if (!audiences.length) return window.alert(errors['publico-invalido']);
                send.disabled = true;
                try {
                    await post('/admin/cronograma/notificar', { id: data.id, audiences });
                    window.location.reload();
                } catch (e) {
                    send.disabled = false;
                    window.alert(errors[e.message] || 'Não foi possível publicar. Tente de novo.');
                }
                return;
            }

            if (event.target.closest('[data-cal-delete]')) {
                if (!window.confirm('Excluir "' + data.title + '" do cronograma? Avisos já publicados no mural continuam lá.')) return;
                try {
                    await post('/admin/cronograma/excluir', { id: data.id });
                    window.location.reload();
                } catch (e) {
                    window.alert('Não foi possível excluir. Tente de novo.');
                }
            }
        });
    })();
</script>
