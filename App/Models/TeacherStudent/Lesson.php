<?php

namespace App\Models\TeacherStudent;

use MF\Model\Model;

/**
 * Diário de aula do portal do docente (database/12_diario_aula.sql).
 *
 * Cada aula guarda a habilidade BNCC trabalhada, a observação, as atividades realizadas
 * e, quando houver, a exceção (substituto ou matéria trocada) com o motivo.
 */
class Lesson extends Model
{

    const ACTIVITIES = [
        'teorica' => 'Aula teórica',
        'documentario' => 'Documentário',
        'questionario' => 'Questionário',
        'revisao' => 'Revisão',
        'pratica' => 'Atividade prática',
        'avaliacao' => 'Avaliação',
        'outro' => 'Outro',
    ];

    const BNCC_CODE = '/^(EI|EF|EM)\d{2}[A-Z]{2,3}\d{2,3}$/';


    /**
     * Grava a aula e as atividades. Use dentro de uma transação para várias disciplinas.
     *
     * @param array $data disciplineClassId, teacherId, date, bnccCode, bnccSkill, notes, activities[],
     *                    attendanceId, substituteId, taughtDisciplineClassId, reason
     * @return int id da aula
     */
    public function save(array $data)
    {

        $stmt = $this->db->prepare(
            "INSERT INTO aula
                (fk_id_turma_disciplina, fk_id_professor, fk_id_chamada, data_aula, habilidade_bncc_codigo, habilidade_bncc, observacao,
                 fk_id_professor_substituto, fk_id_turma_disciplina_ministrada, motivo_troca)
             VALUES
                (:disciplineClass, :teacher, :attendance, :date, :bnccCode, :bnccSkill, :notes, :substitute, :taught, :reason)
             RETURNING id_aula"
        );

        $stmt->execute([
            ':disciplineClass' => (int) $data['disciplineClassId'],
            ':teacher' => (int) $data['teacherId'],
            ':attendance' => $data['attendanceId'] ?: null,
            ':date' => $data['date'],
            ':bnccCode' => $data['bnccCode'] ?: null,
            ':bnccSkill' => $data['bnccSkill'] ?: null,
            ':notes' => $data['notes'] ?: null,
            ':substitute' => $data['substituteId'] ?: null,
            ':taught' => $data['taughtDisciplineClassId'] ?: null,
            ':reason' => $data['reason'] ?: null,
        ]);

        $id = (int) $stmt->fetchColumn();

        $activity = $this->db->prepare("INSERT INTO aula_atividade (fk_id_aula, tipo) VALUES (:lesson, :type)");

        foreach ($data['activities'] as $type) {
            $activity->execute([':lesson' => $id, ':type' => $type]);
        }

        return $id;
    }


    /**
     * Últimas aulas registradas nas turmas/disciplinas informadas
     *
     * @param int[] $disciplineClassIds
     * @return array
     */
    public function recent(array $disciplineClassIds, $limit = 30)
    {

        if (!$disciplineClassIds) return [];

        $ids = implode(',', array_map('intval', $disciplineClassIds));

        $stmt = $this->db->prepare(
            "SELECT
                aula.id_aula AS id,
                aula.fk_id_turma_disciplina AS discipline_class_id,
                TO_CHAR(aula.data_aula, 'DD/MM') AS date,
                aula.habilidade_bncc_codigo AS bncc_code,
                aula.habilidade_bncc AS bncc_skill,
                aula.observacao AS notes,
                aula.motivo_troca AS reason,
                disciplina.nome_disciplina AS discipline,
                serie.sigla AS series_acronym,
                cedula_turma.cedula AS ballot,
                professor.nome_professor AS teacher,
                substituto.nome_professor AS substitute,
                ministrada.nome_disciplina AS taught_discipline,
                COALESCE((SELECT string_agg(tipo, ',' ORDER BY tipo) FROM aula_atividade WHERE fk_id_aula = aula.id_aula), '') AS activities

            FROM aula

            INNER JOIN turma_disciplina ON (aula.fk_id_turma_disciplina = turma_disciplina.id_turma_disciplina)
            INNER JOIN disciplina ON (turma_disciplina.fk_id_disciplina = disciplina.id_disciplina)
            INNER JOIN turma ON (turma_disciplina.fk_id_turma = turma.id_turma)
            INNER JOIN serie ON (turma.fk_id_serie = serie.id_serie)
            INNER JOIN cedula_turma ON (turma.fk_id_cedula = cedula_turma.id_cedula_turma)
            INNER JOIN professor ON (aula.fk_id_professor = professor.id_professor)
            LEFT JOIN professor substituto ON (aula.fk_id_professor_substituto = substituto.id_professor)
            LEFT JOIN turma_disciplina td_ministrada ON (aula.fk_id_turma_disciplina_ministrada = td_ministrada.id_turma_disciplina)
            LEFT JOIN disciplina ministrada ON (td_ministrada.fk_id_disciplina = ministrada.id_disciplina)

            WHERE aula.fk_id_turma_disciplina IN ($ids)

            ORDER BY aula.data_aula DESC, aula.id_aula DESC
            LIMIT :limit"
        );

        $stmt->bindValue(':limit', (int) $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Última chamada do professor nessa turma/disciplina e data (para vincular a aula), ou null
     */
    public function attendanceFor($disciplineClassId, $date, $teacherId)
    {

        $stmt = $this->db->prepare(
            "SELECT MAX(id_chamada) FROM chamada
             WHERE fk_id_turma_disciplina = :disciplineClass AND data_aula = :date AND fk_id_professor = :teacher"
        );

        $stmt->execute([':disciplineClass' => (int) $disciplineClassId, ':date' => $date, ':teacher' => (int) $teacherId]);

        return $stmt->fetchColumn() ?: null;
    }


    /**
     * Turma e disciplina de uma turma_disciplina (matéria efetivamente dada), ou null
     */
    public function disciplineClass($disciplineClassId)
    {

        $stmt = $this->db->prepare(
            "SELECT turma_disciplina.fk_id_turma AS class_id, disciplina.nome_disciplina AS discipline_name
             FROM turma_disciplina
             INNER JOIN disciplina ON (turma_disciplina.fk_id_disciplina = disciplina.id_disciplina)
             WHERE turma_disciplina.id_turma_disciplina = :id"
        );

        $stmt->execute([':id' => (int) $disciplineClassId]);

        return $stmt->fetch(\PDO::FETCH_OBJ) ?: null;
    }


    /**
     * Todas as disciplinas das turmas informadas (para a troca de matéria)
     *
     * @param int[] $classIds
     */
    public function classDisciplines(array $classIds)
    {

        if (!$classIds) return [];

        $ids = implode(',', array_map('intval', $classIds));

        return $this->speedingUp(
            "SELECT turma_disciplina.id_turma_disciplina AS id, turma_disciplina.fk_id_turma AS class_id, disciplina.nome_disciplina AS name
             FROM turma_disciplina
             INNER JOIN disciplina ON (turma_disciplina.fk_id_disciplina = disciplina.id_disciplina)
             WHERE turma_disciplina.fk_id_turma IN ($ids)
             ORDER BY disciplina.nome_disciplina"
        );
    }


    /**
     * Existe o professor? (substituto)
     */
    public function teacherExists($teacherId)
    {

        $stmt = $this->db->prepare("SELECT 1 FROM professor WHERE id_professor = :id");
        $stmt->execute([':id' => (int) $teacherId]);

        return (bool) $stmt->fetchColumn();
    }


    /**
     * Professores para escolher o substituto
     */
    public function teachers()
    {

        return $this->speedingUp(
            "SELECT id_professor AS id, nome_professor AS name FROM professor ORDER BY nome_professor"
        );
    }
}
