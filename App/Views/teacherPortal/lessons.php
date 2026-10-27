<?php
require __DIR__ . '/../../config/variables.php';

$disciplineClasses = $this->view->disciplineClasses;
$activities = $this->view->activities;

require __DIR__ . '/components/classCards.php';

$recentJson = array_map(function ($l) use ($activities) {
    return [
        'class' => $l->series_acronym . 'ª ' . $l->ballot,
        'discipline' => $l->discipline,
        'date' => $l->date,
        'bnccCode' => $l->bncc_code,
        'bnccSkill' => $l->bncc_skill,
        'notes' => $l->notes,
        'activities' => array_values(array_map(function ($a) use ($activities) { return $activities[$a] ?? $a; }, array_filter(explode(',', $l->activities)))),
        'substitute' => $l->substitute,
        'taught' => $l->taught_discipline,
        'reason' => $l->reason,
    ];
}, $this->view->recentLessons);
?>

<section id="lessons" class="m-att m-lesson">

    <div class="row main-container">

        <div class="col-lg-11 mx-auto">

            <!-- Passo 1: escolher a turma -->
            <div data-ls-step="pick">

                <div class="m-page-head">
                    <div>
                        <h5>Diário de aula</h5>
                        <p>Habilidade BNCC, atividades e observações da aula.</p>
                    </div>
                </div>

                <?php if (count($disciplineClasses) === 0) { ?>

                    <div class="card m-panel m-empty">
                        <img src="<?= $app_url ?>/assets/img/illustrations/discipline_link.svg" alt="">
                        <p>Você ainda não tem turmas vinculadas no período letivo ativo.</p>
                    </div>

                <?php } else { ?>

                    <div class="m-att-classes">
                        <?php foreach ($cards as $c) { ?>
                            <button type="button" class="card m-att-class" data-ls-class="<?= $c['id'] ?>">
                                <span class="m-att-class-badge"><?= htmlspecialchars($c['label']) ?></span>
                                <span class="m-att-class-body">
                                    <strong><?= htmlspecialchars($c['discipline']) ?></strong>
                                    <small><?= htmlspecialchars($c['course']) ?> · <?= htmlspecialchars($c['shift']) ?></small>
                                </span>
                                <i class="fas fa-chevron-right m-att-class-go"></i>
                            </button>
                        <?php } ?>
                    </div>

                    <div class="card m-panel mt-4">
                        <div class="m-panel-head"><h6>Últimas aulas registradas</h6></div>
                        <div class="m-lesson-list" data-ls-recent></div>
                    </div>

                <?php } ?>
            </div>

            <!-- Passo 2: registrar a aula -->
            <form data-ls-step="form" hidden novalidate>

                <div class="m-att-head">
                    <button type="button" class="m-icon-btn" data-ls-back aria-label="Voltar"><i class="fas fa-arrow-left"></i></button>
                    <div class="m-att-head-title">
                        <h5 data-ls-title></h5>
                        <p data-ls-subtitle></p>
                    </div>
                </div>

                <div class="card m-att-toolbar m-att-subjects" data-ls-subjects hidden>
                    <span class="m-att-label">Vale para as disciplinas</span>
                    <div class="m-att-subjects-list" data-ls-subjects-list></div>
                </div>

                <div class="card m-panel m-lesson-form">

                    <div class="m-lesson-row">
                        <label class="m-att-field">
                            <span class="m-att-label">Data da aula</span>
                            <input type="date" class="form-control" name="date" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                        </label>
                        <label class="m-att-field">
                            <span class="m-att-label">Código BNCC <small>(opcional)</small></span>
                            <input type="text" class="form-control m-lesson-code" name="bnccCode" placeholder="EF05MA01" maxlength="20" autocomplete="off" autocapitalize="characters">
                        </label>
                    </div>

                    <label class="m-att-field">
                        <span class="m-att-label">Habilidade trabalhada</span>
                        <textarea class="form-control" name="bnccSkill" rows="2" maxlength="1000" placeholder="Ex.: Resolver problemas de adição e subtração com números naturais"></textarea>
                    </label>

                    <div class="m-att-field">
                        <span class="m-att-label">Atividades realizadas</span>
                        <div class="m-att-subjects-list" data-ls-activities>
                            <?php foreach ($activities as $key => $label) { ?>
                                <button type="button" class="m-att-subject" data-activity="<?= $key ?>" aria-pressed="false"><i class="fas fa-square"></i><?= htmlspecialchars($label) ?></button>
                            <?php } ?>
                        </div>
                    </div>

                    <label class="m-att-field">
                        <span class="m-att-label">Observação da aula</span>
                        <textarea class="form-control" name="notes" rows="3" maxlength="2000" placeholder="Como foi a aula, o que ficou pendente…"></textarea>
                    </label>

                    <details class="m-lesson-exception" data-ls-exception>
                        <summary><i class="fas fa-exchange-alt mr-2"></i>Houve troca de professor ou de matéria?</summary>
                        <div class="m-lesson-row">
                            <label class="m-att-field">
                                <span class="m-att-label">Aula dada por substituto</span>
                                <select class="form-control" name="substitute">
                                    <option value="">Não</option>
                                    <?php foreach ($this->view->teachers as $t) { if ((int) $t->id === (int) $_SESSION['Teacher']['id']) continue; ?>
                                        <option value="<?= (int) $t->id ?>"><?= htmlspecialchars($t->name) ?></option>
                                    <?php } ?>
                                </select>
                            </label>
                            <label class="m-att-field">
                                <span class="m-att-label">Matéria dada no lugar</span>
                                <select class="form-control" name="taughtDisciplineClass" data-ls-taught>
                                    <option value="">Não houve troca</option>
                                </select>
                            </label>
                        </div>
                        <label class="m-att-field">
                            <span class="m-att-label">Motivo</span>
                            <input type="text" class="form-control" name="reason" maxlength="255" placeholder="Ex.: professor titular em formação">
                        </label>
                    </details>
                </div>

                <div class="m-att-bar">
                    <div class="m-att-bar-count"><span class="m-att-count" data-ls-hint>Preencha a habilidade, uma atividade ou a observação</span></div>
                    <button type="submit" class="btn btn-primary m-att-save" data-ls-save>
                        <i class="fas fa-check mr-2"></i><span>Salvar aula</span>
                    </button>
                </div>
            </form>

            <!-- Passo 3: confirmação -->
            <div data-ls-step="done" hidden>
                <div class="card m-att-done">
                    <div class="m-att-done-icon"><i class="fas fa-check"></i></div>
                    <h5>Aula registrada!</h5>
                    <p data-ls-done-summary></p>
                    <div class="m-att-done-actions">
                        <button type="button" class="btn btn-primary" data-ls-again><i class="fas fa-book-open mr-2"></i>Registrar outra aula</button>
                        <a href="/portal-docente/chamada" class="btn btn-light">Fazer chamada</a>
                    </div>
                </div>
            </div>

        </div>
    </div>

</section>

<script>
    window.MATER_LESSONS = {
        classes: <?= json_encode($classesJson, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>,
        classDisciplines: <?= json_encode($this->view->classDisciplines, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>,
        activities: <?= json_encode($activities, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>,
        recent: <?= json_encode($recentJson, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>
    };
</script>
<script src="<?= asset('/assets/js/lessons.js') ?>" defer></script>
