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
}
