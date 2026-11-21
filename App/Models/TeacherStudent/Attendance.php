<?php

namespace App\Models\TeacherStudent;

use MF\Model\Model;

/**
 * Chamada rápida do portal do docente.
 *
 * O professor escolhe uma turma/disciplina, todos os alunos começam presentes e ele marca só quem faltou.
 * Ao salvar, cada ausente recebe +N faltas (N = aulas seguidas) em falta_aluno na unidade escolhida,
 * e a chamada fica registrada na tabela chamada. Uma chamada por disciplina e dia: salvar de novo corrige, não soma.
 */
class Attendance extends Model
{

    // pg_advisory_xact_lock(namespace, turma_disciplina); o database/migrate.php usa a chave 7461001
    const LOCK_NAMESPACE = 7461002;

    /**
     * Turmas/disciplinas atribuídas ao professor no período letivo ativo, com total de alunos e a última chamada de hoje.
     * No Fund. 1 (stage 'iniciais') a chamada é a diária (DailyAttendance); estas linhas servem a notas e diário.
     *
     * @return array
     */
    public function disciplineClassesByTeacher($teacherId)
    {

        $stmt = $this->db->prepare(
            "SELECT
                turma_disciplina.id_turma_disciplina AS discipline_class_id,
                turma.id_turma AS class_id,
                curso.etapa AS stage,
                (turma_disciplina.fk_id_professor = :teacherId) AS own,
                disciplina.nome_disciplina AS discipline_name,
                disciplina.sigla_disciplina AS discipline_acronym,
                serie.sigla AS series_acronym,
                cedula_turma.cedula AS ballot,
                curso.sigla AS course,
                turno.nome_turno AS shift,
                (SELECT COUNT(*) FROM matricula
                    WHERE matricula.fk_id_turma_matricula = turma.id_turma AND matricula.fk_id_situacao_aluno = 1) AS student_total,
                (SELECT TO_CHAR(MAX(chamada.criado_em), 'HH24:MI') FROM chamada
                    WHERE chamada.fk_id_turma_disciplina = turma_disciplina.id_turma_disciplina AND chamada.data_aula = CURRENT_DATE) AS done_today_at

            FROM turma_disciplina

            INNER JOIN turma ON (turma_disciplina.fk_id_turma = turma.id_turma)
            INNER JOIN disciplina ON (turma_disciplina.fk_id_disciplina = disciplina.id_disciplina)
            INNER JOIN serie ON (turma.fk_id_serie = serie.id_serie)
            INNER JOIN cedula_turma ON (turma.fk_id_cedula = cedula_turma.id_cedula_turma)
            INNER JOIN curso ON (turma.fk_id_curso = curso.id_curso)
            INNER JOIN turno ON (turma.fk_id_turno = turno.id_turno)
            INNER JOIN periodo_letivo ON (turma.fk_id_periodo_letivo = periodo_letivo.id_ano_letivo)

            WHERE " . \App\Models\Teacher\TeacherAccess::EDITABLE . "
            AND periodo_letivo.fk_id_situacao_periodo_letivo = 1

            ORDER BY serie.sigla, cedula_turma.cedula, disciplina.nome_disciplina"
        );

        $stmt->bindValue(':teacherId', (int) $teacherId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Retorna a turma/disciplina apenas se ela pertence ao professor (ou null)
     */
    public function findForTeacher($disciplineClassId, $teacherId)
    {

        foreach ($this->disciplineClassesByTeacher($teacherId) as $row) {
            if ((int) $row->discipline_class_id === (int) $disciplineClassId) return $row;
        }

        return null;
    }


    /**
     * Alunos matriculados na turma, com o total de faltas na disciplina/unidade
     *
     * @return array
     */
    public function students($disciplineClassId, $unity)
    {

        $stmt = $this->db->prepare(
            "SELECT
                matricula.id_matricula AS enrollment_id,
                aluno.nome_aluno AS name,
                aluno.foto_perfil_aluno AS photo,
                COALESCE(SUM(falta_aluno.total_faltas), 0)::int AS total_lack

            FROM turma_disciplina

            INNER JOIN matricula ON (matricula.fk_id_turma_matricula = turma_disciplina.fk_id_turma AND matricula.fk_id_situacao_aluno = 1)
            INNER JOIN aluno ON (matricula.fk_id_aluno = aluno.id_aluno)
            LEFT JOIN falta_aluno ON (
                falta_aluno.fk_id_matricula_falta = matricula.id_matricula
                AND falta_aluno.fk_id_turma_disciplina_falta = turma_disciplina.id_turma_disciplina
                AND falta_aluno.fk_id_unidade_falta = :unity
            )

            WHERE turma_disciplina.id_turma_disciplina = :disciplineClassId

            GROUP BY matricula.id_matricula, aluno.nome_aluno, aluno.foto_perfil_aluno

            ORDER BY aluno.nome_aluno"
        );

        $stmt->bindValue(':disciplineClassId', (int) $disciplineClassId, \PDO::PARAM_INT);
        $stmt->bindValue(':unity', (int) $unity, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Chamada já salva da disciplina na data (uma por disciplina e dia, migração 41), ou null.
     * absent = matrículas ausentes; saved_at = hora em que foi salva ou corrigida pela última vez
     */
    public function savedAt($disciplineClassId, $date)
    {

        $stmt = $this->db->prepare(
            "SELECT id_chamada AS id, fk_id_unidade AS unity, quantidade_aulas AS lessons, matriculas_ausentes AS absent,
                    TO_CHAR(COALESCE(corrigido_em, criado_em), 'HH24:MI') AS saved_at, (corrigido_em IS NOT NULL) AS corrected
             FROM chamada
             WHERE fk_id_turma_disciplina = :id AND data_aula = :date"
        );

        $stmt->bindValue(':id', (int) $disciplineClassId, \PDO::PARAM_INT);
        $stmt->bindValue(':date', $date);
        $stmt->execute();

        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        if (!$row) return null;

        $row->id = (int) $row->id;
        $row->unity = (int) $row->unity;
        $row->lessons = (int) $row->lessons;
        $row->corrected = (bool) $row->corrected;
        $row->absent = self::intArray($row->absent);

        return $row;
    }


    /**
     * Horário em que a chamada da disciplina na data foi salva (ou null)
     */
    public function doneAtDate($disciplineClassId, $date)
    {

        $saved = $this->savedAt($disciplineClassId, $date);

        return $saved ? $saved->saved_at : null;
    }


    /**
     * Início e fim do período letivo da turma (YYYY-MM-DD), ou null
     */
    public function termRange($classId)
    {

        $stmt = $this->db->prepare(
            "SELECT TO_CHAR(periodo_letivo.data_inicio, 'YYYY-MM-DD') AS start, TO_CHAR(periodo_letivo.data_fim, 'YYYY-MM-DD') AS finish
             FROM turma
             INNER JOIN periodo_letivo ON (periodo_letivo.id_ano_letivo = turma.fk_id_periodo_letivo)
             WHERE turma.id_turma = :classId"
        );

        $stmt->bindValue(':classId', (int) $classId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(\PDO::FETCH_OBJ) ?: null;
    }


    /**
     * Quanto muda o total de faltas de cada aluno, por unidade: tira a chamada anterior do dia (se houver) e lança a nova.
     * Quem não muda fica de fora (não grava nem audita à toa).
     *
     * @param object|null $previous chamada já salva (unity, lessons, absent[])
     * @return array [unidade => [matrícula => diferença]]
     */
    public static function absenceDelta($previous, $unity, $lessons, array $absentEnrollments)
    {

        $delta = [];

        if ($previous) {
            foreach ($previous->absent as $enrollment) {
                $delta[(int) $previous->unity][(int) $enrollment] = ($delta[(int) $previous->unity][(int) $enrollment] ?? 0) - (int) $previous->lessons;
            }
        }

        foreach ($absentEnrollments as $enrollment) {
            $delta[(int) $unity][(int) $enrollment] = ($delta[(int) $unity][(int) $enrollment] ?? 0) + (int) $lessons;
        }

        foreach ($delta as $u => $rows) {
            $delta[$u] = array_filter($rows, function ($d) { return $d !== 0; });
            if (!$delta[$u]) unset($delta[$u]);
        }

        return $delta;
    }


    /**
     * Grava a chamada da disciplina no dia, tudo em uma transação. Uma por disciplina e dia: se já existe,
     * salvar de novo corrige (as faltas da versão anterior saem e as novas entram; não soma).
     *
     * @param int         $disciplineClassId
     * @param int         $unity
     * @param int         $teacherId
     * @param int         $lessons            aulas seguidas (cada ausente recebe essa quantidade de faltas)
     * @param int[]       $absentEnrollments  matrículas ausentes (já validadas como pertencentes à turma)
     * @param int         $presentTotal
     * @param string|null $lessonDate         data da aula (YYYY-MM-DD), padrão hoje
     *
     * @return array ['id' => id da chamada, 'corrected' => se corrigiu uma chamada já salva]
     */
    public function save($disciplineClassId, $unity, $teacherId, $lessons, array $absentEnrollments, $presentTotal, $lessonDate = null)
    {

        $date = ($lessonDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $lessonDate)) ? $lessonDate : date('Y-m-d');
        $absentEnrollments = array_values(array_unique(array_map('intval', $absentEnrollments)));

        // permite salvar várias disciplinas numa transação externa
        $ownTransaction = !$this->db->inTransaction();
        if ($ownTransaction) $this->db->beginTransaction();

        try {

            // toque duplo ou reenvio: o segundo espera o primeiro terminar e encontra a chamada já salva
            $lock = $this->db->prepare('SELECT pg_advisory_xact_lock(:namespace, :disciplineClass)');
            $lock->execute([':namespace' => self::LOCK_NAMESPACE, ':disciplineClass' => (int) $disciplineClassId]);

            $previous = $this->savedAt($disciplineClassId, $date);

            $update = $this->db->prepare(
                "UPDATE falta_aluno
                 SET total_faltas = GREATEST(total_faltas + :delta, 0), data_postagem = CURRENT_TIMESTAMP
                 WHERE fk_id_matricula_falta = :enrollment
                 AND fk_id_turma_disciplina_falta = :disciplineClass
                 AND fk_id_unidade_falta = :unity"
            );

            $insert = $this->db->prepare(
                "INSERT INTO falta_aluno (total_faltas, fk_id_turma_disciplina_falta, fk_id_unidade_falta, fk_id_matricula_falta)
                 VALUES (:delta, :disciplineClass, :unity, :enrollment)"
            );

            foreach (self::absenceDelta($previous, $unity, $lessons, $absentEnrollments) as $deltaUnity => $rows) {
                foreach ($rows as $enrollment => $delta) {

                    $params = [
                        ':delta' => (int) $delta,
                        ':enrollment' => (int) $enrollment,
                        ':disciplineClass' => (int) $disciplineClassId,
                        ':unity' => (int) $deltaUnity,
                    ];

                    $update->execute($params);

                    if ($update->rowCount() === 0 && $delta > 0) $insert->execute($params);
                }
            }

            $values = [
                ':unity' => (int) $unity,
                ':teacher' => (int) $teacherId,
                ':lessons' => (int) $lessons,
                ':present' => (int) $presentTotal,
                ':absent' => count($absentEnrollments),
                ':absentList' => '{' . implode(',', $absentEnrollments) . '}',
            ];

            if ($previous) {

                $fix = $this->db->prepare(
                    "UPDATE chamada
                     SET fk_id_unidade = :unity, fk_id_professor = :teacher, quantidade_aulas = :lessons,
                         total_presentes = :present, total_ausentes = :absent, matriculas_ausentes = :absentList,
                         corrigido_em = CURRENT_TIMESTAMP
                     WHERE id_chamada = :id"
                );
                $fix->execute($values + [':id' => $previous->id]);
                $id = $previous->id;

            } else {

                $log = $this->db->prepare(
                    "INSERT INTO chamada
                        (fk_id_turma_disciplina, fk_id_unidade, fk_id_professor, data_aula, quantidade_aulas, total_presentes, total_ausentes, matriculas_ausentes)
                     VALUES
                        (:disciplineClass, :unity, :teacher, :dataAula, :lessons, :present, :absent, :absentList)
                     RETURNING id_chamada"
                );
                $log->execute($values + [':disciplineClass' => (int) $disciplineClassId, ':dataAula' => $date]);
                $id = (int) $log->fetchColumn();
            }

            if ($ownTransaction) $this->db->commit();

            return ['id' => $id, 'corrected' => $previous !== null];
        } catch (\Throwable $e) {

            if ($ownTransaction) $this->db->rollBack();
            throw $e;
        }
    }


    /**
     * Array do Postgres ("{1,2,3}") em inteiros
     */
    private static function intArray($value)
    {

        $value = trim((string) $value, '{}');

        return $value === '' ? [] : array_map('intval', explode(',', $value));
    }


    /**
     * Total de chamadas que o professor já fez hoje (para o painel inicial)
     */
    public function countTodayByTeacher($teacherId)
    {

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM chamada WHERE fk_id_professor = :teacherId AND data_aula = CURRENT_DATE");
        $stmt->bindValue(':teacherId', (int) $teacherId, \PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
}
