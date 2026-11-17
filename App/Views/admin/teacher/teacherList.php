<section id="list-teacher">

    <div class="row main-container">

        <div class="col-lg-11 mx-auto">

            <h5 class="col-12 mb-4 mt-3">Lista de professores(a)</h5>

            <div class="col-lg-12">

                <div class="card p-3 mb-3" id="advanced-search-accordion">

                    <form class="col-lg-11 accordion mx-auto mt-3" id="seekTeacher">

                        <div class="form-row">

                            <div class="form-group col-12 col-sm-4">
                                <label for="name">Professor:</label>
                                <input class="form-control" type="text" name="name" placeholder="Nome do professor" id="">
                            </div>


                            <div class="form-group col-10 col-sm-4">
                                <label for="course">Curso:</label>
                                <select class="form-control custom-select" name="course">
                                    <option value="0">Todos</option>
                                    <?php foreach ($this->view->listCourse as $key => $course) { ?>
                                        <option value="<?= htmlspecialchars((string) $course->option_value) ?>"><?= htmlspecialchars((string) $course->option_text) ?></option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="form-group col-10 col-sm-3">
                                <label for="discipline">Disciplina:</label>
                                <select class="form-control custom-select" name="discipline">
                                    <option value="0">Todas</option>
                                    <?php foreach ($this->view->listDiscipline as $key => $discipline) { ?>
                                        <option value="<?= htmlspecialchars((string) $discipline->discipline_id) ?>"><?= htmlspecialchars((string) $discipline->discipline_name) ?></option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="form-group col-2 col-lg-1">
                                <label for="">&nbsp;</label><br>
                                <div>
                                    <a class="btn btn-white w-100 p-2" href="" data-toggle="collapse" data-target="#activate-advanced-search-accordion" aria-expanded="false" aria-controls="activate-advanced-search-accordion"><i class="fas fa-ellipsis-h"></i></a>
                                </div>
                            </div>


                        </div>

                        <div id="activate-advanced-search-accordion" class="collapse" data-parent="#advanced-search-accordion">

                            <div class="form-row">

                                <div class="form-group col-10 col-sm-3">
                                    <label for="serie">Série:</label>
                                    <select class="form-control custom-select" name="serie">
                                        <option value="0">Todas</option>
                                        <?php foreach ($this->view->availableSeries as $key => $serie) { ?>
                                            <option value="<?= htmlspecialchars((string) $serie->option_value) ?>"><?= htmlspecialchars((string) $serie->option_text) ?></option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <div class="form-group col-10 col-sm-3">
                                    <label for="shift">Turno:</label>
                                    <select class="form-control custom-select" name="shift">
                                        <option value="0">Todos</option>
                                        <?php foreach ($this->view->availableShift as $key => $shift) { ?>
                                            <option value="<?= htmlspecialchars((string) $shift->option_value) ?>"><?= htmlspecialchars((string) $shift->option_text) ?></option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <div class="form-group col-10 col-sm-3">
                                    <label for="sex">Sexo:</label>
                                    <select class="form-control custom-select" name="sex">
                                        <option value="0">Todos</option>
                                        <?php foreach ($this->view->availableSex as $key => $sex) { ?>
                                            <option value="<?= htmlspecialchars((string) $sex->option_value) ?>"><?= htmlspecialchars((string) $sex->option_text) ?></option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <div class="form-group col-10 col-sm-3">
                                    <label for="classroom">Sala:</label>
                                    <select class="form-control custom-select" name="classRoom">
                                        <option value="0">Todas</option>
                                        <?php foreach ($this->view->availableClassRoom as $key => $classroom) { ?>
                                            <option value="<?= htmlspecialchars((string) $classroom->option_value) ?>"><?= htmlspecialchars((string) $classroom->option_text) ?></option>
                                        <?php } ?>
                                    </select>
                                </div>

                            </div>

                        </div>


                    </form>

                    <hr class="col-lg-11 mx-auto">

                    <div class="table-responsive">

                        <table class="table table-hover mt-3 table-borderless col-lg-11 mx-auto" id="teacher-table">

                            <thead>
                                <tr>
                                    <th class="" colspan="2" scope="col">Nome do professor(a)</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Status da conta</th>
                                    <th scope="col">Disciplinas ativas</th>
                                </tr>
                            </thead>

                            <tbody containerListTeacher>

                                <?php require '../App/Views/admin/teacher/components/teacherListing.php' ?>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal modal-profile fade" id="profileTeacherModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                <div class="modal-dialog modal-full">
                    <div class="modal-content p-2">
                        <div class="row">
                            <div class="col-lg-12"> <button type="button" class="close text-rig" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true"><i class="fas fa-times-circle text-info mr-3 mt-2"></i></span>
                                </button></div>
                        </div>

                        <div containerTeacherProfileModal class="modal-body"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</section>