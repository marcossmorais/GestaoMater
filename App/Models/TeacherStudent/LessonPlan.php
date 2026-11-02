<?php

namespace App\Models\TeacherStudent;

use MF\Model\Model;

/**
 * Planejamento de aula e leituras do calendário do diário (database/19_planejamento_tipos_avaliacao.sql).
 */
class LessonPlan extends Model
{

    /**
     * Grava o plano (novo ou existente) e as atividades previstas
     *
     * @param array $data id?, disciplineClassId, teacherId, date, title, lessons, bnccCode, bnccSkill,
     *                    objectives, development, resources, assessment, homework, activities[]
     * @return int id do plano
     */
    public function save(array $data)
    {

        $params = [
            ':disciplineClass' => (int) $data['disciplineClassId'],
            ':date' => $data['date'],
            ':title' => $data['title'],
            ':lessons' => (int) $data['lessons'],
            ':bnccCode' => $data['bnccCode'] ?: null,
            ':bnccSkill' => $data['bnccSkill'] ?: null,
            ':objectives' => $data['objectives'] ?: null,
            ':development' => $data['development'] ?: null,
            ':resources' => $data['resources'] ?: null,
            ':assessment' => $data['assessment'] ?: null,
            ':homework' => $data['homework'] ?: null,
        ];

        $this->db->beginTransaction();

        try {
            if (!empty($data['id'])) {
                $id = (int) $data['id'];
                $stmt = $this->db->prepare(
                    "UPDATE plano_aula SET fk_id_turma_disciplina = :disciplineClass, data_aula = :date, titulo = :title, quantidade_aulas = :lessons,
                        habilidade_bncc_codigo = :bnccCode, habilidade_bncc = :bnccSkill, objetivos = :objectives, desenvolvimento = :development,
                        recursos = :resources, avaliacao = :assessment, tarefa = :homework, atualizado_em = CURRENT_TIMESTAMP
                     WHERE id_plano = :id"
                );
                $stmt->execute($params + [':id' => $id]);
            } else {
                $stmt = $this->db->prepare(
                    "INSERT INTO plano_aula (fk_id_turma_disciplina, fk_id_professor, data_aula, titulo, quantidade_aulas, habilidade_bncc_codigo,
                        habilidade_bncc, objetivos, desenvolvimento, recursos, avaliacao, tarefa)
                     VALUES (:disciplineClass, :teacher, :date, :title, :lessons, :bnccCode, :bnccSkill, :objectives, :development, :resources, :assessment, :homework)
                     RETURNING id_plano"
                );
                $stmt->execute($params + [':teacher' => (int) $data['teacherId']]);
                $id = (int) $stmt->fetchColumn();
            }

            $this->db->prepare("DELETE FROM plano_aula_atividade WHERE fk_id_plano = :id")->execute([':id' => $id]);

            $activity = $this->db->prepare("INSERT INTO plano_aula_atividade (fk_id_plano, tipo) VALUES (:id, :type)");
            foreach ($data['activities'] as $type) {
                $activity->execute([':id' => $id, ':type' => $type]);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }

        return $id;
    }


    public function find($planId)
    {

        $stmt = $this->db->prepare(
            "SELECT id_plano AS id, fk_id_turma_disciplina AS discipline_class_id, fk_id_professor AS teacher_id, fk_id_aula AS lesson_id
             FROM plano_aula WHERE id_plano = :id"
        );

        $stmt->execute([':id' => (int) $planId]);

        return $stmt->fetch(\PDO::FETCH_OBJ) ?: null;
    }


    public function delete($planId)
    {

        return $this->db->prepare("DELETE FROM plano_aula WHERE id_plano = :id")->execute([':id' => (int) $planId]);
    }


    /**
     * Liga o plano à aula registrada
     */
    public function markGiven($planId, $lessonId)
    {

        return $this->db->prepare("UPDATE plano_aula SET fk_id_aula = :lesson WHERE id_plano = :id")
            ->execute([':lesson' => (int) $lessonId, ':id' => (int) $planId]);
    }


    /**
     * Planos do professor nas turmas/disciplinas e no período informados
     *
     * @param int[] $disciplineClassIds
     */
    public function between($teacherId, array $disciplineClassIds, $from, $to)
    {

        if (!$disciplineClassIds) return [];

        $ids = implode(',', array_map('intval', $disciplineClassIds));

        $stmt = $this->db->prepare(
            "SELECT id_plano AS id, fk_id_turma_disciplina AS discipline_class_id, fk_id_aula AS lesson_id,
                    TO_CHAR(data_aula, 'YYYY-MM-DD') AS date, titulo AS title, quantidade_aulas AS lessons,
                    habilidade_bncc_codigo AS bncc_code, habilidade_bncc AS bncc_skill, objetivos AS objectives,
                    desenvolvimento AS development, recursos AS resources, avaliacao AS assessment, tarefa AS homework,
                    COALESCE((SELECT string_agg(tipo, ',' ORDER BY tipo) FROM plano_aula_atividade WHERE fk_id_plano = plano_aula.id_plano), '') AS activities
             FROM plano_aula
             WHERE fk_id_professor = :teacher AND fk_id_turma_disciplina IN ($ids) AND data_aula BETWEEN :from AND :to
             ORDER BY data_aula, id_plano"
        );

        $stmt->execute([':teacher' => (int) $teacherId, ':from' => $from, ':to' => $to]);

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Avaliações marcadas no período, nas turmas/disciplinas informadas
     *
     * @param int[] $disciplineClassIds
     */
    public function examsBetween(array $disciplineClassIds, $from, $to)
    {

        if (!$disciplineClassIds) return [];

        $ids = implode(',', array_map('intval', $disciplineClassIds));

        $stmt = $this->db->prepare(
            "SELECT id_avaliacao AS id, fk_id_turma_disciplina_avaliacao AS discipline_class_id, descricao_avaliacao AS description,
                    TO_CHAR(data_realizada, 'YYYY-MM-DD') AS date
             FROM avaliacoes
             WHERE fk_id_turma_disciplina_avaliacao IN ($ids) AND data_realizada::date BETWEEN :from AND :to
             ORDER BY data_realizada, id_avaliacao"
        );

        $stmt->execute([':from' => $from, ':to' => $to]);

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Eventos do cronograma que tocam o período: os da escola e os das turmas informadas
     *
     * @param int[] $classIds
     */
    public function eventsBetween(array $classIds, $from, $to)
    {

        $ids = implode(',', array_map('intval', $classIds)) ?: '0';

        $stmt = $this->db->prepare(
            "SELECT id_evento AS id, titulo AS title, tipo AS type,
                    TO_CHAR(data_inicio, 'YYYY-MM-DD') AS start, TO_CHAR(COALESCE(data_fim, data_inicio), 'YYYY-MM-DD') AS finish
             FROM evento_escolar
             WHERE (fk_id_turma IS NULL OR fk_id_turma IN ($ids))
               AND data_inicio <= :to AND COALESCE(data_fim, data_inicio) >= :from
             ORDER BY data_inicio, id_evento"
        );

        $stmt->execute([':from' => $from, ':to' => $to]);

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Horário semanal do professor; aulas seguidas da mesma turma/disciplina viram uma só
     *
     * @return array [discipline_class_id, weekday (1 = segunda), start, finish, count]
     */
    public function weeklySlots($teacherId)
    {

        $stmt = $this->db->prepare(
            "SELECT gh.fk_id_turma_disciplina AS discipline_class_id, gh.dia_semana - 1 AS weekday,
                    TO_CHAR(ha.hora_inicio, 'HH24:MI') AS start, TO_CHAR(ha.hora_fim, 'HH24:MI') AS finish
             FROM grade_horario gh
             INNER JOIN horario_aula ha ON (gh.fk_id_horario_aula = ha.id_horario_aula)
             INNER JOIN turma_disciplina td ON (gh.fk_id_turma_disciplina = td.id_turma_disciplina)
             INNER JOIN turma t ON (gh.fk_id_turma = t.id_turma)
             INNER JOIN periodo_letivo pl ON (t.fk_id_periodo_letivo = pl.id_ano_letivo)
             WHERE td.fk_id_professor = :teacher AND pl.fk_id_situacao_periodo_letivo = 1
             ORDER BY gh.dia_semana, ha.hora_inicio"
        );

        $stmt->execute([':teacher' => (int) $teacherId]);

        $slots = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $slot) {
            $last = end($slots);
            if ($last && $last->weekday === $slot->weekday && $last->discipline_class_id === $slot->discipline_class_id && $last->finish <= $slot->start) {
                $last->finish = $slot->finish;
                $last->count++;
                continue;
            }
            $slot->count = 1;
            $slots[] = $slot;
        }

        return $slots;
    }
}
