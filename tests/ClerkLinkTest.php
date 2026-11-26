<?php
// Vínculo Clerk ↔ cadastro (PortalAccount::findId) contra o banco de dev, numa transação desfeita no fim.
// Uso (dentro do container): php tests/ClerkLinkTest.php (sai com código 1 se falhar)
require __DIR__ . '/../vendor/autoload.php';

use App\Auth\PortalAccount as PA;

$db = \App\Connection::getDb();
$fail = 0;
function check($name, $ok) { global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $name . PHP_EOL; if (!$ok) $fail++; }

$db->beginTransaction();
try {
    $admin = (int) $db->query("SELECT id_administrador FROM administrador ORDER BY id_administrador LIMIT 1")->fetchColumn();
    $db->prepare("UPDATE administrador SET email_administrador = ' Pessoa.Teste@Example.com ', clerk_user_id = NULL WHERE id_administrador = ?")->execute([$admin]);
    $link = function () use ($db, $admin) { $s = $db->prepare("SELECT clerk_user_id FROM administrador WHERE id_administrador = ?"); $s->execute([$admin]); return $s->fetchColumn(); };
    $audits = function () use ($db, $admin) { $s = $db->prepare("SELECT count(*) FROM auditoria WHERE acao = 'VINCULO_TROCADO' AND entidade_id = ?"); $s->execute([(string) $admin]); return (int) $s->fetchColumn(); };

    check('sem e-mail verificado não entra', PA::findId('admin', 'user_A', []) === null && $link() === null);
    check('e-mail de outra pessoa não entra', PA::findId('admin', 'user_A', ['outra@example.com']) === null);
    check('primeiro acesso liga pelo e-mail (maiúsculas e espaços não importam)', PA::findId('admin', 'user_A', ['pessoa.teste@example.com']) === $admin && $link() === 'user_A');
    check('mesma conta entra de novo', PA::findId('admin', 'user_A', ['pessoa.teste@example.com']) === $admin);
    check('primeiro vínculo não grava VINCULO_TROCADO', $audits() === 0);

    // conta do Clerk recriada / instância trocada: id novo, mesmo e-mail verificado
    check('conta nova com o mesmo e-mail entra (antes: sem-cadastro)', PA::findId('admin', 'user_B', ['pessoa.teste@example.com']) === $admin && $link() === 'user_B');
    check('a troca de conta vai para a auditoria', $audits() === 1);
    check('a conta antiga perde o acesso', PA::findId('admin', 'user_A', ['outra@example.com']) === null);

    // conta ligada cujo e-mail deixou de estar verificado (ou foi trocado no Clerk): desliga e recusa
    check('conta ligada sem o e-mail do cadastro é recusada', PA::findId('admin', 'user_B', ['nova@example.com']) === null && $link() === null);

    // o admin troca o e-mail do cadastro: o gatilho da migração 35 limpa o vínculo
    PA::findId('admin', 'user_B', ['pessoa.teste@example.com']);
    $db->prepare("UPDATE administrador SET email_administrador = 'novo.email@example.com' WHERE id_administrador = ?")->execute([$admin]);
    check('trocar o e-mail do cadastro tira o vínculo antigo', $link() === null);
    check('quem tem o e-mail novo entra', PA::findId('admin', 'user_C', ['novo.email@example.com']) === $admin);
    check('o e-mail antigo não entra mais', PA::findId('admin', 'user_B', ['pessoa.teste@example.com']) === null);

    // professor e aluno usam a mesma regra
    $teacher = (int) $db->query("SELECT id_professor FROM professor ORDER BY id_professor LIMIT 1")->fetchColumn();
    $db->prepare("UPDATE professor SET email_professor = 'prof.teste@example.com', clerk_user_id = 'user_velho' WHERE id_professor = ?")->execute([$teacher]);
    check('docente com vínculo antigo entra com a conta nova', PA::findId('docente', 'user_novo', ['prof.teste@example.com']) === $teacher);
} finally {
    $db->rollBack();
}

echo PHP_EOL . ($fail ? "$fail falha(s)" : 'tudo ok') . PHP_EOL;
exit($fail ? 1 : 0);
