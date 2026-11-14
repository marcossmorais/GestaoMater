<?php

namespace App\Models\TeacherStudent;

use MF\Model\Model;

/**
 * Avisos gerais do admin, por público (database/21_mural_geral_tipos.sql e 30_avisos_segmentados.sql).
 * Alunos e responsáveis aparecem no portal do aluno (a família usa o login do aluno); professores, no portal docente.
 * Sem turma em aviso_geral_turma = toda a escola.
 */
class GeneralBoard extends Model
{

    const AUDIENCES = [
        'alunos' => ['label' => 'Alunos', 'icon' => 'fa-user-graduate', 'tag' => 'Para os alunos'],
        'responsaveis' => ['label' => 'Responsáveis', 'icon' => 'fa-users', 'tag' => 'Para a família'],
        'professores' => ['label' => 'Professores', 'icon' => 'fa-chalkboard-teacher', 'tag' => 'Para os professores'],
    ];

    const MAX_PINNED = 3;

    // aviso de aluno/responsável visível para a turma :classId
    const VISIBLE_TO_CLASS = "aviso_geral.publico IN ('alunos', 'responsaveis')
        AND (NOT EXISTS (SELECT 1 FROM aviso_geral_turma x WHERE x.fk_id_aviso_geral = aviso_geral.id_aviso_geral)
             OR EXISTS (SELECT 1 FROM aviso_geral_turma x WHERE x.fk_id_aviso_geral = aviso_geral.id_aviso_geral AND x.fk_id_turma = :classId))";


    /**
     * Avisos de alunos e responsáveis que a turma vê, fixados primeiro. Com $enrollmentId, indica se o aluno já leu.
     */
    public function forClass($classId, $enrollmentId = null)
    {

        $stmt = $this->db->prepare(
            "SELECT aviso_geral.id_aviso_geral AS id, aviso_geral.aviso AS text, aviso_geral.tipo AS type, aviso_geral.fixado AS pinned,
                    aviso_geral.publico AS audience,
                    TO_CHAR(aviso_geral.data_postagem, 'DD/MM/YYYY HH24:MI') AS posted_at,
                    EXISTS (
                        SELECT 1 FROM aviso_geral_leitura
                        WHERE fk_id_aviso_geral = aviso_geral.id_aviso_geral AND fk_id_matricula = :enrollment
                    ) AS read_by_me
             FROM aviso_geral
             WHERE " . self::VISIBLE_TO_CLASS . "
             ORDER BY aviso_geral.fixado DESC, aviso_geral.data_postagem DESC, aviso_geral.id_aviso_geral DESC
             LIMIT 30"
        );

        $stmt->bindValue(':classId', (int) $classId, \PDO::PARAM_INT);
        $stmt->bindValue(':enrollment', (int) $enrollmentId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Avisos para os professores, fixados primeiro, com a leitura do professor
     */
    public function forTeacher($teacherId)
    {

        $stmt = $this->db->prepare(
            "SELECT aviso_geral.id_aviso_geral AS id, aviso_geral.aviso AS text, aviso_geral.tipo AS type, aviso_geral.fixado AS pinned,
                    TO_CHAR(aviso_geral.data_postagem, 'DD/MM/YYYY HH24:MI') AS posted_at,
                    EXISTS (
                        SELECT 1 FROM aviso_geral_leitura_professor
                        WHERE fk_id_aviso_geral = aviso_geral.id_aviso_geral AND fk_id_professor = :teacher
                    ) AS read_by_me
             FROM aviso_geral
             WHERE aviso_geral.publico = 'professores'
             ORDER BY aviso_geral.fixado DESC, aviso_geral.data_postagem DESC, aviso_geral.id_aviso_geral DESC
             LIMIT 30"
        );

        $stmt->bindValue(':teacher', (int) $teacherId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Todos os avisos gerais, para o admin, com o destino e a leitura: quantos leram de quantos recebem.
     * Alunos e responsáveis contam as matrículas ativas das turmas de destino.
     */
    public function all($audience = '')
    {

        $where = isset(self::AUDIENCES[$audience]) ? 'WHERE aviso_geral.publico = :audience' : '';

        $stmt = $this->db->prepare(
            "SELECT aviso_geral.id_aviso_geral AS id, aviso_geral.aviso AS text, aviso_geral.tipo AS type, aviso_geral.fixado AS pinned,
                    aviso_geral.publico AS audience,
                    TO_CHAR(aviso_geral.data_postagem, 'DD/MM/YYYY HH24:MI') AS posted_at,
                    (SELECT STRING_AGG(serie.sigla || 'ª ' || cedula_turma.cedula || ' ' || curso.sigla, ', ' ORDER BY serie.sigla, cedula_turma.cedula)
                     FROM aviso_geral_turma agt
                     INNER JOIN turma ON (agt.fk_id_turma = turma.id_turma)
                     INNER JOIN serie ON (turma.fk_id_serie = serie.id_serie)
                     INNER JOIN cedula_turma ON (turma.fk_id_cedula = cedula_turma.id_cedula_turma)
                     INNER JOIN curso ON (turma.fk_id_curso = curso.id_curso)
                     WHERE agt.fk_id_aviso_geral = aviso_geral.id_aviso_geral) AS class_label,
                    CASE WHEN aviso_geral.publico = 'professores'
                         THEN (SELECT COUNT(*) FROM aviso_geral_leitura_professor l
                               INNER JOIN professor p ON (l.fk_id_professor = p.id_professor AND p.fk_id_situacao_conta_professor = 1)
                               WHERE l.fk_id_aviso_geral = aviso_geral.id_aviso_geral)
                         ELSE (SELECT COUNT(*) FROM aviso_geral_leitura l
                               INNER JOIN matricula m ON (l.fk_id_matricula = m.id_matricula AND m.fk_id_situacao_aluno = 1)
                               WHERE l.fk_id_aviso_geral = aviso_geral.id_aviso_geral)
                    END AS read_total,
                    CASE WHEN aviso_geral.publico = 'professores'
                         THEN (SELECT COUNT(*) FROM professor WHERE fk_id_situacao_conta_professor = 1)
                         ELSE (SELECT COUNT(*) FROM matricula m
                               WHERE m.fk_id_situacao_aluno = 1
                                 AND (NOT EXISTS (SELECT 1 FROM aviso_geral_turma x WHERE x.fk_id_aviso_geral = aviso_geral.id_aviso_geral)
                                      OR m.fk_id_turma_matricula IN (SELECT x.fk_id_turma FROM aviso_geral_turma x WHERE x.fk_id_aviso_geral = aviso_geral.id_aviso_geral)))
                    END AS audience_total
             FROM aviso_geral
             $where
             ORDER BY aviso_geral.fixado DESC, aviso_geral.data_postagem DESC, aviso_geral.id_aviso_geral DESC
             LIMIT 100"
        );

        if ($where) $stmt->bindValue(':audience', $audience);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Turmas para o seletor de destino
     */
    public function classes()
    {

        $stmt = $this->db->query(
            "SELECT turma.id_turma AS id, serie.sigla || 'ª ' || cedula_turma.cedula || ' · ' || curso.sigla || ' · ' || turno.nome_turno AS label
             FROM turma
             INNER JOIN serie ON (turma.fk_id_serie = serie.id_serie)
             INNER JOIN cedula_turma ON (turma.fk_id_cedula = cedula_turma.id_cedula_turma)
             INNER JOIN curso ON (turma.fk_id_curso = curso.id_curso)
             INNER JOIN turno ON (turma.fk_id_turno = turno.id_turno)
             ORDER BY serie.sigla, cedula_turma.cedula"
        );

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Publica o aviso. As regras (até 3 fixados por público, professor sem turma) são do banco.
     *
     * @param int[] $classIds turmas de destino; vazio = toda a escola (ignorado para professores)
     * @return int id do aviso
     * @throws \PDOException 'limite-fixados'
     */
    public function post($adminId, $text, $type, $pinned, $audience, array $classIds)
    {

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO aviso_geral (aviso, tipo, fixado, publico, fk_id_administrador)
                 VALUES (:text, :type, :pinned, :audience, :admin)
                 RETURNING id_aviso_geral"
            );

            $stmt->bindValue(':text', $text);
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':pinned', (bool) $pinned, \PDO::PARAM_BOOL);
            $stmt->bindValue(':audience', $audience);
            $stmt->bindValue(':admin', $adminId === null ? null : (int) $adminId, $adminId === null ? \PDO::PARAM_NULL : \PDO::PARAM_INT);
            $stmt->execute();

            $id = (int) $stmt->fetchColumn();

            if ($audience !== 'professores' && $classIds) {
                $link = $this->db->prepare("INSERT INTO aviso_geral_turma (fk_id_aviso_geral, fk_id_turma) VALUES (:id, :class) ON CONFLICT DO NOTHING");
                foreach (array_unique(array_map('intval', $classIds)) as $classId) {
                    $link->execute([':id' => $id, ':class' => $classId]);
                }
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }

        return $id;
    }


    /**
     * @throws \PDOException 'limite-fixados'
     */
    public function setPinned($id, $pinned)
    {

        $stmt = $this->db->prepare("UPDATE aviso_geral SET fixado = :pinned WHERE id_aviso_geral = :id");
        $stmt->bindValue(':pinned', (bool) $pinned, \PDO::PARAM_BOOL);
        $stmt->bindValue(':id', (int) $id, \PDO::PARAM_INT);

        return $stmt->execute();
    }


    public function delete($id)
    {

        return $this->db->prepare("DELETE FROM aviso_geral WHERE id_aviso_geral = :id")->execute([':id' => (int) $id]);
    }


    /**
     * Confirma a leitura do aluno (ou da família, que usa o mesmo login). Só vale para aviso que a turma vê.
     *
     * @return bool
     */
    public function markRead($id, $enrollmentId, $classId)
    {

        $visible = $this->db->prepare("SELECT 1 FROM aviso_geral WHERE id_aviso_geral = :id AND " . self::VISIBLE_TO_CLASS);
        $visible->execute([':id' => (int) $id, ':classId' => (int) $classId]);

        if (!$visible->fetchColumn()) return false;

        $stmt = $this->db->prepare(
            "INSERT INTO aviso_geral_leitura (fk_id_aviso_geral, fk_id_matricula) VALUES (:id, :enrollment) ON CONFLICT DO NOTHING"
        );

        return $stmt->execute([':id' => (int) $id, ':enrollment' => (int) $enrollmentId]);
    }


    /**
     * Confirma a leitura do professor. Só vale para aviso dos professores.
     *
     * @return bool
     */
    public function markReadTeacher($id, $teacherId)
    {

        $stmt = $this->db->prepare(
            "INSERT INTO aviso_geral_leitura_professor (fk_id_aviso_geral, fk_id_professor)
             SELECT id_aviso_geral, :teacher FROM aviso_geral WHERE id_aviso_geral = :id AND publico = 'professores'
             ON CONFLICT DO NOTHING"
        );
        $stmt->execute([':id' => (int) $id, ':teacher' => (int) $teacherId]);

        if ($stmt->rowCount()) return true;

        // já lido antes também conta
        $check = $this->db->prepare("SELECT 1 FROM aviso_geral WHERE id_aviso_geral = :id AND publico = 'professores'");
        $check->execute([':id' => (int) $id]);

        return (bool) $check->fetchColumn();
    }
}
