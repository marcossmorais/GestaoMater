<?php
require __DIR__ . '/../../config/variables.php';

$disciplineClasses = $this->view->disciplineClasses;

require __DIR__ . '/components/classCards.php';
?>

<section id="board" class="m-att">

    <div class="row main-container">

        <div class="col-lg-11 mx-auto">

            <!-- Passo 1: escolher a turma -->
            <div data-bd-step="pick">

                <div class="m-page-head">
                    <div>
                        <h5>Mural da turma</h5>
                        <p>Avisos para os alunos. Fixe até 3 no topo do mural da turma.</p>
                    </div>
                </div>

                <?php if (count($disciplineClasses) === 0) { ?>
                    <div class="card m-panel m-empty">
                        <img src="<?= $app_url ?>/assets/img/illustrations/discipline_link.svg" alt="">
                        <p>Você ainda não tem turmas vinculadas no período letivo ativo.</p>
                    </div>
                <?php } else { ?>
                    <div class="m-att-classes">
                        <?php
                        $seen = [];
                        foreach ($cards as $c) {
                            if (isset($seen[$c['classId']])) continue;
                            $seen[$c['classId']] = true; ?>
                            <button type="button" class="card m-att-class" data-bd-class="<?= $c['classId'] ?>">
                                <span class="m-att-class-badge"><?= htmlspecialchars($c['label']) ?></span>
                                <span class="m-att-class-body">
                                    <strong><?= htmlspecialchars($c['course']) ?> · <?= htmlspecialchars($c['shift']) ?></strong>
                                    <small><?= $c['students'] ?> alunos</small>
                                </span>
                                <i class="fas fa-chevron-right m-att-class-go"></i>
                            </button>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>

            <!-- Passo 2: avisos da turma -->
            <div data-bd-step="list" hidden>

                <div class="m-att-head">
                    <button type="button" class="m-icon-btn" data-bd-back aria-label="Voltar"><i class="fas fa-arrow-left"></i></button>
                    <div class="m-att-head-title">
                        <h5 data-bd-title></h5>
                        <p data-bd-subtitle></p>
                    </div>
                </div>

                <form class="card m-panel m-post-form" data-bd-form novalidate>
                    <label class="m-att-field">
                        <span class="m-att-label">Novo aviso</span>
                        <textarea class="form-control" name="text" rows="3" maxlength="2000" placeholder="Ex.: Amanhã traremos o material de Ciências para a aula prática." required></textarea>
                    </label>
                    <label class="m-att-field" data-bd-discipline-field>
                        <span class="m-att-label">Disciplina</span>
                        <select class="form-control" name="disciplineClass" data-bd-discipline></select>
                    </label>
                    <div class="m-post-form-actions">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="bdPinned" name="pinned">
                            <label class="custom-control-label" for="bdPinned">Fixar no topo <small data-bd-pinned-count></small></label>
                        </div>
                        <button type="submit" class="btn btn-primary" data-bd-submit><i class="fas fa-paper-plane mr-2"></i>Publicar</button>
                    </div>
                </form>

                <div data-bd-posts aria-live="polite"></div>
            </div>

        </div>
    </div>

</section>

<script>
    window.MATER_BOARD = { classes: <?= json_encode($classesJson, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?> };
</script>
<script src="<?= asset('/assets/js/board.js') ?>" defer></script>
