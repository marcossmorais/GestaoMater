<?php
// Cronograma escolar (dia / semana / mês / ano) — portal do aluno
$agenda = $this->view->agenda ?? [];
?>

<section class="m-agenda" id="studentAgenda">
    <div class="col-lg-11 mx-auto main-content">

        <div class="m-page-head">
            <div>
                <h5>Cronograma</h5>
                <p>Eventos da escola, da sua turma e avaliações.</p>
            </div>
            <div class="m-seg" role="tablist" data-agenda-range>
                <button type="button" data-range="day">Dia</button>
                <button type="button" data-range="week" class="is-active">Semana</button>
                <button type="button" data-range="month">Mês</button>
                <button type="button" data-range="year">Ano</button>
            </div>
        </div>

        <div class="m-agenda-list" data-agenda-list aria-live="polite"></div>
    </div>
</section>

<script>
    window.MATER_AGENDA = <?= json_encode($agenda, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
</script>
