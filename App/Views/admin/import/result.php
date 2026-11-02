<?php
$result = $this->view->result;
$type = $this->view->type;
$noun = ['alunos' => 'aluno(s) cadastrado(s)', 'professores' => 'professor(es) cadastrado(s)', 'turmas' => 'turma(s) criada(s)'][$type];
?>

<section id="import">
    <div class="row main-container">
        <div class="col-lg-11 mx-auto">

            <div class="m-page-head">
                <div>
                    <a href="/admin/importacao?tipo=<?= htmlspecialchars($type) ?>" class="m-obs-back"><i class="fas fa-arrow-left mr-2"></i>Importação</a>
                    <h5>Importação concluída</h5>
                    <p><?= htmlspecialchars($this->view->fileName) ?> · registrada na auditoria</p>
                </div>
            </div>

            <div class="m-imp-alert is-ok" role="status">
                <i class="fas fa-check-circle"></i>
                <span><strong><?= $result['created'] ?></strong> <?= $noun ?><?= $type === 'alunos' ? ' e <strong>' . $result['enrolled'] . '</strong> matrícula(s) no período ativo' : '' ?>.</span>
            </div>

            <?php if ($result['codes']) { ?>
                <div class="card m-panel">
                    <div class="m-panel-head">
                        <h6>Códigos de acesso gerados</h6>
                        <a href="/admin/importacao/codigos" class="btn btn-primary btn-sm"><i class="fas fa-download mr-2"></i>Baixar CSV</a>
                    </div>
                    <p class="m-obs-note">Usados no login por nome + código (quando o Clerk está desligado). Entregue a cada pessoa o próprio código. Esta lista só aparece agora; o download funciona uma vez.</p>
                    <div class="m-obs-scroll">
                        <table class="m-obs-table">
                            <thead><tr><th>Nome</th><th>Código</th></tr></thead>
                            <tbody>
                                <?php foreach ($result['codes'] as [$name, $code]) { ?>
                                    <tr><td data-label="Nome"><?= htmlspecialchars($name) ?></td><td data-label="Código"><code><?= htmlspecialchars($code) ?></code></td></tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php } ?>

            <div class="m-imp-actions">
                <a href="/admin/importacao?tipo=<?= htmlspecialchars($type) ?>" class="btn btn-light"><i class="fas fa-upload mr-2"></i>Importar outro arquivo</a>
                <a href="<?= ['alunos' => '/admin/aluno/lista', 'professores' => '/admin/professor/lista', 'turmas' => '/admin/gestao/turma'][$type] ?>" class="btn btn-light">Ver cadastros</a>
            </div>

        </div>
    </div>
</section>
