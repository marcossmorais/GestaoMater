<?php
require __DIR__ . '/../../config/variables.php';

$disciplineClasses = $this->view->disciplineClasses;
$openUnits = $this->view->openUnits;

require __DIR__ . '/components/classCards.php';
?>

<section id="examGuides" class="m-att">

    <div class="row main-container">

        <div class="col-lg-11 mx-auto">

            <div class="m-page-head">
                <div>
                    <h5>Roteiro de Provas</h5>
                    <p>Escolha a turma, o bimestre e a avaliação. Ao salvar, a coordenação recebe e o aluno vê no mural.</p>
                </div>
            </div>

            <?php if (!$classesJson) { ?>

                <div class="card m-panel m-empty">Você ainda não tem turmas vinculadas no período letivo ativo.</div>

            <?php } else { ?>

                <form class="m-guide-page" data-guide-form novalidate>

                    <div class="card m-att-toolbar">
                        <label class="m-att-field m-class-filter">
                            <span class="m-att-label">Turma e disciplina</span>
                            <select class="form-control" data-guide-class>
                                <?php foreach ($classesJson as $c) { ?>
                                    <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['label'] . ' · ' . $c['discipline']) ?></option>
                                <?php } ?>
                            </select>
                        </label>
                        <div class="m-att-field">
                            <span class="m-att-label">Bimestre</span>
                            <div class="m-seg" data-guide-unity>
                                <?php foreach ($openUnits as $u) { ?>
                                    <button type="button" data-value="<?= (int) $u ?>" class="<?= $u === max($openUnits) ? 'is-active' : '' ?>"><?= (int) $u ?>º</button>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="m-att-field">
                            <span class="m-att-label">Avaliação</span>
                            <div class="m-seg m-guide-slots" data-guide-slots></div>
                        </div>
                    </div>

                    <p class="m-saved-banner" data-guide-status hidden></p>

                    <div class="card m-panel m-lesson-form">

                        <div class="m-lesson-row">
                            <label class="m-att-field">
                                <span class="m-att-label">Data da avaliação</span>
                                <input type="date" class="form-control" name="date" required>
                            </label>
                        </div>

                        <div class="m-att-field">
                            <span class="m-att-label">Tipo de avaliação <small>(marque um ou mais)</small></span>
                            <div class="m-att-subjects-list" data-guide-types>
                                <?php foreach ($this->view->examTypes as $key => $label) { ?>
                                    <button type="button" class="m-att-subject" data-type="<?= htmlspecialchars($key) ?>" aria-pressed="false"><i class="fas fa-square"></i><?= htmlspecialchars($label) ?></button>
                                <?php } ?>
                            </div>
                        </div>

                        <label class="m-att-field">
                            <span class="m-att-label">Assuntos <small>(um por linha)</small></span>
                            <textarea class="form-control" name="topics" rows="5" maxlength="2000" required placeholder="Equação do 2º grau&#10;Fórmula de Bhaskara&#10;Problemas com área"></textarea>
                        </label>

                        <label class="m-att-field">
                            <span class="m-att-label">Material de estudo</span>
                            <input type="text" class="form-control" name="material" maxlength="500" placeholder="Livro cap. 4, p. 80–95 · lista 3">
                        </label>

                        <label class="m-att-field">
                            <span class="m-att-label">Observações</span>
                            <textarea class="form-control" name="notes" rows="2" maxlength="3000" placeholder="Trazer calculadora"></textarea>
                        </label>
                    </div>

                    <div class="m-att-bar">
                        <div class="m-att-bar-count"><span class="m-att-count" data-guide-hint></span></div>
                        <button type="submit" class="btn btn-primary m-att-save" data-guide-save>
                            <i class="fas fa-paper-plane mr-2"></i><span>Salvar e enviar</span>
                        </button>
                    </div>
                </form>

            <?php } ?>

        </div>
    </div>

</section>

<script src="<?= asset('/assets/js/examGuides.js') ?>" defer></script>
