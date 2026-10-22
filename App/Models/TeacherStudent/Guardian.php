<?php

namespace App\Models\TeacherStudent;

use MF\Model\Model;

/**
 * Gestão de Responsáveis e Pessoas Autorizadas para Portaria / Retirada do Aluno
 */
class Guardian extends Model
{

    /**
     * Retorna todos os responsáveis e autorizados vinculados a um aluno
     *
     * @param int $studentId
     * @return array
     */
    public function getByStudent($studentId)
    {
        $stmt = $this->db->prepare(
            "SELECT
                id_responsavel,
                fk_id_aluno,
                nome,
                parentesco,
                cpf,
                rg,
                telefone,
                email,
                autorizado_buscar,
                responsavel_financeiro,
                responsavel_pedagogico,
                observacao_restricao,
                foto_documento,
                TO_CHAR(criado_em, 'DD/MM/YYYY') AS criado_em_fmt
            FROM responsavel_aluno
            WHERE fk_id_aluno = :studentId
            ORDER BY 
                CASE WHEN parentesco IN ('Mãe', 'Pai') THEN 1 ELSE 2 END,
                autorizado_buscar DESC,
                id_responsavel ASC"
        );

        $stmt->bindValue(':studentId', (int) $studentId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Retorna apenas os autorizados a buscar o aluno na portaria
     *
     * @param int $studentId
     * @return array
     */
    public function getAuthorizedPickup($studentId)
    {
        $stmt = $this->db->prepare(
            "SELECT
                id_responsavel,
                nome,
                parentesco,
                cpf,
                rg,
                telefone,
                observacao_restricao
            FROM responsavel_aluno
            WHERE fk_id_aluno = :studentId AND autorizado_buscar = TRUE
            ORDER BY id_responsavel ASC"
        );

        $stmt->bindValue(':studentId', (int) $studentId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Cadastra novo responsável ou pessoa autorizada
     *
     * @param array $data
     * @return int
     */
    public function create(array $data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO responsavel_aluno (
                fk_id_aluno,
                nome,
                parentesco,
                cpf,
                rg,
                telefone,
                email,
                autorizado_buscar,
                responsavel_financeiro,
                responsavel_pedagogico,
                observacao_restricao
            ) VALUES (
                :fk_id_aluno,
                :nome,
                :parentesco,
                :cpf,
                :rg,
                :telefone,
                :email,
                :autorizado_buscar,
                :responsavel_financeiro,
                :responsavel_pedagogico,
                :observacao_restricao
            ) RETURNING id_responsavel"
        );

        $stmt->bindValue(':fk_id_aluno', (int) $data['fk_id_aluno'], \PDO::PARAM_INT);
        $stmt->bindValue(':nome', $data['nome']);
        $stmt->bindValue(':parentesco', $data['parentesco'] ?? 'Outro Autorizado');
        $stmt->bindValue(':cpf', !empty($data['cpf']) ? $data['cpf'] : null);
        $stmt->bindValue(':rg', !empty($data['rg']) ? $data['rg'] : null);
        $stmt->bindValue(':telefone', $data['telefone']);
        $stmt->bindValue(':email', !empty($data['email']) ? $data['email'] : null);
        $stmt->bindValue(':autorizado_buscar', !empty($data['autorizado_buscar']), \PDO::PARAM_BOOL);
        $stmt->bindValue(':responsavel_financeiro', !empty($data['responsavel_financeiro']), \PDO::PARAM_BOOL);
        $stmt->bindValue(':responsavel_pedagogico', !empty($data['responsavel_pedagogico']), \PDO::PARAM_BOOL);
        $stmt->bindValue(':observacao_restricao', !empty($data['observacao_restricao']) ? $data['observacao_restricao'] : null);

        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Remove um responsável / autorizado
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM responsavel_aluno WHERE id_responsavel = :id");
        $stmt->bindValue(':id', (int) $id, \PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Alterna status de autorização de busca na portaria
     *
     * @param int $id
     * @return bool
     */
    public function toggleAuthorized($id)
    {
        $stmt = $this->db->prepare(
            "UPDATE responsavel_aluno
             SET autorizado_buscar = NOT autorizado_buscar
             WHERE id_responsavel = :id"
        );
        $stmt->bindValue(':id', (int) $id, \PDO::PARAM_INT);
        return $stmt->execute();
    }
}
