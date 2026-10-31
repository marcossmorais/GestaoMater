<?php

namespace App\Models\TeacherStudent;

use MF\Model\Model;
use App\Tools\GradeSimulator;

/**
 * Avaliações, roteiro de provas e lançamento de notas do portal do docente.
 *
 * Regra do MATER: 4 bimestres, 3 avaliações (0 a 10) por bimestre, nota do bimestre = média;
 * abaixo de 7 tem recuperação, que substitui a nota do bimestre (ver App/Tools/GradeSimulator.php).
 * A tabela legada chama o bimestre de "unidade". Notas antigas com outro valor máximo são normalizadas para 0 a 10.
 */
class Gradebook extends Model
{

    const MAX_GRADE = 10;


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
                avaliacoes.recuperacao AS recovery,
                (SELECT COUNT(*) FROM nota_avaliacao WHERE fk_id_avaliacao = avaliacoes.id_avaliacao) AS graded
            FROM avaliacoes
            WHERE avaliacoes.fk_id_turma_disciplina_avaliacao = :disciplineClass
            ORDER BY avaliacoes.fk_id_unidade_avaliacao, avaliacoes.recuperacao, avaliacoes.data_realizada, avaliacoes.id_avaliacao"
        );

        $stmt->execute([':disciplineClass' => (int) $disciplineClassId]);

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    public function exam($examId)
    {

        $stmt = $this->db->prepare(
            "SELECT id_avaliacao AS id, fk_id_turma_disciplina_avaliacao AS discipline_class_id,
                    fk_id_unidade_avaliacao AS unity, valor_avaliacao AS value, descricao_avaliacao AS description,
                    recuperacao AS recovery
             FROM avaliacoes WHERE id_avaliacao = :id"
        );

        $stmt->execute([':id' => (int) $examId]);

        return $stmt->fetch(\PDO::FETCH_OBJ) ?: null;
    }


    /**
     * Avaliações do bimestre: quantas regulares e se já existe a recuperação
     *
     * @return array ['regular' => int, 'recovery' => bool]
     */
    public function examCount($disciplineClassId, $unity)
    {

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FILTER (WHERE NOT recuperacao) AS regular, COALESCE(BOOL_OR(recuperacao), false) AS recovery
             FROM avaliacoes
             WHERE fk_id_turma_disciplina_avaliacao = :disciplineClass AND fk_id_unidade_avaliacao = :unity"
        );

        $stmt->execute([':disciplineClass' => (int) $disciplineClassId, ':unity' => (int) $unity]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return ['regular' => (int) $row['regular'], 'recovery' => (bool) $row['recovery']];
    }


    /**
     * Cria uma avaliação de 0 a 10 (regular ou recuperação do bimestre)
     *
     * @return int id da avaliação
     */
    public function createExam($disciplineClassId, $unity, $description, $date, $guide, $recovery = false)
    {

        $stmt = $this->db->prepare(
            "INSERT INTO avaliacoes (descricao_avaliacao, valor_avaliacao, data_realizada, fk_id_unidade_avaliacao, fk_id_turma_disciplina_avaliacao, roteiro, recuperacao)
             VALUES (:description, :value, :date, :unity, :disciplineClass, :guide, :recovery)
             RETURNING id_avaliacao"
        );

        $stmt->bindValue(':description', $description);
        $stmt->bindValue(':value', self::MAX_GRADE);
        $stmt->bindValue(':date', $date);
        $stmt->bindValue(':unity', (int) $unity, \PDO::PARAM_INT);
        $stmt->bindValue(':disciplineClass', (int) $disciplineClassId, \PDO::PARAM_INT);
        $stmt->bindValue(':guide', $guide !== '' ? $guide : null);
        $stmt->bindValue(':recovery', (bool) $recovery, \PDO::PARAM_BOOL);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }


    public function updateGuide($examId, $guide)
    {

        $stmt = $this->db->prepare("UPDATE avaliacoes SET roteiro = :guide WHERE id_avaliacao = :id");

        return $stmt->execute([':guide' => $guide !== '' ? $guide : null, ':id' => (int) $examId]);
    }


    /**
     * Alunos ativos da turma da avaliação, com a nota lançada (ou null) e a média do bimestre
     * (na recuperação, mostra quem ficou abaixo da média)
     */
    public function students($examId)
    {

        $stmt = $this->db->prepare(
            "SELECT
                matricula.id_matricula AS enrollment_id,
                aluno.nome_aluno AS name,
                aluno.foto_perfil_aluno AS photo,
                nota_avaliacao.valor_nota AS grade,
                (SELECT AVG(n.valor_nota / NULLIF(a.valor_avaliacao, 0) * 10)
                   FROM avaliacoes a
                   INNER JOIN nota_avaliacao n ON (n.fk_id_avaliacao = a.id_avaliacao AND n.fk_id_matricula_aluno = matricula.id_matricula)
                  WHERE a.fk_id_turma_disciplina_avaliacao = avaliacoes.fk_id_turma_disciplina_avaliacao
                    AND a.fk_id_unidade_avaliacao = avaliacoes.fk_id_unidade_avaliacao AND NOT a.recuperacao) AS bimester_average,
                (SELECT COUNT(*)
                   FROM avaliacoes a
                   INNER JOIN nota_avaliacao n ON (n.fk_id_avaliacao = a.id_avaliacao AND n.fk_id_matricula_aluno = matricula.id_matricula)
                  WHERE a.fk_id_turma_disciplina_avaliacao = avaliacoes.fk_id_turma_disciplina_avaliacao
                    AND a.fk_id_unidade_avaliacao = avaliacoes.fk_id_unidade_avaliacao AND NOT a.recuperacao) AS bimester_graded,
                (SELECT SUM(n.valor_nota / NULLIF(a.valor_avaliacao, 0) * 10)
                   FROM avaliacoes a
                   INNER JOIN nota_avaliacao n ON (n.fk_id_avaliacao = a.id_avaliacao AND n.fk_id_matricula_aluno = matricula.id_matricula)
                  WHERE a.fk_id_turma_disciplina_avaliacao = avaliacoes.fk_id_turma_disciplina_avaliacao
                    AND a.fk_id_unidade_avaliacao = avaliacoes.fk_id_unidade_avaliacao AND NOT a.recuperacao
                    AND a.id_avaliacao <> avaliacoes.id_avaliacao) AS others_sum,
                (SELECT COUNT(*)
                   FROM avaliacoes a
                   INNER JOIN nota_avaliacao n ON (n.fk_id_avaliacao = a.id_avaliacao AND n.fk_id_matricula_aluno = matricula.id_matricula)
                  WHERE a.fk_id_turma_disciplina_avaliacao = avaliacoes.fk_id_turma_disciplina_avaliacao
                    AND a.fk_id_unidade_avaliacao = avaliacoes.fk_id_unidade_avaliacao AND NOT a.recuperacao
                    AND a.id_avaliacao <> avaliacoes.id_avaliacao) AS others_count
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
     * Notas por aluno, disciplina e bimestre (média das avaliações regulares e recuperação), normalizadas de 0 a 10.
     * Com $enrollmentId, só daquele aluno.
     *
     * @return array linhas: enrollment_id, name, discipline_class_id, unity, graded, average, recovery
     */
    public function bimesterGrades($classId, $enrollmentId = null)
    {

        $stmt = $this->db->prepare(
            "SELECT
                matricula.id_matricula AS enrollment_id,
                aluno.nome_aluno AS name,
                avaliacoes.fk_id_turma_disciplina_avaliacao AS discipline_class_id,
                avaliacoes.fk_id_unidade_avaliacao AS unity,
                COUNT(nota_avaliacao.id_nota) FILTER (WHERE NOT avaliacoes.recuperacao) AS graded,
                AVG(nota_avaliacao.valor_nota / NULLIF(avaliacoes.valor_avaliacao, 0) * 10) FILTER (WHERE NOT avaliacoes.recuperacao) AS average,
                MAX(nota_avaliacao.valor_nota / NULLIF(avaliacoes.valor_avaliacao, 0) * 10) FILTER (WHERE avaliacoes.recuperacao) AS recovery
            FROM matricula
            INNER JOIN aluno ON (matricula.fk_id_aluno = aluno.id_aluno)
            INNER JOIN nota_avaliacao ON (nota_avaliacao.fk_id_matricula_aluno = matricula.id_matricula)
            INNER JOIN avaliacoes ON (nota_avaliacao.fk_id_avaliacao = avaliacoes.id_avaliacao)
            INNER JOIN turma_disciplina ON (avaliacoes.fk_id_turma_disciplina_avaliacao = turma_disciplina.id_turma_disciplina)
            WHERE matricula.fk_id_turma_matricula = :classId AND turma_disciplina.fk_id_turma = :classId
            AND matricula.fk_id_situacao_aluno = 1
            " . ($enrollmentId === null ? "" : "AND matricula.id_matricula = :enrollment") . "
            GROUP BY matricula.id_matricula, aluno.nome_aluno, avaliacoes.fk_id_turma_disciplina_avaliacao, avaliacoes.fk_id_unidade_avaliacao"
        );

        $stmt->bindValue(':classId', (int) $classId, \PDO::PARAM_INT);
        if ($enrollmentId !== null) $stmt->bindValue(':enrollment', (int) $enrollmentId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Aplica a regra do bimestre a uma linha de bimesterGrades()
     */
    public static function applyRule($row, array $rule)
    {
        return GradeSimulator::bimester(
            $row->average === null ? null : (float) $row->average,
            (int) $row->graded,
            $rule['perBimester'],
            $row->recovery === null ? null : (float) $row->recovery,
            $rule['passing']
        );
    }


    /**
     * Desempenho geral da turma: nota de cada aluno em cada disciplina no bimestre (recuperação aplicada)
     *
     * @return array ['disciplines' => [...], 'students' => [...]]
     */
    public function classOverview($classId, $unity, array $rule)
    {

        $disciplines = $this->db->prepare(
            "SELECT turma_disciplina.id_turma_disciplina AS id, disciplina.nome_disciplina AS name, disciplina.sigla_disciplina AS acronym,
                    professor.nome_professor AS teacher
             FROM turma_disciplina
             INNER JOIN disciplina ON (turma_disciplina.fk_id_disciplina = disciplina.id_disciplina)
             INNER JOIN professor ON (turma_disciplina.fk_id_professor = professor.id_professor)
             WHERE turma_disciplina.fk_id_turma = :classId
             ORDER BY disciplina.nome_disciplina"
        );

        $disciplines->execute([':classId' => (int) $classId]);

        $names = $this->db->prepare(
            "SELECT matricula.id_matricula AS id, aluno.nome_aluno AS name
             FROM matricula INNER JOIN aluno ON (matricula.fk_id_aluno = aluno.id_aluno)
             WHERE matricula.fk_id_turma_matricula = :classId AND matricula.fk_id_situacao_aluno = 1
             ORDER BY aluno.nome_aluno"
        );

        $names->execute([':classId' => (int) $classId]);

        $students = [];
        foreach ($names->fetchAll(\PDO::FETCH_OBJ) as $n) {
            $students[(int) $n->id] = ['id' => (int) $n->id, 'name' => $n->name, 'grades' => []];
        }

        foreach ($this->bimesterGrades($classId) as $row) {
            if ((int) $row->unity !== (int) $unity || !isset($students[(int) $row->enrollment_id])) continue;
            $students[(int) $row->enrollment_id]['grades'][(int) $row->discipline_class_id] = self::applyRule($row, $rule);
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
                    avaliacoes.roteiro AS guide, avaliacoes.recuperacao AS recovery, disciplina.nome_disciplina AS discipline
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
