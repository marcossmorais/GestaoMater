<?php

namespace App\Models\TeacherStudent;

use MF\Model\Model;

/**
 * Diário do dia do Fund. 1 (database/26_fund1_diario_do_dia.sql): o titular escreve um texto único por dia
 * para a turma, com códigos BNCC opcionais. Uma linha por turma e dia; salvar de novo corrige.
 */
class DailyLog extends Model
{

    const MAX_TEXT = 4000;
    const MAX_CODES = 10;


    /**
     * Diário da turma na data, ou null
     */
    public function find($classId, $date)
    {

        $stmt = $this->db->prepare(
            "SELECT diario_dia.id_diario_dia AS id, diario_dia.texto AS text,
                    array_to_string(diario_dia.habilidades_bncc, ',') AS codes,
                    professor.nome_professor AS teacher,
                    TO_CHAR(diario_dia.atualizado_em, 'DD/MM \"às\" HH24:MI') AS updated_at
             FROM diario_dia
             INNER JOIN professor ON (diario_dia.fk_id_professor = professor.id_professor)
             WHERE diario_dia.fk_id_turma = :class AND diario_dia.data_aula = :date"
        );
        $stmt->execute([':class' => (int) $classId, ':date' => $date]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);

        if (!$row) return null;
        $row->codes = $row->codes === '' ? [] : explode(',', $row->codes);

        return $row;
    }


    /**
     * Grava (ou corrige) o diário da turma na data
     *
     * @param string[] $codes códigos BNCC já validados
     * @return int id do diário
     */
    public function save($classId, $teacherId, $date, $text, array $codes)
    {

        $stmt = $this->db->prepare(
            "INSERT INTO diario_dia (fk_id_turma, fk_id_professor, data_aula, texto, habilidades_bncc)
             VALUES (:class, :teacher, :date, :text, :codes)
             ON CONFLICT ON CONSTRAINT diario_dia_turma_data_key DO UPDATE SET
                fk_id_professor = EXCLUDED.fk_id_professor, texto = EXCLUDED.texto,
                habilidades_bncc = EXCLUDED.habilidades_bncc, atualizado_em = CURRENT_TIMESTAMP
             RETURNING id_diario_dia"
        );

        $stmt->execute([
            ':class' => (int) $classId,
            ':teacher' => (int) $teacherId,
            ':date' => $date,
            ':text' => $text,
            ':codes' => '{' . implode(',', $codes) . '}',
        ]);

        return (int) $stmt->fetchColumn();
    }


    /**
     * Últimos dias da turma com chamada ou diário (mais recente primeiro), para a lista da aba
     */
    public function recent($classId, $days = 14)
    {

        $stmt = $this->db->prepare(
            "SELECT TO_CHAR(dia, 'YYYY-MM-DD') AS date, TO_CHAR(dia, 'DD/MM') AS short_date,
                    EXTRACT(ISODOW FROM dia)::int AS weekday,
                    EXISTS (SELECT 1 FROM chamada_diaria cd WHERE cd.fk_id_turma = :class AND cd.data_aula = dia) AS attendance,
                    (SELECT texto FROM diario_dia dd WHERE dd.fk_id_turma = :class AND dd.data_aula = dia) AS text
             FROM (
                SELECT data_aula AS dia FROM chamada_diaria WHERE fk_id_turma = :class AND data_aula >= CURRENT_DATE - :days
                UNION
                SELECT data_aula FROM diario_dia WHERE fk_id_turma = :class AND data_aula >= CURRENT_DATE - :days
             ) dias
             ORDER BY dia DESC"
        );
        $stmt->bindValue(':class', (int) $classId, \PDO::PARAM_INT);
        $stmt->bindValue(':days', (int) $days, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Pendências da home: dias com chamada diária e sem diário do dia (últimos 14 dias), nas turmas do titular
     *
     * @param int[] $classIds
     */
    public function missing(array $classIds)
    {

        if (!$classIds) return [];

        $ids = implode(',', array_map('intval', $classIds));

        return $this->db->query(
            "SELECT cd.fk_id_turma AS class_id, TO_CHAR(cd.data_aula, 'YYYY-MM-DD') AS date
             FROM chamada_diaria cd
             WHERE cd.fk_id_turma IN ($ids) AND cd.data_aula >= CURRENT_DATE - 14
             AND NOT EXISTS (SELECT 1 FROM diario_dia dd WHERE dd.fk_id_turma = cd.fk_id_turma AND dd.data_aula = cd.data_aula)
             ORDER BY cd.data_aula DESC
             LIMIT 20"
        )->fetchAll(\PDO::FETCH_OBJ);
    }
}
