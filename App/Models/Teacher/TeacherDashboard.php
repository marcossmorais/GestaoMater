<?php

namespace App\Models\Teacher;

use MF\Model\Model;
use App\Models\TeacherStudent\Gradebook;
use App\Models\TeacherStudent\DailyAttendance;

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
                -- Fund. 1: vale a chamada do dia da turma (titular)
                CASE WHEN curso.etapa = '" . DailyAttendance::STAGE . "'
                    THEN (SELECT TO_CHAR(cd.atualizado_em, 'HH24:MI') FROM chamada_diaria cd WHERE cd.fk_id_turma = t.id_turma AND cd.data_aula = :date)
                    ELSE (SELECT TO_CHAR(MAX(c.criado_em), 'HH24:MI') FROM chamada c
                          WHERE c.fk_id_turma_disciplina = gh.fk_id_turma_disciplina AND c.data_aula = :date)
                END AS attendance_at,
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
     * Números dos gráficos da home, nas disciplinas do professor e no bimestre:
     * situação dos alunos, faixas de nota e, por turma, média, faltas e alunos em atenção
     *
     * @param array $disciplineClasses linhas de Attendance::disciplineClassesByTeacher
     * @param array $atRisk            resultado de atRisk()
     */
    public function charts(array $disciplineClasses, $unity, array $rule, array $atRisk)
    {
        $status = ['ok' => 0, 'partial' => 0, 'recovery' => 0, 'none' => 0];
        $bands = ['0 a 4,9' => 0, '5 a 6,9' => 0, '7 a 8,9' => 0, '9 a 10' => 0];
        $byClass = [];
        $mine = [];

        // mais de uma disciplina na mesma turma (polivalente): o rótulo precisa dizer qual é
        $perLabel = [];
        foreach ($disciplineClasses as $dc) {
            $label = $dc->series_acronym . 'ª ' . $dc->ballot;
            $perLabel[$label] = ($perLabel[$label] ?? 0) + 1;
        }

        foreach ($disciplineClasses as $dc) {
            $id = (int) $dc->discipline_class_id;
            $label = $dc->series_acronym . 'ª ' . $dc->ballot;
            $mine[$id] = $dc;
            $byClass[$id] = [
                'label' => $perLabel[$label] > 1 ? $label . ' · ' . ($dc->discipline_acronym ?: $dc->discipline_name) : $label,
                'turma' => $label,
                'students' => (int) $dc->student_total,
                'graded' => 0, 'sum' => 0, 'average' => null, 'absences' => 0,
                'recovery' => 0, 'partial' => 0, 'attention' => 0,
            ];
        }

        if (!$mine) return ['status' => $status, 'bands' => $bands, 'classes' => []];

        $Gradebook = new Gradebook($this->db);
        foreach (array_unique(array_map(function ($dc) { return (int) $dc->class_id; }, $disciplineClasses)) as $classId) {
            foreach ($Gradebook->bimesterGrades($classId) as $row) {
                $id = (int) $row->discipline_class_id;
                if (!isset($mine[$id]) || (int) $row->unity !== (int) $unity || (int) $row->graded === 0) continue;

                $b = Gradebook::applyRule($row, $rule);
                $grade = (float) $b['grade'];
                $byClass[$id]['graded']++;
                $byClass[$id]['sum'] += $grade;

                if ($b['needsRecovery']) $status['recovery']++;
                elseif ($grade < $rule['passing']) $status['partial']++;
                else $status['ok']++;

                $bands[$grade < 5 ? '0 a 4,9' : ($grade < 7 ? '5 a 6,9' : ($grade < 9 ? '7 a 8,9' : '9 a 10'))]++;
            }
        }

        $ids = implode(',', array_keys($mine));
        $stmt = $this->db->prepare(
            "SELECT fk_id_turma_disciplina_falta AS id, SUM(total_faltas - COALESCE(faltas_abonadas, 0)) AS absences
             FROM falta_aluno WHERE fk_id_turma_disciplina_falta IN ($ids) AND fk_id_unidade_falta = :unity GROUP BY 1"
        );
        $stmt->execute([':unity' => (int) $unity]);
        foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $row) $byClass[(int) $row->id]['absences'] = (int) $row->absences;

        // Fund. 1: dias de falta da turma (chamada diária) na primeira disciplina dela, para não contar a turma várias vezes
        $daily = $this->dailyClasses($disciplineClasses);
        if ($daily) {
            foreach ((new DailyAttendance($this->db))->absencesByClass(array_keys($daily), $unity) as $classId => $count) {
                $byClass[$daily[$classId]]['absences'] += $count;
            }
        }

        foreach ($byClass as &$class) {
            $class['average'] = $class['graded'] ? round($class['sum'] / $class['graded'], 1) : null;
            $status['none'] += max(0, $class['students'] - $class['graded']);
            unset($class['sum']);
        }
        unset($class);

        foreach ($atRisk as $s) {
            foreach ($byClass as &$class) {
                if ($class['turma'] !== $s['label']) continue;
                if (in_array('recovery', $s['kinds'], true)) $class['recovery']++;
                if (in_array('partial', $s['kinds'], true)) $class['partial']++;
                $class['attention']++;
                break;
            }
            unset($class);
        }

        return ['status' => $status, 'bands' => $bands, 'classes' => array_values($byClass)];
    }


    /**
     * O que os alunos andam fazendo: leitura dos avisos do professor (taxa e últimas confirmações)
     *
     * @param int[] $disciplineClassIds
     */
    public function activity(array $disciplineClassIds)
    {
        $empty = ['read' => 0, 'expected' => 0, 'notices' => [], 'feed' => []];
        if (!$disciplineClassIds) return $empty;
        $ids = implode(',', array_map('intval', $disciplineClassIds));

        $notices = $this->db->query(
            "SELECT aviso_turma.id_aviso_turma AS id, LEFT(aviso_turma.aviso, 70) AS text,
                    TO_CHAR(aviso_turma.data_postagem, 'DD/MM') AS date,
                    serie.sigla || 'ª ' || cedula_turma.cedula AS label,
                    (SELECT COUNT(*) FROM aviso_leitura WHERE fk_id_aviso_turma = aviso_turma.id_aviso_turma) AS read_total,
                    (SELECT COUNT(*) FROM matricula WHERE fk_id_turma_matricula = turma.id_turma AND fk_id_situacao_aluno = 1) AS students
             FROM aviso_turma
             INNER JOIN turma_disciplina ON (aviso_turma.fk_id_turma_disciplina = turma_disciplina.id_turma_disciplina)
             INNER JOIN turma ON (turma_disciplina.fk_id_turma = turma.id_turma)
             INNER JOIN serie ON (turma.fk_id_serie = serie.id_serie)
             INNER JOIN cedula_turma ON (turma.fk_id_cedula = cedula_turma.id_cedula_turma)
             WHERE aviso_turma.fk_id_turma_disciplina IN ($ids)
             ORDER BY aviso_turma.data_postagem DESC, aviso_turma.id_aviso_turma DESC"
        )->fetchAll(\PDO::FETCH_OBJ);

        $read = 0;
        $expected = 0;
        foreach ($notices as $n) {
            $read += (int) $n->read_total;
            $expected += (int) $n->students;
        }

        $feed = $this->db->query(
            "SELECT aluno.nome_aluno AS name, matricula.id_matricula AS enrollment_id, LEFT(aviso_turma.aviso, 60) AS text,
                    TO_CHAR(aviso_leitura.lido_em, 'DD/MM HH24:MI') AS at,
                    serie.sigla || 'ª ' || cedula_turma.cedula AS label
             FROM aviso_leitura
             INNER JOIN aviso_turma ON (aviso_leitura.fk_id_aviso_turma = aviso_turma.id_aviso_turma)
             INNER JOIN matricula ON (aviso_leitura.fk_id_matricula = matricula.id_matricula)
             INNER JOIN aluno ON (matricula.fk_id_aluno = aluno.id_aluno)
             INNER JOIN turma ON (matricula.fk_id_turma_matricula = turma.id_turma)
             INNER JOIN serie ON (turma.fk_id_serie = serie.id_serie)
             INNER JOIN cedula_turma ON (turma.fk_id_cedula = cedula_turma.id_cedula_turma)
             WHERE aviso_turma.fk_id_turma_disciplina IN ($ids)
             ORDER BY aviso_leitura.lido_em DESC
             LIMIT 6"
        )->fetchAll(\PDO::FETCH_OBJ);

        return ['read' => $read, 'expected' => $expected, 'notices' => array_slice($notices, 0, 4), 'feed' => $feed];
    }


    /**
     * Quem mais faltou no bimestre nas disciplinas do professor (sem as abonadas)
     *
     * @param array $disciplineClasses linhas de Attendance::disciplineClassesByTeacher
     * @return array [id, name, label, absences]
     */
    public function topAbsences(array $disciplineClasses, $unity, $limit = 5)
    {

        if (!$disciplineClasses) return [];

        $ids = implode(',', array_map(function ($dc) { return (int) $dc->discipline_class_id; }, $disciplineClasses));

        $stmt = $this->db->prepare(
            "SELECT matricula.id_matricula AS id, aluno.nome_aluno AS name, serie.sigla || 'ª ' || cedula_turma.cedula AS label,
                    SUM(f.total_faltas - COALESCE(f.faltas_abonadas, 0))::int AS absences
             FROM falta_aluno f
             INNER JOIN matricula ON (matricula.id_matricula = f.fk_id_matricula_falta AND matricula.fk_id_situacao_aluno = 1)
             INNER JOIN aluno ON (aluno.id_aluno = matricula.fk_id_aluno)
             INNER JOIN turma ON (turma.id_turma = matricula.fk_id_turma_matricula)
             INNER JOIN serie ON (turma.fk_id_serie = serie.id_serie)
             INNER JOIN cedula_turma ON (turma.fk_id_cedula = cedula_turma.id_cedula_turma)
             WHERE f.fk_id_turma_disciplina_falta IN ($ids) AND f.fk_id_unidade_falta = :unity
             GROUP BY 1, 2, 3
             HAVING SUM(f.total_faltas - COALESCE(f.faltas_abonadas, 0)) > 0
             ORDER BY absences DESC, name
             LIMIT " . (int) $limit
        );

        $stmt->execute([':unity' => (int) $unity]);
        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);

        // Fund. 1: dias de falta da chamada diária, somados por aluno
        $daily = $this->dailyClasses($disciplineClasses);
        if ($daily) {
            $byId = [];
            foreach ($rows as $r) $byId[(int) $r->id] = $r;
            foreach ((new DailyAttendance($this->db))->absentStudents(array_keys($daily), $unity) as $s) {
                if (isset($byId[(int) $s->id])) $byId[(int) $s->id]->absences += (int) $s->absences;
                else $byId[(int) $s->id] = (object) ['id' => (int) $s->id, 'name' => $s->name, 'label' => $s->label, 'absences' => (int) $s->absences];
            }
            $rows = array_values($byId);
            usort($rows, function ($a, $b) { return [$b->absences, $a->name] <=> [$a->absences, $b->name]; });
            $rows = array_slice($rows, 0, (int) $limit);
        }

        return $rows;
    }


    /**
     * Fund. 1 nas turmas do professor: [id_turma => primeira turma_disciplina dele nela]
     *
     * @param array $disciplineClasses linhas de Attendance::disciplineClassesByTeacher
     */
    private function dailyClasses(array $disciplineClasses)
    {
        $result = [];
        foreach ($disciplineClasses as $dc) {
            if ($dc->stage === DailyAttendance::STAGE && !isset($result[(int) $dc->class_id])) $result[(int) $dc->class_id] = (int) $dc->discipline_class_id;
        }
        return $result;
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

        $add = function ($enrollmentId, $name, $dcId, $reason, $weight, $kind) use (&$students, $mine) {
            $key = $enrollmentId . '-' . $dcId;
            if (!isset($students[$key])) {
                $dc = $mine[$dcId];
                $students[$key] = [
                    'enrollmentId' => (int) $enrollmentId, 'name' => $name, 'classId' => (int) $dc->class_id,
                    'label' => $dc->series_acronym . 'ª ' . $dc->ballot, 'discipline' => $dc->discipline_name,
                    'reasons' => [], 'kinds' => [], 'weight' => 0,
                ];
            }
            $students[$key]['reasons'][] = $reason;
            $students[$key]['kinds'][] = $kind;
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
                    $add($row->enrollment_id, $row->name, $dcId, "Aguarda recuperação (média $grade)", 3, 'recovery');
                } elseif (!$b['closed'] && $b['average'] < $rule['passing']) {
                    $add($row->enrollment_id, $row->name, $dcId, "Média parcial $grade ({$row->graded} de {$rule['perBimester']})", 2, 'partial');
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
            $add($row->enrollment_id, $row->name, (int) $row->discipline_class_id, "{$row->absences} faltas no bimestre", 1, 'absences');
        }

        // Fund. 1: dias de falta da chamada diária (um alerta por aluno, na primeira disciplina do professor na turma)
        $daily = $this->dailyClasses($disciplineClasses);
        foreach ((new DailyAttendance($this->db))->absentStudents(array_keys($daily), $unity, self::ABSENCE_ALERT) as $s) {
            $add($s->id, $s->name, $daily[(int) $s->class_id], "{$s->absences} dias de falta no bimestre", 1, 'absences');
        }

        $students = array_values($students);
        usort($students, function ($a, $b) { return [$b['weight'], $a['name']] <=> [$a['weight'], $b['name']]; });

        return $students;
    }
}
