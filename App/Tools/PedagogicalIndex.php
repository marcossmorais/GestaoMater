<?php

namespace App\Tools;

/**
 * Índice pedagógico do aluno (0 a 100): apoio à conversa com o aluno e a família, não é nota.
 * Notas 40%, frequência 30% e comportamento 30%; a parte sem dados sai da conta e o peso vai para as outras.
 */
class PedagogicalIndex
{

    const WEIGHTS = ['grades' => 40, 'attendance' => 30, 'behavior' => 30];

    // comportamento começa em 70 e sobe ou desce a cada registro
    const BEHAVIOR_BASE = 70;
    const POSITIVE_NOTE = 5;
    const NEGATIVE_NOTE = -5;
    const OCCURRENCE = [
        'Elogio' => 10,
        'Baixa' => -5,
        'Média' => -10,
        'Alta' => -15,
        'Gravíssima' => -20,
    ];

    // o tom vem das caixas marcáveis; texto livre não conta (o sistema não adivinha o que o professor escreveu)
    const POSITIVE_TEXTS = ['Participou bem da aula', 'Ajudou os colegas', 'Evoluiu no conteúdo'];
    const NEGATIVE_TEXTS = ['Não fez a tarefa', 'Sem material', 'Conversa / indisciplina'];


    /**
     * Quantas marcações positivas e negativas há nos textos das observações
     *
     * @param string[] $texts
     * @return array ['positive' => int, 'negative' => int]
     */
    public static function countTones(array $texts)
    {
        $count = ['positive' => 0, 'negative' => 0];

        foreach ($texts as $text) {
            foreach (self::POSITIVE_TEXTS as $mark) $count['positive'] += substr_count((string) $text, $mark);
            foreach (self::NEGATIVE_TEXTS as $mark) $count['negative'] += substr_count((string) $text, $mark);
        }

        return $count;
    }


    /**
     * @param float[] $grades notas dos bimestres (0 a 10)
     * @param float[] $attendance frequência por disciplina (0 a 100)
     * @param int $positive marcações positivas
     * @param int $negative marcações negativas
     * @param array $occurrences [['category' => 'Elogio'|'Disciplinar'|'Pedagógica'|'Saúde', 'severity' => 'Baixa'|...]]
     * @return array ['score' => int|null, 'band' => string, 'label' => string, 'parts' => [...]]
     */
    public static function compute(array $grades, array $attendance, $positive, $negative, array $occurrences = [])
    {
        $records = (int) $positive + (int) $negative + count($occurrences);

        $values = [
            'grades' => $grades ? array_sum($grades) / count($grades) * 10 : null,
            'attendance' => $attendance ? array_sum($attendance) / count($attendance) : null,
            'behavior' => null,
        ];

        // sem nenhum registro, o comportamento não pesa contra nem a favor
        if ($records > 0) {
            $behavior = self::BEHAVIOR_BASE + (int) $positive * self::POSITIVE_NOTE + (int) $negative * self::NEGATIVE_NOTE;
            foreach ($occurrences as $o) $behavior += self::occurrencePoints($o);
            $values['behavior'] = max(0, min(100, $behavior));
        }

        $weight = 0;
        $sum = 0;
        foreach (self::WEIGHTS as $key => $w) {
            if ($values[$key] === null) continue;
            $weight += $w;
            $sum += $values[$key] * $w;
        }

        $score = $weight ? (int) round($sum / $weight) : null;

        return [
            'score' => $score,
            'band' => self::band($score),
            'label' => self::label($score),
            'parts' => array_map(function ($v) { return $v === null ? null : (int) round($v); }, $values),
        ];
    }


    private static function occurrencePoints(array $occurrence)
    {
        $category = $occurrence['category'] ?? '';
        if ($category === 'Elogio') return self::OCCURRENCE['Elogio'];
        if ($category !== 'Disciplinar' && $category !== 'Pedagógica') return 0;

        return self::OCCURRENCE[$occurrence['severity'] ?? 'Média'] ?? self::OCCURRENCE['Média'];
    }


    public static function band($score)
    {
        if ($score === null) return 'none';
        if ($score >= 80) return 'high';
        if ($score >= 60) return 'ok';
        if ($score >= 40) return 'mid';

        return 'low';
    }


    public static function label($score)
    {
        return [
            'none' => 'Sem dados ainda',
            'high' => 'Muito bom',
            'ok' => 'Adequado',
            'mid' => 'Atenção',
            'low' => 'Prioridade',
        ][self::band($score)];
    }
}
