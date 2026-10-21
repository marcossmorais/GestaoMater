<?php

namespace App\Models\Admin;

use MF\Model\Model;

class Dashboard extends Model
{

    /**
     * Totais do painel inicial do admin, em uma única consulta
     *
     * @return object
     */
    public function totals()
    {

        return $this->speedingUp(

            "SELECT

            (SELECT COUNT(*) FROM professor WHERE fk_id_situacao_conta_professor = 1) AS teachers ,

            (SELECT COUNT(*) FROM turma
                INNER JOIN periodo_letivo ON (turma.fk_id_periodo_letivo = periodo_letivo.id_ano_letivo)
                WHERE periodo_letivo.fk_id_situacao_periodo_letivo = 1) AS classes ,

            (SELECT COUNT(*) FROM disciplina) AS disciplines"
        )[0];
    }
}
