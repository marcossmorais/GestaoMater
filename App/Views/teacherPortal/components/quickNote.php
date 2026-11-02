<?php
// Observação rápida: caixas marcáveis e texto livre. Entrada: $quickNotes (textos) e $quickDisciplines ([id, name, subject]).
?>
<form class="m-quick" data-quick>
    <?php if (count($quickDisciplines) > 1) { ?>
        <label class="m-att-field">
            <span class="m-att-label">Disciplina</span>
            <select class="form-control" data-quick-discipline>
                <?php foreach ($quickDisciplines as $d) { ?>
                    <option value="<?= (int) $d['id'] ?>" data-subject="<?= htmlspecialchars($d['subject']) ?>"><?= htmlspecialchars($d['name']) ?></option>
                <?php } ?>
            </select>
        </label>
    <?php } else { $only = $quickDisciplines[0]; ?>
        <select hidden data-quick-discipline><option value="<?= (int) $only['id'] ?>" data-subject="<?= htmlspecialchars($only['subject']) ?>" selected><?= htmlspecialchars($only['name']) ?></option></select>
    <?php } ?>
    <span class="m-att-label">Observação rápida <small>(marque uma ou mais)</small></span>
    <div class="m-att-subjects-list">
        <?php foreach ($quickNotes as $q) { ?>
            <button type="button" class="m-att-subject" data-quick-note="<?= htmlspecialchars($q) ?>" aria-pressed="false"><i class="fas fa-square"></i><?= htmlspecialchars($q) ?></button>
        <?php } ?>
    </div>
    <div class="m-quick-form">
        <input type="text" class="form-control" maxlength="700" data-quick-text placeholder="Escreva mais detalhes, se quiser…" aria-label="Observação sobre o aluno">
        <button type="submit" class="btn btn-primary">Salvar</button>
    </div>
</form>
