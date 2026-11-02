<?php

namespace App\Tools;

/**
 * Leitura e normalização dos arquivos de importação do admin (CSV ou JSON).
 *
 * Sem acesso a banco: só transforma o arquivo em linhas ['coluna' => 'valor'] e valida formatos.
 */
class ImportParser
{

    const MAX_ROWS = 2000;


    /**
     * @return array ['rows' => [[coluna => valor]], 'error' => string|null]
     */
    public static function parse($content, $extension)
    {

        $content = (string) $content;

        // Excel salva CSV em Windows-1252; o resto do sistema é UTF-8
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        if (trim($content) === '') return ['rows' => [], 'error' => 'O arquivo está vazio.'];

        $rows = strtolower($extension) === 'json' ? self::parseJson($content) : self::parseCsv($content);

        if (is_string($rows)) return ['rows' => [], 'error' => $rows];
        if (!$rows) return ['rows' => [], 'error' => 'Nenhuma linha de dados encontrada.'];
        if (count($rows) > self::MAX_ROWS) return ['rows' => [], 'error' => 'O arquivo tem mais de ' . self::MAX_ROWS . ' linhas. Divida em partes menores.'];

        return ['rows' => $rows, 'error' => null];
    }


    private static function parseJson($content)
    {

        $data = json_decode($content, true);

        if (!is_array($data)) return 'JSON inválido: ' . json_last_error_msg() . '.';

        // aceita [ {...}, {...} ] ou { "dados": [ ... ] }
        if (!array_is_list($data)) {
            $lists = array_values(array_filter($data, function ($v) { return is_array($v) && array_is_list($v); }));
            if (count($lists) !== 1) return 'O JSON deve ser uma lista de objetos.';
            $data = $lists[0];
        }

        $rows = [];
        foreach ($data as $item) {
            if (!is_array($item) || array_is_list($item)) return 'Cada item do JSON deve ser um objeto com os campos.';
            $row = [];
            foreach ($item as $key => $value) {
                $row[self::key($key)] = is_scalar($value) ? trim((string) $value) : '';
            }
            $rows[] = $row;
        }

        return $rows;
    }


    private static function parseCsv($content)
    {

        $lines = preg_split('/\r\n|\r|\n/', $content);
        $first = '';
        foreach ($lines as $line) {
            if (trim($line) !== '') { $first = $line; break; }
        }

        // planilha brasileira costuma usar ponto e vírgula
        $delimiter = substr_count($first, ';') >= substr_count($first, ',') ? ';' : ',';
        if (substr_count($first, "\t") > max(substr_count($first, ';'), substr_count($first, ','))) $delimiter = "\t";

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        $headers = null;
        $rows = [];

        while (($fields = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            if ($fields === [null] || implode('', array_map('trim', $fields)) === '') continue;

            if ($headers === null) {
                $headers = array_map([self::class, 'key'], $fields);
                if (count(array_filter($headers)) === 0) return 'A primeira linha deve ter os nomes das colunas.';
                continue;
            }

            $row = [];
            foreach ($headers as $i => $header) {
                if ($header === '') continue;
                $row[$header] = trim((string) ($fields[$i] ?? ''));
            }
            $rows[] = $row;

            if (count($rows) > self::MAX_ROWS) break;
        }

        fclose($handle);

        if ($headers === null) return 'A primeira linha deve ter os nomes das colunas.';

        return $rows;
    }


    /**
     * "Data de Nascimento" => "data_de_nascimento"; "E-mail" => "e_mail"
     */
    public static function key($text)
    {

        $text = self::fold($text);
        $text = preg_replace('/[^a-z0-9]+/', '_', $text);

        return trim($text, '_');
    }


    /**
     * Minúsculas sem acento, para comparar nomes ("Matutino" = "matutino", "1ª" = "1a")
     */
    public static function fold($text)
    {

        $text = mb_strtolower(trim((string) $text), 'UTF-8');
        $text = strtr($text, ['ª' => 'a', 'º' => 'o', '°' => 'o']);

        // separa letra e acento e descarta o acento (iconv translitera diferente em cada sistema)
        if (class_exists('Normalizer')) {
            $text = preg_replace('/\p{Mn}+/u', '', \Normalizer::normalize($text, \Normalizer::FORM_D));
        } else {
            $text = strtr($text, ['á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'é' => 'e', 'ê' => 'e', 'è' => 'e', 'í' => 'i', 'ì' => 'i', 'ó' => 'o',
                                  'ô' => 'o', 'õ' => 'o', 'ò' => 'o', 'ú' => 'u', 'ü' => 'u', 'ù' => 'u', 'ç' => 'c', 'ñ' => 'n']);
        }

        return $text;
    }


    /**
     * Só letras e números ("1ª A · EM · Matutino" => "1aemmatutino")
     */
    public static function compact($text)
    {

        return preg_replace('/[^a-z0-9]/', '', self::fold($text));
    }


    public static function digits($text)
    {

        return preg_replace('/\D/', '', (string) $text);
    }


    /**
     * CPF com dígitos verificadores válidos; devolve só os 11 números ou null
     */
    public static function cpf($text)
    {

        $cpf = str_pad(self::digits($text), 11, '0', STR_PAD_LEFT);

        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf) || self::digits($text) === '') return null;

        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) $sum += (int) $cpf[$i] * (($t + 1) - $i);
            $digit = ((10 * $sum) % 11) % 10;
            if ((int) $cpf[$t] !== $digit) return null;
        }

        return $cpf;
    }


    /**
     * dd/mm/aaaa, dd-mm-aaaa ou aaaa-mm-dd => aaaa-mm-dd (ou null se inválida)
     */
    public static function date($text)
    {

        $text = trim((string) $text);

        if (preg_match('#^(\d{1,2})[/.-](\d{1,2})[/.-](\d{4})$#', $text, $m)) {
            [$d, $mo, $y] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('#^(\d{4})-(\d{1,2})-(\d{1,2})$#', $text, $m)) {
            [$y, $mo, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } else {
            return null;
        }

        if (!checkdate($mo, $d, $y) || $y < 1900 || $y > (int) date('Y')) return null;

        return sprintf('%04d-%02d-%02d', $y, $mo, $d);
    }


    public static function email($text)
    {

        $text = trim((string) $text);

        return $text !== '' && filter_var($text, FILTER_VALIDATE_EMAIL) ? mb_strtolower($text) : null;
    }
}
