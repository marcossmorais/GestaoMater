<?php
// Mural: comunicados da coordenação sobre o aluno (elogio, disciplina…), com ciência dos responsáveis
$occurrences = $this->view->listOccurrences ?? [];
if ($occurrences) { ?>
<h5 class="title-mural mt-4"><i class="fas fa-flag mr-2"></i>Comunicados sobre você</h5>
<?php }
foreach ($occurrences as $occ) {
    $icon = 'fa-info-circle text-secondary';
    $badgeStyle = 'badge-secondary';
    if ($occ->categoria === 'Elogio') {
        $icon = 'fa-award text-success';
        $badgeStyle = 'badge-success';
    } else if ($occ->categoria === 'Disciplinar') {
        $icon = 'fa-exclamation-triangle text-warning';
        $badgeStyle = 'badge-warning text-dark';
    } else if ($occ->categoria === 'Pedagógica') {
        $icon = 'fa-book-reader text-primary';
        $badgeStyle = 'badge-primary';
    } else if ($occ->categoria === 'Saúde') {
        $icon = 'fa-heartbeat text-danger';
        $badgeStyle = 'badge-danger';
    }
?>
    <div class="col-11 mx-auto card mb-3 shadow-sm border" style="border-radius: 12px;" data-portal-occ="<?= $occ->id_ocorrencia ?>">
        <div class="row p-3">
            <div class="col-sm-1 d-flex justify-content-center align-items-center">
                <i class="fas <?= $icon ?> fa-2x"></i>
            </div>
            <div class="col-sm-11">
                <div class="d-flex align-items-center justify-content-between flex-wrap">
                    <div>
                        <span class="badge <?= $badgeStyle ?> mr-2"><?= $occ->categoria ?></span>
                        <strong class="text-dark"><?= htmlspecialchars($occ->titulo) ?></strong>
                    </div>
                    <small class="text-muted"><i class="fas fa-calendar-alt mr-1"></i><?= $occ->data_ocorrencia_fmt ?></small>
                </div>
                <p class="mt-2 text-muted mb-2"><?= nl2br(htmlspecialchars($occ->descricao)) ?></p>

                <?php if (!empty($occ->medida_tomada)) { ?>
                    <div class="p-2 mb-2 rounded bg-light small">
                        <strong>Providência / Medida:</strong> <?= htmlspecialchars($occ->medida_tomada) ?>
                    </div>
                <?php } ?>

                <div class="d-flex align-items-center justify-content-between flex-wrap mt-2 pt-2 border-top">
                    <small class="text-muted"><i class="fas fa-user-edit mr-1"></i>Registrado por <?= htmlspecialchars($occ->registrado_por) ?></small>

                    <?php if ($occ->requer_ciencia) { ?>
                        <div id="occAckBox_<?= $occ->id_ocorrencia ?>">
                            <?php if ($occ->ciencia_responsavel) { ?>
                                <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i> Ciência dos Pais Confirmada</span>
                            <?php } else { ?>
                                <button type="button" class="btn btn-sm btn-outline-success font-weight-bold" onclick="acknowledgeOccurrence(<?= $occ->id_ocorrencia ?>)">
                                    <i class="fas fa-signature mr-1"></i> Confirmar Ciência dos Responsáveis
                                </button>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

<?php }
