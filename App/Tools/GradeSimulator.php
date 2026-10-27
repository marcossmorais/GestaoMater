<?php

namespace App\Tools;

/**
 * Regra de notas do MATER (funções puras, testadas em tests/GradeSimulatorTest.php).
 * A mesma conta roda no navegador em public/assets/js/performance.js.
 *
 * - 4 bimestres, 3 avaliações por bimestre (notas de 0 a 10).
 * - Nota do bimestre = média aritmética das avaliações.
 * - Bimestre abaixo de 7 tem recuperação; vale a maior nota entre o bimestre e a recuperação.
 * - Resultado do ano = soma das notas dos 4 bimestres; precisa de 7 × 4 = 28. Frequência não entra na conta.
 */
class GradeSimulator
{

    /**
     * Nota do bimestre de um aluno
     *
     * @param float|null $average  média das avaliações já lançadas (0 a 10) ou null
     * @param int        $graded   avaliações com nota
     * @param int        $required avaliações do bimestre (regra: 3)
     * @param float|null $recovery nota da recuperação (0 a 10) ou null
     * @param float      $passing  média do bimestre (regra: 7)
     *
     * @return array grade: nota que vale · closed: as 3 avaliações têm nota · recoveryTaken: fez a recuperação ·
     *               recovered: a recuperação foi maior e substituiu · needsRecovery: fechado, abaixo da média e sem recuperação lançada
     */
    public static function bimester($average, $graded, $required, $recovery, $passing)
    {

        $closed = $graded >= $required && $average !== null;
        $below = $average !== null && $average < $passing - 0.0001;
        $taken = $closed && $below && $recovery !== null;
        // recuperação menor que a nota do bimestre não derruba a nota
        $recovered = $taken && (float) $recovery > (float) $average;

        return [
            'average' => $average === null ? null : round((float) $average, 2),
            'grade' => $recovered ? round((float) $recovery, 2) : ($average === null ? null : round((float) $average, 2)),
            'closed' => $closed,
            'recoveryTaken' => $taken,
            'recovered' => $recovered,
            'needsRecovery' => $closed && $below && $recovery === null,
        ];
    }


    /**
     * Quanto falta para o resultado do ano (soma dos bimestres)
     *
     * @param array $units      [bimestre => nota que vale (float) ou null se ainda aberto], 1..total
     * @param float $passing    média por bimestre (regra: 7); o ano precisa de passing × total (28)
     * @param int   $totalUnits bimestres (regra: 4)
     * @param float $maxPerUnit nota máxima (10)
     *
     * @return array status: aprovado | possivel | impossivel
     *               needed: nota mínima em cada bimestre que falta (null se aprovado ou nenhum falta)
     *               total: soma dos bimestres que já fecharam · target: soma necessária (28)
     *               average: total / bimestres (bimestres abertos contam 0)
     *               missing: bimestres que faltam
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
        $base = ['total' => round($sum, 2), 'target' => round($target, 2), 'average' => $average, 'missing' => $missing];

        if ($remaining <= 0.0001) {
            return ['status' => 'aprovado', 'needed' => null] + $base;
        }

        if ($missing === 0) {
            return ['status' => 'impossivel', 'needed' => null] + $base;
        }

        // arredonda para cima em décimos (nota que o aluno precisa tirar)
        $needed = ceil(round($remaining / $missing, 4) * 10) / 10;

        return ['status' => $needed > $maxPerUnit ? 'impossivel' : 'possivel', 'needed' => $needed] + $base;
    }
}
