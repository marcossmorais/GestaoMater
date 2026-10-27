<?php

namespace App\Tools;

/**
 * Simulador de notas (função pura, testada em tests/GradeSimulatorTest.php).
 * A mesma conta roda no navegador em public/assets/js/performance.js.
 *
 * Média final = soma das notas das unidades / total de unidades.
 * Aprova quem atinge a média de aprovação (regra em regra_aprovacao).
 */
class GradeSimulator
{

    /**
     * @param array $units      [unidade => nota (float) ou null se ainda não lançada], 1..total
     * @param float $passing    média de aprovação (ex.: 6)
     * @param int   $totalUnits total de unidades (ex.: 3)
     * @param float $maxPerUnit nota máxima por unidade (ex.: 10)
     *
     * @return array status: aprovado | possivel | impossivel
     *               needed: nota mínima em cada unidade pendente (null se aprovado ou nenhuma pendente)
     *               average: média com o que já foi lançado (pendentes = 0)
     *               missing: unidades pendentes
     */
    public static function simulate(array $units, $passing, $totalUnits, $maxPerUnit)
    {

        $sum = 0.0;
        $missing = 0;

        for ($u = 1; $u <= $totalUnits; $u++) {
            $grade = $units[$u] ?? null;
            if ($grade === null) {
                $missing++;
            } else {
                $sum += min((float) $grade, (float) $maxPerUnit);
            }
        }

        $target = (float) $passing * $totalUnits;
        $remaining = $target - $sum;
        $average = round($sum / $totalUnits, 2);

        if ($remaining <= 0.0001) {
            return ['status' => 'aprovado', 'needed' => null, 'average' => $average, 'missing' => $missing];
        }

        if ($missing === 0) {
            return ['status' => 'impossivel', 'needed' => null, 'average' => $average, 'missing' => 0];
        }

        // arredonda para cima em décimos (nota que o aluno precisa tirar)
        $needed = ceil(round($remaining / $missing, 4) * 10) / 10;

        return [
            'status' => $needed > $maxPerUnit ? 'impossivel' : 'possivel',
            'needed' => $needed,
            'average' => $average,
            'missing' => $missing,
        ];
    }
}
