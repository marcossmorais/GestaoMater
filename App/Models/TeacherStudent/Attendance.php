<?php

namespace App\Models\TeacherStudent;

use MF\Model\Model;

/**
 * Chamada rápida do portal do docente.
 *
 * O professor escolhe uma turma/disciplina, todos os alunos começam presentes e ele marca só quem faltou.
 * Ao salvar, cada ausente recebe +N faltas (N = aulas seguidas) em falta_aluno na unidade escolhida,
 * e a chamada fica registrada na tabela chamada.
 */
class Attendance extends Model
{

    /**
     * Turmas/disciplinas do professor no período letivo ativo, com total de alunos e a última chamada de hoje
     *
     * @return array
     */
    public function disciplineClassesByTeacher($teacherId)
    {

        $stmt = $this->db->prepare(
            "SELECT
                turma_disciplina.id_turma_disciplina AS discipline_class_id,
                turma.id_turma AS class_id,
                disciplina.nome_disciplina AS discipline_name,
                disciplina.sigla_disciplina AS discipline_acronym,
                serie.sigla AS series_acronym,
                cedula_turma.cedula AS ballot,
                curso.sigla AS course,
                turno.nome_turno AS shift,
                (SELECT COUNT(*) FROM matricula
                    WHERE matricula.fk_id_turma_matricula = turma.id_turma AND matricula.fk_id_situacao_aluno = 1) AS student_total,
                (SELECT TO_CHAR(MAX(chamada.criado_em), 'HH24:MI') FROM chamada
                    WHERE chamada.fk_id_turma_disciplina = turma_disciplina.id_turma_disciplina AND chamada.data_aula = CURRENT_DATE) AS done_today_at

            FROM turma_disciplina

            INNER JOIN turma ON (turma_disciplina.fk_id_turma = turma.id_turma)
            INNER JOIN disciplina ON (turma_disciplina.fk_id_disciplina = disciplina.id_disciplina)
            INNER JOIN serie ON (turma.fk_id_serie = serie.id_serie)
            INNER JOIN cedula_turma ON (turma.fk_id_cedula = cedula_turma.id_cedula_turma)
            INNER JOIN curso ON (turma.fk_id_curso = curso.id_curso)
            INNER JOIN turno ON (turma.fk_id_turno = turno.id_turno)
            INNER JOIN periodo_letivo ON (turma.fk_id_periodo_letivo = periodo_letivo.id_ano_letivo)

            WHERE turma_disciplina.fk_id_professor = :teacherId
            AND periodo_letivo.fk_id_situacao_periodo_letivo = 1

            ORDER BY serie.sigla, cedula_turma.cedula, disciplina.nome_disciplina"
        );

        $stmt->bindValue(':teacherId', (int) $teacherId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Retorna a turma/disciplina apenas se ela pertence ao professor (ou null)
     */
    public function findForTeacher($disciplineClassId, $teacherId)
    {

        foreach ($this->disciplineClassesByTeacher($teacherId) as $row) {
            if ((int) $row->discipline_class_id === (int) $disciplineClassId) return $row;
        }

        return null;
    }


    /**
     * Alunos matriculados na turma, com o total de faltas na disciplina/unidade
     *
     * @return array
     */
    public function students($disciplineClassId, $unity)
    {

        $stmt = $this->db->prepare(
            "SELECT
                matricula.id_matricula AS enrollment_id,
                aluno.nome_aluno AS name,
                aluno.foto_perfil_aluno AS photo,
                COALESCE(SUM(falta_aluno.total_faltas), 0)::int AS total_lack

            FROM turma_disciplina

            INNER JOIN matricula ON (matricula.fk_id_turma_matricula = turma_disciplina.fk_id_turma AND matricula.fk_id_situacao_aluno = 1)
            INNER JOIN aluno ON (matricula.fk_id_aluno = aluno.id_aluno)
            LEFT JOIN falta_aluno ON (
                falta_aluno.fk_id_matricula_falta = matricula.id_matricula
                AND falta_aluno.fk_id_turma_disciplina_falta = turma_disciplina.id_turma_disciplina
                AND falta_aluno.fk_id_unidade_falta = :unity
            )

            WHERE turma_disciplina.id_turma_disciplina = :disciplineClassId

            GROUP BY matricula.id_matricula, aluno.nome_aluno, aluno.foto_perfil_aluno

            ORDER BY aluno.nome_aluno"
        );

        $stmt->bindValue(':disciplineClassId', (int) $disciplineClassId, \PDO::PARAM_INT);
        $stmt->bindValue(':unity', (int) $unity, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Retorna o horário da última chamada feita em uma data específica para a turma (ou null)
     */
    public function doneAtDate($disciplineClassId, $date)
    {

        $stmt = $this->db->prepare(
            "SELECT TO_CHAR(MAX(chamada.criado_em), 'HH24:MI') FROM chamada
             WHERE chamada.fk_id_turma_disciplina = :id AND chamada.data_aula = :date"
        );

        $stmt->bindValue(':id', (int) $disciplineClassId, \PDO::PARAM_INT);
        $stmt->bindValue(':date', $date);
        $stmt->execute();

        return $stmt->fetchColumn() ?: null;
    }


    /**
     * Grava a chamada: soma as faltas dos ausentes e registra o histórico, tudo em uma transação
     *
     * @param int         $disciplineClassId
     * @param int         $unity
     * @param int         $teacherId
     * @param int         $lessons            aulas seguidas (cada ausente recebe essa quantidade de faltas)
     * @param int[]       $absentEnrollments  matrículas ausentes (já validadas como pertencentes à turma)
     * @param int         $presentTotal
     * @param string|null $lessonDate         data da aula (YYYY-MM-DD), padrão hoje
     *
     * @return int id da chamada
     */
    public function save($disciplineClassId, $unity, $teacherId, $lessons, array $absentEnrollments, $presentTotal, $lessonDate = null)
    {

        $date = ($lessonDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $lessonDate)) ? $lessonDate : date('Y-m-d');

        $this->db->beginTransaction();

        try {

            $update = $this->db->prepare(
                "UPDATE falta_aluno
                 SET total_faltas = total_faltas + :lessons, data_postagem = CURRENT_TIMESTAMP
                 WHERE fk_id_matricula_falta = :enrollment
                 AND fk_id_turma_disciplina_falta = :disciplineClass
                 AND fk_id_unidade_falta = :unity"
            );

            $insert = $this->db->prepare(
                "INSERT INTO falta_aluno (total_faltas, fk_id_turma_disciplina_falta, fk_id_unidade_falta, fk_id_matricula_falta)
                 VALUES (:lessons, :disciplineClass, :unity, :enrollment)"
            );

            foreach ($absentEnrollments as $enrollment) {

                $params = [
                    ':lessons' => (int) $lessons,
                    ':enrollment' => (int) $enrollment,
                    ':disciplineClass' => (int) $disciplineClassId,
                    ':unity' => (int) $unity,
                ];

                $update->execute($params);

                if ($update->rowCount() === 0) $insert->execute($params);
            }

            $log = $this->db->prepare(
                "INSERT INTO chamada
                    (fk_id_turma_disciplina, fk_id_unidade, fk_id_professor, data_aula, quantidade_aulas, total_presentes, total_ausentes, matriculas_ausentes)
                 VALUES
                    (:disciplineClass, :unity, :teacher, :dataAula, :lessons, :present, :absent, :absentList)
                 RETURNING id_chamada"
            );

            $log->execute([
                ':disciplineClass' => (int) $disciplineClassId,
                ':unity' => (int) $unity,
                ':teacher' => (int) $teacherId,
                ':dataAula' => $date,
                ':lessons' => (int) $lessons,
                ':present' => (int) $presentTotal,
                ':absent' => count($absentEnrollments),
                ':absentList' => '{' . implode(',', array_map('intval', $absentEnrollments)) . '}',
            ]);

            $id = (int) $log->fetchColumn();

            $this->db->commit();

            return $id;
        } catch (\Throwable $e) {

            $this->db->rollBack();
            throw $e;
        }
    }


    /**
     * Total de chamadas que o professor já fez hoje (para o painel inicial)
     */
    public function countTodayByTeacher($teacherId)
    {

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM chamada WHERE fk_id_professor = :teacherId AND data_aula = CURRENT_DATE");
        $stmt->bindValue(':teacherId', (int) $teacherId, \PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
}
