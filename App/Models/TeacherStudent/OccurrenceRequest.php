<?php

namespace App\Models\TeacherStudent;

use MF\Model\Model;

/**
 * Solicitação de ocorrência: o professor pede, o admin aceita (vira ocorrência) ou recusa
 * (database/20_roteiro_envio_solicitacao_ocorrencia.sql).
 */
class OccurrenceRequest extends Model
{

    const CATEGORIES = ['Elogio', 'Disciplinar', 'Pedagógica'];
    const SEVERITIES = ['Baixa', 'Média', 'Alta', 'Gravíssima'];


    /**
     * @param array $data enrollmentId, disciplineClassId, teacherId, category, title, description, severity, date
     * @return int id da solicitação
     */
    public function create(array $data)
    {

        $stmt = $this->db->prepare(
            "INSERT INTO solicitacao_ocorrencia (fk_id_matricula, fk_id_turma_disciplina, fk_id_professor, categoria, titulo, descricao, gravidade, data_fato)
             VALUES (:enrollment, :disciplineClass, :teacher, :category, :title, :description, :severity, :date)
             RETURNING id_solicitacao"
        );

        $stmt->execute([
            ':enrollment' => (int) $data['enrollmentId'],
            ':disciplineClass' => (int) $data['disciplineClassId'],
            ':teacher' => (int) $data['teacherId'],
            ':category' => $data['category'],
            ':title' => $data['title'],
            ':description' => $data['description'],
            ':severity' => $data['severity'],
            ':date' => $data['date'],
        ]);

        return (int) $stmt->fetchColumn();
    }


    /**
     * Solicitações sobre um aluno, mais recentes primeiro (perfil do aluno no portal do docente)
     */
    public function forEnrollment($enrollmentId)
    {

        $stmt = $this->db->prepare(
            "SELECT s.id_solicitacao AS id, s.categoria AS category, s.titulo AS title, s.descricao AS description, s.gravidade AS severity,
                    s.situacao AS status, s.resposta AS answer, TO_CHAR(s.data_fato, 'DD/MM/YYYY') AS date, professor.nome_professor AS teacher
             FROM solicitacao_ocorrencia s
             INNER JOIN professor ON (s.fk_id_professor = professor.id_professor)
             WHERE s.fk_id_matricula = :enrollment
             ORDER BY s.criado_em DESC, s.id_solicitacao DESC"
        );

        $stmt->execute([':enrollment' => (int) $enrollmentId]);

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Fila do admin: pendentes primeiro
     */
    public function queue($limit = 100)
    {

        $stmt = $this->db->query(
            "SELECT s.id_solicitacao AS id, s.categoria AS category, s.titulo AS title, s.descricao AS description, s.gravidade AS severity,
                    s.situacao AS status, s.resposta AS answer, TO_CHAR(s.data_fato, 'DD/MM/YYYY') AS date,
                    matricula.id_matricula AS enrollment_id, aluno.nome_aluno AS student, professor.nome_professor AS teacher, disciplina.nome_disciplina AS discipline,
                    serie.sigla || 'ª ' || cedula_turma.cedula AS class_label
             FROM solicitacao_ocorrencia s
             INNER JOIN matricula ON (s.fk_id_matricula = matricula.id_matricula)
             INNER JOIN aluno ON (matricula.fk_id_aluno = aluno.id_aluno)
             INNER JOIN professor ON (s.fk_id_professor = professor.id_professor)
             INNER JOIN turma_disciplina ON (s.fk_id_turma_disciplina = turma_disciplina.id_turma_disciplina)
             INNER JOIN disciplina ON (turma_disciplina.fk_id_disciplina = disciplina.id_disciplina)
             INNER JOIN turma ON (turma_disciplina.fk_id_turma = turma.id_turma)
             INNER JOIN serie ON (turma.fk_id_serie = serie.id_serie)
             INNER JOIN cedula_turma ON (turma.fk_id_cedula = cedula_turma.id_cedula_turma)
             ORDER BY (s.situacao = 'pendente') DESC, s.criado_em DESC, s.id_solicitacao DESC
             LIMIT " . (int) $limit
        );

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Aceita a solicitação pendente e cria a ocorrência na mesma transação
     *
     * @return int|null id da ocorrência; null se já foi analisada
     */
    public function accept($id, $answer, $visibleToFamily)
    {

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                "SELECT s.*, matricula.fk_id_aluno AS student_id, professor.nome_professor AS teacher
                 FROM solicitacao_ocorrencia s
                 INNER JOIN matricula ON (s.fk_id_matricula = matricula.id_matricula)
                 INNER JOIN professor ON (s.fk_id_professor = professor.id_professor)
                 WHERE s.id_solicitacao = :id AND s.situacao = 'pendente'
                 FOR UPDATE OF s"
            );
            $stmt->execute([':id' => (int) $id]);
            $request = $stmt->fetch(\PDO::FETCH_OBJ);

            if (!$request) {
                $this->db->rollBack();
                return null;
            }

            $occurrenceId = (new Occurrence($this->db))->create([
                'fk_id_matricula' => $request->fk_id_matricula,
                'fk_id_aluno' => $request->student_id,
                'fk_id_turma_disciplina' => $request->fk_id_turma_disciplina,
                'categoria' => $request->categoria,
                'titulo' => $request->titulo,
                'descricao' => $request->descricao,
                'gravidade' => $request->gravidade,
                'medida_tomada' => $answer,
                'visivel_responsavel' => $visibleToFamily,
                'registrado_por' => mb_substr($request->teacher, 0, 100),
                'data_ocorrencia' => $request->data_fato,
            ]);

            $this->finish($id, 'aceita', $answer, $occurrenceId);
            $this->db->commit();

            return $occurrenceId;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }


    /**
     * @return bool false se já foi analisada
     */
    public function reject($id, $answer)
    {
        return $this->finish($id, 'recusada', $answer, null);
    }


    private function finish($id, $status, $answer, $occurrenceId)
    {

        $stmt = $this->db->prepare(
            "UPDATE solicitacao_ocorrencia
             SET situacao = :status, resposta = :answer, fk_id_ocorrencia = :occurrence, analisada_em = CURRENT_TIMESTAMP
             WHERE id_solicitacao = :id AND situacao = 'pendente'"
        );

        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':answer', $answer !== '' ? $answer : null);
        $stmt->bindValue(':occurrence', $occurrenceId, $occurrenceId === null ? \PDO::PARAM_NULL : \PDO::PARAM_INT);
        $stmt->bindValue(':id', (int) $id, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
