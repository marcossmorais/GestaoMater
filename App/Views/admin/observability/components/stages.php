<?php
/**
 * Etapas (Todas · Fund. 1 · Fund. 2 · Médio) como abas. $stageUrl(etapa|null) monta o link mantendo os demais filtros.
 */
$currentStage = $this->view->filters['stage'];
?>
<nav class="m-seg m-obs-stages mb-3" aria-label="Etapa de ensino">
    <a class="btn <?= $currentStage === null ? 'is-active' : '' ?>" href="<?= htmlspecialchars($stageUrl(null)) ?>" <?= $currentStage === null ? 'aria-current="page"' : '' ?>>Todas as etapas</a>
    <?php foreach ($this->view->stages as $key => $s) { ?>
        <a class="btn <?= $currentStage === $key ? 'is-active' : '' ?>" href="<?= htmlspecialchars($stageUrl($key)) ?>" title="<?= htmlspecialchars($s['label']) ?>" <?= $currentStage === $key ? 'aria-current="page"' : '' ?>><?= htmlspecialchars($s['short']) ?></a>
    <?php } ?>
</nav>
