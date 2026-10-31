<?php

namespace App\Models\TeacherStudent;

use MF\Model\Model;

/**
 * Ficha de desempenho do aluno vista pelo docente: dados da matrícula, notas de cada avaliação,
 * observações e observações rápidas (observacao_aluno, auditada).
 */
class StudentFile extends Model
{

    // botões de um toque; o texto livre continua disponível
    const QUICK_NOTES = [
        'Participou bem da aula',
        'Não fez a tarefa',
        'Sem material',
        'Conversa / indisciplina',
        'Dificuldade no conteúdo',
        'Faltou à avaliação',
    ];


    /**
     * Matrícula ativa com nome, foto e turma (ou null)
     */
    public function enrollment($enrollmentId)
    {

        $stmt = $this->db->prepare(
            "SELECT matricula.id_matricula AS id, matricula.fk_id_turma_matricula AS class_id,
                    aluno.nome_aluno AS name, aluno.foto_perfil_aluno AS photo
             FROM matricula INNER JOIN aluno ON (matricula.fk_id_aluno = aluno.id_aluno)
             WHERE matricula.id_matricula = :id AND matricula.fk_id_situacao_aluno = 1"
        );

        $stmt->execute([':id' => (int) $enrollmentId]);

        return $stmt->fetch(\PDO::FETCH_OBJ) ?: null;
    }


    /**
     * A matrícula está na turma desta turma/disciplina?
     */
    public function belongsTo($enrollmentId, $disciplineClassId)
    {

        $stmt = $this->db->prepare(
            "SELECT 1 FROM matricula
             INNER JOIN turma_disciplina ON (turma_disciplina.fk_id_turma = matricula.fk_id_turma_matricula)
             WHERE matricula.id_matricula = :enrollment AND turma_disciplina.id_turma_disciplina = :dc AND matricula.fk_id_situacao_aluno = 1"
        );

        $stmt->execute([':enrollment' => (int) $enrollmentId, ':dc' => (int) $disciplineClassId]);

        return (bool) $stmt->fetchColumn();
    }


    /**
     * Nota de cada avaliação do aluno nas turma/disciplinas indicadas (normalmente as do professor)
     *
     * @param int[] $disciplineClassIds
     */
    public function exams($enrollmentId, array $disciplineClassIds)
    {

        if (!$disciplineClassIds) return [];
        $ids = implode(',', array_map('intval', $disciplineClassIds));

        $stmt = $this->db->prepare(
            "SELECT avaliacoes.fk_id_turma_disciplina_avaliacao AS discipline_class_id, avaliacoes.fk_id_unidade_avaliacao AS unity,
                    avaliacoes.descricao_avaliacao AS description, avaliacoes.recuperacao AS recovery,
                    TO_CHAR(avaliacoes.data_realizada, 'DD/MM') AS date,
                    nota_avaliacao.valor_nota / NULLIF(avaliacoes.valor_avaliacao, 0) * 10 AS grade
             FROM avaliacoes
             LEFT JOIN nota_avaliacao ON (nota_avaliacao.fk_id_avaliacao = avaliacoes.id_avaliacao AND nota_avaliacao.fk_id_matricula_aluno = :enrollment)
             WHERE avaliacoes.fk_id_turma_disciplina_avaliacao IN ($ids)
             ORDER BY avaliacoes.fk_id_unidade_avaliacao, avaliacoes.recuperacao, avaliacoes.data_realizada, avaliacoes.id_avaliacao"
        );

        $stmt->execute([':enrollment' => (int) $enrollmentId]);

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Observações sobre o aluno, mais recentes primeiro
     */
    public function notes($enrollmentId, $limit = 30)
    {

        $stmt = $this->db->prepare(
            "SELECT observacao_aluno.id_observacao AS id, observacao_aluno.descricao AS text, observacao_aluno.fk_id_unidade AS unity,
                    TO_CHAR(observacao_aluno.data_postagem, 'DD/MM/YYYY') AS date,
                    disciplina.nome_disciplina AS discipline,
                    COALESCE(autor.nome_professor, titular.nome_professor) AS teacher
             FROM observacao_aluno
             INNER JOIN turma_disciplina ON (observacao_aluno.fk_id_turma_disciplina_observacao = turma_disciplina.id_turma_disciplina)
             INNER JOIN disciplina ON (turma_disciplina.fk_id_disciplina = disciplina.id_disciplina)
             INNER JOIN professor titular ON (turma_disciplina.fk_id_professor = titular.id_professor)
             LEFT JOIN professor autor ON (observacao_aluno.fk_id_professor = autor.id_professor)
             WHERE observacao_aluno.fk_id_matricula_observacao = :enrollment
             ORDER BY observacao_aluno.data_postagem DESC, observacao_aluno.id_observacao DESC
             LIMIT " . (int) $limit
        );

        $stmt->execute([':enrollment' => (int) $enrollmentId]);

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    public function addNote($enrollmentId, $disciplineClassId, $unity, $teacherId, $text)
    {

        $stmt = $this->db->prepare(
            "INSERT INTO observacao_aluno (fk_id_turma_disciplina_observacao, fk_id_unidade, descricao, fk_id_matricula_observacao, fk_id_professor)
             VALUES (:dc, :unity, :text, :enrollment, :teacher)
             RETURNING id_observacao"
        );

        $stmt->execute([
            ':dc' => (int) $disciplineClassId, ':unity' => (int) $unity, ':text' => $text,
            ':enrollment' => (int) $enrollmentId, ':teacher' => (int) $teacherId,
        ]);

        return (int) $stmt->fetchColumn();
    }
}
