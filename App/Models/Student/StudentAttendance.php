<?php

namespace App\Models\Student;

use MF\Model\Model;

/**
 * Frequência do aluno: dias de aula perdidos, atestados e justificativas enviados por ele
 * e a análise pela coordenação (database/22_atestado_enviado_aluno.sql).
 */
class StudentAttendance extends Model
{

    const TYPES = ['atestado' => 'Atestado médico', 'justificativa' => 'Justificativa'];

    // anexos com dados de saúde: fora de public/, entregues só pelas rotas com permissão
    const MIME_TYPES = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
    const MAX_BYTES = 5 * 1024 * 1024;
    const MAX_DAYS = 30;


    public static function storageDir()
    {
        return dirname(__DIR__, 3) . '/storage/atestados/';
    }


    /**
     * Aulas em que o aluno foi marcado ausente na chamada, mais recentes primeiro
     */
    public function missedDays($enrollmentId)
    {

        $stmt = $this->db->prepare(
            "SELECT TO_CHAR(chamada.data_aula, 'YYYY-MM-DD') AS date, chamada.quantidade_aulas AS lessons,
                    disciplina.nome_disciplina AS discipline,
                    (SELECT atestado_medico.status FROM atestado_medico
                     WHERE atestado_medico.fk_id_matricula = :enrollment AND chamada.data_aula BETWEEN atestado_medico.data_inicio AND atestado_medico.data_fim
                     ORDER BY CASE atestado_medico.status WHEN 'Aprovado' THEN 1 WHEN 'Pendente' THEN 2 ELSE 3 END LIMIT 1) AS certificate
             FROM chamada
             INNER JOIN turma_disciplina ON (chamada.fk_id_turma_disciplina = turma_disciplina.id_turma_disciplina)
             INNER JOIN disciplina ON (turma_disciplina.fk_id_disciplina = disciplina.id_disciplina)
             WHERE :enrollment = ANY (chamada.matriculas_ausentes)
             ORDER BY chamada.data_aula DESC, disciplina.nome_disciplina"
        );

        $stmt->execute([':enrollment' => (int) $enrollmentId]);

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Atestados e justificativas do aluno, para a aba Frequência
     */
    public function submissions($enrollmentId)
    {

        $stmt = $this->db->prepare(
            "SELECT id_atestado AS id, tipo AS type, motivo AS reason, status, resposta AS answer, faltas_abonadas AS excused,
                    enviado_pelo_aluno AS sent_by_student, anexo_privado AS private_file, anexo_documento IS NOT NULL AS has_file,
                    TO_CHAR(data_inicio, 'DD/MM/YYYY') AS start_date, TO_CHAR(data_fim, 'DD/MM/YYYY') AS end_date
             FROM atestado_medico
             WHERE fk_id_matricula = :enrollment
             ORDER BY criado_em DESC, id_atestado DESC"
        );

        $stmt->execute([':enrollment' => (int) $enrollmentId]);

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    public function hasPendingOverlap($enrollmentId, $start, $end)
    {

        $stmt = $this->db->prepare(
            "SELECT 1 FROM atestado_medico
             WHERE fk_id_matricula = :enrollment AND status = 'Pendente' AND data_inicio <= :end AND data_fim >= :start"
        );

        $stmt->execute([':enrollment' => (int) $enrollmentId, ':start' => $start, ':end' => $end]);

        return (bool) $stmt->fetchColumn();
    }


    /**
     * @param array $data enrollmentId, studentId, type, start, end, reason, file (nome no armazenamento privado ou null)
     * @return int id
     */
    public function submit(array $data)
    {

        $stmt = $this->db->prepare(
            "INSERT INTO atestado_medico (fk_id_matricula, fk_id_aluno, data_inicio, data_fim, dias, motivo, status, tipo, enviado_pelo_aluno, anexo_privado, anexo_documento, registrado_por)
             VALUES (:enrollment, :student, :start, :end, (:end::date - :start::date) + 1, :reason, 'Pendente', :type, true, true, :file, 'Portal do aluno')
             RETURNING id_atestado"
        );

        $stmt->bindValue(':enrollment', (int) $data['enrollmentId'], \PDO::PARAM_INT);
        $stmt->bindValue(':student', (int) $data['studentId'], \PDO::PARAM_INT);
        $stmt->bindValue(':start', $data['start']);
        $stmt->bindValue(':end', $data['end']);
        $stmt->bindValue(':reason', $data['reason']);
        $stmt->bindValue(':type', $data['type']);
        $stmt->bindValue(':file', $data['file'], $data['file'] === null ? \PDO::PARAM_NULL : \PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }


    /**
     * Enviados pelos alunos, para o admin: pendentes primeiro
     */
    public function queue($limit = 100)
    {

        $stmt = $this->db->query(
            "SELECT atestado_medico.id_atestado AS id, atestado_medico.tipo AS type, atestado_medico.motivo AS reason, atestado_medico.status,
                    atestado_medico.resposta AS answer, atestado_medico.faltas_abonadas AS excused, atestado_medico.anexo_documento IS NOT NULL AS has_file,
                    TO_CHAR(atestado_medico.data_inicio, 'DD/MM/YYYY') AS start_date, TO_CHAR(atestado_medico.data_fim, 'DD/MM/YYYY') AS end_date,
                    TO_CHAR(atestado_medico.criado_em, 'DD/MM/YYYY \"às\" HH24:MI') AS sent_at,
                    aluno.nome_aluno AS student, serie.sigla || 'ª ' || cedula_turma.cedula AS class_label
             FROM atestado_medico
             INNER JOIN matricula ON (atestado_medico.fk_id_matricula = matricula.id_matricula)
             INNER JOIN aluno ON (matricula.fk_id_aluno = aluno.id_aluno)
             INNER JOIN turma ON (matricula.fk_id_turma_matricula = turma.id_turma)
             INNER JOIN serie ON (turma.fk_id_serie = serie.id_serie)
             INNER JOIN cedula_turma ON (turma.fk_id_cedula = cedula_turma.id_cedula_turma)
             WHERE atestado_medico.enviado_pelo_aluno
             ORDER BY (atestado_medico.status = 'Pendente') DESC, atestado_medico.criado_em DESC
             LIMIT " . (int) $limit
        );

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    /**
     * Aceita o pendente e abona as faltas das chamadas do período, sem passar do total lançado
     *
     * @return int|null faltas abonadas; null se já foi analisado
     */
    public function approve($id, $answer)
    {

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("SELECT * FROM atestado_medico WHERE id_atestado = :id AND status = 'Pendente' FOR UPDATE");
            $stmt->execute([':id' => (int) $id]);
            $certificate = $stmt->fetch(\PDO::FETCH_OBJ);

            if (!$certificate) {
                $this->db->rollBack();
                return null;
            }

            // Fund. 1: frequência em dias; o abono é calculado pelo atestado aprovado, aqui só se conta quantos dias cobre
            $Daily = new \App\Models\TeacherStudent\DailyAttendance($this->db);
            $class = $this->db->prepare("SELECT fk_id_turma_matricula FROM matricula WHERE id_matricula = :enrollment");
            $class->execute([':enrollment' => $certificate->fk_id_matricula]);

            if ($Daily->isDaily((int) $class->fetchColumn())) {
                $total = $Daily->absentDaysBetween($certificate->fk_id_matricula, $certificate->data_inicio, $certificate->data_fim);
                $this->finish($id, 'Aprovado', $answer, $total);
                $this->db->commit();
                return $total;
            }

            $absences = $this->db->prepare(
                "SELECT fk_id_turma_disciplina AS dc, fk_id_unidade AS unity, SUM(quantidade_aulas) AS lessons
                 FROM chamada
                 WHERE :enrollment = ANY (matriculas_ausentes) AND data_aula BETWEEN :start AND :end
                 GROUP BY 1, 2"
            );
            $absences->execute([':enrollment' => $certificate->fk_id_matricula, ':start' => $certificate->data_inicio, ':end' => $certificate->data_fim]);

            $launched = $this->db->prepare(
                "SELECT id_falta AS id, total_faltas - faltas_abonadas AS available FROM falta_aluno
                 WHERE fk_id_matricula_falta = :enrollment AND fk_id_turma_disciplina_falta = :dc AND fk_id_unidade_falta = :unity
                 FOR UPDATE"
            );
            $excuse = $this->db->prepare(
                "UPDATE falta_aluno SET faltas_abonadas = faltas_abonadas + :quantity, data_postagem = CURRENT_TIMESTAMP WHERE id_falta = :id"
            );

            $total = 0;
            foreach ($absences->fetchAll(\PDO::FETCH_OBJ) as $a) {
                $left = (int) $a->lessons;
                $launched->execute([':enrollment' => $certificate->fk_id_matricula, ':dc' => $a->dc, ':unity' => $a->unity]);
                foreach ($launched->fetchAll(\PDO::FETCH_OBJ) as $row) {
                    $quantity = min($left, max(0, (int) $row->available));
                    if ($quantity === 0) continue;
                    $excuse->execute([':quantity' => $quantity, ':id' => $row->id]);
                    $left -= $quantity;
                    $total += $quantity;
                }
            }

            $this->finish($id, 'Aprovado', $answer, $total);
            $this->db->commit();

            return $total;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }


    /**
     * @return bool false se já foi analisado
     */
    public function reject($id, $answer)
    {
        return $this->finish($id, 'Recusado', $answer, 0);
    }


    private function finish($id, $status, $answer, $excused)
    {

        $stmt = $this->db->prepare(
            "UPDATE atestado_medico SET status = :status, resposta = :answer, faltas_abonadas = :excused, analisado_em = CURRENT_TIMESTAMP
             WHERE id_atestado = :id AND status = 'Pendente'"
        );

        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':answer', $answer !== '' ? $answer : null);
        $stmt->bindValue(':excused', (int) $excused, \PDO::PARAM_INT);
        $stmt->bindValue(':id', (int) $id, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }


    /**
     * Anexo privado: nome do arquivo, ou null. Com $enrollmentId, só o do próprio aluno.
     */
    public function privateFile($id, $enrollmentId = null)
    {

        $stmt = $this->db->prepare(
            "SELECT anexo_documento FROM atestado_medico
             WHERE id_atestado = :id AND anexo_privado AND anexo_documento IS NOT NULL
             AND (:enrollment = 0 OR fk_id_matricula = :enrollment)"
        );

        $stmt->bindValue(':id', (int) $id, \PDO::PARAM_INT);
        $stmt->bindValue(':enrollment', (int) $enrollmentId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchColumn() ?: null;
    }


    /**
     * Entrega o anexo privado: tipo verificado de novo e sem cache
     */
    public static function sendFile($fileName)
    {

        $path = self::storageDir() . basename($fileName);

        if (!is_file($path)) {
            http_response_code(404);
            echo 'Arquivo não encontrado.';
            return;
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);

        if (!isset(self::MIME_TYPES[$mime])) {
            http_response_code(415);
            return;
        }

        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="anexo.' . self::MIME_TYPES[$mime] . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($path);
    }
}
