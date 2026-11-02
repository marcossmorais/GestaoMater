<?php
$tab = $this->view->tab;
$unity = $this->view->unity;
$guides = $this->view->guides;
$requests = $this->view->requests;
$typeLabels = function ($types) { return \App\Models\TeacherStudent\Gradebook::typeLabels($types); };
$newGuides = count(array_filter($guides, function ($g) { return !$g->seen; }));
$pending = count(array_filter($requests, function ($r) { return $r->status === 'pendente'; }));
$certificates = $this->view->certificates;
$pendingCertificates = count(array_filter($certificates, function ($c) { return $c->status === 'Pendente'; }));
?>

<section id="adminPedagogical" class="m-att">

    <div class="row main-container">

        <div class="col-lg-11 mx-auto">

            <div class="m-page-head">
                <div>
                    <h5>Recebidos dos professores</h5>
                    <p>Roteiros de prova, pedidos de ocorrência e atestados para analisar.</p>
                </div>
            </div>

            <div class="card m-att-toolbar">
                <div class="m-att-field">
                    <span class="m-att-label">Ver</span>
                    <div class="m-seg m-class-tabs">
                        <a href="?ver=roteiros&unidade=<?= (int) $unity ?>" role="button" class="btn <?= $tab === 'roteiros' ? 'is-active' : '' ?>"><i class="fas fa-route mr-2"></i>Roteiros<?= $newGuides ? ' (' . $newGuides . ')' : '' ?></a>
                        <a href="?ver=ocorrencias" role="button" class="btn <?= $tab === 'ocorrencias' ? 'is-active' : '' ?>"><i class="fas fa-flag mr-2"></i>Ocorrências<?= $pending ? ' (' . $pending . ')' : '' ?></a>
                        <a href="?ver=atestados" role="button" class="btn <?= $tab === 'atestados' ? 'is-active' : '' ?>"><i class="fas fa-notes-medical mr-2"></i>Atestados<?= $pendingCertificates ? ' (' . $pendingCertificates . ')' : '' ?></a>
                    </div>
                </div>
                <?php if ($tab === 'roteiros') { ?>
                    <div class="m-att-field">
                        <span class="m-att-label">Bimestre</span>
                        <div class="m-seg">
                            <a href="?ver=roteiros" role="button" class="btn <?= $unity === 0 ? 'is-active' : '' ?>">Todos</a>
                            <?php for ($u = 1; $u <= 4; $u++) { ?>
                                <a href="?ver=roteiros&unidade=<?= $u ?>" role="button" class="btn <?= $unity === $u ? 'is-active' : '' ?>"><?= $u ?>º</a>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <?php if ($tab === 'roteiros') { ?>

                <?php if (!$guides) { ?><div class="card m-panel m-empty">Nenhum roteiro recebido<?= $unity ? ' neste bimestre' : '' ?>.</div><?php } ?>

                <div class="m-inbox">
                    <?php foreach ($guides as $g) { ?>
                        <article class="card m-panel m-inbox-item <?= $g->seen ? '' : 'is-new' ?>" data-guide="<?= (int) $g->id ?>">
                            <div class="m-inbox-head">
                                <strong><?= htmlspecialchars($g->class_label . ' · ' . $g->discipline) ?></strong>
                                <span class="m-pill <?= $g->seen ? 'm-pill-ok' : 'm-pill-warn' ?>" data-guide-pill><?= $g->seen ? 'Visto' : 'Novo' ?></span>
                            </div>
                            <small><?= htmlspecialchars($g->description) ?> · <?= (int) $g->unity ?>º bimestre · prova em <?= htmlspecialchars($g->date) ?><?= $g->types ? ' · ' . htmlspecialchars($typeLabels($g->types)) : '' ?></small>
                            <small><?= htmlspecialchars($g->teacher) ?> · enviado em <?= htmlspecialchars($g->sent_at) ?></small>
                            <?php if ($g->topics) { ?>
                                <ul class="m-inbox-topics">
                                    <?php foreach (explode("\n", $g->topics) as $topic) { ?><li><?= htmlspecialchars($topic) ?></li><?php } ?>
                                </ul>
                            <?php } ?>
                            <?php if ($g->material) { ?><p><b>Material:</b> <?= htmlspecialchars($g->material) ?></p><?php } ?>
                            <?php if ($g->notes) { ?><p><b>Observações:</b> <?= nl2br(htmlspecialchars($g->notes)) ?></p><?php } ?>
                            <?php if (!$g->seen) { ?><div><button type="button" class="btn btn-light" data-guide-seen><i class="fas fa-check mr-2"></i>Marcar como visto</button></div><?php } ?>
                        </article>
                    <?php } ?>
                </div>

            <?php } elseif ($tab === 'atestados') { ?>

                <?php if (!$certificates) { ?><div class="card m-panel m-empty">Nenhum atestado ou justificativa enviados pelos alunos.</div><?php } ?>

                <div class="m-inbox">
                    <?php foreach ($certificates as $c) {
                        $pill = ['Pendente' => ['m-pill-warn', 'Pendente'], 'Aprovado' => ['m-pill-ok', 'Aceito'], 'Recusado' => ['m-pill-bad', 'Recusado']][$c->status] ?? ['m-pill-info', $c->status];
                        $period = $c->start_date === $c->end_date ? $c->start_date : $c->start_date . ' a ' . $c->end_date;
                    ?>
                        <article class="card m-panel m-inbox-item <?= $c->status === 'Pendente' ? 'is-new' : '' ?>" data-certificate="<?= (int) $c->id ?>">
                            <div class="m-inbox-head">
                                <strong><?= htmlspecialchars($c->student) ?> · <?= htmlspecialchars($c->class_label) ?></strong>
                                <span class="m-pill <?= $pill[0] ?>"><?= $pill[1] ?></span>
                            </div>
                            <small><?= htmlspecialchars(\App\Models\Student\StudentAttendance::TYPES[$c->type] ?? $c->type) ?> · falta em <?= htmlspecialchars($period) ?> · enviado em <?= htmlspecialchars($c->sent_at) ?></small>
                            <p><?= htmlspecialchars($c->reason) ?></p>
                            <?php if ($c->has_file) { ?><div><a class="btn btn-light" href="/admin/pedagogico/atestado/anexo?id=<?= (int) $c->id ?>" target="_blank" rel="noopener"><i class="fas fa-paperclip mr-2"></i>Ver arquivo</a></div><?php } ?>
                            <?php if ($c->status === 'Aprovado') { ?><p class="m-muted-note mb-0"><?= (int) $c->excused ?> falta<?= (int) $c->excused === 1 ? '' : 's' ?> abonada<?= (int) $c->excused === 1 ? '' : 's' ?></p><?php } ?>
                            <?php if ($c->answer) { ?><p class="m-muted-note mb-0">Resposta: <?= htmlspecialchars($c->answer) ?></p><?php } ?>
                            <?php if ($c->status === 'Pendente') { ?>
                                <label class="m-att-field">
                                    <span class="m-att-label">Observação ou motivo da recusa</span>
                                    <input type="text" class="form-control" maxlength="255" data-certificate-answer>
                                </label>
                                <div class="m-actions">
                                    <button type="button" class="btn btn-primary" data-certificate-action="accept"><i class="fas fa-check mr-2"></i>Aceitar e abonar faltas</button>
                                    <button type="button" class="btn btn-light" data-certificate-action="reject">Recusar</button>
                                </div>
                            <?php } ?>
                        </article>
                    <?php } ?>
                </div>

            <?php } else { ?>

                <?php if (!$requests) { ?><div class="card m-panel m-empty">Nenhuma solicitação de ocorrência.</div><?php } ?>

                <div class="m-inbox">
                    <?php foreach ($requests as $r) {
                        $pill = ['pendente' => ['m-pill-warn', 'Pendente'], 'aceita' => ['m-pill-ok', 'Aceita'], 'recusada' => ['m-pill-bad', 'Recusada']][$r->status];
                    ?>
                        <article class="card m-panel m-inbox-item <?= $r->status === 'pendente' ? 'is-new' : '' ?>" data-request="<?= (int) $r->id ?>">
                            <div class="m-inbox-head">
                                <strong><?= htmlspecialchars($r->student) ?> · <?= htmlspecialchars($r->title) ?></strong>
                                <span class="m-pill <?= $pill[0] ?>"><?= $pill[1] ?></span>
                            </div>
                            <small><?= htmlspecialchars($r->category) ?><?= $r->category === 'Elogio' ? '' : ' · gravidade ' . htmlspecialchars($r->severity) ?> · fato em <?= htmlspecialchars($r->date) ?></small>
                            <small><?= htmlspecialchars($r->teacher) ?> · <?= htmlspecialchars($r->class_label . ' · ' . $r->discipline) ?></small>
                            <p><?= nl2br(htmlspecialchars($r->description)) ?></p>
                            <?php if ($r->answer) { ?><p class="m-muted-note mb-0">Resposta: <?= htmlspecialchars($r->answer) ?></p><?php } ?>
                            <?php if ($r->status === 'pendente') { ?>
                                <label class="m-att-field">
                                    <span class="m-att-label">Medida tomada ou motivo da recusa</span>
                                    <input type="text" class="form-control" maxlength="255" data-request-answer>
                                </label>
                                <label class="m-overview-low"><input type="checkbox" data-request-visible> Visível para a família</label>
                                <div class="m-actions">
                                    <button type="button" class="btn btn-primary" data-request-action="accept"><i class="fas fa-check mr-2"></i>Aceitar e registrar ocorrência</button>
                                    <button type="button" class="btn btn-light" data-request-action="reject">Recusar</button>
                                </div>
                            <?php } ?>
                        </article>
                    <?php } ?>
                </div>

            <?php } ?>

        </div>
    </div>

</section>

<script>
    (function () {
        const root = document.getElementById('adminPedagogical');

        async function post(url, body) {
            const response = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body) });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(result.error || 'falha');
        }

        const messages = { 'motivo-obrigatorio': 'Escreva o motivo da recusa.', 'ja-analisada': 'Esta solicitação já foi analisada.' };

        root.addEventListener('click', async (event) => {
            const seen = event.target.closest('[data-guide-seen]');
            const action = event.target.closest('[data-request-action]');
            const certificate = event.target.closest('[data-certificate-action]');
            if (!seen && !action && !certificate) return;

            const button = seen || action || certificate;
            button.disabled = true;
            try {
                if (certificate) {
                    const card = button.closest('[data-certificate]');
                    await post('/admin/pedagogico/atestado/responder', {
                        id: parseInt(card.dataset.certificate, 10),
                        accept: certificate.dataset.certificateAction === 'accept',
                        answer: card.querySelector('[data-certificate-answer]').value.trim()
                    });
                } else if (seen) {
                    await post('/admin/pedagogico/roteiro/visto', { id: parseInt(button.closest('[data-guide]').dataset.guide, 10) });
                } else {
                    const card = button.closest('[data-request]');
                    await post('/admin/pedagogico/solicitacao/responder', {
                        id: parseInt(card.dataset.request, 10),
                        accept: action.dataset.requestAction === 'accept',
                        answer: card.querySelector('[data-request-answer]').value.trim(),
                        visible: card.querySelector('[data-request-visible]').checked
                    });
                }
                window.location.reload();
            } catch (e) {
                button.disabled = false;
                window.alert(messages[e.message] || 'Não foi possível salvar. Tente de novo.');
            }
        });
    })();
</script>
