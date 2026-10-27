<?php
require __DIR__ . '/../../config/variables.php';

$disciplineClasses = $this->view->disciplineClasses;
$openUnits = $this->view->openUnits;

require __DIR__ . '/components/classCards.php';
?>

<section id="grades" class="m-att">

    <div class="row main-container">

        <div class="col-lg-11 mx-auto">

            <!-- Passo 1: turma/disciplina -->
            <div data-gr-step="pick">

                <div class="m-page-head">
                    <div>
                        <h5>Notas e roteiro de provas</h5>
                        <p>Crie a avaliação com o roteiro de estudo e lance as notas pelo celular.</p>
                    </div>
                    <span class="m-pill m-pill-info"><?= max($openUnits) ?>ª unidade aberta</span>
                </div>

                <?php if (count($disciplineClasses) === 0) { ?>
                    <div class="card m-panel m-empty">
                        <img src="<?= $app_url ?>/assets/img/illustrations/discipline_link.svg" alt="">
                        <p>Você ainda não tem turmas vinculadas no período letivo ativo.</p>
                    </div>
                <?php } else { ?>
                    <div class="m-att-classes">
                        <?php foreach ($classesJson as $c) { ?>
                            <button type="button" class="card m-att-class" data-gr-class="<?= $c['id'] ?>">
                                <span class="m-att-class-badge"><?= htmlspecialchars($c['label']) ?></span>
                                <span class="m-att-class-body">
                                    <strong><?= htmlspecialchars($c['discipline']) ?></strong>
                                    <small><?= htmlspecialchars($c['course']) ?> · <?= htmlspecialchars($c['shift']) ?><?= $c['own'] ? '' : ' · polivalente' ?></small>
                                </span>
                                <i class="fas fa-chevron-right m-att-class-go"></i>
                            </button>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>

            <!-- Passo 2: avaliações -->
            <div data-gr-step="exams" hidden>

                <div class="m-att-head">
                    <button type="button" class="m-icon-btn" data-gr-back="pick" aria-label="Voltar"><i class="fas fa-arrow-left"></i></button>
                    <div class="m-att-head-title">
                        <h5 data-gr-title></h5>
                        <p data-gr-subtitle></p>
                    </div>
                </div>

                <div class="m-units-info" data-gr-units></div>

                <div class="card m-panel mb-3">
                    <div class="m-panel-head"><h6>Avaliações</h6></div>
                    <div class="m-exam-list" data-gr-exams></div>
                </div>

                <details class="card m-panel m-lesson-exception" data-gr-new>
                    <summary><i class="fas fa-plus mr-2"></i>Nova avaliação com roteiro</summary>
                    <form class="m-lesson-form mb-0" data-gr-exam-form novalidate>
                        <div class="m-lesson-row">
                            <label class="m-att-field">
                                <span class="m-att-label">Descrição</span>
                                <input type="text" class="form-control" name="description" maxlength="180" placeholder="Ex.: Prova 2" required>
                            </label>
                            <label class="m-att-field">
                                <span class="m-att-label">Unidade</span>
                                <select class="form-control" name="unity">
                                    <?php foreach ($openUnits as $u) { ?>
                                        <option value="<?= $u ?>" <?= $u === max($openUnits) ? 'selected' : '' ?>><?= $u ?>ª unidade</option>
                                    <?php } ?>
                                </select>
                            </label>
                            <label class="m-att-field">
                                <span class="m-att-label">Valor (pontos)</span>
                                <input type="number" class="form-control" name="value" min="0.1" max="10" step="0.1" inputmode="decimal" required>
                            </label>
                            <label class="m-att-field">
                                <span class="m-att-label">Data</span>
                                <input type="date" class="form-control" name="date" value="<?= date('Y-m-d') ?>" required>
                            </label>
                        </div>
                        <label class="m-att-field">
                            <span class="m-att-label">Roteiro de estudo <small>(o aluno vê no mural)</small></span>
                            <textarea class="form-control" name="guide" rows="4" maxlength="3000" placeholder="Conteúdos, páginas do livro, exercícios para revisar…"></textarea>
                        </label>
                        <div><button type="submit" class="btn btn-primary"><i class="fas fa-check mr-2"></i>Criar avaliação</button></div>
                    </form>
                </details>
            </div>

            <!-- Passo 3: notas -->
            <div data-gr-step="grades" hidden>

                <div class="m-att-head">
                    <button type="button" class="m-icon-btn" data-gr-back="exams" aria-label="Voltar"><i class="fas fa-arrow-left"></i></button>
                    <div class="m-att-head-title">
                        <h5 data-gr-exam-title></h5>
                        <p data-gr-exam-subtitle></p>
                    </div>
                </div>

                <div class="alert alert-warning" data-gr-closed hidden><i class="fas fa-lock mr-2"></i>Unidade fechada: notas só para consulta.</div>

                <details class="card m-panel m-lesson-exception mb-3" data-gr-guide-box>
                    <summary><i class="fas fa-route mr-2"></i>Roteiro de estudo</summary>
                    <textarea class="form-control mb-2" rows="4" maxlength="3000" data-gr-guide></textarea>
                    <button type="button" class="btn btn-light" data-gr-guide-save><i class="fas fa-save mr-2"></i>Salvar roteiro</button>
                </details>

                <div class="m-grade-list" data-gr-list></div>

                <div class="m-att-bar">
                    <div class="m-att-bar-count"><span class="m-att-count" data-gr-count></span></div>
                    <button type="button" class="btn btn-primary m-att-save" data-gr-save><i class="fas fa-check mr-2"></i><span>Salvar notas</span></button>
                </div>
            </div>

        </div>
    </div>

</section>

<script>
    window.MATER_GRADES = { classes: <?= json_encode($classesJson, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?> };
</script>
<script src="<?= asset('/assets/js/grades.js') ?>" defer></script>
