<?php

namespace App\Auth;

use App\Connection;
use App\Tools\Audit;

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

    /** Chave de $_SESSION de cada portal */
    const SESSION_KEYS = ['admin' => 'Admin', 'docente' => 'Teacher', 'aluno' => 'Student'];

    public static function config($portal)
    {
        return self::PORTALS[$portal] ?? null;
    }

    /**
     * Abre a sessão de um único portal: troca o id da sessão e descarta o login
     * de qualquer outro portal, para que admin, docente e aluno nunca convivam
     * no mesmo navegador. Toda entrada vai para a auditoria (LOGIN).
     */
    public static function open($portal, array $data)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        session_regenerate_id(true);

        foreach (self::SESSION_KEYS as $key) {
            unset($_SESSION[$key]);
        }

        $_SESSION[self::SESSION_KEYS[$portal]] = $data;

        Audit::log(Connection::getDb(), 'LOGIN', self::PORTALS[$portal]['table'], $data['id']);
    }

    /**
     * Saída do portal: registra na auditoria (LOGOUT) enquanto o ator ainda está na sessão e encerra o login
     */
    public static function close($portal)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $key = self::SESSION_KEYS[$portal];

        if (!empty($_SESSION[$key]['id'])) {
            Audit::log(Connection::getDb(), 'LOGOUT', self::PORTALS[$portal]['table'], $_SESSION[$key]['id']);
        }

        unset($_SESSION[$key]);
    }

    /**
     * Tentativa de entrada recusada (LOGIN_NEGADO). LGPD: sem o nome ou e-mail digitado, só IP e motivo
     */
    public static function logFailure($portal, $reason)
    {
        Audit::log(Connection::getDb(), 'LOGIN_NEGADO', self::PORTALS[$portal]['table'], null, ['motivo' => $reason]);
        (new \App\Tools\RateLimit(Connection::getDb()))->hit(\App\Tools\RateLimit::LOGIN, \App\Tools\RateLimit::ip());
    }


    /**
     * Este IP errou o login por código vezes demais na janela? (10 erros em 15 minutos, todos os portais)
     */
    public static function tooManyFailures($portal)
    {
        $blocked = (new \App\Tools\RateLimit(Connection::getDb()))->exceeded(\App\Tools\RateLimit::LOGIN, \App\Tools\RateLimit::ip());
        if ($blocked) Audit::log(Connection::getDb(), 'LOGIN_NEGADO', self::PORTALS[$portal]['table'], null, ['motivo' => 'muitas-tentativas']);
        return $blocked;
    }

    public static function normalizeEmail($email)
    {
        $email = is_string($email) ? trim($email) : '';

        return $email === '' ? null : mb_strtolower($email, 'UTF-8');
    }

    /**
     * Esse e-mail já pertence a outro cadastro do mesmo portal?
     */
    public static function emailInUse($portal, $email, $excludeId = 0)
    {
        $email = self::normalizeEmail($email);
        if ($email === null) return false;

        $c = self::config($portal);
        if (!$c) return false;

        $stmt = Connection::getDb()->prepare(
            "SELECT 1 FROM {$c['table']} WHERE LOWER(BTRIM({$c['email']})) = :email AND {$c['id']} <> :exclude LIMIT 1"
        );
        $stmt->execute([':email' => $email, ':exclude' => (int) $excludeId]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Procura o cadastro pelo id do Clerk já vinculado (desde que o e-mail local continue entre os
     * verificados dessa conta) ou, no primeiro acesso, por um dos e-mails verificados. Retorna o id local ou null.
     */
    public static function findId($portal, $clerkUserId, array $emails)
    {
        $c = self::config($portal);
        $db = Connection::getDb();
        $verified = array_values(array_unique(array_filter(array_map([self::class, 'normalizeEmail'], $emails))));

        $stmt = $db->prepare("SELECT {$c['id']} AS id, {$c['email']} AS email FROM {$c['table']} WHERE clerk_user_id = :clerkId");
        $stmt->bindValue(':clerkId', $clerkUserId);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_OBJ);

        if ($row) {
            $localEmail = self::normalizeEmail($row->email);
            if ($localEmail !== null && in_array($localEmail, $verified, true)) {
                return (int) $row->id;
            }
            $unlink = $db->prepare("UPDATE {$c['table']} SET clerk_user_id = NULL WHERE {$c['id']} = :id");
            $unlink->execute([':id' => (int) $row->id]);
        }

        if (!$verified) return null;

        $placeholders = implode(',', array_fill(0, count($verified), '?'));
        $stmt = $db->prepare(
            "SELECT {$c['id']} AS id FROM {$c['table']}
             WHERE LOWER(BTRIM({$c['email']})) IN ($placeholders) AND clerk_user_id IS NULL"
        );
        $stmt->execute($verified);
        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);

        // e-mail duplicado entre cadastros: não dá para saber quem é, recusa
        if (count($rows) !== 1) return null;

        $id = (int) $rows[0]->id;

        $stmt = $db->prepare("UPDATE {$c['table']} SET clerk_user_id = :clerkId WHERE {$c['id']} = :id");
        $stmt->bindValue(':clerkId', $clerkUserId);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();

        return $id;
    }

    /**
     * Confere no banco se a conta da sessão aberta continua ativa (encerra o acesso assim que a secretaria desativa)
     */
    public static function accountActive($portal, $id)
    {
        $db = Connection::getDb();
        $id = (int) $id;
        if ($id <= 0) return false;

        switch ($portal) {
            case 'admin':
                $stmt = $db->prepare("SELECT fk_id_situacao_conta_administrador = 1 FROM administrador WHERE id_administrador = :id");
                break;
            case 'docente':
                $stmt = $db->prepare("SELECT fk_id_situacao_conta_professor = 1 FROM professor WHERE id_professor = :id");
                break;
            case 'aluno':
                $stmt = $db->prepare(
                    "SELECT EXISTS (
                         SELECT 1 FROM aluno
                         INNER JOIN matricula ON (aluno.id_aluno = matricula.fk_id_aluno)
                         INNER JOIN turma ON (matricula.fk_id_turma_matricula = turma.id_turma)
                         INNER JOIN periodo_letivo ON (turma.fk_id_periodo_letivo = periodo_letivo.id_ano_letivo)
                         WHERE aluno.id_aluno = :id
                           AND aluno.fk_id_situacao_geral_aluno = 1
                           AND periodo_letivo.fk_id_situacao_periodo_letivo = 1
                           AND matricula.fk_id_situacao_aluno <> 4
                     )"
                );
                break;
            default:
                return false;
        }

        $stmt->execute([':id' => $id]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Cria a sessão do portal. Retorna uma mensagem de erro ou null em caso de sucesso.
     */
    public static function startSession($portal, $id)
    {
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

                self::open('admin', [
                    'id' => $a->id,
                    'name' => implode(' ', array_slice(explode(' ', $a->name), 0, 3)),
                    'profilePhoto' => $a->profile_photo,
                    'hierarchyFunction' => $a->hierarchy_function,
                ]);
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

                self::open('docente', [
                    'id' => $t->id,
                    'name' => $t->name,
                    'profilePhoto' => $t->profile_photo,
                    'hierarchyFunction' => $t->hierarchy_function,
                ]);
                return null;

            case 'aluno':
                $statusStmt = $db->prepare("SELECT fk_id_situacao_geral_aluno FROM aluno WHERE id_aluno = :id");
                $statusStmt->execute([':id' => $id]);
                $generalStatus = $statusStmt->fetchColumn();

                if ($generalStatus === false) return 'sem-cadastro';
                if ((int) $generalStatus !== 1) return 'conta-desativada';

                $stmt = $db->prepare(
                    "SELECT aluno.id_aluno AS id, aluno.nome_aluno AS name, aluno.foto_perfil_aluno AS profile_photo,
                     aluno.fk_id_aluno_hierarquia_funcao AS hierarchy_function, matricula.id_matricula AS enrollment_id,
                     serie.sigla AS acronym_series, cedula_turma.cedula AS ballot, curso.sigla AS course,
                     turno.nome_turno AS shift, turma.id_turma AS class_id
                     FROM aluno
                     INNER JOIN matricula ON (aluno.id_aluno = matricula.fk_id_aluno)
                     INNER JOIN turma ON (matricula.fk_id_turma_matricula = turma.id_turma)
                     INNER JOIN periodo_letivo ON (turma.fk_id_periodo_letivo = periodo_letivo.id_ano_letivo)
                     INNER JOIN serie ON (turma.fk_id_serie = serie.id_serie)
                     INNER JOIN curso ON (turma.fk_id_curso = curso.id_curso)
                     INNER JOIN cedula_turma ON (turma.fk_id_cedula = cedula_turma.id_cedula_turma)
                     INNER JOIN turno ON (turma.fk_id_turno = turno.id_turno)
                     WHERE aluno.id_aluno = :id
                       AND aluno.fk_id_situacao_geral_aluno = 1
                       AND periodo_letivo.fk_id_situacao_periodo_letivo = 1
                       AND matricula.fk_id_situacao_aluno <> 4
                     ORDER BY (matricula.fk_id_situacao_aluno = 1) DESC, matricula.id_matricula DESC
                     LIMIT 1"
                );
                $stmt->execute([':id' => $id]);
                $s = $stmt->fetch(\PDO::FETCH_OBJ);

                if (!$s) return 'sem-matricula';

                self::open('aluno', [
                    'id' => $s->id,
                    'name' => $s->name,
                    'profilePhoto' => $s->profile_photo,
                    'hierarchyFunction' => $s->hierarchy_function,
                    'enrollmentId' => $s->enrollment_id,
                    'class' => $s->acronym_series . ' ' . $s->ballot . ' - ' . $s->course . ' - ' . $s->shift,
                    'classId' => $s->class_id,
                ]);
                return null;
        }

        return 'portal-invalido';
    }
}
