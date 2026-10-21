<?php

namespace App\Auth;

use App\Connection;

/**
 * Liga um usuário autenticado (Clerk) ao cadastro local de cada portal e
 * monta a sessão PHP no mesmo formato usado pelo restante do sistema.
 */
class PortalAccount
{
    const PORTALS = [
        'admin' => [
            'table' => 'administrador',
            'id' => 'id_administrador',
            'email' => 'email_administrador',
            'home' => '/admin/home',
            'login' => '/admin',
        ],
        'docente' => [
            'table' => 'professor',
            'id' => 'id_professor',
            'email' => 'email_professor',
            'home' => '/portal-docente/home',
            'login' => '/portal-docente',
        ],
        'aluno' => [
            'table' => 'aluno',
            'id' => 'id_aluno',
            'email' => 'email_aluno',
            'home' => '/portal-aluno/home',
            'login' => '/portal-aluno',
        ],
    ];

    public static function config($portal)
    {
        return self::PORTALS[$portal] ?? null;
    }

    /**
     * Procura o cadastro pelo id do Clerk já vinculado ou, no primeiro acesso,
     * por um dos e-mails verificados. Retorna o id local ou null.
     */
    public static function findId($portal, $clerkUserId, array $emails)
    {
        $c = self::config($portal);
        $db = Connection::getDb();

        $stmt = $db->prepare("SELECT {$c['id']} AS id FROM {$c['table']} WHERE clerk_user_id = :clerkId");
        $stmt->bindValue(':clerkId', $clerkUserId);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_OBJ);

        if ($row) return (int) $row->id;
        if (!$emails) return null;

        $placeholders = implode(',', array_fill(0, count($emails), '?'));
        $stmt = $db->prepare(
            "SELECT {$c['id']} AS id FROM {$c['table']}
             WHERE LOWER({$c['email']}) IN ($placeholders) AND clerk_user_id IS NULL"
        );
        $stmt->execute(array_values($emails));
        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);

        // e-mail duplicado entre cadastros: não dá para saber quem é, recusa
        if (count($rows) !== 1) return null;

        $id = (int) $rows[0]->id;

        $stmt = $db->prepare("UPDATE {$c['table']} SET clerk_user_id = :clerkId WHERE {$c['id']} = :id");
        $stmt->bindValue(':clerkId', $clerkUserId);
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        return $id;
    }

    /**
     * Cria a sessão do portal. Retorna uma mensagem de erro ou null em caso de sucesso.
     */
    public static function startSession($portal, $id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        session_regenerate_id(true);

        $db = Connection::getDb();

        switch ($portal) {

            case 'admin':
                $stmt = $db->prepare(
                    "SELECT id_administrador AS id, nome_administrador AS name, foto_perfil_administrador AS profile_photo,
                     fk_id_administrador_hierarquia_funcao AS hierarchy_function, fk_id_situacao_conta_administrador AS account_status
                     FROM administrador WHERE id_administrador = :id"
                );
                $stmt->execute([':id' => $id]);
                $a = $stmt->fetch(\PDO::FETCH_OBJ);

                if (!$a) return 'sem-cadastro';
                if ($a->account_status != 1) return 'conta-desativada';

                $_SESSION['Admin'] = [
                    'id' => $a->id,
                    'name' => implode(' ', array_slice(explode(' ', $a->name), 0, 3)),
                    'profilePhoto' => $a->profile_photo,
                    'hierarchyFunction' => $a->hierarchy_function,
                ];
                return null;

            case 'docente':
                $stmt = $db->prepare(
                    "SELECT id_professor AS id, nome_professor AS name, foto_perfil_professor AS profile_photo,
                     fk_id_professor_hierarquia_funcao AS hierarchy_function, fk_id_situacao_conta_professor AS account_status
                     FROM professor WHERE id_professor = :id"
                );
                $stmt->execute([':id' => $id]);
                $t = $stmt->fetch(\PDO::FETCH_OBJ);

                if (!$t) return 'sem-cadastro';
                if ($t->account_status != 1) return 'conta-desativada';

                $_SESSION['Teacher'] = [
                    'id' => $t->id,
                    'name' => $t->name,
                    'profilePhoto' => $t->profile_photo,
                    'hierarchyFunction' => $t->hierarchy_function,
                ];
                return null;

            case 'aluno':
                $stmt = $db->prepare(
                    "SELECT aluno.id_aluno AS id, aluno.nome_aluno AS name, aluno.foto_perfil_aluno AS profile_photo,
                     aluno.fk_id_aluno_hierarquia_funcao AS hierarchy_function, matricula.id_matricula AS enrollment_id,
                     serie.sigla AS acronym_series, cedula_turma.cedula AS ballot, curso.sigla AS course,
                     turno.nome_turno AS shift, turma.id_turma AS class_id
                     FROM aluno
                     INNER JOIN matricula ON (aluno.id_aluno = matricula.fk_id_aluno)
                     INNER JOIN turma ON (matricula.fk_id_turma_matricula = turma.id_turma)
                     INNER JOIN serie ON (turma.fk_id_serie = serie.id_serie)
                     INNER JOIN curso ON (turma.fk_id_curso = curso.id_curso)
                     INNER JOIN cedula_turma ON (turma.fk_id_cedula = cedula_turma.id_cedula_turma)
                     INNER JOIN turno ON (turma.fk_id_turno = turno.id_turno)
                     WHERE aluno.id_aluno = :id
                     ORDER BY serie.id_serie DESC
                     LIMIT 1"
                );
                $stmt->execute([':id' => $id]);
                $s = $stmt->fetch(\PDO::FETCH_OBJ);

                if (!$s) return 'sem-matricula';

                $_SESSION['Student'] = [
                    'id' => $s->id,
                    'name' => $s->name,
                    'profilePhoto' => $s->profile_photo,
                    'hierarchyFunction' => $s->hierarchy_function,
                    'enrollmentId' => $s->enrollment_id,
                    'class' => $s->acronym_series . 'ª ' . $s->ballot . ' - ' . $s->course . ' - ' . $s->shift,
                    'classId' => $s->class_id,
                ];
                return null;
        }

        return 'portal-invalido';
    }
}
