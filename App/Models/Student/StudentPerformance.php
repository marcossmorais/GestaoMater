<?php

namespace App\Models\Student;

use MF\Model\Model;
use App\Tools\GradeSimulator;

/**
 * Desempenho do aluno por disciplina (pontos por unidade, frequência com abonos, simulador)
 * e cronograma da turma. Tudo filtrado pela matrícula/turma da sessão do aluno.
 */
class StudentPerformance extends Model
{

    /**
     * Regra de aprovação (database/14_desempenho_cronograma.sql)
     */
    public function rule()
    {

        $row = $this->db->query(
            "SELECT total_unidades, pontos_por_unidade, media_aprovacao, frequencia_minima FROM regra_aprovacao WHERE id_regra_aprovacao = 1"
        )->fetch(\PDO::FETCH_ASSOC);

        return [
            'units' => (int) ($row['total_unidades'] ?? 3),
            'maxPerUnit' => (float) ($row['pontos_por_unidade'] ?? 10),
            'passing' => (float) ($row['media_aprovacao'] ?? 6),
            'minAttendance' => (int) ($row['frequencia_minima'] ?? 75),
        ];
    }


    /**
     * @return array uma linha por disciplina com units[u] = [earned, distributed, closed], faltas e simulação
     */
    public function byDiscipline($enrollmentId, $classId)
    {

        $rule = $this->rule();

        $stmt = $this->db->prepare(
            "SELECT
                turma_disciplina.id_turma_disciplina AS id,
                disciplina.nome_disciplina AS discipline,
                professor.nome_professor AS teacher,
                avaliacoes.fk_id_unidade_avaliacao AS unity,
                SUM(avaliacoes.valor_avaliacao) AS distributed,
                SUM(nota_avaliacao.valor_nota) AS earned
            FROM turma_disciplina
            INNER JOIN disciplina ON (turma_disciplina.fk_id_disciplina = disciplina.id_disciplina)
            INNER JOIN professor ON (turma_disciplina.fk_id_professor = professor.id_professor)
            LEFT JOIN avaliacoes ON (avaliacoes.fk_id_turma_disciplina_avaliacao = turma_disciplina.id_turma_disciplina)
            LEFT JOIN nota_avaliacao ON (nota_avaliacao.fk_id_avaliacao = avaliacoes.id_avaliacao AND nota_avaliacao.fk_id_matricula_aluno = :enrollment)
            WHERE turma_disciplina.fk_id_turma = :classId
            GROUP BY turma_disciplina.id_turma_disciplina, disciplina.nome_disciplina, professor.nome_professor, avaliacoes.fk_id_unidade_avaliacao
            ORDER BY disciplina.nome_disciplina, avaliacoes.fk_id_unidade_avaliacao"
        );

        $stmt->execute([':enrollment' => (int) $enrollmentId, ':classId' => (int) $classId]);

        $rows = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $r) {
            $id = (int) $r->id;
            if (!isset($rows[$id])) {
                $rows[$id] = ['id' => $id, 'discipline' => $r->discipline, 'teacher' => $r->teacher, 'units' => []];
                for ($u = 1; $u <= $rule['units']; $u++) $rows[$id]['units'][$u] = ['earned' => 0.0, 'distributed' => 0.0, 'closed' => false];
            }
            $u = (int) $r->unity;
            if ($u >= 1 && $u <= $rule['units']) {
                $rows[$id]['units'][$u] = [
                    'earned' => round((float) $r->earned, 2),
                    'distributed' => round((float) $r->distributed, 2),
                    // a unidade entra na média quando todos os pontos já foram distribuídos
                    'closed' => (float) $r->distributed >= $rule['maxPerUnit'] - 0.001,
                ];
            }
        }

        $attendance = $this->attendance($enrollmentId, $classId);

        foreach ($rows as $id => &$row) {
            $grades = [];
            foreach ($row['units'] as $u => $unit) $grades[$u] = $unit['closed'] ? $unit['earned'] : null;

            $row['simulation'] = GradeSimulator::simulate($grades, $rule['passing'], $rule['units'], $rule['maxPerUnit']);

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
