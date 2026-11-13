<?php

namespace App\Models\GeneralManagement;

use MF\Model\Model;
use App\Models\TeacherStudent\DailyAttendance;

/**
 * Substituição do titular do Fund. 1 por período (database/27_fund1_substituicao.sql), designada pelo admin.
 * O acesso do substituto é dado em DailyAttendance::homeroomClasses (só enquanto o período vale).
 */
class Substitution extends Model
{

    const MAX_DAYS = 120; // período longo é troca de titular, não substituição


    /**
     * Substituições da turma que ainda valem ou terminaram nos últimos 30 dias
     */
    public function forClass($classId)
    {

        $stmt = $this->db->prepare(
            "SELECT s.id_substituicao AS id, professor.nome_professor AS teacher, s.motivo AS reason,
                    TO_CHAR(s.data_inicio, 'DD/MM/YYYY') AS start_date, TO_CHAR(s.data_fim, 'DD/MM/YYYY') AS end_date,
                    CASE WHEN CURRENT_DATE BETWEEN s.data_inicio AND s.data_fim THEN 'ativa'
                         WHEN s.data_inicio > CURRENT_DATE THEN 'agendada' ELSE 'encerrada' END AS status
             FROM substituicao_titular s
             INNER JOIN professor ON (s.fk_id_professor_substituto = professor.id_professor)
             WHERE s.fk_id_turma = :class AND s.data_fim >= CURRENT_DATE - 30
             ORDER BY s.data_inicio DESC"
        );
        $stmt->execute([':class' => (int) $classId]);

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Designa o substituto. Retorna null ou o código do erro:
     * 'etapa', 'professor', 'titular', 'periodo', 'longo', 'sobreposta'
     */
    public function add($classId, $teacherId, $start, $end, $reason)
    {

        $this->db->beginTransaction();

        try {
            // trava a turma: dois admins ao mesmo tempo não criam períodos sobrepostos
            $class = $this->db->prepare(
                "SELECT curso.etapa AS stage, turma.fk_id_professor_titular AS homeroom
                 FROM turma INNER JOIN curso ON (turma.fk_id_curso = curso.id_curso)
                 WHERE turma.id_turma = :class FOR UPDATE OF turma"
            );
            $class->execute([':class' => (int) $classId]);
            $class = $class->fetch(\PDO::FETCH_OBJ);

            $error = null;
            if (!$class || $class->stage !== DailyAttendance::STAGE) $error = 'etapa';
            elseif (!$this->activeTeacher($teacherId)) $error = 'professor';
            elseif ((int) $class->homeroom === (int) $teacherId) $error = 'titular';
            elseif ($end < $start) $error = 'periodo';
            elseif ((strtotime($end) - strtotime($start)) / 86400 + 1 > self::MAX_DAYS) $error = 'longo';
            elseif ($this->overlaps($classId, $start, $end)) $error = 'sobreposta';

            if ($error) {
                $this->db->rollBack();
                return $error;
            }

            $stmt = $this->db->prepare(
                "INSERT INTO substituicao_titular (fk_id_turma, fk_id_professor_substituto, data_inicio, data_fim, motivo)
                 VALUES (:class, :teacher, :start, :end, :reason)"
            );
            $stmt->execute([
                ':class' => (int) $classId,
                ':teacher' => (int) $teacherId,
                ':start' => $start,
                ':end' => $end,
                ':reason' => $reason !== '' ? $reason : null,
            ]);

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }

        return null;
    }


    /**
     * Remove a substituição da turma (true se removeu)
     */
    public function delete($id, $classId)
    {

        $stmt = $this->db->prepare("DELETE FROM substituicao_titular WHERE id_substituicao = :id AND fk_id_turma = :class");
        $stmt->execute([':id' => (int) $id, ':class' => (int) $classId]);

        return $stmt->rowCount() > 0;
    }


    /**
     * Professores com conta ativa, para escolher o substituto
     */
    public function teachers()
    {

        return $this->db->query(
            "SELECT id_professor AS id, nome_professor AS name FROM professor WHERE fk_id_situacao_conta_professor = 1 ORDER BY nome_professor"
        )->fetchAll(\PDO::FETCH_OBJ);
    }


    private function activeTeacher($teacherId)
    {

        $stmt = $this->db->prepare("SELECT 1 FROM professor WHERE id_professor = :teacher AND fk_id_situacao_conta_professor = 1");
        $stmt->execute([':teacher' => (int) $teacherId]);

        return (bool) $stmt->fetchColumn();
    }


    private function overlaps($classId, $start, $end)
    {

        $stmt = $this->db->prepare(
            "SELECT 1 FROM substituicao_titular
             WHERE fk_id_turma = :class AND data_inicio <= :end AND data_fim >= :start LIMIT 1"
        );
        $stmt->execute([':class' => (int) $classId, ':start' => $start, ':end' => $end]);

        return (bool) $stmt->fetchColumn();
    }
}
