<?php
/**
 * Filtros da observabilidade (GET). $filterAction = rota do formulário; $filterHidden = campos extras;
 * $filterShift = false esconde o turno (na página da turma ele já é fixo).
 */
$f = $this->view->filters;
?>

<form class="m-obs-filters" method="get" action="<?= htmlspecialchars($filterAction) ?>">
    <?php foreach ($filterHidden ?? [] as $name => $value) { ?>
        <input type="hidden" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars($value) ?>">
    <?php } ?>

    <select name="periodo" class="form-control" aria-label="Período letivo" onchange="this.form.submit()">
        <?php foreach ($this->view->periods as $p) { ?>
            <option value="<?= (int) $p->id ?>" <?= (int) $p->id === $f['period'] ? 'selected' : '' ?>>Período <?= htmlspecialchars($p->year) ?></option>
        <?php } ?>
    </select>

    <select name="bimestre" class="form-control" aria-label="Bimestre" onchange="this.form.submit()">
        <option value="">Ano inteiro</option>
        <?php for ($u = 1; $u <= $this->view->rule['units']; $u++) { ?>
            <option value="<?= $u ?>" <?= $u === $f['unit'] ? 'selected' : '' ?>><?= $u ?>º bimestre</option>
        <?php } ?>
    </select>

    <?php if ($filterShift ?? true) { ?>
    <select name="turno" class="form-control" aria-label="Turno" onchange="this.form.submit()">
        <option value="">Todos os turnos</option>
        <?php foreach ($this->view->shifts as $s) { ?>
            <option value="<?= (int) $s->id ?>" <?= (int) $s->id === $f['shift'] ? 'selected' : '' ?>><?= htmlspecialchars($s->name) ?></option>
        <?php } ?>
    </select>
    <?php } ?>

    <noscript><button type="submit" class="btn btn-primary">Filtrar</button></noscript>
</form>
