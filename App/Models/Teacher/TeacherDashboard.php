<?php

namespace App\Models\Teacher;

use MF\Model\Model;
use App\Models\TeacherStudent\Gradebook;

/**
 * Painel inicial do docente: aulas do dia, pendências e alunos que pedem atenção.
 * Recebe sempre a lista de turma_disciplina editáveis pelo professor (Attendance::disciplineClassesByTeacher).
 */
class TeacherDashboard extends Model
{

    // faltas não abonadas no bimestre, por disciplina, a partir das quais o aluno entra em "Atenção"
    const ABSENCE_ALERT = 5;


    /**
     * Aulas do professor no dia da semana da data, com chamada e diário daquele dia.
     * Aulas seguidas da mesma turma/disciplina viram um item só.
     *
     * @param string $date YYYY-MM-DD
     */
    public function lessonsOn($teacherId, $date)
    {

        $weekday = (int) date('N', strtotime($date));
        if ($weekday > 5) return [];

        $stmt = $this->db->prepare(
            "SELECT
                gh.fk_id_turma_disciplina AS discipline_class_id,
                t.id_turma AS class_id,
                TO_CHAR(ha.hora_inicio, 'HH24:MI') AS start,
                TO_CHAR(ha.hora_fim, 'HH24:MI') AS finish,
                d.nome_disciplina AS discipline,
                serie.sigla || 'ª ' || cedula_turma.cedula AS label,
                curso.sigla AS course,
                turno.nome_turno AS shift,
                (SELECT TO_CHAR(MAX(c.criado_em), 'HH24:MI') FROM chamada c
                  WHERE c.fk_id_turma_disciplina = gh.fk_id_turma_disciplina AND c.data_aula = :date) AS attendance_at,
                EXISTS (SELECT 1 FROM aula a WHERE a.fk_id_turma_disciplina = gh.fk_id_turma_disciplina AND a.data_aula = :date) AS lesson_done
            FROM grade_horario gh
            INNER JOIN horario_aula ha ON (gh.fk_id_horario_aula = ha.id_horario_aula)
            INNER JOIN turma_disciplina td ON (gh.fk_id_turma_disciplina = td.id_turma_disciplina)
            INNER JOIN disciplina d ON (td.fk_id_disciplina = d.id_disciplina)
            INNER JOIN turma t ON (gh.fk_id_turma = t.id_turma)
            INNER JOIN serie ON (t.fk_id_serie = serie.id_serie)
            INNER JOIN cedula_turma ON (t.fk_id_cedula = cedula_turma.id_cedula_turma)
            INNER JOIN curso ON (t.fk_id_curso = curso.id_curso)
            INNER JOIN turno ON (t.fk_id_turno = turno.id_turno)
            INNER JOIN periodo_letivo pl ON (t.fk_id_periodo_letivo = pl.id_ano_letivo)
            WHERE td.fk_id_professor = :teacherId AND gh.dia_semana = :weekday AND pl.fk_id_situacao_periodo_letivo = 1
            ORDER BY ha.hora_inicio"
        );

        $stmt->bindValue(':teacherId', (int) $teacherId, \PDO::PARAM_INT);
        $stmt->bindValue(':weekday', $weekday + 1, \PDO::PARAM_INT);
        $stmt->bindValue(':date', $date);
        $stmt->execute();

        $lessons = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $slot) {
            $last = end($lessons);
            if ($last && $last->discipline_class_id === $slot->discipline_class_id && $last->finish <= $slot->start) {
                $last->finish = $slot->finish;
                $last->count++;
                continue;
            }
            $slot->count = 1;
            $lessons[] = $slot;
        }

        return $lessons;
    }


    /**
     * O que o professor ainda precisa fazer: chamada sem diário (últimos 14 dias) e avaliações já aplicadas sem todas as notas
     *
     * @param int[] $disciplineClassIds
     * @param int[] $openUnits
     */
    public function pending(array $disciplineClassIds, array $openUnits)
    {

        if (!$disciplineClassIds) return ['lessons' => [], 'exams' => []];

        $ids = implode(',', array_map('intval', $disciplineClassIds));
        $units = implode(',', array_map('intval', $openUnits ?: [0]));

        $lessons = $this->db->query(
            "SELECT c.fk_id_turma_disciplina AS discipline_class_id, TO_CHAR(c.data_aula, 'YYYY-MM-DD') AS date
             FROM chamada c
             WHERE c.fk_id_turma_disciplina IN ($ids) AND c.data_aula >= CURRENT_DATE - 14
             AND NOT EXISTS (SELECT 1 FROM aula a WHERE a.fk_id_turma_disciplina = c.fk_id_turma_disciplina AND a.data_aula = c.data_aula)
             GROUP BY 1, 2
             ORDER BY 2 DESC
             LIMIT 20"
        )->fetchAll(\PDO::FETCH_OBJ);

        $exams = $this->db->query(
            "SELECT * FROM (
                SELECT a.id_avaliacao AS id, a.descricao_avaliacao AS description, a.fk_id_turma_disciplina_avaliacao AS discipline_class_id,
                       a.fk_id_unidade_avaliacao AS unity, TO_CHAR(a.data_realizada, 'YYYY-MM-DD') AS date,
                       (SELECT COUNT(*) FROM nota_avaliacao n WHERE n.fk_id_avaliacao = a.id_avaliacao) AS graded,
                       (SELECT COUNT(*) FROM matricula m INNER JOIN turma_disciplina td ON (td.fk_id_turma = m.fk_id_turma_matricula)
                         WHERE td.id_turma_disciplina = a.fk_id_turma_disciplina_avaliacao AND m.fk_id_situacao_aluno = 1) AS students
                FROM avaliacoes a
                WHERE a.fk_id_turma_disciplina_avaliacao IN ($ids) AND a.fk_id_unidade_avaliacao IN ($units)
                AND NOT a.recuperacao AND a.data_realizada <= CURRENT_DATE
             ) x
             WHERE graded < students
             ORDER BY date
             LIMIT 20"
        )->fetchAll(\PDO::FETCH_OBJ);

        return ['lessons' => $lessons, 'exams' => $exams];
    }


    /**
     * Alunos que pedem atenção no bimestre: média parcial abaixo da média, aguardando recuperação ou com muitas faltas
     *
     * @param array $disciplineClasses linhas de Attendance::disciplineClassesByTeacher
     * @return array [enrollmentId, name, classId, disciplineClassId, reasons[]] ordenado pela gravidade
     */
    public function atRisk(array $disciplineClasses, $unity, array $rule)
    {

        if (!$disciplineClasses) return [];

        $mine = [];
        foreach ($disciplineClasses as $dc) $mine[(int) $dc->discipline_class_id] = $dc;

        $Gradebook = new Gradebook($this->db);
        $students = [];

        $add = function ($enrollmentId, $name, $dcId, $reason, $weight) use (&$students, $mine) {
            $key = $enrollmentId . '-' . $dcId;
            if (!isset($students[$key])) {
                $dc = $mine[$dcId];
                $students[$key] = [
                    'enrollmentId' => (int) $enrollmentId, 'name' => $name, 'classId' => (int) $dc->class_id,
                    'label' => $dc->series_acronym . 'ª ' . $dc->ballot, 'discipline' => $dc->discipline_name,
                    'reasons' => [], 'weight' => 0,
                ];
            }
            $students[$key]['reasons'][] = $reason;
            $students[$key]['weight'] += $weight;
        };

        $classIds = array_unique(array_map(function ($dc) { return (int) $dc->class_id; }, $disciplineClasses));
        foreach ($classIds as $classId) {
            foreach ($Gradebook->bimesterGrades($classId) as $row) {
                $dcId = (int) $row->discipline_class_id;
                if (!isset($mine[$dcId]) || (int) $row->unity !== (int) $unity || (int) $row->graded === 0) continue;

                $b = Gradebook::applyRule($row, $rule);
                $grade = str_replace('.', ',', (string) round((float) $b['average'], 1));

                if ($b['needsRecovery']) {
                    $add($row->enrollment_id, $row->name, $dcId, "Aguarda recuperação (média $grade)", 3);
                } elseif (!$b['closed'] && $b['average'] < $rule['passing']) {
                    $add($row->enrollment_id, $row->name, $dcId, "Média parcial $grade ({$row->graded} de {$rule['perBimester']})", 2);
                }
            }
        }

        $ids = implode(',', array_keys($mine));
        $stmt = $this->db->prepare(
            "SELECT f.fk_id_matricula_falta AS enrollment_id, f.fk_id_turma_disciplina_falta AS discipline_class_id, aluno.nome_aluno AS name,
                    SUM(f.total_faltas - COALESCE(f.faltas_abonadas, 0)) AS absences
             FROM falta_aluno f
             INNER JOIN matricula ON (matricula.id_matricula = f.fk_id_matricula_falta AND matricula.fk_id_situacao_aluno = 1)
             INNER JOIN aluno ON (aluno.id_aluno = matricula.fk_id_aluno)
             WHERE f.fk_id_turma_disciplina_falta IN ($ids) AND f.fk_id_unidade_falta = :unity
             GROUP BY 1, 2, 3
             HAVING SUM(f.total_faltas - COALESCE(f.faltas_abonadas, 0)) >= :limit"
        );
        $stmt->execute([':unity' => (int) $unity, ':limit' => self::ABSENCE_ALERT]);

        foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $row) {
            $add($row->enrollment_id, $row->name, (int) $row->discipline_class_id, "{$row->absences} faltas no bimestre", 1);
        }

        $students = array_values($students);
        usort($students, function ($a, $b) { return [$b['weight'], $a['name']] <=> [$a['weight'], $b['name']]; });

        return $students;
    }
}
