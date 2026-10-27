<?php

namespace App\Models\TeacherStudent;

use MF\Model\Model;

/**
 * Avaliações, roteiro de provas e lançamento de notas do portal do docente.
 * Cada turma/disciplina tem até 10 pontos por unidade (mesma regra do admin).
 */
class Gradebook extends Model
{

    const MAX_UNIT_POINTS = 10;


    /**
     * Avaliações de uma turma/disciplina, com quantas notas já foram lançadas
     */
    public function exams($disciplineClassId)
    {

        $stmt = $this->db->prepare(
            "SELECT
                avaliacoes.id_avaliacao AS id,
                avaliacoes.descricao_avaliacao AS description,
                avaliacoes.valor_avaliacao AS value,
                avaliacoes.fk_id_unidade_avaliacao AS unity,
                TO_CHAR(avaliacoes.data_realizada, 'YYYY-MM-DD') AS date,
                avaliacoes.roteiro AS guide,
                (SELECT COUNT(*) FROM nota_avaliacao WHERE fk_id_avaliacao = avaliacoes.id_avaliacao) AS graded
            FROM avaliacoes
            WHERE avaliacoes.fk_id_turma_disciplina_avaliacao = :disciplineClass
            ORDER BY avaliacoes.fk_id_unidade_avaliacao, avaliacoes.data_realizada, avaliacoes.id_avaliacao"
        );

        $stmt->execute([':disciplineClass' => (int) $disciplineClassId]);

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    public function exam($examId)
    {

        $stmt = $this->db->prepare(
            "SELECT id_avaliacao AS id, fk_id_turma_disciplina_avaliacao AS discipline_class_id,
                    fk_id_unidade_avaliacao AS unity, valor_avaliacao AS value, descricao_avaliacao AS description
             FROM avaliacoes WHERE id_avaliacao = :id"
        );

        $stmt->execute([':id' => (int) $examId]);

        return $stmt->fetch(\PDO::FETCH_OBJ) ?: null;
    }


    /**
     * Pontos já distribuídos na unidade (sem contar $exceptExamId)
     */
    public function unitPoints($disciplineClassId, $unity, $exceptExamId = 0)
    {

        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(valor_avaliacao), 0) FROM avaliacoes
             WHERE fk_id_turma_disciplina_avaliacao = :disciplineClass AND fk_id_unidade_avaliacao = :unity AND id_avaliacao <> :except"
        );

        $stmt->execute([':disciplineClass' => (int) $disciplineClassId, ':unity' => (int) $unity, ':except' => (int) $exceptExamId]);

        return (float) $stmt->fetchColumn();
    }


    /**
     * @return int id da avaliação
     */
    public function createExam($disciplineClassId, $unity, $description, $value, $date, $guide)
    {

        $stmt = $this->db->prepare(
            "INSERT INTO avaliacoes (descricao_avaliacao, valor_avaliacao, data_realizada, fk_id_unidade_avaliacao, fk_id_turma_disciplina_avaliacao, roteiro)
             VALUES (:description, :value, :date, :unity, :disciplineClass, :guide)
             RETURNING id_avaliacao"
        );

        $stmt->execute([
            ':description' => $description,
            ':value' => $value,
            ':date' => $date,
            ':unity' => (int) $unity,
            ':disciplineClass' => (int) $disciplineClassId,
            ':guide' => $guide !== '' ? $guide : null,
        ]);

        return (int) $stmt->fetchColumn();
    }


    public function updateGuide($examId, $guide)
    {

        $stmt = $this->db->prepare("UPDATE avaliacoes SET roteiro = :guide WHERE id_avaliacao = :id");

        return $stmt->execute([':guide' => $guide !== '' ? $guide : null, ':id' => (int) $examId]);
    }


    /**
     * Alunos ativos da turma da avaliação, com a nota lançada (ou null)
     */
    public function students($examId)
    {

        $stmt = $this->db->prepare(
            "SELECT
                matricula.id_matricula AS enrollment_id,
                aluno.nome_aluno AS name,
                aluno.foto_perfil_aluno AS photo,
                nota_avaliacao.valor_nota AS grade
            FROM avaliacoes
            INNER JOIN turma_disciplina ON (avaliacoes.fk_id_turma_disciplina_avaliacao = turma_disciplina.id_turma_disciplina)
            INNER JOIN matricula ON (matricula.fk_id_turma_matricula = turma_disciplina.fk_id_turma AND matricula.fk_id_situacao_aluno = 1)
            INNER JOIN aluno ON (matricula.fk_id_aluno = aluno.id_aluno)
            LEFT JOIN nota_avaliacao ON (nota_avaliacao.fk_id_avaliacao = avaliacoes.id_avaliacao AND nota_avaliacao.fk_id_matricula_aluno = matricula.id_matricula)
            WHERE avaliacoes.id_avaliacao = :exam
            ORDER BY aluno.nome_aluno"
        );

        $stmt->execute([':exam' => (int) $examId]);

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Grava as notas em lote. null apaga a nota. Use dentro de transação.
     *
     * @param array $grades [enrollmentId => float|null] (já validadas)
     */
    public function saveGrades($examId, array $grades)
    {

        $upsert = $this->db->prepare(
            "INSERT INTO nota_avaliacao (valor_nota, fk_id_avaliacao, fk_id_matricula_aluno)
             VALUES (:grade, :exam, :enrollment)
             ON CONFLICT (fk_id_matricula_aluno, fk_id_avaliacao)
             DO UPDATE SET valor_nota = EXCLUDED.valor_nota, data_postagem = CURRENT_TIMESTAMP
             WHERE nota_avaliacao.valor_nota IS DISTINCT FROM EXCLUDED.valor_nota"
        );

        $delete = $this->db->prepare(
            "DELETE FROM nota_avaliacao WHERE fk_id_avaliacao = :exam AND fk_id_matricula_aluno = :enrollment"
        );

        foreach ($grades as $enrollment => $grade) {
            if ($grade === null) {
                $delete->execute([':exam' => (int) $examId, ':enrollment' => (int) $enrollment]);
            } else {
                $upsert->execute([':grade' => $grade, ':exam' => (int) $examId, ':enrollment' => (int) $enrollment]);
            }
        }
    }


    /**
     * Desempenho geral da turma: pontos de cada aluno em cada disciplina na unidade
     *
     * @return array ['disciplines' => [...], 'students' => [...]]
     */
    public function classOverview($classId, $unity)
    {

        $disciplines = $this->db->prepare(
            "SELECT turma_disciplina.id_turma_disciplina AS id, disciplina.nome_disciplina AS name, disciplina.sigla_disciplina AS acronym,
                    professor.nome_professor AS teacher,
                    (SELECT COALESCE(SUM(valor_avaliacao), 0) FROM avaliacoes
                      WHERE fk_id_turma_disciplina_avaliacao = turma_disciplina.id_turma_disciplina AND fk_id_unidade_avaliacao = :unity) AS distributed
             FROM turma_disciplina
             INNER JOIN disciplina ON (turma_disciplina.fk_id_disciplina = disciplina.id_disciplina)
             INNER JOIN professor ON (turma_disciplina.fk_id_professor = professor.id_professor)
             WHERE turma_disciplina.fk_id_turma = :classId
             ORDER BY disciplina.nome_disciplina"
        );

        $disciplines->execute([':classId' => (int) $classId, ':unity' => (int) $unity]);

        $points = $this->db->prepare(
            "SELECT matricula.id_matricula AS enrollment_id, aluno.nome_aluno AS name,
                    avaliacoes.fk_id_turma_disciplina_avaliacao AS discipline_class_id, SUM(nota_avaliacao.valor_nota) AS points
             FROM matricula
             INNER JOIN aluno ON (matricula.fk_id_aluno = aluno.id_aluno)
             LEFT JOIN nota_avaliacao ON (nota_avaliacao.fk_id_matricula_aluno = matricula.id_matricula)
             LEFT JOIN avaliacoes ON (nota_avaliacao.fk_id_avaliacao = avaliacoes.id_avaliacao AND avaliacoes.fk_id_unidade_avaliacao = :unity)
             WHERE matricula.fk_id_turma_matricula = :classId AND matricula.fk_id_situacao_aluno = 1
             GROUP BY matricula.id_matricula, aluno.nome_aluno, avaliacoes.fk_id_turma_disciplina_avaliacao
             ORDER BY aluno.nome_aluno"
        );

        $points->execute([':classId' => (int) $classId, ':unity' => (int) $unity]);

        $students = [];
        foreach ($points->fetchAll(\PDO::FETCH_OBJ) as $row) {
            $id = (int) $row->enrollment_id;
            if (!isset($students[$id])) $students[$id] = ['id' => $id, 'name' => $row->name, 'points' => []];
            if ($row->discipline_class_id !== null) $students[$id]['points'][(int) $row->discipline_class_id] = round((float) $row->points, 1);
        }

        return ['disciplines' => $disciplines->fetchAll(\PDO::FETCH_OBJ), 'students' => array_values($students)];
    }


    /**
     * Ex.: "1ª A · EM · Matutino"
     */
    public function classLabel($classId)
    {

        $stmt = $this->db->prepare(
            "SELECT serie.sigla || 'ª ' || cedula_turma.cedula || ' · ' || curso.sigla || ' · ' || turno.nome_turno
             FROM turma
             INNER JOIN serie ON (turma.fk_id_serie = serie.id_serie)
             INNER JOIN cedula_turma ON (turma.fk_id_cedula = cedula_turma.id_cedula_turma)
             INNER JOIN curso ON (turma.fk_id_curso = curso.id_curso)
             INNER JOIN turno ON (turma.fk_id_turno = turno.id_turno)
             WHERE turma.id_turma = :id"
        );

        $stmt->execute([':id' => (int) $classId]);

        return $stmt->fetchColumn() ?: '';
    }


    /**
     * Próximas avaliações da turma com roteiro (portal do aluno)
     */
    public function guidesForClass($classId)
    {

        $stmt = $this->db->prepare(
            "SELECT avaliacoes.id_avaliacao AS id, avaliacoes.descricao_avaliacao AS description, avaliacoes.valor_avaliacao AS value,
                    avaliacoes.fk_id_unidade_avaliacao AS unity, TO_CHAR(avaliacoes.data_realizada, 'DD/MM') AS date,
                    avaliacoes.roteiro AS guide, disciplina.nome_disciplina AS discipline
             FROM avaliacoes
             INNER JOIN turma_disciplina ON (avaliacoes.fk_id_turma_disciplina_avaliacao = turma_disciplina.id_turma_disciplina)
             INNER JOIN disciplina ON (turma_disciplina.fk_id_disciplina = disciplina.id_disciplina)
             WHERE turma_disciplina.fk_id_turma = :classId AND avaliacoes.roteiro IS NOT NULL
             AND avaliacoes.data_realizada >= CURRENT_DATE - INTERVAL '7 days'
             ORDER BY avaliacoes.data_realizada
             LIMIT 20"
        );

        $stmt->execute([':classId' => (int) $classId]);

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
}
