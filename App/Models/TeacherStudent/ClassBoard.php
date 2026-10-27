<?php

namespace App\Models\TeacherStudent;

use MF\Model\Model;

/**
 * Mural da turma (aviso_turma). O professor publica por disciplina; o aluno vê todos os da turma,
 * fixados primeiro (até 3 por turma, garantido em database/13_mural_roteiro.sql) e confirma a leitura.
 */
class ClassBoard extends Model
{

    const MAX_PINNED = 3;


    /**
     * Avisos da turma, fixados primeiro. Com $enrollmentId, indica se o aluno já leu.
     */
    public function forClass($classId, $enrollmentId = null)
    {

        $stmt = $this->db->prepare(
            "SELECT
                aviso_turma.id_aviso_turma AS id,
                aviso_turma.aviso AS text,
                aviso_turma.fixado AS pinned,
                aviso_turma.fk_id_turma_disciplina AS discipline_class_id,
                TO_CHAR(aviso_turma.data_postagem, 'DD/MM/YYYY HH24:MI') AS posted_at,
                disciplina.nome_disciplina AS discipline,
                COALESCE(autor.nome_professor, titular.nome_professor) AS teacher,
                COALESCE(autor.foto_perfil_professor, titular.foto_perfil_professor) AS teacher_photo,
                (SELECT COUNT(*) FROM aviso_leitura WHERE fk_id_aviso_turma = aviso_turma.id_aviso_turma) AS read_total,
                EXISTS (
                    SELECT 1 FROM aviso_leitura
                    WHERE fk_id_aviso_turma = aviso_turma.id_aviso_turma AND fk_id_matricula = :enrollment
                ) AS read_by_me

            FROM aviso_turma

            INNER JOIN turma_disciplina ON (aviso_turma.fk_id_turma_disciplina = turma_disciplina.id_turma_disciplina)
            INNER JOIN disciplina ON (turma_disciplina.fk_id_disciplina = disciplina.id_disciplina)
            INNER JOIN professor titular ON (turma_disciplina.fk_id_professor = titular.id_professor)
            LEFT JOIN professor autor ON (aviso_turma.fk_id_professor = autor.id_professor)

            WHERE turma_disciplina.fk_id_turma = :classId

            ORDER BY aviso_turma.fixado DESC, aviso_turma.data_postagem DESC, aviso_turma.id_aviso_turma DESC
            LIMIT 50"
        );

        $stmt->bindValue(':classId', (int) $classId, \PDO::PARAM_INT);
        $stmt->bindValue(':enrollment', (int) $enrollmentId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    public function find($id)
    {

        $stmt = $this->db->prepare(
            "SELECT aviso_turma.id_aviso_turma AS id, aviso_turma.fk_id_turma_disciplina AS discipline_class_id,
                    aviso_turma.fixado AS pinned, turma_disciplina.fk_id_turma AS class_id
             FROM aviso_turma
             INNER JOIN turma_disciplina ON (aviso_turma.fk_id_turma_disciplina = turma_disciplina.id_turma_disciplina)
             WHERE aviso_turma.id_aviso_turma = :id"
        );

        $stmt->execute([':id' => (int) $id]);

        return $stmt->fetch(\PDO::FETCH_OBJ) ?: null;
    }


    /**
     * @return int id do aviso
     */
    public function post($disciplineClassId, $teacherId, $text, $pinned)
    {

        $stmt = $this->db->prepare(
            "INSERT INTO aviso_turma (aviso, fk_id_turma_disciplina, fk_id_professor, fixado)
             VALUES (:text, :disciplineClass, :teacher, :pinned)
             RETURNING id_aviso_turma"
        );

        $stmt->bindValue(':text', $text);
        $stmt->bindValue(':disciplineClass', (int) $disciplineClassId, \PDO::PARAM_INT);
        $stmt->bindValue(':teacher', (int) $teacherId, \PDO::PARAM_INT);
        $stmt->bindValue(':pinned', (bool) $pinned, \PDO::PARAM_BOOL);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }


    public function setPinned($id, $pinned)
    {

        $stmt = $this->db->prepare("UPDATE aviso_turma SET fixado = :pinned WHERE id_aviso_turma = :id");
        $stmt->bindValue(':pinned', (bool) $pinned, \PDO::PARAM_BOOL);
        $stmt->bindValue(':id', (int) $id, \PDO::PARAM_INT);

        return $stmt->execute();
    }


    public function delete($id)
    {

        $stmt = $this->db->prepare("DELETE FROM aviso_turma WHERE id_aviso_turma = :id");

        return $stmt->execute([':id' => (int) $id]);
    }


    /**
     * Confirma a leitura do aviso pelo aluno. Só vale para avisos da turma dele.
     *
     * @return bool
     */
    public function markRead($id, $enrollmentId, $classId)
    {

        $stmt = $this->db->prepare(
            "INSERT INTO aviso_leitura (fk_id_aviso_turma, fk_id_matricula)
             SELECT aviso_turma.id_aviso_turma, :enrollment
             FROM aviso_turma
             INNER JOIN turma_disciplina ON (aviso_turma.fk_id_turma_disciplina = turma_disciplina.id_turma_disciplina)
             WHERE aviso_turma.id_aviso_turma = :id AND turma_disciplina.fk_id_turma = :classId
             ON CONFLICT DO NOTHING
             RETURNING 1"
        );

        $stmt->execute([':id' => (int) $id, ':enrollment' => (int) $enrollmentId, ':classId' => (int) $classId]);

        return $stmt->fetchColumn() !== false || $this->belongsToClass($id, $classId);
    }


    private function belongsToClass($id, $classId)
    {

        $stmt = $this->db->prepare(
            "SELECT 1 FROM aviso_turma
             INNER JOIN turma_disciplina ON (aviso_turma.fk_id_turma_disciplina = turma_disciplina.id_turma_disciplina)
             WHERE aviso_turma.id_aviso_turma = :id AND turma_disciplina.fk_id_turma = :classId"
        );

        $stmt->execute([':id' => (int) $id, ':classId' => (int) $classId]);

        return (bool) $stmt->fetchColumn();
    }
}
