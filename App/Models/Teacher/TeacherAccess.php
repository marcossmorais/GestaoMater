<?php

namespace App\Models\Teacher;

use MF\Model\Model;

/**
 * Regras de acesso do professor (proposta v2.0 §04/§05). Sempre checar no servidor.
 *
 * - Todo professor edita só as disciplinas atribuídas a ele em turma_disciplina e vê a turma inteira.
 * - Fund. 1 (curso.etapa = 'iniciais'): o titular (turma.fk_id_professor_titular) recebe as disciplinas dele e faz a
 *   chamada diária (DailyAttendance); os especialistas (Ed. Física, Inglês…) editam só a deles e não fazem chamada.
 *   Antes, qualquer professor com uma disciplina na turma editava todas: o especialista mexia em Português.
 */
class TeacherAccess extends Model
{

    /**
     * Condição SQL: turma_disciplina editável pelo professor :teacherId.
     * Mantém os aliases turma_disciplina, turma e curso nas consultas que a usam.
     */
    const EDITABLE = "(turma_disciplina.fk_id_professor = :teacherId)";


    /**
     * O professor pode lançar dados (chamada, notas, diário) nesta turma/disciplina?
     */
    public function canEdit($teacherId, $disciplineClassId)
    {

        $stmt = $this->db->prepare(
            "SELECT 1
             FROM turma_disciplina
             INNER JOIN turma ON (turma_disciplina.fk_id_turma = turma.id_turma)
             INNER JOIN curso ON (turma.fk_id_curso = curso.id_curso)
             WHERE turma_disciplina.id_turma_disciplina = :disciplineClassId
             AND " . self::EDITABLE
        );

        $stmt->bindValue(':teacherId', (int) $teacherId, \PDO::PARAM_INT);
        $stmt->bindValue(':disciplineClassId', (int) $disciplineClassId, \PDO::PARAM_INT);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }


    /**
     * O professor pode ver o desempenho geral desta turma? (leciona ao menos uma disciplina nela ou é o titular)
     */
    public function canViewClass($teacherId, $classId)
    {

        $stmt = $this->db->prepare(
            "SELECT 1 FROM turma_disciplina WHERE fk_id_turma = :classId AND fk_id_professor = :teacherId
             UNION ALL
             SELECT 1 FROM turma WHERE id_turma = :classId AND fk_id_professor_titular = :teacherId
             LIMIT 1"
        );

        $stmt->bindValue(':teacherId', (int) $teacherId, \PDO::PARAM_INT);
        $stmt->bindValue(':classId', (int) $classId, \PDO::PARAM_INT);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }
}
