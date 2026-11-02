<?php
require __DIR__ . '/../../config/variables.php';

$teacher = $this->view->Data[0];
$photoDir = "$app_url/assets/img/teacherProfilePhotos/";
$photo = $teacher->profile_photo ? $photoDir . $teacher->profile_photo : $photoDir . 'foto-vazia.jpg';
$cpf = preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', str_pad((string) $teacher->cpf, 11, '0', STR_PAD_LEFT));
$birth = $teacher->birth_date ? date('d/m/Y', strtotime($teacher->birth_date)) : '–';
?>

<section id="teacherProfile" class="m-profile-page">

    <div class="row main-container">

        <div class="col-lg-11 mx-auto">

            <header class="m-profile-head">
                <img src="<?= htmlspecialchars($photo) ?>" alt="" onerror="this.src='<?= $photoDir ?>foto-vazia.jpg'">
                <div>
                    <h5><?= htmlspecialchars($teacher->name) ?></h5>
                    <p><?= htmlspecialchars($teacher->hierarchy_function) ?></p>
                </div>
                <button type="button" class="btn btn-light" data-profile-edit><i class="fas fa-pen mr-2"></i>Editar</button>
            </header>

            <form id="teacherProfileForm" class="m-profile-grid" novalidate>

                <section class="m-profile-box m-profile-wide">
                    <h6>Contato e endereço</h6>
                    <div class="m-form-grid">
                        <label class="m-att-field m-span-2"><span class="m-att-label">E-mail</span><input class="form-control" name="email" type="email" value="<?= htmlspecialchars($teacher->email) ?>" disabled></label>
                        <label class="m-att-field"><span class="m-att-label">Telefone</span><input class="form-control" id="telephoneNumber" name="telephoneNumber" value="<?= htmlspecialchars($teacher->telephone_number) ?>" disabled inputmode="tel"></label>
                        <label class="m-att-field"><span class="m-att-label">CEP</span><input class="form-control" id="zipCode" name="zipCode" value="<?= htmlspecialchars($teacher->zip_code) ?>" disabled inputmode="numeric"></label>
                        <label class="m-att-field m-span-2"><span class="m-att-label">Endereço</span><input class="form-control" name="address" value="<?= htmlspecialchars($teacher->address) ?>" disabled></label>
                        <label class="m-att-field"><span class="m-att-label">Bairro</span><input class="form-control" name="district" value="<?= htmlspecialchars($teacher->district) ?>" disabled></label>
                        <label class="m-att-field"><span class="m-att-label">Município</span><input class="form-control" name="county" value="<?= htmlspecialchars($teacher->county) ?>" disabled></label>
                        <label class="m-att-field"><span class="m-att-label">UF</span><input class="form-control" name="uf" value="<?= htmlspecialchars($teacher->uf) ?>" maxlength="2" disabled></label>
                    </div>
                </section>

                <section class="m-profile-box">
                    <h6>Dados pessoais <small>Para corrigir, fale com a secretaria.</small></h6>
                    <dl class="m-data-list m-data-list-3">
                        <div><dt>Nome</dt><dd><?= htmlspecialchars($teacher->name) ?></dd></div>
                        <div><dt>CPF</dt><dd><?= htmlspecialchars($cpf) ?></dd></div>
                        <div><dt>Nascimento</dt><dd><?= htmlspecialchars($birth) ?></dd></div>
                        <div><dt>Sexo</dt><dd><?= htmlspecialchars($teacher->sex) ?></dd></div>
                        <div><dt>Nacionalidade</dt><dd><?= htmlspecialchars($teacher->nationality) ?></dd></div>
                        <div><dt>Naturalidade</dt><dd><?= htmlspecialchars($teacher->naturalness) ?></dd></div>
                    </dl>
                </section>

                <div class="m-profile-stack">
                    <section class="m-profile-box">
                        <h6>Saúde e acessibilidade</h6>
                        <div class="m-form-grid m-form-grid-2">
                            <label class="m-att-field"><span class="m-att-label">Tipo sanguíneo</span>
                                <select class="form-control custom-select" name="bloodType" disabled>
                                    <option value="<?= (int) $teacher->blood_type_id ?>"><?= htmlspecialchars($teacher->blood_type) ?></option>
                                    <?php foreach ($this->view->bloodType as $b) { if ($b->option_value == $teacher->blood_type_id) continue; ?><option value="<?= (int) $b->option_value ?>"><?= htmlspecialchars($b->option_text) ?></option><?php } ?>
                                </select>
                            </label>
                            <label class="m-att-field"><span class="m-att-label">Pessoa com deficiência</span>
                                <select class="form-control custom-select" name="pcd" disabled>
                                    <option value="<?= (int) $teacher->pcd_id ?>"><?= htmlspecialchars($teacher->pcd) ?></option>
                                    <?php foreach ($this->view->pcd as $p) { if ($p->option_value == $teacher->pcd_id) continue; ?><option value="<?= (int) $p->option_value ?>"><?= htmlspecialchars($p->option_text) ?></option><?php } ?>
                                </select>
                            </label>
                        </div>
                    </section>

                    <section class="m-profile-box">
                        <h6>Acesso ao portal</h6>
                        <label class="m-att-field"><span class="m-att-label">Código de acesso</span>
                            <span class="m-code-input">
                                <input class="form-control" name="accessCode" type="password" value="<?= htmlspecialchars($teacher->access_code) ?>" maxlength="30" disabled autocomplete="off">
                                <button type="button" class="m-icon-btn" data-reveal aria-label="Mostrar código"><i class="fas fa-eye"></i></button>
                            </span>
                        </label>
                    </section>
                </div>

                <div class="m-att-bar" data-profile-bar hidden>
                    <div class="m-att-bar-count"><span class="m-att-count">Editando seus dados</span></div>
                    <div class="m-profile-actions">
                        <button type="button" class="btn btn-light" data-profile-cancel>Cancelar</button>
                        <button type="submit" class="btn btn-primary" data-profile-save><i class="fas fa-check mr-2"></i>Salvar</button>
                    </div>
                </div>
            </form>

        </div>
    </div>

</section>

<script src="<?= asset('/assets/js/teacherProfile.js') ?>" defer></script>
