<?php

namespace App\Models\Admin;

use MF\Model\Model;
use App\Models\TeacherStudent\Gradebook;

/**
 * Observabilidade 360° do admin: desempenho, frequência e pendências por turma, disciplina e professor.
 *
 * Tudo sai de collect(), que lê os dados brutos do período uma vez; os resumos são montados em PHP
 * com a mesma regra do portal do aluno (Gradebook::applyRule, frequência = aulas dadas nas chamadas).
 */
class Observability extends Model
{

    /**
     * Períodos letivos, o ativo primeiro
     */
    public function periods()
    {

        return $this->speedingUp(
            "SELECT periodo_letivo.id_ano_letivo AS id, periodo_disponivel.ano_letivo AS year,
                    periodo_letivo.fk_id_situacao_periodo_letivo = 1 AS active
             FROM periodo_letivo
             INNER JOIN periodo_disponivel ON (periodo_letivo.fk_id_ano_letivo = periodo_disponivel.id_periodo_disponivel)
             ORDER BY (periodo_letivo.fk_id_situacao_periodo_letivo = 1) DESC, periodo_disponivel.ano_letivo DESC"
        );
    }


    public function shifts()
    {

        return $this->speedingUp("SELECT id_turno AS id, nome_turno AS name FROM turno ORDER BY id_turno");
    }


    /**
     * Regra de aprovação + frequência mínima (database/14 e 16)
     */
    public function rule()
    {

        $row = $this->db->query(
            "SELECT total_unidades, media_aprovacao, avaliacoes_por_bimestre, nota_recuperacao, frequencia_minima
             FROM regra_aprovacao WHERE id_regra_aprovacao = 1"
        )->fetch(\PDO::FETCH_ASSOC) ?: [];

        return [
            'units' => (int) ($row['total_unidades'] ?? 4),
            'passing' => (float) ($row['media_aprovacao'] ?? 7),
            'perBimester' => (int) ($row['avaliacoes_por_bimestre'] ?? 3),
            'recoveryBelow' => (float) ($row['nota_recuperacao'] ?? 7),
            'minAttendance' => (float) ($row['frequencia_minima'] ?? 75),
        ];
    }


    /**
     * Bimestres liberados para lançamento (configuração do admin: 1 = só o primeiro … 4 = todos)
     */
    public function openUnits()
    {

        return max(1, (int) $this->db->query("SELECT fk_id_controle_unidade FROM configuracao LIMIT 1")->fetchColumn());
    }


    /**
     * Dados brutos do período. $unit null = ano inteiro. $classId restringe a uma turma.
     */
    public function collect($periodId, $unit = null, $shiftId = null, $classId = null)
    {

        $where = "turma.fk_id_periodo_letivo = :period";
        $params = [':period' => (int) $periodId];

        if ($shiftId) {
            $where .= " AND turma.fk_id_turno = :shift";
            $params[':shift'] = (int) $shiftId;
        }

        if ($classId) {
            $where .= " AND turma.id_turma = :class";
            $params[':class'] = (int) $classId;
        }

        $unitParams = $params + ($unit ? [':unit' => (int) $unit] : []);

        $label = "serie.sigla || 'ª ' || cedula_turma.cedula || ' · ' || curso.sigla || ' · ' || turno.nome_turno";

        $data = [];

        $data['disciplineClasses'] = $this->fetch(
            "SELECT turma_disciplina.id_turma_disciplina AS id, turma.id_turma AS class_id, $label AS class_label,
                    disciplina.id_disciplina AS discipline_id, disciplina.nome_disciplina AS discipline,
                    professor.id_professor AS teacher_id, professor.nome_professor AS teacher
             FROM turma_disciplina
             INNER JOIN turma ON (turma_disciplina.fk_id_turma = turma.id_turma)
             INNER JOIN serie ON (turma.fk_id_serie = serie.id_serie)
             INNER JOIN cedula_turma ON (turma.fk_id_cedula = cedula_turma.id_cedula_turma)
             INNER JOIN curso ON (turma.fk_id_curso = curso.id_curso)
             INNER JOIN turno ON (turma.fk_id_turno = turno.id_turno)
             INNER JOIN disciplina ON (turma_disciplina.fk_id_disciplina = disciplina.id_disciplina)
             INNER JOIN professor ON (turma_disciplina.fk_id_professor = professor.id_professor)
             WHERE $where
             ORDER BY serie.sigla, cedula_turma.cedula, disciplina.nome_disciplina",
            $params
        );

        $data['classes'] = $this->fetch(
            "SELECT turma.id_turma AS id, $label AS label, turno.nome_turno AS shift
             FROM turma
             INNER JOIN serie ON (turma.fk_id_serie = serie.id_serie)
             INNER JOIN cedula_turma ON (turma.fk_id_cedula = cedula_turma.id_cedula_turma)
             INNER JOIN curso ON (turma.fk_id_curso = curso.id_curso)
             INNER JOIN turno ON (turma.fk_id_turno = turno.id_turno)
             WHERE $where
             ORDER BY serie.sigla, cedula_turma.cedula",
            $params
        );

        $data['students'] = $this->fetch(
            "SELECT matricula.id_matricula AS id, matricula.fk_id_turma_matricula AS class_id, aluno.nome_aluno AS name
             FROM matricula
             INNER JOIN aluno ON (matricula.fk_id_aluno = aluno.id_aluno)
             INNER JOIN turma ON (matricula.fk_id_turma_matricula = turma.id_turma)
             WHERE matricula.fk_id_situacao_aluno = 1 AND $where
             ORDER BY aluno.nome_aluno",
            $params
        );

        // nota do bimestre por aluno e disciplina (mesma conta de Gradebook::bimesterGrades)
        $data['grades'] = $this->fetch(
            "SELECT matricula.id_matricula AS enrollment_id, avaliacoes.fk_id_turma_disciplina_avaliacao AS discipline_class_id,
                    avaliacoes.fk_id_unidade_avaliacao AS unity,
                    COUNT(nota_avaliacao.id_nota) FILTER (WHERE NOT avaliacoes.recuperacao) AS graded,
                    AVG(nota_avaliacao.valor_nota / NULLIF(avaliacoes.valor_avaliacao, 0) * 10) FILTER (WHERE NOT avaliacoes.recuperacao) AS average,
                    MAX(nota_avaliacao.valor_nota / NULLIF(avaliacoes.valor_avaliacao, 0) * 10) FILTER (WHERE avaliacoes.recuperacao) AS recovery
             FROM nota_avaliacao
             INNER JOIN matricula ON (nota_avaliacao.fk_id_matricula_aluno = matricula.id_matricula)
             INNER JOIN avaliacoes ON (nota_avaliacao.fk_id_avaliacao = avaliacoes.id_avaliacao)
             INNER JOIN turma_disciplina ON (avaliacoes.fk_id_turma_disciplina_avaliacao = turma_disciplina.id_turma_disciplina)
             INNER JOIN turma ON (turma_disciplina.fk_id_turma = turma.id_turma)
             WHERE matricula.fk_id_situacao_aluno = 1 AND matricula.fk_id_turma_matricula = turma.id_turma AND $where
             " . ($unit ? "AND avaliacoes.fk_id_unidade_avaliacao = :unit" : "") . "
             GROUP BY matricula.id_matricula, avaliacoes.fk_id_turma_disciplina_avaliacao, avaliacoes.fk_id_unidade_avaliacao",
            $unitParams
        );

        $data['absences'] = $this->fetch(
            "SELECT falta_aluno.fk_id_matricula_falta AS enrollment_id, falta_aluno.fk_id_turma_disciplina_falta AS discipline_class_id,
                    SUM(falta_aluno.total_faltas) AS absences, SUM(COALESCE(falta_aluno.faltas_abonadas, 0)) AS excused
             FROM falta_aluno
             INNER JOIN turma_disciplina ON (falta_aluno.fk_id_turma_disciplina_falta = turma_disciplina.id_turma_disciplina)
             INNER JOIN turma ON (turma_disciplina.fk_id_turma = turma.id_turma)
             WHERE $where " . ($unit ? "AND falta_aluno.fk_id_unidade_falta = :unit" : "") . "
             GROUP BY 1, 2",
            $unitParams
        );

        $data['lessons'] = $this->fetch(
            "SELECT chamada.fk_id_turma_disciplina AS discipline_class_id, SUM(chamada.quantidade_aulas) AS lessons,
                    COUNT(*) AS calls, TO_CHAR(MAX(chamada.data_aula), 'DD/MM/YYYY') AS last_call, MAX(chamada.data_aula) AS last_call_date
             FROM chamada
             INNER JOIN turma_disciplina ON (chamada.fk_id_turma_disciplina = turma_disciplina.id_turma_disciplina)
             INNER JOIN turma ON (turma_disciplina.fk_id_turma = turma.id_turma)
             WHERE $where " . ($unit ? "AND chamada.fk_id_unidade = :unit" : "") . "
             GROUP BY 1",
            $unitParams
        );

        // aulas previstas pela grade semanal, do início do período até hoje (sem descontar feriados)
        $data['expected'] = $this->fetch(
            "SELECT grade_horario.fk_id_turma_disciplina AS discipline_class_id, COUNT(*) AS expected
             FROM grade_horario
             INNER JOIN turma_disciplina ON (grade_horario.fk_id_turma_disciplina = turma_disciplina.id_turma_disciplina)
             INNER JOIN turma ON (turma_disciplina.fk_id_turma = turma.id_turma)
             INNER JOIN periodo_letivo ON (turma.fk_id_periodo_letivo = periodo_letivo.id_ano_letivo)
             INNER JOIN LATERAL generate_series(periodo_letivo.data_inicio, LEAST(periodo_letivo.data_fim, CURRENT_DATE), interval '1 day') AS dia(d)
                ON (EXTRACT(ISODOW FROM dia.d) = grade_horario.dia_semana - 1)
             WHERE $where
             GROUP BY 1",
            $params
        );

        // avaliações regulares já realizadas e quantas notas de alunos ativos foram lançadas em cada uma
        $data['exams'] = $this->fetch(
            "SELECT avaliacoes.id_avaliacao AS id, avaliacoes.fk_id_turma_disciplina_avaliacao AS discipline_class_id,
                    avaliacoes.fk_id_unidade_avaliacao AS unity, avaliacoes.recuperacao AS recovery,
                    avaliacoes.data_realizada::date <= CURRENT_DATE AS due,
                    (SELECT COUNT(*) FROM nota_avaliacao
                       INNER JOIN matricula ON (nota_avaliacao.fk_id_matricula_aluno = matricula.id_matricula)
                       WHERE nota_avaliacao.fk_id_avaliacao = avaliacoes.id_avaliacao AND matricula.fk_id_situacao_aluno = 1
                       AND matricula.fk_id_turma_matricula = turma.id_turma) AS graded
             FROM avaliacoes
             INNER JOIN turma_disciplina ON (avaliacoes.fk_id_turma_disciplina_avaliacao = turma_disciplina.id_turma_disciplina)
             INNER JOIN turma ON (turma_disciplina.fk_id_turma = turma.id_turma)
             WHERE $where " . ($unit ? "AND avaliacoes.fk_id_unidade_avaliacao = :unit" : ""),
            $unitParams
        );

        $data['occurrences'] = $this->fetch(
            "SELECT ocorrencia_disciplinar.fk_id_matricula AS enrollment_id, COUNT(*) AS total,
                    COUNT(*) FILTER (WHERE ocorrencia_disciplinar.requer_ciencia AND NOT COALESCE(ocorrencia_disciplinar.ciencia_responsavel, false)) AS pending
             FROM ocorrencia_disciplinar
             INNER JOIN matricula ON (ocorrencia_disciplinar.fk_id_matricula = matricula.id_matricula)
             INNER JOIN turma ON (matricula.fk_id_turma_matricula = turma.id_turma)
             WHERE $where
             GROUP BY 1",
            $params
        );

        // diário: aulas registradas e quantas tiveram substituto ou matéria trocada
        $data['diary'] = $this->fetch(
            "SELECT aula.fk_id_turma_disciplina AS discipline_class_id, COUNT(*) AS entries,
                    COUNT(*) FILTER (WHERE aula.fk_id_professor_substituto IS NOT NULL OR aula.fk_id_turma_disciplina_ministrada IS NOT NULL) AS exceptions
             FROM aula
             INNER JOIN turma_disciplina ON (aula.fk_id_turma_disciplina = turma_disciplina.id_turma_disciplina)
             INNER JOIN turma ON (turma_disciplina.fk_id_turma = turma.id_turma)
             WHERE $where
             GROUP BY 1",
            $params
        );

        $data['substitutions'] = $this->fetch(
            "SELECT aula.fk_id_professor_substituto AS teacher_id, COUNT(*) AS total
             FROM aula
             INNER JOIN turma_disciplina ON (aula.fk_id_turma_disciplina = turma_disciplina.id_turma_disciplina)
             INNER JOIN turma ON (turma_disciplina.fk_id_turma = turma.id_turma)
             WHERE aula.fk_id_professor_substituto IS NOT NULL AND $where
             GROUP BY 1",
            $params
        );

        return $data;
    }


    /**
     * Monta os resumos por turma, disciplina e professor e o detalhe de cada aluno
     *
     * @param array $data  saída de collect()
     * @param int|null $unit bimestre escolhido (null = ano)
     */
    public function summarize(array $data, array $rule, $unit, $openUnits)
    {

        $passing = $rule['passing'];

        // turma_disciplina indexada
        $tds = [];
        foreach ($data['disciplineClasses'] as $td) {
            $tds[(int) $td->id] = [
                'id' => (int) $td->id, 'classId' => (int) $td->class_id, 'classLabel' => $td->class_label,
                'disciplineId' => (int) $td->discipline_id, 'discipline' => $td->discipline,
                'teacherId' => (int) $td->teacher_id, 'teacher' => $td->teacher,
                'lessons' => 0, 'calls' => 0, 'lastCall' => null, 'lastCallDate' => null, 'expected' => 0,
                'diary' => 0, 'exceptions' => 0, 'exams' => 0, 'pendingGrades' => 0,
                'gradeSum' => 0, 'gradeCount' => 0, 'below' => 0, 'students' => 0,
            ];
        }

        foreach ($data['lessons'] as $r) {
            if (!isset($tds[(int) $r->discipline_class_id])) continue;
            $t = &$tds[(int) $r->discipline_class_id];
            $t['lessons'] = (int) $r->lessons;
            $t['calls'] = (int) $r->calls;
            $t['lastCall'] = $r->last_call;
            $t['lastCallDate'] = $r->last_call_date;
            unset($t);
        }

        foreach ($data['expected'] as $r) {
            if (isset($tds[(int) $r->discipline_class_id])) $tds[(int) $r->discipline_class_id]['expected'] = (int) $r->expected;
        }

        foreach ($data['diary'] as $r) {
            if (!isset($tds[(int) $r->discipline_class_id])) continue;
            $tds[(int) $r->discipline_class_id]['diary'] = (int) $r->entries;
            $tds[(int) $r->discipline_class_id]['exceptions'] = (int) $r->exceptions;
        }

        // alunos ativos por turma
        $classStudents = [];
        $students = [];
        foreach ($data['students'] as $s) {
            $classStudents[(int) $s->class_id] = ($classStudents[(int) $s->class_id] ?? 0) + 1;
            $students[(int) $s->id] = [
                'id' => (int) $s->id, 'classId' => (int) $s->class_id, 'name' => $s->name,
                'grades' => [], 'absences' => [], 'occurrences' => 0, 'pendingAck' => 0,
            ];
        }

        foreach ($tds as &$t) $t['students'] = $classStudents[$t['classId']] ?? 0;
        unset($t);

        // avaliações: regulares criadas e notas pendentes nas que já aconteceram
        foreach ($data['exams'] as $e) {
            $id = (int) $e->discipline_class_id;
            if (!isset($tds[$id]) || $e->recovery) continue;
            $tds[$id]['exams']++;
            if ($e->due) $tds[$id]['pendingGrades'] += max(0, $tds[$id]['students'] - (int) $e->graded);
        }

        $unitsConsidered = $unit ? 1 : min($openUnits, $rule['units']);
        foreach ($tds as &$t) {
            $t['expectedExams'] = $rule['perBimester'] * $unitsConsidered;
            $t['missingExams'] = max(0, $t['expectedExams'] - $t['exams']);
        }
        unset($t);

        // nota do aluno na disciplina: do bimestre escolhido ou média dos bimestres com nota
        $byUnit = [];
        foreach ($data['grades'] as $g) {
            $e = (int) $g->enrollment_id;
            $id = (int) $g->discipline_class_id;
            if (!isset($students[$e], $tds[$id]) || (int) $g->graded === 0) continue;
            $applied = Gradebook::applyRule($g, $rule);
            if ($applied['grade'] !== null) $byUnit[$e][$id][(int) $g->unity] = $applied['grade'];
        }

        foreach ($byUnit as $e => $disciplines) {
            foreach ($disciplines as $id => $units) {
                $grade = round(array_sum($units) / count($units), 2);
                $students[$e]['grades'][$id] = $grade;
                $tds[$id]['gradeSum'] += $grade;
                $tds[$id]['gradeCount']++;
                if ($grade < $passing - 0.0001) $tds[$id]['below']++;
            }
        }

        foreach ($data['absences'] as $a) {
            $e = (int) $a->enrollment_id;
            if (!isset($students[$e])) continue;
            $students[$e]['absences'][(int) $a->discipline_class_id] = max(0, (int) $a->absences - (int) $a->excused);
        }

        foreach ($data['occurrences'] as $o) {
            $e = (int) $o->enrollment_id;
            if (!isset($students[$e])) continue;
            $students[$e]['occurrences'] = (int) $o->total;
            $students[$e]['pendingAck'] = (int) $o->pending;
        }

        // tds da turma, para a frequência geral do aluno
        $classTds = [];
        foreach ($tds as $t) $classTds[$t['classId']][] = $t['id'];

        foreach ($students as &$s) {
            $grades = $s['grades'];
            $s['average'] = $grades ? round(array_sum($grades) / count($grades), 2) : null;
            $s['below'] = count(array_filter($grades, function ($g) use ($passing) { return $g < $passing - 0.0001; }));

            $lessons = 0;
            $counted = 0;
            $absences = 0;
            foreach ($classTds[$s['classId']] ?? [] as $id) {
                $absences += $s['absences'][$id] ?? 0;
                if ($tds[$id]['lessons'] > 0) {
                    $lessons += $tds[$id]['lessons'];
                    $counted += min($tds[$id]['lessons'], $s['absences'][$id] ?? 0);
                }
            }
            $s['absenceTotal'] = $absences;
            $s['attendance'] = $lessons > 0 ? round(100 * ($lessons - $counted) / $lessons, 1) : null;
            $s['lowAttendance'] = $s['attendance'] !== null && $s['attendance'] < $rule['minAttendance'];
            $s['atRisk'] = $s['below'] > 0 || $s['lowAttendance'];
        }
        unset($s);

        foreach ($tds as &$t) {
            $t['average'] = $t['gradeCount'] ? round($t['gradeSum'] / $t['gradeCount'], 2) : null;
            $t['belowShare'] = $t['gradeCount'] ? round(100 * $t['below'] / $t['gradeCount']) : null;
            $t['callShare'] = $t['expected'] > 0 ? min(100, round(100 * $t['lessons'] / $t['expected'])) : null;
        }
        unset($t);

        return [
            'classes' => $this->byClass($data['classes'], $tds, $students),
            'disciplines' => $this->byDiscipline($tds),
            'teachers' => $this->byTeacher($tds, $data['substitutions']),
            'disciplineClasses' => $tds,
            'students' => $students,
        ];
    }


    private function byClass(array $classes, array $tds, array $students)
    {

        $rows = [];
        foreach ($classes as $c) {
            $rows[(int) $c->id] = [
                'id' => (int) $c->id, 'label' => $c->label, 'students' => 0, 'avgSum' => 0, 'avgCount' => 0,
                'attSum' => 0, 'attCount' => 0, 'atRisk' => 0, 'occurrences' => 0, 'pendingAck' => 0,
                'pendingGrades' => 0, 'lessons' => 0, 'expected' => 0,
            ];
        }

        foreach ($students as $s) {
            if (!isset($rows[$s['classId']])) continue;
            $r = &$rows[$s['classId']];
            $r['students']++;
            if ($s['average'] !== null) { $r['avgSum'] += $s['average']; $r['avgCount']++; }
            if ($s['attendance'] !== null) { $r['attSum'] += $s['attendance']; $r['attCount']++; }
            if ($s['atRisk']) $r['atRisk']++;
            $r['occurrences'] += $s['occurrences'];
            $r['pendingAck'] += $s['pendingAck'];
            unset($r);
        }

        foreach ($tds as $t) {
            if (!isset($rows[$t['classId']])) continue;
            $rows[$t['classId']]['pendingGrades'] += $t['pendingGrades'];
            $rows[$t['classId']]['lessons'] += min($t['lessons'], $t['expected'] ?: $t['lessons']);
            $rows[$t['classId']]['expected'] += $t['expected'];
        }

        return array_values(array_map(function ($r) {
            $r['average'] = $r['avgCount'] ? round($r['avgSum'] / $r['avgCount'], 2) : null;
            $r['attendance'] = $r['attCount'] ? round($r['attSum'] / $r['attCount'], 1) : null;
            $r['callShare'] = $r['expected'] > 0 ? min(100, round(100 * $r['lessons'] / $r['expected'])) : null;
            return $r;
        }, $rows));
    }


    private function byDiscipline(array $tds)
    {

        $rows = [];
        foreach ($tds as $t) {
            $id = $t['disciplineId'];
            if (!isset($rows[$id])) {
                $rows[$id] = ['id' => $id, 'name' => $t['discipline'], 'classes' => [], 'gradeSum' => 0, 'gradeCount' => 0,
                              'below' => 0, 'pendingGrades' => 0, 'missingExams' => 0];
            }
            $rows[$id]['classes'][] = $t;
            $rows[$id]['gradeSum'] += $t['gradeSum'];
            $rows[$id]['gradeCount'] += $t['gradeCount'];
            $rows[$id]['below'] += $t['below'];
            $rows[$id]['pendingGrades'] += $t['pendingGrades'];
            $rows[$id]['missingExams'] += $t['missingExams'];
        }

        $rows = array_map(function ($r) {
            $r['average'] = $r['gradeCount'] ? round($r['gradeSum'] / $r['gradeCount'], 2) : null;
            $r['belowShare'] = $r['gradeCount'] ? round(100 * $r['below'] / $r['gradeCount']) : null;
            return $r;
        }, $rows);

        usort($rows, function ($a, $b) { return strcmp($a['name'], $b['name']); });

        return $rows;
    }


    private function byTeacher(array $tds, array $substitutions)
    {

        $given = [];
        foreach ($substitutions as $s) $given[(int) $s->teacher_id] = (int) $s->total;

        $rows = [];
        foreach ($tds as $t) {
            $id = $t['teacherId'];
            if (!isset($rows[$id])) {
                $rows[$id] = ['id' => $id, 'name' => $t['teacher'], 'classes' => [], 'disciplines' => [], 'assignments' => [],
                              'lessons' => 0, 'expected' => 0, 'calls' => 0, 'diary' => 0, 'exceptions' => 0,
                              'pendingGrades' => 0, 'missingExams' => 0, 'lastCall' => null, 'lastCallDate' => null,
                              'substitute' => $given[$id] ?? 0];
            }
            $r = &$rows[$id];
            $r['classes'][$t['classId']] = $t['classLabel'];
            $r['disciplines'][$t['disciplineId']] = $t['discipline'];
            $r['assignments'][] = $t;
            $r['lessons'] += min($t['lessons'], $t['expected'] ?: $t['lessons']);
            $r['expected'] += $t['expected'];
            $r['calls'] += $t['calls'];
            $r['diary'] += $t['diary'];
            $r['exceptions'] += $t['exceptions'];
            $r['pendingGrades'] += $t['pendingGrades'];
            $r['missingExams'] += $t['missingExams'];
            if ($t['lastCallDate'] && $t['lastCallDate'] > ($r['lastCallDate'] ?? '')) {
                $r['lastCallDate'] = $t['lastCallDate'];
                $r['lastCall'] = $t['lastCall'];
            }
            unset($r);
        }

        $rows = array_map(function ($r) {
            $r['callShare'] = $r['expected'] > 0 ? min(100, round(100 * $r['lessons'] / $r['expected'])) : null;
            // em dia: chamadas cobrindo 90% das aulas previstas e nenhuma nota pendente
            $r['status'] = $r['expected'] === 0 ? 'sem-grade'
                : (($r['callShare'] >= 90 && $r['pendingGrades'] === 0) ? 'em-dia' : 'atrasado');
            return $r;
        }, $rows);

        usort($rows, function ($a, $b) { return strcmp($a['name'], $b['name']); });

        return $rows;
    }


    private function fetch($sql, array $params)
    {

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) $stmt->bindValue($key, $value, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
}
