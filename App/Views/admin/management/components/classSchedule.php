<?php
// Perfil da turma → Horário: grade semanal montada pelo admin (/admin/gestao/turma/perfil-turma/horario)
$slots = $this->view->scheduleSlots;
$disciplines = $this->view->scheduleDisciplines;
$grid = $this->view->scheduleGrid;
?>
<?php if (!$slots) { ?>
    <p class="text-muted">Não há faixas de horário cadastradas para o turno desta turma.</p>
<?php } elseif (!$disciplines) { ?>
    <p class="text-muted">Adicione as disciplinas da turma (com os professores) antes de montar o horário.</p>
<?php } else { ?>
    <form id="formClassSchedule">
        <input type="hidden" name="classId" value="<?= (int) $this->view->classId ?>">
        <input type="hidden" name="grid" value="1">
        <p class="text-muted small mb-2">Escolha a disciplina de cada horário; deixe "—" onde não há aula. O sistema avisa se o professor já tiver aula em outra turma no mesmo horário.</p>
        <?php if ($this->view->scheduleHomeroom) { ?>
            <p class="small mb-2"><strong>Fund. 1:</strong> marque só as aulas dos especialistas. Os horários com "—" aparecem para o aluno como aula do(a) titular, <?= htmlspecialchars($this->view->scheduleHomeroom) ?>.</p>
        <?php } ?>
        <div class="m-schedule-editor">
            <?php foreach ($this->view->scheduleDays as $day => $dayName) { ?>
                <fieldset class="card p-2 m-schedule-day">
                    <legend class="font-weight-bold h6 px-1 mb-1"><?= htmlspecialchars($dayName) ?></legend>
                    <?php foreach ($slots as $slot) { $current = $grid[$day][(int) $slot->id] ?? 0; ?>
                        <label class="mb-1 small d-block">
                            <span class="text-muted"><?= htmlspecialchars($slot->start) ?>–<?= htmlspecialchars($slot->finish) ?></span>
                            <select class="form-control custom-select" name="cell[<?= (int) $day ?>][<?= (int) $slot->id ?>]" style="min-height:44px">
                                <option value="">—</option>
                                <?php foreach ($disciplines as $d) { ?>
                                    <option value="<?= (int) $d->id ?>" <?= (int) $d->id === $current ? 'selected' : '' ?>><?= htmlspecialchars($d->discipline) ?> · <?= htmlspecialchars($d->teacher) ?></option>
                                <?php } ?>
                            </select>
                        </label>
                    <?php } ?>
                </fieldset>
            <?php } ?>
        </div>
        <div class="d-flex justify-content-end mt-3">
            <button type="button" class="btn btn-success" id="buttonSaveSchedule" style="min-height:44px">Salvar horário</button>
        </div>
    </form>
<?php } ?>
