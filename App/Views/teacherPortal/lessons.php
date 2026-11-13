<?php
require __DIR__ . '/../../config/variables.php';

$disciplineClasses = $this->view->disciplineClasses;
$activities = $this->view->activities;

require __DIR__ . '/components/classCards.php';
?>

<section id="lessons" class="m-att m-lesson">

    <div class="row main-container">

        <div class="col-lg-11 mx-auto">

            <!-- Passo 1: escolher a turma -->
            <div data-ls-step="pick">

                <div class="m-page-head">
                    <div>
                        <h5>Planejamento e Horário</h5>
                        <p>Planeje as próximas aulas, registre o que foi dado e consulte seu horário.</p>
                    </div>
                    <div class="m-seg m-class-tabs" role="group" aria-label="Seções" data-page-tabs>
                        <button type="button" data-page-tab="planejamento" class="<?= $this->view->pageTab === 'planejamento' ? 'is-active' : '' ?>"><i class="fas fa-calendar-check mr-2"></i>Planejamento</button>
                        <button type="button" data-page-tab="horario" class="<?= $this->view->pageTab === 'horario' ? 'is-active' : '' ?>"><i class="fas fa-calendar-alt mr-2"></i>Horário</button>
                    </div>
                </div>

                <div data-page-panel="horario" <?= $this->view->pageTab === 'horario' ? '' : 'hidden' ?>>
                    <?php require __DIR__ . '/components/scheduleBoard.php'; ?>
                </div>

                <div data-page-panel="planejamento" <?= $this->view->pageTab === 'planejamento' ? '' : 'hidden' ?>>

                <?php foreach ($this->view->homerooms as $h) { ?>
                    <p class="m-saved-banner">
                        <i class="fas fa-book-open"></i>
                        <span><strong><?= htmlspecialchars($h->label) ?> · Fund. 1:</strong> o diário do titular é um texto único por dia, na página da turma.</span>
                        <a href="/portal-docente/turma?id=<?= (int) $h->class_id ?>&ver=diario">Diário do dia</a>
                    </p>
                <?php } ?>

                <?php if (count($disciplineClasses) === 0) { ?>

                    <div class="card m-panel m-empty">
                        <img src="<?= $app_url ?>/assets/img/illustrations/discipline_link.svg" alt="">
                        <p>Você ainda não tem turmas vinculadas no período letivo ativo.</p>
                    </div>

                <?php } else { ?>

                    <ol class="m-cal-steps">
                        <li><b>1</b>Clique em um dia do calendário</li>
                        <li><b>2</b>Escreva o tema de cada aula</li>
                        <li><b>3</b>Depois da aula, registre o que foi feito</li>
                    </ol>

                    <div class="card m-panel m-cal" data-cal>
                        <div class="m-cal-head">
                            <div class="m-cal-nav">
                                <button type="button" class="m-icon-btn" data-cal-move="-1" aria-label="Anterior"><i class="fas fa-chevron-left"></i></button>
                                <button type="button" class="btn btn-light" data-cal-today>Hoje</button>
                                <button type="button" class="m-icon-btn" data-cal-move="1" aria-label="Próximo"><i class="fas fa-chevron-right"></i></button>
                            </div>
                            <h6 data-cal-title aria-live="polite"></h6>
                            <div class="m-seg" role="group" aria-label="Ver por">
                                <button type="button" data-view="month">Mês</button>
                                <button type="button" data-view="week">Semana</button>
                            </div>
                        </div>
                        <div class="m-filter-chips" data-cal-filter role="group" aria-label="Filtrar por turma" hidden></div>
                        <div data-cal-grid><div class="m-empty">Carregando o calendário…</div></div>
                        <div class="m-cal-legend" data-cal-legend></div>
                    </div>

                    <div class="card m-panel mt-3" data-cal-day></div>

                    <details class="m-cal-other">
                    <summary><i class="fas fa-plus-circle mr-2"></i>Registrar aula fora do horário (reposição ou troca)</summary>

                    <div class="m-att-classes">
                        <?php foreach ($cards as $c) { ?>
                            <button type="button" class="card m-att-class <?= $c['subject'] ?? '' ?>" data-ls-class="<?= $c['id'] ?>">
                                <span class="m-att-class-badge"><?= htmlspecialchars($c['label']) ?></span>
                                <span class="m-att-class-body">
                                    <strong><i class="fas <?= $c['icon'] ?> m-subj-ico" aria-hidden="true"></i><?= htmlspecialchars($c['discipline']) ?></strong>
                                    <small><?= htmlspecialchars($c['course']) ?> · <?= htmlspecialchars($c['shift']) ?></small>
                                </span>
                                <i class="fas fa-chevron-right m-att-class-go"></i>
                            </button>
                        <?php } ?>
                    </div>
                    </details>

                <?php } ?>
                </div>
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

                <p class="m-saved-banner" data-ls-plan-note hidden></p>

                <div class="card m-panel m-lesson-form">

                    <div class="m-lesson-row">
                        <label class="m-att-field">
                            <span class="m-att-label">Data da aula</span>
                            <input type="date" class="form-control" name="date" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                        </label>
                    </div>

                    <div class="m-att-field m-bncc">
                        <label class="m-att-label" for="bnccSearch">Habilidade BNCC <small>(busque por palavra ou código)</small></label>
                        <input type="search" id="bnccSearch" class="form-control" data-bncc-search placeholder="Ex.: função, equação, EM13MAT…" autocomplete="off" aria-controls="bnccResults">
                        <ul class="m-bncc-results" id="bnccResults" data-bncc-results hidden></ul>
                        <input type="hidden" name="bnccCode">
                    </div>

                    <label class="m-att-field">
                        <span class="m-att-label">Habilidade trabalhada <span class="m-lesson-code-tag" data-bncc-tag hidden></span></span>
                        <textarea class="form-control" name="bnccSkill" rows="2" maxlength="1000" placeholder="Escolha na busca acima ou escreva com suas palavras"></textarea>
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

            <!-- Planejar aula -->
            <form data-ls-step="plan" hidden novalidate>

                <div class="m-att-head">
                    <button type="button" class="m-icon-btn" data-plan-back aria-label="Voltar"><i class="fas fa-arrow-left"></i></button>
                    <div class="m-att-head-title">
                        <h5 data-plan-title>Planejar aula</h5>
                        <p>Só o tema é obrigatório. O resto é opcional.</p>
                    </div>
                </div>

                <div class="card m-panel m-lesson-form">

                    <div class="m-lesson-row">
                        <label class="m-att-field">
                            <span class="m-att-label">Turma e disciplina</span>
                            <select class="form-control" name="disciplineClass">
                                <?php foreach ($classesJson as $c) { ?>
                                    <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['label'] . ' · ' . $c['discipline']) ?></option>
                                <?php } ?>
                            </select>
                        </label>
                        <label class="m-att-field">
                            <span class="m-att-label">Data</span>
                            <input type="date" class="form-control" name="date" required>
                        </label>
                        <label class="m-att-field">
                            <span class="m-att-label">Nº de aulas</span>
                            <input type="number" class="form-control" name="lessons" min="1" max="10" value="1" inputmode="numeric">
                        </label>
                    </div>

                    <label class="m-att-field">
                        <span class="m-att-label">Tema da aula</span>
                        <input type="text" class="form-control" name="title" maxlength="160" required placeholder="Ex.: Introdução à função seno">
                    </label>

                    <div class="m-att-field">
                        <span class="m-att-label">O que você vai fazer <small>(marque se quiser)</small></span>
                        <div class="m-att-subjects-list" data-plan-activities>
                            <?php foreach ($activities as $key => $label) { ?>
                                <button type="button" class="m-att-subject" data-plan-activity="<?= $key ?>" aria-pressed="false"><i class="fas fa-square"></i><?= htmlspecialchars($label) ?></button>
                            <?php } ?>
                        </div>
                    </div>

                    <details class="m-lesson-exception" data-plan-more>
                    <summary><i class="fas fa-list-ul mr-2"></i>Detalhar o plano: objetivos, BNCC, passo a passo, tarefa</summary>

                    <div class="m-att-field m-bncc">
                        <label class="m-att-label" for="planBnccSearch">Habilidade BNCC <small>(busque por palavra ou código)</small></label>
                        <input type="search" id="planBnccSearch" class="form-control" data-bncc-search placeholder="Ex.: função, equação, EM13MAT…" autocomplete="off" aria-controls="planBnccResults">
                        <ul class="m-bncc-results" id="planBnccResults" data-bncc-results hidden></ul>
                        <input type="hidden" name="bnccCode">
                    </div>

                    <label class="m-att-field">
                        <span class="m-att-label">Habilidade trabalhada <span class="m-lesson-code-tag" data-plan-bncc-tag hidden></span></span>
                        <textarea class="form-control" name="bnccSkill" rows="2" maxlength="1000" placeholder="Escolha na busca acima ou escreva com suas palavras"></textarea>
                    </label>

                    <label class="m-att-field">
                        <span class="m-att-label">Objetivos <small>(o que o aluno deve aprender)</small></span>
                        <textarea class="form-control" name="objectives" rows="2" maxlength="2000" placeholder="Ao final da aula, o aluno será capaz de…"></textarea>
                    </label>

                    <label class="m-att-field">
                        <span class="m-att-label">Desenvolvimento <small>(passo a passo)</small></span>
                        <textarea class="form-control" name="development" rows="4" maxlength="4000" placeholder="Abertura: problema ou pergunta para começar&#10;Desenvolvimento: explicação e prática&#10;Fechamento: o que retomar no final"></textarea>
                    </label>

                    <label class="m-att-field">
                        <span class="m-att-label">Recursos e materiais</span>
                        <input type="text" class="form-control" name="resources" maxlength="1000" placeholder="Livro p. 80, projetor, régua, vídeo…">
                    </label>

                    <label class="m-att-field">
                        <span class="m-att-label">Como avaliar <small>(como saber se aprenderam)</small></span>
                        <input type="text" class="form-control" name="assessment" maxlength="1000" placeholder="Exercício em dupla, participação, pergunta de saída…">
                    </label>

                    <label class="m-att-field">
                        <span class="m-att-label">Tarefa de casa</span>
                        <input type="text" class="form-control" name="homework" maxlength="1000" placeholder="Lista 3, questões 1 a 5">
                    </label>
                    </details>
                </div>

                <div class="m-att-bar">
                    <div class="m-att-bar-count"><span class="m-att-count">O plano aparece no calendário</span></div>
                    <button type="submit" class="btn btn-primary m-att-save" data-plan-save>
                        <i class="fas fa-check mr-2"></i><span>Salvar plano</span>
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
        today: <?= json_encode(date('Y-m-d')) ?>
    };
</script>
<script>
    // Abas Planejamento e Horário: sem recarregar; ?aba=horario abre direto
    document.querySelectorAll('[data-page-tab]').forEach((button) => button.addEventListener('click', () => {
        const tab = button.dataset.pageTab;
        document.querySelectorAll('[data-page-tab]').forEach((b) => b.classList.toggle('is-active', b === button));
        document.querySelectorAll('[data-page-panel]').forEach((panel) => { panel.hidden = panel.dataset.pagePanel !== tab; });
        window.history.replaceState(null, '', tab === 'horario' ? '?aba=horario' : window.location.pathname);
    }));
</script>
<script src="<?= asset('/assets/js/lessons.js') ?>" defer></script>
<script src="<?= asset('/assets/js/lessonCalendar.js') ?>" defer></script>
