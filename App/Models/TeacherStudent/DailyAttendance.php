<?php

namespace App\Models\TeacherStudent;

use MF\Model\Model;

/**
 * Chamada diária do Fund. 1 (curso.etapa = 'iniciais'; database/24_fund1_titular_chamada_diaria.sql).
 *
 * O professor titular da turma faz uma chamada por dia; a frequência é contada em dias:
 * dias letivos = dias com chamada; falta abonada = dia de falta coberto por atestado aprovado.
 * Fund. 2 e Ensino Médio continuam com a chamada por disciplina (Attendance).
 */
class DailyAttendance extends Model
{

    const STAGE = 'iniciais';

    // dia de falta coberto por atestado aprovado (usa o alias cd para chamada_diaria e :enrollment ou m.id_matricula)
    const EXCUSED = "EXISTS (SELECT 1 FROM atestado_medico am
                      WHERE am.fk_id_matricula = %s AND am.status = 'Aprovado'
                      AND cd.data_aula BETWEEN am.data_inicio AND COALESCE(am.data_fim, am.data_inicio))";


    /**
     * A turma conta a frequência por dia (Fund. 1)?
     */
    public function isDaily($classId)
    {

        $stmt = $this->db->prepare(
            "SELECT curso.etapa = :stage FROM turma INNER JOIN curso ON (turma.fk_id_curso = curso.id_curso) WHERE turma.id_turma = :class"
        );
        $stmt->execute([':stage' => self::STAGE, ':class' => (int) $classId]);

        return (bool) $stmt->fetchColumn();
    }


    /**
     * Turmas de Fund. 1 do período letivo ativo em que o professor é o titular, com total de alunos e a chamada de hoje
     */
    public function homeroomClasses($teacherId)
    {

        $stmt = $this->db->prepare(
            "SELECT turma.id_turma AS class_id,
                    serie.sigla || 'ª ' || cedula_turma.cedula AS label,
                    serie.sigla AS series_acronym,
                    curso.sigla AS course,
                    turno.nome_turno AS shift,
                    (SELECT COUNT(*) FROM matricula
                      WHERE matricula.fk_id_turma_matricula = turma.id_turma AND matricula.fk_id_situacao_aluno = 1) AS student_total,
                    (SELECT TO_CHAR(cd.atualizado_em, 'HH24:MI') FROM chamada_diaria cd
                      WHERE cd.fk_id_turma = turma.id_turma AND cd.data_aula = CURRENT_DATE) AS done_today_at
             FROM turma
             INNER JOIN curso ON (turma.fk_id_curso = curso.id_curso)
             INNER JOIN serie ON (turma.fk_id_serie = serie.id_serie)
             INNER JOIN cedula_turma ON (turma.fk_id_cedula = cedula_turma.id_cedula_turma)
             INNER JOIN turno ON (turma.fk_id_turno = turno.id_turno)
             INNER JOIN periodo_letivo ON (turma.fk_id_periodo_letivo = periodo_letivo.id_ano_letivo)
             WHERE turma.fk_id_professor_titular = :teacher AND curso.etapa = :stage
             AND periodo_letivo.fk_id_situacao_periodo_letivo = 1
             ORDER BY serie.sigla, cedula_turma.cedula"
        );
        $stmt->execute([':teacher' => (int) $teacherId, ':stage' => self::STAGE]);

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * A turma (Fund. 1) do período ativo, se o professor for o titular dela (ou null)
     */
    public function homeroomFor($teacherId, $classId)
    {

        foreach ($this->homeroomClasses($teacherId) as $c) {
            if ((int) $c->class_id === (int) $classId) return $c;
        }

        return null;
    }


    /**
     * Alunos da turma com os dias de falta no bimestre (sem os abonados) e se faltaram na data
     */
    public function students($classId, $unity, $date)
    {

        $stmt = $this->db->prepare(
            "SELECT m.id_matricula AS enrollment_id, aluno.nome_aluno AS name, aluno.foto_perfil_aluno AS photo,
                    (SELECT COUNT(*) FROM chamada_diaria cd
                      WHERE cd.fk_id_turma = m.fk_id_turma_matricula AND cd.fk_id_unidade = :unity AND cd.data_aula <> :date
                      AND m.id_matricula = ANY (cd.matriculas_ausentes)
                      AND NOT " . sprintf(self::EXCUSED, 'm.id_matricula') . ")::int AS absent_days,
                    EXISTS (SELECT 1 FROM chamada_diaria cd
                      WHERE cd.fk_id_turma = m.fk_id_turma_matricula AND cd.data_aula = :date
                      AND m.id_matricula = ANY (cd.matriculas_ausentes)) AS absent_on_date
             FROM matricula m
             INNER JOIN aluno ON (m.fk_id_aluno = aluno.id_aluno)
             WHERE m.fk_id_turma_matricula = :class AND m.fk_id_situacao_aluno = 1
             ORDER BY aluno.nome_aluno"
        );
        $stmt->execute([':class' => (int) $classId, ':unity' => (int) $unity, ':date' => $date]);

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Horário da chamada já feita na data (ou null)
     */
    public function doneAt($classId, $date)
    {

        $stmt = $this->db->prepare("SELECT TO_CHAR(atualizado_em, 'HH24:MI') FROM chamada_diaria WHERE fk_id_turma = :class AND data_aula = :date");
        $stmt->execute([':class' => (int) $classId, ':date' => $date]);

        return $stmt->fetchColumn() ?: null;
    }


    /**
     * Grava a chamada do dia. Se já existe, substitui a lista (corrige; não soma faltas).
     *
     * @param int[] $absentEnrollments matrículas já validadas como da turma
     * @return int id da chamada_diaria
     */
    public function save($classId, $unity, $teacherId, array $absentEnrollments, $presentTotal, $date)
    {

        $stmt = $this->db->prepare(
            "INSERT INTO chamada_diaria (fk_id_turma, fk_id_unidade, fk_id_professor, data_aula, total_presentes, total_ausentes, matriculas_ausentes)
             VALUES (:class, :unity, :teacher, :date, :present, :absent, :absentList)
             ON CONFLICT ON CONSTRAINT chamada_diaria_turma_data_key DO UPDATE SET
                fk_id_unidade = EXCLUDED.fk_id_unidade, fk_id_professor = EXCLUDED.fk_id_professor,
                total_presentes = EXCLUDED.total_presentes, total_ausentes = EXCLUDED.total_ausentes,
                matriculas_ausentes = EXCLUDED.matriculas_ausentes, atualizado_em = CURRENT_TIMESTAMP
             RETURNING id_chamada_diaria"
        );

        $stmt->execute([
            ':class' => (int) $classId,
            ':unity' => (int) $unity,
            ':teacher' => (int) $teacherId,
            ':date' => $date,
            ':present' => (int) $presentTotal,
            ':absent' => count($absentEnrollments),
            ':absentList' => '{' . implode(',', array_map('intval', $absentEnrollments)) . '}',
        ]);

        return (int) $stmt->fetchColumn();
    }


    /**
     * Frequência do aluno em dias: letivos, faltas, abonadas e o percentual (null sem chamada)
     *
     * @param int|null $unity só um bimestre (null = ano todo)
     */
    public function summary($enrollmentId, $classId, $unity = null)
    {

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS days,
                    COUNT(*) FILTER (WHERE :enrollment = ANY (cd.matriculas_ausentes)) AS absences,
                    COUNT(*) FILTER (WHERE :enrollment = ANY (cd.matriculas_ausentes) AND " . sprintf(self::EXCUSED, ':enrollment') . ") AS excused
             FROM chamada_diaria cd
             WHERE cd.fk_id_turma = :class AND (:unity = 0 OR cd.fk_id_unidade = :unity)"
        );
        $stmt->execute([':enrollment' => (int) $enrollmentId, ':class' => (int) $classId, ':unity' => (int) $unity]);
        $r = $stmt->fetch(\PDO::FETCH_OBJ);

        $days = (int) $r->days;
        $counted = max(0, (int) $r->absences - (int) $r->excused);

        return [
            'days' => $days,
            'absences' => (int) $r->absences,
            'excused' => (int) $r->excused,
            'attendance' => $days > 0 ? round(100 * ($days - $counted) / $days, 1) : null,
        ];
    }


    /**
     * Dias em que o aluno faltou, mais recentes primeiro, com a situação do atestado do dia
     */
    public function missedDays($enrollmentId)
    {

        $stmt = $this->db->prepare(
            "SELECT TO_CHAR(cd.data_aula, 'YYYY-MM-DD') AS date,
                    (SELECT am.status FROM atestado_medico am
                      WHERE am.fk_id_matricula = :enrollment AND cd.data_aula BETWEEN am.data_inicio AND COALESCE(am.data_fim, am.data_inicio)
                      ORDER BY CASE am.status WHEN 'Aprovado' THEN 1 WHEN 'Pendente' THEN 2 ELSE 3 END LIMIT 1) AS certificate
             FROM chamada_diaria cd
             INNER JOIN matricula m ON (m.id_matricula = :enrollment AND m.fk_id_turma_matricula = cd.fk_id_turma)
             WHERE :enrollment = ANY (cd.matriculas_ausentes)
             ORDER BY cd.data_aula DESC"
        );
        $stmt->execute([':enrollment' => (int) $enrollmentId]);

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Dias de falta no período do atestado (para abonar ao aceitar)
     */
    public function absentDaysBetween($enrollmentId, $start, $end)
    {

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM chamada_diaria cd
             WHERE :enrollment = ANY (cd.matriculas_ausentes) AND cd.data_aula BETWEEN :start AND :end"
        );
        $stmt->execute([':enrollment' => (int) $enrollmentId, ':start' => $start, ':end' => $end ?: $start]);

        return (int) $stmt->fetchColumn();
    }


    /**
     * Dias de falta (sem os abonados) por matrícula, nas turmas e no bimestre: [id_matricula => dias]
     *
     * @param int[] $classIds
     */
    public function absencesByEnrollment(array $classIds, $unity)
    {

        if (!$classIds) return [];

        $ids = implode(',', array_map('intval', $classIds));

        $rows = $this->db->prepare(
            "SELECT m.id_matricula AS id, COUNT(*)::int AS days
             FROM chamada_diaria cd
             INNER JOIN matricula m ON (m.fk_id_turma_matricula = cd.fk_id_turma AND m.id_matricula = ANY (cd.matriculas_ausentes) AND m.fk_id_situacao_aluno = 1)
             WHERE cd.fk_id_turma IN ($ids) AND cd.fk_id_unidade = :unity
             AND NOT " . sprintf(self::EXCUSED, 'm.id_matricula') . "
             GROUP BY 1"
        );
        $rows->execute([':unity' => (int) $unity]);

        $result = [];
        foreach ($rows->fetchAll(\PDO::FETCH_OBJ) as $r) $result[(int) $r->id] = (int) $r->days;

        return $result;
    }


    /**
     * Alunos com dias de falta (sem os abonados) no bimestre, nas turmas: nome e rótulo da turma, mais faltosos primeiro
     *
     * @param int[] $classIds
     * @return array [id, class_id, name, label, absences]
     */
    public function absentStudents(array $classIds, $unity, $minimum = 1)
    {

        if (!$classIds) return [];

        $ids = implode(',', array_map('intval', $classIds));

        $stmt = $this->db->prepare(
            "SELECT m.id_matricula AS id, m.fk_id_turma_matricula AS class_id, aluno.nome_aluno AS name,
                    serie.sigla || 'ª ' || cedula_turma.cedula AS label, COUNT(*)::int AS absences
             FROM chamada_diaria cd
             INNER JOIN matricula m ON (m.fk_id_turma_matricula = cd.fk_id_turma AND m.id_matricula = ANY (cd.matriculas_ausentes) AND m.fk_id_situacao_aluno = 1)
             INNER JOIN aluno ON (aluno.id_aluno = m.fk_id_aluno)
             INNER JOIN turma ON (turma.id_turma = m.fk_id_turma_matricula)
             INNER JOIN serie ON (turma.fk_id_serie = serie.id_serie)
             INNER JOIN cedula_turma ON (turma.fk_id_cedula = cedula_turma.id_cedula_turma)
             WHERE cd.fk_id_turma IN ($ids) AND cd.fk_id_unidade = :unity
             AND NOT " . sprintf(self::EXCUSED, 'm.id_matricula') . "
             GROUP BY 1, 2, 3, 4
             HAVING COUNT(*) >= :minimum
             ORDER BY absences DESC, name"
        );
        $stmt->execute([':unity' => (int) $unity, ':minimum' => (int) $minimum]);

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Total de dias de falta (sem os abonados) no bimestre, por turma: [id_turma => dias]
     *
     * @param int[] $classIds
     */
    public function absencesByClass(array $classIds, $unity)
    {

        $result = [];
        foreach ($this->absentStudents($classIds, $unity) as $s) {
            $result[(int) $s->class_id] = ($result[(int) $s->class_id] ?? 0) + (int) $s->absences;
        }

        return $result;
    }
}
