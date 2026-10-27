<?php

namespace App\Models\Student;

use MF\Model\Model;
use App\Tools\GradeSimulator;

/**
 * Desempenho do aluno por disciplina (nota por bimestre com recuperação, simulador, faltas)
 * e cronograma da turma. Tudo filtrado pela matrícula/turma da sessão do aluno.
 */
class StudentPerformance extends Model
{

    /**
     * Regra do MATER (database/16_regra_bimestral.sql): 4 bimestres, 3 avaliações, média 7, recuperação abaixo de 7
     */
    public function rule()
    {

        $row = $this->db->query(
            "SELECT total_unidades, pontos_por_unidade, media_aprovacao, avaliacoes_por_bimestre, nota_recuperacao
             FROM regra_aprovacao WHERE id_regra_aprovacao = 1"
        )->fetch(\PDO::FETCH_ASSOC);

        return [
            'units' => (int) ($row['total_unidades'] ?? 4),
            'maxPerUnit' => (float) ($row['pontos_por_unidade'] ?? 10),
            'passing' => (float) ($row['media_aprovacao'] ?? 7),
            'perBimester' => (int) ($row['avaliacoes_por_bimestre'] ?? 3),
            'recoveryBelow' => (float) ($row['nota_recuperacao'] ?? 7),
        ];
    }


    /**
     * Uma linha por disciplina: units[bimestre] = regra aplicada (grade, average, closed, recovered, needsRecovery, graded),
     * simulação da média final e faltas (só informativo; frequência não entra na nota)
     */
    public function byDiscipline($enrollmentId, $classId)
    {

        $rule = $this->rule();

        $stmt = $this->db->prepare(
            "SELECT turma_disciplina.id_turma_disciplina AS id, disciplina.nome_disciplina AS discipline, professor.nome_professor AS teacher
             FROM turma_disciplina
             INNER JOIN disciplina ON (turma_disciplina.fk_id_disciplina = disciplina.id_disciplina)
             INNER JOIN professor ON (turma_disciplina.fk_id_professor = professor.id_professor)
             WHERE turma_disciplina.fk_id_turma = :classId
             ORDER BY disciplina.nome_disciplina"
        );

        $stmt->execute([':classId' => (int) $classId]);

        $empty = \App\Tools\GradeSimulator::bimester(null, 0, $rule['perBimester'], null, $rule['passing']) + ['graded' => 0];

        $rows = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $r) {
            $rows[(int) $r->id] = ['id' => (int) $r->id, 'discipline' => $r->discipline, 'teacher' => $r->teacher, 'units' => []];
            for ($u = 1; $u <= $rule['units']; $u++) $rows[(int) $r->id]['units'][$u] = $empty;
        }

        $Gradebook = new \App\Models\TeacherStudent\Gradebook($this->db);

        foreach ($Gradebook->bimesterGrades($classId, $enrollmentId) as $g) {
            $id = (int) $g->discipline_class_id;
            $u = (int) $g->unity;
            if (!isset($rows[$id]) || $u < 1 || $u > $rule['units']) continue;
            $rows[$id]['units'][$u] = \App\Models\TeacherStudent\Gradebook::applyRule($g, $rule) + ['graded' => (int) $g->graded];
        }

        $attendance = $this->attendance($enrollmentId, $classId);

        foreach ($rows as $id => &$row) {
            $grades = [];
            foreach ($row['units'] as $u => $unit) {
                // bimestre só entra na média final quando fechou e, se ficou abaixo de 7, depois da recuperação
                $grades[$u] = ($unit['closed'] && !$unit['needsRecovery']) ? $unit['grade'] : null;
            }

            $row['simulation'] = GradeSimulator::simulate($grades, $rule['passing'], $rule['units'], $rule['maxPerUnit']);
            $row['pendingRecovery'] = array_keys(array_filter($row['units'], function ($unit) { return $unit['needsRecovery']; }));

            $a = $attendance[$id] ?? ['absences' => 0, 'excused' => 0, 'lessons' => 0];
            $counted = max(0, $a['absences'] - $a['excused']);
            $row['absences'] = $a['absences'];
            $row['excused'] = $a['excused'];
            $row['lessons'] = $a['lessons'];
            $row['attendance'] = $a['lessons'] > 0 ? round(100 * max(0, $a['lessons'] - $counted) / $a['lessons'], 1) : null;
        }

        return array_values($rows);
    }


    /**
     * Faltas, abonos e aulas dadas (chamadas registradas) por disciplina
     */
    private function attendance($enrollmentId, $classId)
    {

        $stmt = $this->db->prepare(
            "SELECT
                turma_disciplina.id_turma_disciplina AS id,
                (SELECT COALESCE(SUM(total_faltas), 0) FROM falta_aluno
                  WHERE fk_id_matricula_falta = :enrollment AND fk_id_turma_disciplina_falta = turma_disciplina.id_turma_disciplina) AS absences,
                (SELECT COALESCE(SUM(faltas_abonadas), 0) FROM falta_aluno
                  WHERE fk_id_matricula_falta = :enrollment AND fk_id_turma_disciplina_falta = turma_disciplina.id_turma_disciplina) AS excused,
                (SELECT COALESCE(SUM(quantidade_aulas), 0) FROM chamada
                  WHERE fk_id_turma_disciplina = turma_disciplina.id_turma_disciplina) AS lessons
            FROM turma_disciplina
            WHERE turma_disciplina.fk_id_turma = :classId"
        );

        $stmt->execute([':enrollment' => (int) $enrollmentId, ':classId' => (int) $classId]);

        $result = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $r) {
            $result[(int) $r->id] = ['absences' => (int) $r->absences, 'excused' => (int) $r->excused, 'lessons' => (int) $r->lessons];
        }

        return $result;
    }


    /**
     * Cronograma do ano: eventos da escola e da turma + avaliações da turma
     */
    public function agenda($classId)
    {

        $stmt = $this->db->prepare(
            "SELECT * FROM (
                SELECT titulo AS title, descricao AS description, tipo AS type,
                       TO_CHAR(data_inicio, 'YYYY-MM-DD') AS start, TO_CHAR(COALESCE(data_fim, data_inicio), 'YYYY-MM-DD') AS end
                FROM evento_escolar
                WHERE (fk_id_turma IS NULL OR fk_id_turma = :classId)
                AND EXTRACT(YEAR FROM data_inicio) = EXTRACT(YEAR FROM CURRENT_DATE)

                UNION ALL

                SELECT disciplina.nome_disciplina || ' · ' || avaliacoes.descricao_avaliacao, avaliacoes.roteiro, 'avaliacao',
                       TO_CHAR(avaliacoes.data_realizada, 'YYYY-MM-DD'), TO_CHAR(avaliacoes.data_realizada, 'YYYY-MM-DD')
                FROM avaliacoes
                INNER JOIN turma_disciplina ON (avaliacoes.fk_id_turma_disciplina_avaliacao = turma_disciplina.id_turma_disciplina)
                INNER JOIN disciplina ON (turma_disciplina.fk_id_disciplina = disciplina.id_disciplina)
                WHERE turma_disciplina.fk_id_turma = :classId
                AND EXTRACT(YEAR FROM avaliacoes.data_realizada) = EXTRACT(YEAR FROM CURRENT_DATE)
            ) agenda
            ORDER BY start, title"
        );

        $stmt->execute([':classId' => (int) $classId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
