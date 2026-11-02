<?php
// Aba Turma do aluno: a sua nota x média da turma em cada matéria (sem nomes de colegas) e os professores (sem contato)
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
            <?php $any = false; foreach ($rows as $d) {
                $mine = $d['units'][$u]['grade'];
                $class = $averages[(int) $d['id']][$u] ?? null;
                if ($mine === null && $class === null) continue;
                $any = true;
                $diff = ($mine !== null && $class !== null) ? $mine - $class : null;
            ?>
                <div class="m-compare-row <?= Subject::css($d['discipline']) ?>">
                    <span class="m-perf-ico"><i class="fas <?= Subject::icon($d['discipline']) ?>" aria-hidden="true"></i></span>
                    <div class="m-compare-body">
                        <div class="m-compare-title">
                            <strong><?= htmlspecialchars($d['discipline']) ?></strong>
                            <?php if ($diff !== null) { ?>
                                <span class="m-pill <?= $diff >= 0 ? 'm-pill-ok' : 'm-pill-warn' ?>"><?= $diff >= 0 ? '+' : '' ?><?= $fmt($diff) ?> da turma</span>
                            <?php } ?>
                        </div>
                        <div class="m-bar-line"><span class="m-bar-label">Você</span><span class="m-bar"><i class="m-bar-fill is-me" style="width: <?= $mine === null ? 0 : min(100, $mine * 10) ?>%"></i><i class="m-bar-goal" style="left: <?= $rule['passing'] * 10 ?>%"></i></span><b><?= $fmt($mine) ?></b></div>
                        <div class="m-bar-line"><span class="m-bar-label">Turma</span><span class="m-bar"><i class="m-bar-fill" style="width: <?= $class === null ? 0 : min(100, $class * 10) ?>%"></i><i class="m-bar-goal" style="left: <?= $rule['passing'] * 10 ?>%"></i></span><b><?= $fmt($class) ?></b></div>
                    </div>
                </div>
            <?php } ?>
            <?php if (!$any) { ?><p class="m-empty mb-0">Ainda não há notas neste bimestre.</p><?php } ?>
            <p class="m-muted-note mb-0 mt-2"><i class="fas fa-flag mr-1"></i>O traço marca a média <?= $fmt($rule['passing']) ?>.</p>
        </div>
    <?php } ?>

    <h5 class="mt-4 mb-3">Seus professores</h5>
    <div class="m-teachers">
        <?php foreach ($this->view->listTeacher as $t) { ?>
            <div class="m-teacher <?= Subject::css($t->discipline) ?>">
                <img src="<?= $app_url ?>/assets/img/teacherProfilePhotos/<?= htmlspecialchars($t->photo ?: 'foto-vazia.jpg') ?>" alt="" onerror="this.src='<?= $app_url ?>/assets/img/teacherProfilePhotos/foto-vazia.jpg'">
                <div><strong><?= htmlspecialchars($t->name) ?></strong><small><i class="fas <?= Subject::icon($t->discipline) ?> m-subj-ico"></i><?= htmlspecialchars($t->discipline) ?></small></div>
            </div>
        <?php } ?>
    </div>

</div>

<script>
    document.querySelectorAll('[data-compare-unit] [data-unit]').forEach(function (button) {
        button.addEventListener('click', function () {
            document.querySelectorAll('[data-compare-unit] [data-unit]').forEach(function (b) { b.classList.toggle('is-active', b === button); });
            document.querySelectorAll('[data-compare-panel]').forEach(function (p) { p.hidden = p.dataset.comparePanel !== button.dataset.unit; });
        });
    });
</script>
