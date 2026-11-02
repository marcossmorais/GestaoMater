<?php

namespace App\Models\TeacherStudent;

use MF\Model\Model;

/**
 * Área geral do mural (aviso_geral): a coordenação publica para toda a escola ou para uma turma
 * (database/21_mural_geral_tipos.sql).
 */
class GeneralBoard extends Model
{

    /**
     * Avisos que a turma vê, fixados primeiro. Com $enrollmentId, indica se o aluno já leu.
     */
    public function forClass($classId, $enrollmentId = null)
    {

        $stmt = $this->db->prepare(
            "SELECT aviso_geral.id_aviso_geral AS id, aviso_geral.aviso AS text, aviso_geral.tipo AS type, aviso_geral.fixado AS pinned,
                    TO_CHAR(aviso_geral.data_postagem, 'DD/MM/YYYY HH24:MI') AS posted_at,
                    EXISTS (
                        SELECT 1 FROM aviso_geral_leitura
                        WHERE fk_id_aviso_geral = aviso_geral.id_aviso_geral AND fk_id_matricula = :enrollment
                    ) AS read_by_me
             FROM aviso_geral
             WHERE aviso_geral.fk_id_turma IS NULL OR aviso_geral.fk_id_turma = :classId
             ORDER BY aviso_geral.fixado DESC, aviso_geral.data_postagem DESC, aviso_geral.id_aviso_geral DESC
             LIMIT 30"
        );

        $stmt->bindValue(':classId', (int) $classId, \PDO::PARAM_INT);
        $stmt->bindValue(':enrollment', (int) $enrollmentId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Todos os avisos gerais, para o admin
     */
    public function all()
    {

        $stmt = $this->db->query(
            "SELECT aviso_geral.id_aviso_geral AS id, aviso_geral.aviso AS text, aviso_geral.tipo AS type, aviso_geral.fixado AS pinned,
                    TO_CHAR(aviso_geral.data_postagem, 'DD/MM/YYYY HH24:MI') AS posted_at,
                    serie.sigla || 'ª ' || cedula_turma.cedula AS class_label,
                    (SELECT COUNT(*) FROM aviso_geral_leitura WHERE fk_id_aviso_geral = aviso_geral.id_aviso_geral) AS read_total
             FROM aviso_geral
             LEFT JOIN turma ON (aviso_geral.fk_id_turma = turma.id_turma)
             LEFT JOIN serie ON (turma.fk_id_serie = serie.id_serie)
             LEFT JOIN cedula_turma ON (turma.fk_id_cedula = cedula_turma.id_cedula_turma)
             ORDER BY aviso_geral.fixado DESC, aviso_geral.data_postagem DESC, aviso_geral.id_aviso_geral DESC
             LIMIT 100"
        );

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
     * @param int|null $classId turma de destino; null = toda a escola
     * @return int id do aviso
     */
    public function post($adminId, $text, $type, $pinned, $classId)
    {

        $stmt = $this->db->prepare(
            "INSERT INTO aviso_geral (aviso, tipo, fixado, fk_id_turma, fk_id_administrador)
             VALUES (:text, :type, :pinned, :class, :admin)
             RETURNING id_aviso_geral"
        );

        $stmt->bindValue(':text', $text);
        $stmt->bindValue(':type', $type);
        $stmt->bindValue(':pinned', (bool) $pinned, \PDO::PARAM_BOOL);
        $stmt->bindValue(':class', $classId, $classId === null ? \PDO::PARAM_NULL : \PDO::PARAM_INT);
        $stmt->bindValue(':admin', (int) $adminId, \PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }


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
     * Confirma a leitura. Só vale para aviso da escola ou da turma do aluno.
     *
     * @return bool
     */
    public function markRead($id, $enrollmentId, $classId)
    {

        $visible = $this->db->prepare(
            "SELECT 1 FROM aviso_geral WHERE id_aviso_geral = :id AND (fk_id_turma IS NULL OR fk_id_turma = :classId)"
        );
        $visible->execute([':id' => (int) $id, ':classId' => (int) $classId]);

        if (!$visible->fetchColumn()) return false;

        $stmt = $this->db->prepare(
            "INSERT INTO aviso_geral_leitura (fk_id_aviso_geral, fk_id_matricula) VALUES (:id, :enrollment) ON CONFLICT DO NOTHING"
        );

        return $stmt->execute([':id' => (int) $id, ':enrollment' => (int) $enrollmentId]);
    }
}
