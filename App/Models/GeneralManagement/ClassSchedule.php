<?php

namespace App\Models\GeneralManagement;

use MF\Model\Model;

/**
 * Gestão de Grade de Horários Semanal (Quadro de Aulas)
 */
class ClassSchedule extends Model
{

    public static function dayNames()
    {
        return [
            2 => 'Segunda-feira',
            3 => 'Terça-feira',
            4 => 'Quarta-feira',
            5 => 'Quinta-feira',
            6 => 'Sexta-feira',
            7 => 'Sábado',
        ];
    }

    public static function shortDayNames()
    {
        return [
            2 => 'Segunda',
            3 => 'Terça',
            4 => 'Quarta',
            5 => 'Quinta',
            6 => 'Sexta',
            7 => 'Sábado',
        ];
    }

    /**
     * Retorna a grade semanal de uma turma (para o Portal do Aluno e Secretaria)
     *
     * @param int $classId
     * @return array
     */
    public function getScheduleByClass($classId)
    {
        $stmt = $this->db->prepare(
            "SELECT
                gh.id_grade_horario,
                gh.fk_id_turma_disciplina AS discipline_class_id,
                gh.dia_semana,
                ha.ordem_aula,
                TO_CHAR(ha.hora_inicio, 'HH24:MI') AS hora_inicio,
                TO_CHAR(ha.hora_fim, 'HH24:MI') AS hora_fim,
                d.nome_disciplina,
                d.sigla_disciplina,
                p.nome_professor,
                nsa.numero_sala_aula AS sala
            FROM grade_horario gh
            INNER JOIN horario_aula ha ON (gh.fk_id_horario_aula = ha.id_horario_aula)
            INNER JOIN turma_disciplina td ON (gh.fk_id_turma_disciplina = td.id_turma_disciplina)
            INNER JOIN disciplina d ON (td.fk_id_disciplina = d.id_disciplina)
            INNER JOIN professor p ON (td.fk_id_professor = p.id_professor)
            INNER JOIN turma t ON (gh.fk_id_turma = t.id_turma)
            INNER JOIN sala s ON (t.fk_id_sala = s.id_sala)
            INNER JOIN numero_sala_aula nsa ON (s.id_sala = nsa.id_numero_sala_aula)
            WHERE gh.fk_id_turma = :classId
            ORDER BY gh.dia_semana, ha.ordem_aula"
        );

        $stmt->bindValue(':classId', (int) $classId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Retorna a grade semanal do professor com suas turmas (para o Portal do Docente)
     *
     * @param int $teacherId
     * @return array
     */
    public function getScheduleByTeacher($teacherId)
    {
        $stmt = $this->db->prepare(
            "SELECT
                gh.id_grade_horario,
                gh.fk_id_turma_disciplina AS discipline_class_id,
                gh.dia_semana,
                ha.ordem_aula,
                TO_CHAR(ha.hora_inicio, 'HH24:MI') AS hora_inicio,
                TO_CHAR(ha.hora_fim, 'HH24:MI') AS hora_fim,
                d.nome_disciplina,
                d.sigla_disciplina,
                serie.sigla AS series_acronym,
                cedula_turma.cedula AS ballot,
                curso.sigla AS course,
                turno.nome_turno AS shift,
                nsa.numero_sala_aula AS sala,
                t.id_turma AS class_id
            FROM grade_horario gh
            INNER JOIN horario_aula ha ON (gh.fk_id_horario_aula = ha.id_horario_aula)
            INNER JOIN turma_disciplina td ON (gh.fk_id_turma_disciplina = td.id_turma_disciplina)
            INNER JOIN disciplina d ON (td.fk_id_disciplina = d.id_disciplina)
            INNER JOIN turma t ON (gh.fk_id_turma = t.id_turma)
            INNER JOIN serie ON (t.fk_id_serie = serie.id_serie)
            INNER JOIN cedula_turma ON (t.fk_id_cedula = cedula_turma.id_cedula_turma)
            INNER JOIN curso ON (t.fk_id_curso = curso.id_curso)
            INNER JOIN turno ON (t.fk_id_turno = turno.id_turno)
            INNER JOIN sala s ON (t.fk_id_sala = s.id_sala)
            INNER JOIN numero_sala_aula nsa ON (s.id_sala = nsa.id_numero_sala_aula)
            INNER JOIN periodo_letivo pl ON (t.fk_id_periodo_letivo = pl.id_ano_letivo)
            WHERE td.fk_id_professor = :teacherId
            AND pl.fk_id_situacao_periodo_letivo = 1
            ORDER BY gh.dia_semana, ha.hora_inicio, serie.sigla"
        );

        $stmt->bindValue(':teacherId', (int) $teacherId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    // ------------------------------------------------------------------ Montagem pelo admin (perfil da turma → Horário)

    const WEEKDAYS = [2, 3, 4, 5, 6]; // segunda a sexta (dia_semana: 2 = segunda)


    /**
     * Faixas de horário que a turma usa: as do turno dela (database/28_grade_horario_admin.sql) e da etapa,
     * quando a etapa tem faixas próprias no turno (Fund. 1, database/29_horario_por_etapa.sql); senão, as padrão
     */
    public function slotsForClass($classId)
    {
        $stmt = $this->db->prepare(
            "SELECT ha.id_horario_aula AS id, ha.ordem_aula AS position,
                    TO_CHAR(ha.hora_inicio, 'HH24:MI') AS start, TO_CHAR(ha.hora_fim, 'HH24:MI') AS finish
             FROM horario_aula ha
             INNER JOIN turma t ON (t.fk_id_turno = ha.fk_id_turno)
             INNER JOIN curso c ON (c.id_curso = t.fk_id_curso)
             WHERE t.id_turma = :classId
             AND ha.etapa IS NOT DISTINCT FROM (
                CASE WHEN EXISTS (SELECT 1 FROM horario_aula propria WHERE propria.fk_id_turno = t.fk_id_turno AND propria.etapa = c.etapa)
                     THEN c.etapa END
             )
             ORDER BY ha.hora_inicio"
        );
        $stmt->execute([':classId' => (int) $classId]);

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Disciplinas da turma que podem entrar na grade, com o professor.
     * Fund. 1: só as dos especialistas (o resto do dia é do titular, que não entra na grade).
     */
    public function disciplinesForClass($classId)
    {
        $stmt = $this->db->prepare(
            "SELECT td.id_turma_disciplina AS id, d.nome_disciplina AS discipline, p.nome_professor AS teacher
             FROM turma_disciplina td
             INNER JOIN disciplina d ON (td.fk_id_disciplina = d.id_disciplina)
             INNER JOIN professor p ON (td.fk_id_professor = p.id_professor)
             INNER JOIN turma t ON (t.id_turma = td.fk_id_turma)
             INNER JOIN curso c ON (c.id_curso = t.fk_id_curso)
             WHERE td.fk_id_turma = :classId
             AND NOT (c.etapa = :stage AND td.fk_id_professor IS NOT DISTINCT FROM t.fk_id_professor_titular)
             ORDER BY d.nome_disciplina"
        );
        $stmt->execute([':classId' => (int) $classId, ':stage' => \App\Models\TeacherStudent\DailyAttendance::STAGE]);

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Fund. 1: nome do professor titular da turma (preenche os horários sem especialista); null nas outras etapas
     */
    public function homeroomTeacherName($classId)
    {
        $stmt = $this->db->prepare(
            "SELECT p.nome_professor
             FROM turma t
             INNER JOIN curso c ON (c.id_curso = t.fk_id_curso)
             INNER JOIN professor p ON (p.id_professor = t.fk_id_professor_titular)
             WHERE t.id_turma = :classId AND c.etapa = :stage"
        );
        $stmt->execute([':classId' => (int) $classId, ':stage' => \App\Models\TeacherStudent\DailyAttendance::STAGE]);

        return $stmt->fetchColumn() ?: null;
    }


    /**
     * Grade atual da turma: [dia_semana][id da faixa] => id da turma_disciplina
     */
    public function gridForClass($classId)
    {
        $stmt = $this->db->prepare("SELECT dia_semana, fk_id_horario_aula, fk_id_turma_disciplina FROM grade_horario WHERE fk_id_turma = :classId");
        $stmt->execute([':classId' => (int) $classId]);

        $grid = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_NUM) as [$day, $slot, $disciplineClass]) $grid[(int) $day][(int) $slot] = (int) $disciplineClass;

        return $grid;
    }


    /**
     * Grava a grade inteira da turma (substitui a anterior).
     *
     * @param array $cells [dia_semana][id da faixa] => id da turma_disciplina (vazio = sem aula)
     * @return array|null null se gravou; senão ['error' => código, 'conflicts' => [...]]
     *                    códigos: 'celula' (dia, faixa ou disciplina fora da turma), 'choque' (professor em outra turma no mesmo horário)
     */
    public function saveGrid($classId, array $cells)
    {
        $classId = (int) $classId;
        $slots = array_map(function ($s) { return (int) $s->id; }, $this->slotsForClass($classId));
        $disciplines = array_map(function ($d) { return (int) $d->id; }, $this->disciplinesForClass($classId));

        $rows = [];
        foreach ($cells as $day => $bySlot) {
            foreach ((array) $bySlot as $slot => $disciplineClass) {
                $disciplineClass = (int) $disciplineClass;
                if (!$disciplineClass) continue;
                if (!in_array((int) $day, self::WEEKDAYS, true) || !in_array((int) $slot, $slots, true) || !in_array($disciplineClass, $disciplines, true)) {
                    return ['error' => 'celula', 'conflicts' => []];
                }
                $rows[] = [(int) $day, (int) $slot, $disciplineClass];
            }
        }

        $this->db->beginTransaction();

        try {
            // trava a turma: duas gravações ao mesmo tempo não se misturam
            $this->db->prepare("SELECT 1 FROM turma WHERE id_turma = :classId FOR UPDATE")->execute([':classId' => $classId]);

            $conflicts = $rows ? $this->conflicts($classId, $rows) : [];
            if ($conflicts) {
                $this->db->rollBack();
                return ['error' => 'choque', 'conflicts' => $conflicts];
            }

            $this->db->prepare("DELETE FROM grade_horario WHERE fk_id_turma = :classId")->execute([':classId' => $classId]);

            $insert = $this->db->prepare(
                "INSERT INTO grade_horario (fk_id_turma, fk_id_turma_disciplina, dia_semana, fk_id_horario_aula) VALUES (:classId, :disciplineClass, :day, :slot)"
            );
            foreach ($rows as [$day, $slot, $disciplineClass]) {
                $insert->execute([':classId' => $classId, ':disciplineClass' => $disciplineClass, ':day' => $day, ':slot' => $slot]);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }

        return null;
    }


    /**
     * Professor que já tem aula em outra turma (do período letivo ativo) no mesmo dia e em horário que se cruza
     *
     * @param array $rows [[dia, faixa, turma_disciplina], ...] já validados
     */
    private function conflicts($classId, array $rows)
    {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT p.nome_professor AS teacher, nova.dia AS day,
                    TO_CHAR(ha.hora_inicio, 'HH24:MI') AS start,
                    serie.sigla || 'ª ' || cedula_turma.cedula || ' ' || curso.sigla AS other_class
             FROM unnest(CAST(:days AS int[]), CAST(:slots AS int[]), CAST(:disciplineClasses AS int[])) AS nova (dia, faixa, td)
             INNER JOIN turma_disciplina td ON (td.id_turma_disciplina = nova.td)
             INNER JOIN professor p ON (p.id_professor = td.fk_id_professor)
             INNER JOIN horario_aula ha ON (ha.id_horario_aula = nova.faixa)
             INNER JOIN grade_horario outra ON (outra.dia_semana = nova.dia AND outra.fk_id_turma <> :classId)
             INNER JOIN turma_disciplina td2 ON (td2.id_turma_disciplina = outra.fk_id_turma_disciplina AND td2.fk_id_professor = td.fk_id_professor)
             INNER JOIN horario_aula ha2 ON (ha2.id_horario_aula = outra.fk_id_horario_aula
                                             AND ha2.hora_inicio < ha.hora_fim AND ha2.hora_fim > ha.hora_inicio)
             INNER JOIN turma t2 ON (t2.id_turma = outra.fk_id_turma)
             INNER JOIN periodo_letivo pl ON (pl.id_ano_letivo = t2.fk_id_periodo_letivo AND pl.fk_id_situacao_periodo_letivo = 1)
             INNER JOIN serie ON (serie.id_serie = t2.fk_id_serie)
             INNER JOIN cedula_turma ON (cedula_turma.id_cedula_turma = t2.fk_id_cedula)
             INNER JOIN curso ON (curso.id_curso = t2.fk_id_curso)
             ORDER BY nova.dia, start
             LIMIT 10"
        );

        $stmt->execute([
            ':classId' => (int) $classId,
            ':days' => '{' . implode(',', array_column($rows, 0)) . '}',
            ':slots' => '{' . implode(',', array_column($rows, 1)) . '}',
            ':disciplineClasses' => '{' . implode(',', array_column($rows, 2)) . '}',
        ]);

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
}
