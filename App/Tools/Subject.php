<?php

namespace App\Tools;

/**
 * Cor e ícone de cada disciplina (visual educacional: cada matéria tem a sua "capa de caderno").
 * As cores são classes CSS m-subj-{cor} em public/assets/css/edu.css.
 */
class Subject
{

    const MAP = [
        'matematica' => ['blue', 'fa-square-root-alt'],
        'lingua portuguesa' => ['coral', 'fa-book-open'],
        'portugues' => ['coral', 'fa-book-open'],
        'historia' => ['amber', 'fa-landmark'],
        'geografia' => ['green', 'fa-globe-americas'],
        'fisica' => ['purple', 'fa-atom'],
        'quimica' => ['teal', 'fa-flask'],
        'biologia' => ['lime', 'fa-leaf'],
        'ciencias' => ['teal', 'fa-microscope'],
        'lingua inglesa' => ['pink', 'fa-language'],
        'ingles' => ['pink', 'fa-language'],
        'educacao fisica' => ['orange', 'fa-running'],
        'artes' => ['magenta', 'fa-palette'],
        'arte' => ['magenta', 'fa-palette'],
        'filosofia' => ['indigo', 'fa-lightbulb'],
        'sociologia' => ['brown', 'fa-users'],
        'ensino religioso' => ['indigo', 'fa-dove'],
    ];

    const COLORS = ['blue', 'coral', 'amber', 'green', 'purple', 'teal', 'lime', 'pink', 'orange', 'magenta', 'indigo', 'brown'];


    private static function normalize($name)
    {
        $name = mb_strtolower(trim((string) $name));
        return strtr($name, ['á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c']);
    }


    /**
     * Cor da disciplina; nome fora da lista recebe uma cor fixa calculada pelo nome
     */
    public static function color($name)
    {
        $key = self::normalize($name);
        if (isset(self::MAP[$key])) return self::MAP[$key][0];

        return self::COLORS[abs(crc32($key)) % count(self::COLORS)];
    }


    public static function icon($name)
    {
        return self::MAP[self::normalize($name)][1] ?? 'fa-book';
    }


    /**
     * Classe CSS pronta: "m-subj m-subj-blue"
     */
    public static function css($name)
    {
        return 'm-subj m-subj-' . self::color($name);
    }
}
