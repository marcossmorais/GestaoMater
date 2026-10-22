<?php

namespace App\Models\TeacherStudent;

use MF\Model\Model;

/**
 * Gestão de Atestados Médicos e Justificativa / Abono de Faltas
 */
class MedicalCertificate extends Model
{

    /**
     * Retorna todos os atestados de uma matrícula
     *
     * @param int $enrollmentId
     * @return array
     */
    public function getByEnrollment($enrollmentId)
    {
        $stmt = $this->db->prepare(
            "SELECT
                am.id_atestado,
                am.fk_id_matricula,
                am.fk_id_aluno,
                TO_CHAR(am.data_inicio, 'DD/MM/YYYY') AS data_inicio_formatada,
                TO_CHAR(am.data_fim, 'DD/MM/YYYY') AS data_fim_formatada,
                am.data_inicio,
                am.data_fim,
                am.dias,
                am.motivo,
                am.cid,
                am.medico_nome,
                am.medico_crm,
                am.instituicao_saude,
                am.status,
                am.faltas_abonadas,
                am.observacao,
                am.anexo_documento,
                am.registrado_por,
                TO_CHAR(am.criado_em, 'DD/MM/YYYY HH24:MI') AS criado_em_formatado
            FROM atestado_medico am
            WHERE am.fk_id_matricula = :enrollmentId
            ORDER BY am.data_inicio DESC, am.id_atestado DESC"
        );

        $stmt->bindValue(':enrollmentId', (int) $enrollmentId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Retorna resumo de faltas, abonos e atestados de uma matrícula
     *
     * @param int $enrollmentId
     * @return object
     */
    public function getSummaryByEnrollment($enrollmentId)
    {
        $stmt = $this->db->prepare(
            "SELECT
                COALESCE((SELECT SUM(total_faltas) FROM falta_aluno WHERE fk_id_matricula_falta = :e1), 0)::int AS total_faltas,
                COALESCE((SELECT SUM(faltas_abonadas) FROM falta_aluno WHERE fk_id_matricula_falta = :e2), 0)::int AS faltas_abonadas,
                COALESCE((SELECT COUNT(*) FROM atestado_medico WHERE fk_id_matricula = :e3), 0)::int AS total_atestados"
        );

        $eid = (int) $enrollmentId;
        $stmt->bindValue(':e1', $eid, \PDO::PARAM_INT);
        $stmt->bindValue(':e2', $eid, \PDO::PARAM_INT);
        $stmt->bindValue(':e3', $eid, \PDO::PARAM_INT);
        $stmt->execute();

        $res = $stmt->fetch(\PDO::FETCH_OBJ);
        if (!$res) {
            return (object) [
                'total_faltas' => 0,
                'faltas_abonadas' => 0,
                'faltas_injustificadas' => 0,
                'total_atestados' => 0
            ];
        }

        $res->faltas_injustificadas = max(0, $res->total_faltas - $res->faltas_abonadas);
        return $res;
    }

    /**
     * Busca atestados ativos de alunos de uma turma em uma data específica
     * Utilizado na Chamada Rápida do Docente para exibir alerta visual
     *
     * @param int $classId
     * @param string $date (YYYY-MM-DD)
     * @return array Mapa [matricula_id => [dados_atestado]]
     */
    public function getActiveCertificatesForClassAndDate($classId, $date)
    {
        $stmt = $this->db->prepare(
            "SELECT
                am.fk_id_matricula,
                am.motivo,
                am.dias,
                am.medico_nome,
                am.cid,
                TO_CHAR(am.data_inicio, 'DD/MM') AS data_inicio_fmt,
                TO_CHAR(am.data_fim, 'DD/MM') AS data_fim_fmt
            FROM atestado_medico am
            INNER JOIN matricula m ON (am.fk_id_matricula = m.id_matricula)
            WHERE m.fk_id_turma_matricula = :classId
              AND am.status = 'Aprovado'
              AND :dateVal BETWEEN am.data_inicio AND am.data_fim"
        );

        $stmt->bindValue(':classId', (int) $classId, \PDO::PARAM_INT);
        $stmt->bindValue(':dateVal', $date);
        $stmt->execute();

        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $map = [];
        foreach ($rows as $r) {
            $map[$r->fk_id_matricula] = $r;
        }

        return $map;
    }

    /**
     * Cadastra um novo atestado e aplica abono de faltas se solicitado
     *
     * @param array $data
     * @return int ID do atestado inserido
     */
    public function create(array $data)
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO atestado_medico (
                    fk_id_matricula,
                    fk_id_aluno,
                    data_inicio,
                    data_fim,
                    dias,
                    motivo,
                    cid,
                    medico_nome,
                    medico_crm,
                    instituicao_saude,
                    status,
                    faltas_abonadas,
                    observacao,
                    anexo_documento,
                    registrado_por
                ) VALUES (
                    :fk_id_matricula,
                    :fk_id_aluno,
                    :data_inicio,
                    :data_fim,
                    :dias,
                    :motivo,
                    :cid,
                    :medico_nome,
                    :medico_crm,
                    :instituicao_saude,
                    :status,
                    :faltas_abonadas,
                    :observacao,
                    :anexo_documento,
                    :registrado_por
                ) RETURNING id_atestado"
            );

            $stmt->bindValue(':fk_id_matricula', (int) $data['fk_id_matricula'], \PDO::PARAM_INT);
            $stmt->bindValue(':fk_id_aluno', (int) $data['fk_id_aluno'], \PDO::PARAM_INT);
            $stmt->bindValue(':data_inicio', $data['data_inicio']);
            $stmt->bindValue(':data_fim', $data['data_fim']);
            $stmt->bindValue(':dias', max(1, (int) ($data['dias'] ?? 1)), \PDO::PARAM_INT);
            $stmt->bindValue(':motivo', $data['motivo']);
            $stmt->bindValue(':cid', !empty($data['cid']) ? $data['cid'] : null);
            $stmt->bindValue(':medico_nome', !empty($data['medico_nome']) ? $data['medico_nome'] : null);
            $stmt->bindValue(':medico_crm', !empty($data['medico_crm']) ? $data['medico_crm'] : null);
            $stmt->bindValue(':instituicao_saude', !empty($data['instituicao_saude']) ? $data['instituicao_saude'] : null);
            $stmt->bindValue(':status', $data['status'] ?? 'Aprovado');
            $stmt->bindValue(':faltas_abonadas', (int) ($data['faltas_abonadas'] ?? 0), \PDO::PARAM_INT);
            $stmt->bindValue(':observacao', !empty($data['observacao']) ? $data['observacao'] : null);
            $stmt->bindValue(':anexo_documento', !empty($data['anexo_documento']) ? $data['anexo_documento'] : null);
            $stmt->bindValue(':registrado_por', $data['registrado_por'] ?? 'Secretaria Escolar');

            $stmt->execute();
            $atestadoId = (int) $stmt->fetchColumn();

            // Se indicou abono de faltas e disciplina/unidade:
            $abonoQty = (int) ($data['faltas_abonadas'] ?? 0);
            $turmaDiscId = !empty($data['fk_id_turma_disciplina_falta']) ? (int) $data['fk_id_turma_disciplina_falta'] : null;
            $unidadeId = !empty($data['fk_id_unidade_falta']) ? (int) $data['fk_id_unidade_falta'] : null;

            if ($abonoQty > 0 && $turmaDiscId && $unidadeId) {
                // Atualiza ou insere registro em falta_aluno
                $updateStmt = $this->db->prepare(
                    "UPDATE falta_aluno
                     SET faltas_abonadas = faltas_abonadas + :qty, data_postagem = CURRENT_TIMESTAMP
                     WHERE fk_id_matricula_falta = :enrollmentId
                       AND fk_id_turma_disciplina_falta = :turmaDiscId
                       AND fk_id_unidade_falta = :unidadeId"
                );
                $updateStmt->bindValue(':qty', $abonoQty, \PDO::PARAM_INT);
                $updateStmt->bindValue(':enrollmentId', (int) $data['fk_id_matricula'], \PDO::PARAM_INT);
                $updateStmt->bindValue(':turmaDiscId', $turmaDiscId, \PDO::PARAM_INT);
                $updateStmt->bindValue(':unidadeId', $unidadeId, \PDO::PARAM_INT);
                $updateStmt->execute();

                if ($updateStmt->rowCount() === 0) {
                    $insertFalta = $this->db->prepare(
                        "INSERT INTO falta_aluno (total_faltas, faltas_abonadas, fk_id_turma_disciplina_falta, fk_id_unidade_falta, fk_id_matricula_falta)
                         VALUES (:qty, :qty, :turmaDiscId, :unidadeId, :enrollmentId)"
                    );
                    $insertFalta->bindValue(':qty', $abonoQty, \PDO::PARAM_INT);
                    $insertFalta->bindValue(':turmaDiscId', $turmaDiscId, \PDO::PARAM_INT);
                    $insertFalta->bindValue(':unidadeId', $unidadeId, \PDO::PARAM_INT);
                    $insertFalta->bindValue(':enrollmentId', (int) $data['fk_id_matricula'], \PDO::PARAM_INT);
                    $insertFalta->execute();
                }
            }

            $this->db->commit();
            return $atestadoId;

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Remove um atestado
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM atestado_medico WHERE id_atestado = :id");
        $stmt->bindValue(':id', (int) $id, \PDO::PARAM_INT);
        return $stmt->execute();
    }
}
