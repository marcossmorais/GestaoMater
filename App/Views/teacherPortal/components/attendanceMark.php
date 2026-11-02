<?php
// Chamada rápida na tela da turma. Entrada: $openUnits (bimestres abertos).
?>
<div id="attendance" class="m-att">

    <!-- Marcar ausentes -->
    <div data-att-step="mark">

        <div class="alert alert-warning m-att-warning" data-att-warning hidden></div>

        <!-- Anos Iniciais: disciplinas em que a chamada vale -->
        <div class="card m-att-toolbar m-att-subjects" data-att-subjects hidden>
            <span class="m-att-label">Vale para as disciplinas</span>
            <div class="m-att-subjects-list" data-att-subjects-list></div>
        </div>

        <div class="card m-att-toolbar">

            <div class="m-att-field">
                <span class="m-att-label">Bimestre</span>
                <div class="m-seg" data-att-unity>
                    <?php foreach ($openUnits as $u) { ?>
                        <button type="button" data-value="<?= $u ?>" class="<?= $u === max($openUnits) ? 'is-active' : '' ?>"><?= $u ?>º</button>
                    <?php } ?>
                </div>
            </div>

            <div class="m-att-field">
                <span class="m-att-label">Data da aula</span>
                <input type="date" class="form-control m-att-date-input" data-att-date value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
            </div>

            <div class="m-att-field">
                <span class="m-att-label">Aulas seguidas</span>
                <div class="m-stepper">
                    <button type="button" data-att-lessons="-1" aria-label="Menos uma aula"><i class="fas fa-minus"></i></button>
                    <output data-att-lessons-value>1</output>
                    <button type="button" data-att-lessons="1" aria-label="Mais uma aula"><i class="fas fa-plus"></i></button>
                </div>
            </div>

            <div class="m-att-field m-att-field-grow">
                <span class="m-att-label">Buscar aluno</span>
                <div class="m-att-search">
                    <i class="fas fa-search"></i>
                    <input type="search" data-att-search placeholder="Digite um nome" autocomplete="off">
                </div>
            </div>

            <button type="button" class="btn btn-light m-att-reset" data-att-reset><i class="fas fa-undo mr-2"></i>Todos presentes</button>
        </div>

        <div class="m-att-grid" data-att-grid aria-live="polite"></div>

        <div class="m-att-bar">
            <div class="m-att-bar-count">
                <span class="m-att-count m-att-count-ok"><strong data-att-present>0</strong> presentes</span>
                <span class="m-att-count m-att-count-bad"><strong data-att-absent>0</strong> ausentes</span>
            </div>
            <button type="button" class="btn btn-primary m-att-save" data-att-save>
                <i class="fas fa-check mr-2"></i><span>Salvar chamada</span>
            </button>
        </div>
    </div>

    <!-- Confirmação -->
    <div data-att-step="done" hidden>
        <div class="card m-att-done">
            <div class="m-att-done-icon"><i class="fas fa-check"></i></div>
            <h5>Chamada salva!</h5>
            <p data-att-done-summary></p>
            <ul class="m-att-done-list" data-att-done-list></ul>
            <div class="m-att-done-actions">
                <a href="/portal-docente/diario" class="btn btn-primary" data-att-lesson><i class="fas fa-book-open mr-2"></i>Registrar aula no diário</a>
                <button type="button" class="btn btn-light" data-att-again><i class="fas fa-clipboard-list mr-2"></i>Fazer outra chamada</button>
                <a href="/portal-docente/home" class="btn btn-light">Voltar ao início</a>
            </div>
        </div>
    </div>

</div>
