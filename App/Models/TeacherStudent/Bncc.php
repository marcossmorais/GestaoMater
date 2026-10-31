<?php

namespace App\Models\TeacherStudent;

use MF\Model\Model;

/**
 * Busca de habilidades da BNCC para o diário de aula (lista em App/Data/bncc-habilidades.tsv, ver App/Data/README.md).
 * Filtra pela etapa/série da turma e pelo componente da disciplina; o professor pode buscar por código ou palavra.
 */
class Bncc extends Model
{

    // disciplina → componente (Ensino Fundamental) e áreas/componentes (Ensino Médio, códigos EM13xxx)
    const COMPONENTS = [
        'lingua portuguesa' => ['EF' => ['LP'], 'EM' => ['LP', 'LGG']],
        'matematica' => ['EF' => ['MA'], 'EM' => ['MAT']],
        'historia' => ['EF' => ['HI'], 'EM' => ['CHS']],
        'geografia' => ['EF' => ['GE'], 'EM' => ['CHS']],
        'filosofia' => ['EF' => [], 'EM' => ['CHS']],
        'sociologia' => ['EF' => [], 'EM' => ['CHS']],
        'fisica' => ['EF' => ['CI'], 'EM' => ['CNT']],
        'quimica' => ['EF' => ['CI'], 'EM' => ['CNT']],
        'biologia' => ['EF' => ['CI'], 'EM' => ['CNT']],
        'ciencias' => ['EF' => ['CI'], 'EM' => ['CNT']],
        'artes' => ['EF' => ['AR'], 'EM' => ['LGG']],
        'arte' => ['EF' => ['AR'], 'EM' => ['LGG']],
        'educacao fisica' => ['EF' => ['EF'], 'EM' => ['LGG']],
        'lingua inglesa' => ['EF' => ['LI'], 'EM' => ['LGG']],
        'ensino religioso' => ['EF' => ['ER'], 'EM' => []],
    ];


    private static function normalize($text)
    {
        $text = mb_strtolower(trim((string) $text));
        return strtr($text, ['á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ü' => 'u', 'ç' => 'c']);
    }


    private static function all()
    {
        static $rows = null;
        if ($rows === null) {
            $rows = [];
            foreach (file(__DIR__ . '/../../Data/bncc-habilidades.tsv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                // rtrim: o checkout no Windows pode trazer o arquivo com CRLF
                [$code, $text] = explode("\t", rtrim($line, "\r"), 2) + [1 => ''];
                $rows[] = [$code, $text];
            }
        }
        return $rows;
    }


    /**
     * O código vale para a turma? EF06MA01 = 6º ano; EF67LP01 = 6º e 7º; EF15LP01 = 1º ao 5º; EM13… = Ensino Médio
     */
    private static function fits($code, $stage, $series, array $components)
    {
        if ($stage === 'medio') {
            if (strpos($code, 'EM13') !== 0) return false;
            return !$components || in_array(preg_replace('/\d+$/', '', substr($code, 4)), $components, true);
        }

        if (strpos($code, 'EF') !== 0) return false;
        $from = (int) $code[2];
        $to = (int) $code[3];
        if ($from === 0) $from = $to;
        if ($series && ($series < $from || $series > $to)) return false;

        return !$components || in_array(preg_replace('/\d+$/', '', substr($code, 4)), $components, true);
    }


    /**
     * @param string $stage      curso.etapa da turma ('iniciais', 'medio'…)
     * @param int    $series     série/ano da turma (Anos Iniciais: 1 a 5)
     * @param string $discipline nome da disciplina
     * @return array [['code' => …, 'text' => …], …] até $limit
     */
    public function search($query, $stage, $series, $discipline, $limit = 20)
    {
        $key = $stage === 'medio' ? 'EM' : 'EF';
        $components = self::COMPONENTS[self::normalize($discipline)][$key] ?? [];
        $query = self::normalize($query);
        $words = array_filter(preg_split('/\s+/', $query));
        $codeQuery = strtoupper(str_replace(' ', '', $query));

        $result = [];
        foreach (self::all() as [$code, $text]) {
            if (!self::fits($code, $stage, (int) $series, $components)) continue;

            if ($words && strpos($code, $codeQuery) !== 0) {
                $haystack = self::normalize($text);
                foreach ($words as $w) {
                    if (strpos($haystack, $w) === false) continue 2;
                }
            }

            $result[] = ['code' => $code, 'text' => $text];
            if (count($result) >= $limit) break;
        }

        return $result;
    }
}
