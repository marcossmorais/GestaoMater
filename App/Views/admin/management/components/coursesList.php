<?php

if (count($this->view->listCourse) >= 1) {

    foreach ($this->view->listCourse as $i => $course) { ?>

        <form id="formCourse<?= htmlspecialchars((string) $course->course_id) ?>" class="card mb-4" action="">

            <div class="form-row d-flex align-items-center col-lg-11 mx-auto option-icon-group">

                <input type="hidden" name="courseId" value="<?= htmlspecialchars((string) $course->course_id) ?>">

                <div class="col-sm-8 font-weight-bold">Segmento: <?= htmlspecialchars((string) $course->course_name) ?>
                </div>

                <div class="col-sm-4 d-flex justify-content-end mt-2">

                    <span idElement="#formCourse<?= htmlspecialchars((string) $course->course_id) ?>" formGroup="containerListCourse" class="mr-2 edit-data-icon" data-toggle="tooltip" data-placement="left" title="Editar">

                        <i class="fas fa-edit"></i>

                    </span>

                    <span idElement="#formCourse<?= htmlspecialchars((string) $course->course_id) ?>" routeUpdate="/admin/gestao/curso/atualizar" toastData="Segmento atualizado" container="containerListCourse" routeList="/admin/gestao/curso/lista" class="mr-2 update-data-icon" data-toggle="tooltip" data-placement="bottom" title="Atualizar">

                        <i class="fas fa-check"></i>

                    </span>

                </div>

            </div>

            <div class="form-row mt-4 mb-2 col-lg-11 mx-auto">

                <div class="form-group col-lg-4">
                    <label for="">Nome do segmento:</label>
                    <input class="form-control" disabled value="<?= htmlspecialchars((string) $course->course_name) ?>" type="text" name="courseName" id="">
                </div>

                <div class="form-group col-lg-2">
                    <label for="">Sigla:</label>
                    <input class="form-control" maxlength="4" disabled onkeyup="this.value = this.value.toUpperCase()" value="<?= htmlspecialchars((string) $course->acronym) ?>" type="text" name="acronym" id="">
                </div>

                <div class="form-group col-lg-3">

                    <label for="">Modalidade:</label>

                    <select name="courseMode" id="courseMode" disabled class="form-control custom-select" required>

                        <option value="<?= htmlspecialchars((string) $course->course_mode_id) ?>"><?= htmlspecialchars((string) $course->course_mode) ?></option>

                        <?php foreach ($this->view->courseMode as $key => $value) { ?>
                            <?php if ($course->course_mode_id != $value->option_value) { ?>
                                <option value="<?= htmlspecialchars((string) $value->option_value) ?>"><?= htmlspecialchars((string) $value->option_text) ?></option>
                            <?php } ?>
                        <?php } ?>

                    </select>
                </div>

                <div class="form-group col-lg-3">
                    <label for="">Etapa:</label>
                    <?php if ((int) $course->class_total > 0) { ?>
                        <?php // com turma cadastrada a etapa fica travada: as turmas usam as séries dela ?>
                        <input class="form-control" disabled readonly value="<?= htmlspecialchars(\App\Models\GeneralManagement\Course::STAGES[$course->stage] ?? $course->stage) ?>" title="Tem turma cadastrada: a etapa não muda">
                    <?php } else { ?>
                        <select name="stage" disabled class="form-control custom-select">
                            <?php foreach (\App\Models\GeneralManagement\Course::STAGES as $key => $label) { ?>
                                <option value="<?= htmlspecialchars($key) ?>" <?= $course->stage === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php } ?>
                        </select>
                    <?php } ?>
                </div>

            </div>

        </form>

    <?php }
} else { ?>

    <h5 class="mt-3">Nenhum segmento cadastrado</h5>

<?php } ?>