<?php

require __DIR__ . '../../../../../config/variables.php';

// Turmas distintas em que o docente leciona (a lista vem por disciplina)
$classVector = [];

foreach ($this->view->subjectsThatTeacherTeaches as $data) {

    $class = [$data->series_acronym, $data->ballot, $data->course, $data->shift_name, $data->classroom_number, $data->class_id];

    if (!in_array($class, $classVector)) {
        array_push($classVector, $class);
    }
}

?>

<div class="row mb-4 d-flex justify-content-center" id="main-accordion">

    <?php foreach ($this->view->teacherProfile as $key => $teacher) { ?>

        <?php $photoDir =  "$app_url/assets/img/teacherProfilePhotos/" ?>

        <div class="col-lg-3 col-12 col-sm-4">

            <div class="col-lg-12 modal-sidebar m-prof-side">

                <div class="row p-3">

                    <div class="col-lg-12">

                        <div class="m-prof-hero">

                            <div class="m-prof-avatar" data-toggle="tooltip" data-placement="bottom" title="Alterar foto">
                                <img src='<?= $teacher->profile_photo == null ? $photoDir . "foto-vazia.jpg" : $photoDir . $teacher->profile_photo ?>' onerror='this.src="<?= $photoDir . "foto-vazia.jpg" ?>"' data-toggle="modal" data-target="#profilePhotoModal" alt="Foto de <?= $teacher->name ?>">
                                <span class="m-prof-avatar-edit"><i class="fas fa-camera"></i></span>
                            </div>

                            <span class="m-prof-name"><?= $teacher->name ?></span>
                            <span class="m-prof-sub">Docente</span>

                            <div class="m-prof-chips">
                                <span class="m-prof-chip <?= $teacher->account_state_id == 1 ? 'is-ok' : 'is-off' ?>"><?= $teacher->account_state ?></span>
                                <span class="m-prof-chip"><i class="fas fa-book"></i> <?= count($this->view->subjectsThatTeacherTeaches) ?> <?= count($this->view->subjectsThatTeacherTeaches) == 1 ? 'disciplina' : 'disciplinas' ?></span>
                            </div>

                        </div>

                        <div class="container-list-group">

                            <nav>

                                <span class="m-prof-nav-label">Perfil</span>

                                <ul>

                                    <a href="#" data-toggle="collapse" aria-expanded="true" data-target="#accordion-data-teacher">
                                        <span class="box-icon"><i class="fas fa-user"></i></span> Dados do docente
                                    </a>

                                    <a class="collapsed" href="#" data-toggle="collapse" aria-expanded="false" data-target="#class-teacher-accordion">
                                        <span class="box-icon"><i class="fas fa-chalkboard-teacher"></i></span> Turmas
                                    </a>

                                </ul>

                            </nav>

                        </div>

                    </div>

                    <div class="modal fade border border-dark" id="profilePhotoModal" tabindex="-1" aria-labelledby="profilePhotoModal" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header border-0">
                                    <h5 class="modal-title" id="profilePhotoModal">Foto do perfil</h5>
                                    <div class="row">
                                        <div class="col-lg-12"> <button type="button" class="close text-rig close-modal" data-toggle="modal" data-target="#profilePhotoModal">
                                                <span><i class="fas fa-times-circle text-dark mr-3 mt-2"></i></span>
                                            </button></div>
                                    </div>
                                </div>

                                <div class="modal-body">

                                    <div class="container-img col-lg-12 d-flex justify-content-center">

                                        <img class="mx-auto" src='<?= $teacher->profile_photo == null ? $photoDir . "foto-vazia.jpg" : $photoDir . $teacher->profile_photo ?>' onerror='this.src="<?= $photoDir . "foto-vazia.jpg" ?>" '>

                                    </div>

                                    <p class="m-prof-photo-hint">Clique na foto para escolher uma nova imagem</p>

                                    <form class="" id="formUpdateProfilePhoto" method="POST" enctype="multipart/form-data">

                                        <input type="file" id="profilePhoto" name="profilePhoto" accept="image/*" id="">

                                        <input type="hidden" id="id" value="<?= $teacher->id ?>" name="id">

                                        <input type="hidden" id="oldPhoto" name="oldPhoto" value="<?= $teacher->profile_photo ?>">

                                        <div class="row">

                                            <div class="col-lg-12 mt-3 d-flex justify-content-end">

                                                <button id="updateImg" type="submit" disabled class="btn btn-success">Atualizar foto</button>

                                            </div>

                                        </div>

                                    </form>

                                </div>

                                <div class="modal-footer d-flex justify-content-start">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Retornar</button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-9 col-sm-8 col-12 mx-auto">

            <div class="col-lg-12 card main-content m-prof-main">

                <div class="row">

                    <div class="col-12 m-prof-pane collapse show" id="accordion-data-teacher" data-parent="#main-accordion">

                        <form id="teacherModal<?= $teacher->id ?>" class="m-prof-form" action="">

                            <input type="hidden" value="<?= $teacher->id ?>" name="teacherId">
                            <input type="hidden" value="<?= $teacher->telephone_id ?>" name="telephoneId">
                            <input type="hidden" value="<?= $teacher->address_id ?>" name="addressId">

                            <div class="m-prof-head">

                                <div>
                                    <h4>Dados do docente</h4>
                                    <p>Informações pessoais, contato e acesso ao portal</p>
                                </div>

                                <div class="m-prof-actions">

                                    <span idElement="#teacherModal<?= $teacher->id ?>" formGroup="containerListTeacher" class="m-prof-btn edit-data-icon"><i class="fas fa-pen"></i> Editar</span>

                                    <span idElement="#teacherModal<?= $teacher->id ?>" routeUpdate="/admin/professor/lista/perfil-professor/atualizar" toastData="Dados atualizados" routeData="#teacherModal<?= $teacher->id ?>" container="containerTeacherProfileModal" routeList="/admin/professor/lista/perfil-professor" class="m-prof-btn is-primary update-data-icon"><i class="fas fa-check"></i> Salvar</span>

                                </div>

                            </div>

                            <div class="m-prof-editing"><i class="fas fa-pen"></i> Modo de edição: altere os campos e clique em Salvar.</div>

                            <div class="m-prof-kpis">

                                <a class="m-prof-kpi" href="#" data-toggle="collapse" data-target="#class-teacher-accordion">
                                    <span class="m-prof-kpi-icon"><i class="fas fa-users"></i></span>
                                    <span><span class="m-prof-kpi-label">Turmas</span><span class="m-prof-kpi-value"><?= count($classVector) ?></span></span>
                                </a>

                                <a class="m-prof-kpi" href="#" data-toggle="collapse" data-target="#class-teacher-accordion">
                                    <span class="m-prof-kpi-icon is-green"><i class="fas fa-book"></i></span>
                                    <span><span class="m-prof-kpi-label">Disciplinas</span><span class="m-prof-kpi-value"><?= count($this->view->subjectsThatTeacherTeaches) ?></span></span>
                                </a>

                                <div class="m-prof-kpi">
                                    <span class="m-prof-kpi-icon is-gold"><i class="fas fa-calendar-alt"></i></span>
                                    <span><span class="m-prof-kpi-label">No colégio desde</span><span class="m-prof-kpi-value"><?= date("m/Y", strtotime($teacher->initial_enrollment_date)) ?></span></span>
                                </div>

                            </div>

                            <section class="m-prof-section">

                                <div class="m-prof-section-head">
                                    <span class="m-prof-section-icon"><i class="fas fa-id-card"></i></span>
                                    <h6>Informações pessoais</h6>
                                </div>

                                <div class="m-prof-grid">

                                    <div class="m-prof-field m-span-2">
                                        <label for="name">Nome completo</label>
                                        <input type="text" id="name" name="name" disabled class="form-control" value="<?= $teacher->name ?>">
                                    </div>

                                    <div class="m-prof-field">
                                        <label for="cpf">CPF</label>
                                        <input type="text" id="cpf" name="cpf" disabled class="form-control" value="<?= $teacher->cpf ?>">
                                    </div>

                                    <div class="m-prof-field">
                                        <label for="birthDate">Data de nascimento</label>
                                        <input type="date" id="birthDate" name="birthDate" disabled class="form-control" value="<?= $teacher->birth_date ?>" max="2006-01-31" min="1940-01-31">
                                    </div>

                                    <div class="m-prof-field">
                                        <label for="sex">Sexo</label>
                                        <select id="sex" name="sex" disabled class="form-control custom-select">
                                            <option value="<?= $teacher->sex_id ?>"><?= $teacher->sex ?></option>
                                            <?php foreach ($this->view->availableSex as $key => $sex) { ?>
                                                <?php if ($sex->option_value != $teacher->sex_id) { ?>
                                                    <option value="<?= $sex->option_value ?>"><?= $sex->option_text ?></option>
                                            <?php }
                                            } ?>
                                        </select>
                                    </div>

                                    <div class="m-prof-field">
                                        <label for="nationality">Nacionalidade</label>
                                        <input type="text" id="nationality" name="nationality" disabled class="form-control" value="<?= $teacher->nationality ?>">
                                    </div>

                                    <div class="m-prof-field">
                                        <label for="naturalness">Naturalidade</label>
                                        <input type="text" id="naturalness" name="naturalness" disabled class="form-control" value="<?= $teacher->naturalness ?>">
                                    </div>

                                    <div class="m-prof-field">
                                        <label for="pcd">PcD</label>
                                        <select id="pcd" name="pcd" disabled class="form-control custom-select">
                                            <option value="<?= $teacher->pcd_id ?>"><?= $teacher->pcd ?></option>
                                            <?php foreach ($this->view->pcd as $key => $pcd) { ?>
                                                <?php if ($pcd->option_value != $teacher->pcd_id) { ?>
                                                    <option value="<?= $pcd->option_value ?>"><?= $pcd->option_text ?></option>
                                            <?php }
                                            } ?>
                                        </select>
                                    </div>

                                    <div class="m-prof-field">
                                        <label for="bloodType">Tipo sanguíneo</label>
                                        <select id="bloodType" name="bloodType" disabled class="form-control custom-select">
                                            <option value="<?= $teacher->blood_type_id ?>"><?= $teacher->blood_type ?></option>
                                            <?php foreach ($this->view->bloodType as $key => $bloodType) { ?>
                                                <?php if ($bloodType->option_value != $teacher->blood_type_id) { ?>
                                                    <option value="<?= $bloodType->option_value ?>"><?= $bloodType->option_text ?></option>
                                            <?php }
                                            } ?>
                                        </select>
                                    </div>

                                </div>

                            </section>

                            <section class="m-prof-section">

                                <div class="m-prof-section-head">
                                    <span class="m-prof-section-icon"><i class="fas fa-map-marker-alt"></i></span>
                                    <h6>Endereço e contato</h6>
                                </div>

                                <div class="m-prof-grid">

                                    <div class="m-prof-field m-span-2">
                                        <label for="email">E-mail</label>
                                        <input type="email" id="email" name="email" disabled class="form-control" value="<?= $teacher->email ?>">
                                    </div>

                                    <div class="m-prof-field">
                                        <label for="telephoneNumber">Telefone</label>
                                        <input id="telephoneNumber" name="telephoneNumber" type="text" disabled class="form-control" value="<?= $teacher->telephone_number ?>">
                                    </div>

                                    <div class="m-prof-field">
                                        <label for="zipCode">CEP</label>
                                        <input type="text" id="zipCode" name="zipCode" disabled class="form-control" value="<?= $teacher->zip_code ?>">
                                    </div>

                                    <div class="m-prof-field">
                                        <label for="uf">UF</label>
                                        <input type="text" id="uf" name="uf" disabled class="form-control" value="<?= $teacher->uf ?>">
                                    </div>

                                    <div class="m-prof-field">
                                        <label for="county">Município</label>
                                        <input type="text" id="county" name="county" disabled class="form-control" value="<?= $teacher->county ?>">
                                    </div>

                                    <div class="m-prof-field">
                                        <label for="district">Bairro</label>
                                        <input type="text" id="district" name="district" disabled class="form-control" value="<?= $teacher->district ?>">
                                    </div>

                                    <div class="m-prof-field m-span-2">
                                        <label for="address">Endereço</label>
                                        <input type="text" id="address" name="address" disabled class="form-control" value="<?= $teacher->address ?>">
                                    </div>

                                </div>

                            </section>

                            <section class="m-prof-section">

                                <div class="m-prof-section-head">
                                    <span class="m-prof-section-icon"><i class="fas fa-user-shield"></i></span>
                                    <h6>Conta e acesso</h6>
                                </div>

                                <div class="m-prof-grid">

                                    <div class="m-prof-field">
                                        <label for="accountState">Situação da conta</label>
                                        <select id="accountState" name="accountState" disabled class="form-control custom-select">
                                            <option value="<?= $teacher->account_state_id ?>"><?= $teacher->account_state ?></option>
                                            <?php foreach ($this->view->accountStates as $key => $account) { ?>
                                                <?php if ($account->option_value != $teacher->account_state_id) { ?>
                                                    <option value="<?= $account->option_value ?>"><?= $account->option_text ?></option>
                                            <?php }
                                            } ?>
                                        </select>
                                    </div>

                                    <div class="m-prof-field">
                                        <label for="accessCode">Código de acesso ao portal</label>
                                        <div class="m-prof-secret">
                                            <input id="accessCode" name="accessCode" type="password" disabled class="form-control" value="<?= $teacher->access_code ?>" maxlength="30">
                                            <div class="input-group-accessCode" title="Mostrar/ocultar"><i class="fas fa-eye-slash"></i></div>
                                        </div>
                                    </div>

                                    <div class="m-prof-field">
                                        <label>Cadastrado no colégio em</label>
                                        <input type="text" disabled class="form-control" value="<?= date("d/m/Y - H:i", strtotime($teacher->initial_enrollment_date)) ?>" style="pointer-events:none">
                                    </div>

                                </div>

                            </section>

                        <?php } ?>

                        </form>

                    </div>

                    <div class="col-12 m-prof-pane collapse" id="class-teacher-accordion" data-parent="#main-accordion">

                        <div class="m-prof-head">
                            <div>
                                <h4>Turmas vinculadas</h4>
                                <p>Turmas e disciplinas do período letivo em que o docente leciona</p>
                            </div>
                        </div>

                        <?php if (count($classVector) > 0) { ?>

                            <div class="m-prof-classes">

                                <?php foreach ($classVector as $key => $data) { ?>

                                    <div class="m-prof-class">

                                        <div class="m-prof-class-title">
                                            <span class="m-prof-kpi-icon"><i class="fas fa-users"></i></span>
                                            <div>
                                                <div class="teacher-class-title mb-0"><?= $data[0] ?>ª <?= $data[1] ?> · <?= $data[2] ?></div>
                                                <div class="m-prof-class-meta"><?= $data[3] ?> · Sala <?= $data[4] ?></div>
                                            </div>
                                        </div>

                                        <div class="m-prof-tags">
                                            <?php foreach ($this->view->subjectsThatTeacherTeaches as $key => $discipline) {

                                                if ($discipline->class_id == $data[5]) { ?>

                                                    <span class="m-prof-tag"><?= $discipline->discipline_name ?></span>

                                            <?php }
                                            } ?>
                                        </div>

                                    </div>

                                <?php } ?>

                            </div>

                        <?php } else { ?>

                            <div class="m-empty">

                                <img class="link_discipline" src="<?= $app_url ?>/assets/img/illustrations/link_discipline.svg" alt="">

                                <p>Docente não está vinculado a nenhuma turma</p>

                                <a href="/admin/gestao/turma" class="btn btn-primary">Ir para turmas</a>

                            </div>

                        <?php } ?>

                    </div>

                </div>

            </div>
        </div>

</div>
