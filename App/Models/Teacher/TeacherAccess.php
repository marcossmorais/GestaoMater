<?php

namespace App\Models\Teacher;

use MF\Model\Model;

/**
 * Regras de acesso do professor (proposta v2.0 §04/§05). Sempre checar no servidor.
 *
 * - Anos Iniciais (curso.etapa = 'iniciais'): polivalente, atua em todas as disciplinas das turmas em que leciona.
 * - Anos Finais / Ensino Médio: especialista, vê a turma inteira mas edita só a própria disciplina.
 */
class TeacherAccess extends Model
{

    /**
     * Condição SQL: turma_disciplina editável pelo professor :teacherId.
     * Exige os aliases turma_disciplina, turma e curso na consulta.
     */
    const EDITABLE = "(
        turma_disciplina.fk_id_professor = :teacherId
        OR (
            curso.etapa = 'iniciais'
            AND EXISTS (
                SELECT 1 FROM turma_disciplina own_td
                WHERE own_td.fk_id_turma = turma.id_turma AND own_td.fk_id_professor = :teacherId
            )
        )
    )";


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
     * O professor pode ver o desempenho geral desta turma? (leciona ao menos uma disciplina nela)
     */
    public function canViewClass($teacherId, $classId)
    {

        $stmt = $this->db->prepare(
            "SELECT 1 FROM turma_disciplina WHERE fk_id_turma = :classId AND fk_id_professor = :teacherId LIMIT 1"
        );

        $stmt->bindValue(':teacherId', (int) $teacherId, \PDO::PARAM_INT);
        $stmt->bindValue(':classId', (int) $classId, \PDO::PARAM_INT);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }
}
