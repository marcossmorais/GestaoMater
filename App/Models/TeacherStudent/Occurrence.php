<?php

namespace App\Models\TeacherStudent;

use MF\Model\Model;

/**
 * Gestão de Ocorrências Disciplinares, Pedagógicas e Elogios
 */
class Occurrence extends Model
{

    /**
     * Retorna todas as ocorrências de um aluno por matrícula
     *
     * @param int $enrollmentId
     * @return array
     */
    public function getByEnrollment($enrollmentId)
    {
        $stmt = $this->db->prepare(
            "SELECT
                od.id_ocorrencia,
                od.fk_id_matricula,
                od.fk_id_aluno,
                od.categoria,
                od.titulo,
                od.descricao,
                od.gravidade,
                od.medida_tomada,
                od.visivel_responsavel,
                od.requer_ciencia,
                od.ciencia_responsavel,
                TO_CHAR(od.ciencia_em, 'DD/MM/YYYY HH24:MI') AS ciencia_em_fmt,
                od.registrado_por,
                TO_CHAR(od.data_ocorrencia, 'DD/MM/YYYY') AS data_ocorrencia_fmt,
                TO_CHAR(od.criado_em, 'DD/MM/YYYY HH24:MI') AS criado_em_fmt
            FROM ocorrencia_disciplinar od
            WHERE od.fk_id_matricula = :enrollmentId
            ORDER BY od.data_ocorrencia DESC, od.id_ocorrencia DESC"
        );

        $stmt->bindValue(':enrollmentId', (int) $enrollmentId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Retorna ocorrências visíveis para o Portal do Aluno/Família
     *
     * @param int $enrollmentId
     * @return array
     */
    public function getForPortal($enrollmentId)
    {
        $stmt = $this->db->prepare(
            "SELECT
                od.id_ocorrencia,
                od.categoria,
                od.titulo,
                od.descricao,
                od.gravidade,
                od.medida_tomada,
                od.requer_ciencia,
                od.ciencia_responsavel,
                od.registrado_por,
                TO_CHAR(od.data_ocorrencia, 'DD/MM/YYYY') AS data_ocorrencia_fmt
            FROM ocorrencia_disciplinar od
            WHERE od.fk_id_matricula = :enrollmentId
              AND od.visivel_responsavel = TRUE
            ORDER BY od.data_ocorrencia DESC, od.id_ocorrencia DESC"
        );

        $stmt->bindValue(':enrollmentId', (int) $enrollmentId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Resumo de ocorrências por categoria
     *
     * @param int $enrollmentId
     * @return object
     */
    public function getSummaryByEnrollment($enrollmentId)
    {
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*)::int AS total,
                COUNT(CASE WHEN categoria = 'Disciplinar' THEN 1 END)::int AS disciplinares,
                COUNT(CASE WHEN categoria = 'Pedagógica' THEN 1 END)::int AS pedagogicas,
                COUNT(CASE WHEN categoria = 'Elogio' THEN 1 END)::int AS elogios,
                COUNT(CASE WHEN categoria = 'Saúde' THEN 1 END)::int AS saude,
                COUNT(CASE WHEN requer_ciencia = TRUE AND ciencia_responsavel = FALSE THEN 1 END)::int AS pendentes_ciencia
            FROM ocorrencia_disciplinar
            WHERE fk_id_matricula = :enrollmentId"
        );

        $stmt->bindValue(':enrollmentId', (int) $enrollmentId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(\PDO::FETCH_OBJ) ?: (object) [
            'total' => 0,
            'disciplinares' => 0,
            'pedagogicas' => 0,
            'elogios' => 0,
            'saude' => 0,
            'pendentes_ciencia' => 0
        ];
    }

    /**
     * Cadastra nova ocorrência
     *
     * @param array $data
     * @return int
     */
    public function create(array $data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO ocorrencia_disciplinar (
                fk_id_matricula,
                fk_id_aluno,
                fk_id_turma_disciplina,
                categoria,
                titulo,
                descricao,
                gravidade,
                medida_tomada,
                visivel_responsavel,
                requer_ciencia,
                registrado_por,
                data_ocorrencia
            ) VALUES (
                :fk_id_matricula,
                :fk_id_aluno,
                :fk_id_turma_disciplina,
                :categoria,
                :titulo,
                :descricao,
                :gravidade,
                :medida_tomada,
                :visivel_responsavel,
                :requer_ciencia,
                :registrado_por,
                :data_ocorrencia
            ) RETURNING id_ocorrencia"
        );

        $stmt->bindValue(':fk_id_matricula', (int) $data['fk_id_matricula'], \PDO::PARAM_INT);
        $stmt->bindValue(':fk_id_aluno', (int) $data['fk_id_aluno'], \PDO::PARAM_INT);
        $stmt->bindValue(':fk_id_turma_disciplina', !empty($data['fk_id_turma_disciplina']) ? (int) $data['fk_id_turma_disciplina'] : null, \PDO::PARAM_INT);
        $stmt->bindValue(':categoria', $data['categoria'] ?? 'Disciplinar');
        $stmt->bindValue(':titulo', $data['titulo']);
        $stmt->bindValue(':descricao', $data['descricao']);
        $stmt->bindValue(':gravidade', $data['gravidade'] ?? 'Média');
        $stmt->bindValue(':medida_tomada', !empty($data['medida_tomada']) ? $data['medida_tomada'] : null);
        $stmt->bindValue(':visivel_responsavel', !empty($data['visivel_responsavel']), \PDO::PARAM_BOOL);
        $stmt->bindValue(':requer_ciencia', !empty($data['requer_ciencia']), \PDO::PARAM_BOOL);
        $stmt->bindValue(':registrado_por', $data['registrado_por'] ?? 'Coordenação Pedagógica');
        $stmt->bindValue(':data_ocorrencia', $data['data_ocorrencia'] ?? date('Y-m-d'));

        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Registra ciência do responsável
     *
     * @param int $id
     * @return bool
     */
    public function acknowledge($id, $enrollmentId)
    {
        $stmt = $this->db->prepare(
            "UPDATE ocorrencia_disciplinar
             SET ciencia_responsavel = TRUE, ciencia_em = CURRENT_TIMESTAMP
             WHERE id_ocorrencia = :id AND fk_id_matricula = :enrollment"
        );
        $stmt->bindValue(':id', (int) $id, \PDO::PARAM_INT);
        $stmt->bindValue(':enrollment', (int) $enrollmentId, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Remove ocorrência
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM ocorrencia_disciplinar WHERE id_ocorrencia = :id");
        $stmt->bindValue(':id', (int) $id, \PDO::PARAM_INT);
        return $stmt->execute();
    }
}
