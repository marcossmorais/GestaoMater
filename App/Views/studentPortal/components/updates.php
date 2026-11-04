<?php
// Mural: card lateral com as notas lançadas e, abaixo, faltas e observações; do mais recente para o mais antigo
$grades = [];
$others = [];
$addUpdate = function (array &$list, $date, $icon, $class, $title, $detail) {
    $list[] = ['date' => (string) $date, 'icon' => $icon, 'class' => $class, 'title' => $title, 'detail' => $detail];
};

foreach ($this->view->listNote as $n) {
    $grade = str_replace('.', ',', (string) round($n->note_value / max($n->exam_value, 0.01) * 10, 1));
    $addUpdate($grades, $n->post_date, 'fa-star', 'is-note', $n->discipline_name, 'Nota ' . $grade . ' · ' . $n->exam_description . ' · ' . (int) $n->unity . 'º bim.');
}
foreach ($this->view->disciplineAverageList as $a) {
    $addUpdate($grades, $a->post_date, 'fa-medal', 'is-average', $a->discipline_name, 'Média final ' . number_format($a->average, 1, ',', ''));
}
foreach ($this->view->lackList as $l) {
    $addUpdate($others, $l->post_date, 'fa-user-times', 'is-lack', $l->discipline_name, (int) $l->total_lack . ' falta' . ((int) $l->total_lack === 1 ? '' : 's') . ' no ' . (int) $l->unity . 'º bim.');
}
foreach ($this->view->listObservation as $o) {
    $addUpdate($others, $o->post_date, 'fa-comment-dots', 'is-note-text', $o->discipline_name, $o->observation_description);
}

$newest = function ($a, $b) { return strcmp($b['date'], $a['date']); };
usort($grades, $newest);
usort($others, $newest);

$section = function ($title, $icon, array $items, $empty) {
    ob_start(); ?>
    <div class="card m-updates">
        <h6><i class="fas <?= $icon ?> mr-2" aria-hidden="true"></i><?= $title ?></h6>
        <?php if (!$items) { ?>
            <p class="m-muted-note mb-0"><?= $empty ?></p>
        <?php } else { ?>
            <ul class="m-updates-list">
                <?php foreach ($items as $u) { $time = strtotime($u['date']); ?>
                    <li class="<?= $u['class'] ?>">
                        <i class="fas <?= $u['icon'] ?>" aria-hidden="true"></i>
                        <div>
                            <strong><?= htmlspecialchars($u['title']) ?></strong>
                            <span><?= htmlspecialchars($u['detail']) ?></span>
                            <?php if ($time) { ?><small><?= date('d/m', $time) ?></small><?php } ?>
                        </div>
                    </li>
                <?php } ?>
            </ul>
        <?php } ?>
    </div>
    <?php return ob_get_clean();
};

echo $section('Atualização de notas', 'fa-star', $grades, 'Nenhuma nota nova por enquanto.');
echo $section('Faltas e observações', 'fa-bell', $others, 'Nenhuma falta ou observação por enquanto.');
