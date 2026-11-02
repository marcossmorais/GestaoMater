<?php

namespace App\Models\TeacherStudent;

use MF\Model\Model;

/**
 * Ficha de desempenho do aluno vista pelo docente: dados da matrícula, notas de cada avaliação,
 * observações e observações rápidas (observacao_aluno, auditada).
 */
class StudentFile extends Model
{

    // caixas marcáveis (uma ou mais); o texto livre continua disponível
    const QUICK_NOTES = [
        'Participou bem da aula',
        'Ajudou os colegas',
        'Evoluiu no conteúdo',
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

        // só o que o professor precisa (LGPD): sem CPF, endereço ou dados de saúde além da indicação de PCD
        $stmt = $this->db->prepare(
            "SELECT matricula.id_matricula AS id, matricula.fk_id_turma_matricula AS class_id,
                    aluno.id_aluno AS student_id, aluno.nome_aluno AS name, aluno.foto_perfil_aluno AS photo,
                    TO_CHAR(aluno.data_nascimento_aluno, 'DD/MM/YYYY') AS birth_date,
                    DATE_PART('year', AGE(aluno.data_nascimento_aluno))::int AS age,
                    pcd.pcd AS pcd,
                    TO_CHAR(matricula.data_hora_matricula, 'DD/MM/YYYY') AS enrolled_at
             FROM matricula
             INNER JOIN aluno ON (matricula.fk_id_aluno = aluno.id_aluno)
             LEFT JOIN pcd ON (aluno.fk_id_aluno_pcd = pcd.id_pcd)
             WHERE matricula.id_matricula = :id AND matricula.fk_id_situacao_aluno = 1"
        );

        $stmt->execute([':id' => (int) $enrollmentId]);

        return $stmt->fetch(\PDO::FETCH_OBJ) ?: null;
    }


    /**
     * Carômetro: alunos ativos da turma, com as faltas não abonadas do bimestre nas disciplinas informadas
     *
     * @param int[] $disciplineClassIds disciplinas do professor na turma
     */
    public function classRoster($classId, array $disciplineClassIds, $unity)
    {

        $ids = implode(',', array_map('intval', $disciplineClassIds)) ?: '0';

        $stmt = $this->db->prepare(
            "SELECT matricula.id_matricula AS id, aluno.nome_aluno AS name, aluno.foto_perfil_aluno AS photo,
                    COALESCE((SELECT SUM(f.total_faltas - COALESCE(f.faltas_abonadas, 0)) FROM falta_aluno f
                              WHERE f.fk_id_matricula_falta = matricula.id_matricula AND f.fk_id_unidade_falta = :unity
                                AND f.fk_id_turma_disciplina_falta IN ($ids)), 0)::int AS absences
             FROM matricula
             INNER JOIN aluno ON (matricula.fk_id_aluno = aluno.id_aluno)
             WHERE matricula.fk_id_turma_matricula = :class AND matricula.fk_id_situacao_aluno = 1
             ORDER BY aluno.nome_aluno"
        );

        $stmt->execute([':class' => (int) $classId, ':unity' => (int) $unity]);

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Responsáveis para contato: nome, parentesco e telefone (sem documentos)
     */
    public function guardians($studentId)
    {

        $stmt = $this->db->prepare(
            "SELECT nome AS name, parentesco AS relation, telefone AS phone, email,
                    responsavel_pedagogico AS pedagogical
             FROM responsavel_aluno
             WHERE fk_id_aluno = :student
             ORDER BY responsavel_pedagogico DESC, CASE WHEN parentesco IN ('Mãe', 'Pai') THEN 1 ELSE 2 END, id_responsavel"
        );

        $stmt->execute([':student' => (int) $studentId]);

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Média da turma em cada disciplina e bimestre (regra aplicada), para comparar com o aluno
     *
     * @return array [disciplineClassId][bimestre] => média
     */
    public function classAverages($classId, array $rule, $minGrades = 1)
    {
        $sum = [];
        foreach ((new Gradebook($this->db))->bimesterGrades($classId) as $row) {
            $grade = Gradebook::applyRule($row, $rule)['grade'];
            if ($grade === null) continue;
            $sum[(int) $row->discipline_class_id][(int) $row->unity][] = $grade;
        }

        $averages = [];
        foreach ($sum as $dc => $units) {
            // com poucas notas a média deixaria adivinhar a nota de um colega: não mostra
            foreach ($units as $u => $grades) {
                if (count($grades) >= $minGrades) $averages[$dc][$u] = round(array_sum($grades) / count($grades), 2);
            }
        }

        return $averages;
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
                    COALESCE((SELECT string_agg(tipo, ',' ORDER BY tipo) FROM avaliacao_tipo WHERE fk_id_avaliacao = avaliacoes.id_avaliacao), '') AS types,
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
    public function notes($enrollmentId, $limit = 500)
    {

        $stmt = $this->db->prepare(
            "SELECT observacao_aluno.id_observacao AS id, observacao_aluno.descricao AS text, observacao_aluno.fk_id_unidade AS unity,
                    TO_CHAR(observacao_aluno.data_postagem, 'DD/MM/YYYY') AS date,
                    TO_CHAR(observacao_aluno.data_postagem, 'HH24:MI') AS time,
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
