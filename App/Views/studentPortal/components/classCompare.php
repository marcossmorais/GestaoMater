<?php
// Tela de Desempenho do aluno: a sua nota x média da turma em cada matéria (sem nomes de colegas)
use App\Tools\Subject;

$rule = $this->view->performanceRule;
$rows = $this->view->performance ?? [];
$averages = $this->view->classAverages ?? [];
$fmt = function ($n) { return $n === null ? '–' : str_replace('.', ',', (string) round((float) $n, 1)); };

// começa no último bimestre em que o aluno tem nota
$start = 1;
foreach ($rows as $d) foreach ($d['units'] as $u => $unit) if ($unit['grade'] !== null) $start = max($start, (int) $u);
?>

<div class="m-compare">

    <div class="m-page-head">
        <div>
            <h5>Você e a sua turma</h5>
            <p>A sua nota em cada matéria ao lado da média da turma. Ninguém vê a nota de ninguém: a média é calculada sem nomes.</p>
        </div>
        <div class="m-seg" data-compare-unit role="group" aria-label="Bimestre">
            <?php for ($u = 1; $u <= $rule['units']; $u++) { ?>
                <button type="button" class="<?= $u === $start ? 'is-active' : '' ?>" data-unit="<?= $u ?>"><?= $u ?>º bim.</button>
            <?php } ?>
        </div>
    </div>

    <?php for ($u = 1; $u <= $rule['units']; $u++) { ?>
        <div class="card m-panel m-compare-panel" data-compare-panel="<?= $u ?>" <?= $u === $start ? '' : 'hidden' ?>>
            <?php
            $bars = [];
            foreach ($rows as $d) {
                $mine = $d['units'][$u]['grade'];
                $class = $averages[(int) $d['id']][$u] ?? null;
                if ($mine !== null || $class !== null) $bars[] = ['d' => $d, 'mine' => $mine, 'class' => $class];
            }
            $max = (float) $rule['maxPerUnit'];
            $height = function ($n) use ($max) { return $n === null ? 0 : round(min(100, 100 * (float) $n / $max), 1); };
            ?>
            <?php if (!$bars) { ?>
                <p class="m-empty mb-0">Ainda não há notas neste bimestre.</p>
            <?php } else { ?>
                <div class="m-vchart-wrap">
                    <div class="m-vchart" style="--passing:<?= $height($rule['passing']) ?>%; --cols:<?= count($bars) ?>">
                        <?php foreach ($bars as $b) { ?>
                            <div class="m-vchart-col <?= Subject::css($b['d']['discipline']) ?>" title="<?= htmlspecialchars($b['d']['discipline'] . ': você ' . $fmt($b['mine']) . ' · turma ' . $fmt($b['class'])) ?>">
                                <div class="m-vchart-bars">
                                    <span class="m-vchart-bar is-me" style="height:<?= $height($b['mine']) ?>%"><b><?= $fmt($b['mine']) ?></b></span>
                                    <span class="m-vchart-bar" style="height:<?= $height($b['class']) ?>%"></span>
                                </div>
                                <small><?= htmlspecialchars($b['d']['discipline']) ?></small>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <p class="m-muted-note mb-0 mt-2">Barra colorida: sua nota · cinza: média da turma · linha vermelha: média <?= $fmt($rule['passing']) ?></p>
            <?php } ?>
        </div>
    <?php } ?>

</div>

<script>
    document.querySelectorAll('[data-compare-unit] [data-unit]').forEach(function (button) {
        button.addEventListener('click', function () {
            document.querySelectorAll('[data-compare-unit] [data-unit]').forEach(function (b) { b.classList.toggle('is-active', b === button); });
            document.querySelectorAll('[data-compare-panel]').forEach(function (p) { p.hidden = p.dataset.comparePanel !== button.dataset.unit; });
        });
    });
</script>
