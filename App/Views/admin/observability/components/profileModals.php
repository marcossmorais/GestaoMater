<?php
/**
 * Modais de perfil do aluno e do docente (os mesmos das listas), abertos por [data-student-profile] e [data-teacher-profile]
 */
foreach (['profileStudentModal' => 'containerStudentProfileModal', 'profileTeacherModal' => 'containerTeacherProfileModal'] as $modal => $container) { ?>
    <div class="modal fade modal-profile" id="<?= $modal ?>" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-full">
            <div class="modal-content">
                <div class="row">
                    <div class="col-lg-12">
                        <button type="button" class="close text-rig" data-dismiss="modal" aria-label="Fechar">
                            <span aria-hidden="true"><i class="fas fa-times-circle text-info mr-3 mt-2"></i></span>
                        </button>
                    </div>
                </div>
                <div <?= $container ?> class="modal-body"></div>
            </div>
        </div>
    </div>
<?php } ?>
