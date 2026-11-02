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
                        <h5>Notas</h5>
                        <p>Por bimestre: 1ª, 2ª e 3ª avaliação, de 0 a 10. Média abaixo de 7 vai para a recuperação.</p>
                    </div>
                    <span class="m-pill m-pill-info"><?= max($openUnits) ?>º bimestre aberto</span>
                </div>

                <?php if (count($disciplineClasses) === 0) { ?>
                    <div class="card m-panel m-empty">
                        <img src="<?= $app_url ?>/assets/img/illustrations/discipline_link.svg" alt="">
                        <p>Você ainda não tem turmas vinculadas no período letivo ativo.</p>
                    </div>
                <?php } else { ?>
                    <div class="m-att-classes">
                        <?php foreach ($classesJson as $c) { ?>
                            <button type="button" class="card m-att-class <?= $c['subject'] ?? '' ?>" data-gr-class="<?= $c['id'] ?>">
                                <span class="m-att-class-badge"><?= htmlspecialchars($c['label']) ?></span>
                                <span class="m-att-class-body">
                                    <strong><i class="fas <?= $c['icon'] ?> m-subj-ico" aria-hidden="true"></i><?= htmlspecialchars($c['discipline']) ?></strong>
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

                <div data-gr-bimesters></div>

                <div class="card m-panel" data-gr-new hidden>
                    <div class="m-panel-head"><h6 data-gr-new-title></h6></div>
                    <form class="m-lesson-form mb-0" data-gr-exam-form novalidate>
                        <label class="m-att-field">
                            <span class="m-att-label">Data</span>
                            <input type="date" class="form-control" name="date" value="<?= date('Y-m-d') ?>" required>
                        </label>
                        <div class="m-att-field">
                            <span class="m-att-label">O que vale nota nesta avaliação <small>(marque um ou mais)</small></span>
                            <div class="m-att-subjects-list" data-gr-new-types></div>
                        </div>
                        <div class="m-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-check mr-2"></i>Criar</button>
                            <button type="button" class="btn btn-light" data-gr-new-cancel>Cancelar</button>
                        </div>
                    </form>
                </div>
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

                <details class="card m-panel m-lesson-exception mb-3" data-gr-types-box open>
                    <summary><i class="fas fa-tasks mr-2"></i>O que vale nota nesta avaliação <span class="m-pill m-pill-info ml-2" data-gr-types-count></span></summary>
                    <div class="m-att-subjects-list" data-gr-exam-types></div>
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
    window.MATER_GRADES = {
        classes: <?= json_encode($classesJson, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>,
        examTypes: <?= json_encode(\App\Models\TeacherStudent\Gradebook::EXAM_TYPES, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>
    };
</script>
<script src="<?= asset('/assets/js/grades.js') ?>" defer></script>
