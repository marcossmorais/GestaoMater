<?php

namespace App\Models\Student;

use MF\Model\Model;
use App\Tools\DocumentSigner;

/**
 * Registro dos documentos emitidos com assinatura (declarações e boletim).
 */
class IssuedDocument extends Model
{

    const TYPES = [
        'declaracao_matricula' => 'Declaração de matrícula',
        'declaracao_frequencia' => 'Declaração de frequência',
        'boletim' => 'Boletim escolar',
    ];


    /**
     * Emite (ou reaproveita, se o mesmo conteúdo já foi emitido hoje) e devolve o código público
     *
     * @param array $content dados que aparecem impressos (entram no hash)
     */
    public function issue($type, $enrollmentId, $studentName, array $content, $actorType, $actorId)
    {

        $hash = DocumentSigner::contentHash($content);

        $existing = $this->db->prepare(
            "SELECT codigo FROM documento_emitido
             WHERE fk_id_matricula = :enrollment AND tipo = :type AND conteudo_hash = :hash
             AND emitido_em::date = CURRENT_DATE AND revogado_em IS NULL
             ORDER BY id_documento DESC LIMIT 1"
        );
        $existing->execute([':enrollment' => (int) $enrollmentId, ':type' => $type, ':hash' => $hash]);

        if ($code = $existing->fetchColumn()) return $code;

        $code = DocumentSigner::newCode();
        $issuedAt = (new \DateTime('now', new \DateTimeZone('America/Sao_Paulo')))->format('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            "INSERT INTO documento_emitido
                (codigo, tipo, fk_id_matricula, aluno_nome, conteudo_hash, assinatura, emitido_em, emitido_por_tipo, emitido_por_id)
             VALUES
                (:code, :type, :enrollment, :name, :hash, :signature, :issuedAt, :actorType, :actorId)"
        );

        $stmt->execute([
            ':code' => $code,
            ':type' => $type,
            ':enrollment' => (int) $enrollmentId,
            ':name' => $studentName,
            ':hash' => $hash,
            ':signature' => DocumentSigner::sign($code, $type, $enrollmentId, $studentName, $issuedAt, $hash),
            ':issuedAt' => $issuedAt,
            ':actorType' => $actorType,
            ':actorId' => $actorId ?: null,
        ]);

        return $code;
    }


    /**
     * Resultado da validação pública: valid | revoked | tampered | not_found
     */
    public function validate($code)
    {

        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $code));
        if (strlen($code) !== 12) return ['status' => 'not_found'];
        $code = substr($code, 0, 4) . '-' . substr($code, 4, 4) . '-' . substr($code, 8, 4);

        $stmt = $this->db->prepare(
            "SELECT codigo, tipo, fk_id_matricula, aluno_nome, conteudo_hash, assinatura,
                    TO_CHAR(emitido_em, 'YYYY-MM-DD HH24:MI:SS') AS emitido_em, revogado_em
             FROM documento_emitido WHERE codigo = :code"
        );
        $stmt->execute([':code' => $code]);
        $doc = $stmt->fetch(\PDO::FETCH_OBJ);

        if (!$doc) return ['status' => 'not_found'];

        $valid = DocumentSigner::verify($doc->codigo, $doc->tipo, $doc->fk_id_matricula, $doc->aluno_nome, $doc->emitido_em, $doc->conteudo_hash, $doc->assinatura);

        return [
            'status' => !$valid ? 'tampered' : ($doc->revogado_em ? 'revoked' : 'valid'),
            'code' => $doc->codigo,
            'type' => self::TYPES[$doc->tipo] ?? $doc->tipo,
            'student' => $doc->aluno_nome,
            'issuedAt' => date('d/m/Y H:i', strtotime($doc->emitido_em)),
        ];
    }
}
