<?php

namespace App\Tools;

/**
 * Assinatura eletrônica dos documentos emitidos (database/15_documentos_assinados.sql).
 *
 * HMAC-SHA256 com a chave APP_SIGNING_KEY (variável de ambiente, nunca no banco) sobre os campos do documento.
 * Não é certificado ICP-Brasil: garante que o documento foi emitido pelo sistema e não foi alterado.
 */
class DocumentSigner
{

    /**
     * @throws \RuntimeException sem chave configurada
     */
    private static function key()
    {
        $key = getenv('APP_SIGNING_KEY');

        if (!$key || strlen($key) < 32) {
            throw new \RuntimeException('APP_SIGNING_KEY ausente ou curta (mínimo 32 caracteres).');
        }

        return $key;
    }


    /**
     * Hash do conteúdo do documento (dados que aparecem impressos)
     */
    public static function contentHash(array $content)
    {
        ksort($content);

        return hash('sha256', json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }


    public static function sign($code, $type, $enrollmentId, $studentName, $issuedAt, $contentHash)
    {
        $payload = implode('|', [$code, $type, (int) $enrollmentId, $studentName, $issuedAt, $contentHash]);

        return hash_hmac('sha256', $payload, self::key());
    }


    public static function verify($code, $type, $enrollmentId, $studentName, $issuedAt, $contentHash, $signature)
    {
        return hash_equals(self::sign($code, $type, $enrollmentId, $studentName, $issuedAt, $contentHash), (string) $signature);
    }


    /**
     * Código público: 12 caracteres sem ambiguidade (sem 0/O/1/I), ~60 bits aleatórios
     */
    public static function newCode()
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < 12; $i++) $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];

        return substr($code, 0, 4) . '-' . substr($code, 4, 4) . '-' . substr($code, 8, 4);
    }


    /**
     * QR code (SVG) apontando para a página de validação
     */
    public static function qrSvg($url)
    {
        $options = new \chillerlan\QRCode\QROptions([
            'outputType' => \chillerlan\QRCode\Output\QROutputInterface::MARKUP_SVG,
            'outputBase64' => false,
            'addQuietzone' => true,
            'svgViewBoxSize' => null,
        ]);

        return (new \chillerlan\QRCode\QRCode($options))->render($url);
    }
}
