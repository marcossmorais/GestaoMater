<?php
require __DIR__ . '/components/helpers.php';

use App\Models\Admin\Observability;

$query = $this->view->query;
$classId = $this->view->classId;
$classes = $this->view->classes;
$roster = $this->view->roster;
$rosterUnity = $this->view->openUnits;
$stageUrl = function ($stage) use ($query) {
    $q = $query;
    unset($q['etapa'], $q['bimestre']);
    return '/admin/carometro?' . http_build_query($q + array_filter(['etapa' => $stage]));
};

// seletor agrupado por etapa
$groups = [];
foreach ($classes as $c) $groups[$c->stage][] = $c;
$current = null;
foreach ($classes as $c) if ((int) $c->id === $classId) $current = $c;
?>

<section id="observability">
    <div class="row main-container">
        <div class="col-lg-11 mx-auto">

            <div class="m-page-head">
                <div>
                    <h5>Carômetro</h5>
                    <p>Fotos dos alunos ativos de cada turma, com as faltas do bimestre. Toque no aluno para abrir o perfil pedagógico e o boletim.</p>
                </div>
            </div>

            <?php require __DIR__ . '/components/stages.php' ?>

            <?php if (!$classes) { ?>
                <div class="card m-panel m-empty"><p>Nenhuma turma no período com esses filtros.</p></div>
            <?php } else { ?>
                <form class="m-obs-filters mb-3" method="get" action="/admin/carometro">
                    <?php foreach (array_diff_key($query, ['bimestre' => 1]) as $name => $value) { ?>
                        <input type="hidden" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars((string) $value) ?>">
                    <?php } ?>
                    <select name="turma" class="form-control" aria-label="Turma" onchange="this.form.submit()">
                        <?php foreach ($groups as $stage => $list) { ?>
                            <optgroup label="<?= htmlspecialchars(Observability::STAGES[$stage]['label'] ?? 'Outras') ?>">
                                <?php foreach ($list as $c) { ?>
                                    <option value="<?= (int) $c->id ?>" <?= (int) $c->id === $classId ? 'selected' : '' ?>><?= htmlspecialchars($c->label) ?> (<?= (int) $c->students ?>)</option>
                                <?php } ?>
                            </optgroup>
                        <?php } ?>
                    </select>
                    <noscript><button type="submit" class="btn btn-primary">Ver</button></noscript>
                    <?php if ($current) { ?>
                        <a class="btn btn-light" href="/admin/observabilidade/turma?<?= htmlspecialchars(http_build_query(['id' => $classId] + $query)) ?>"><i class="fas fa-chart-line mr-2"></i>Desempenho da turma</a>
                    <?php } ?>
                </form>

                <?php require __DIR__ . '/components/roster.php' ?>
            <?php } ?>

        </div>
    </div>
</section>
