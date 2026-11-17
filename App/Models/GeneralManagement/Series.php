<?php

namespace App\Models\GeneralManagement;

use MF\Model\Model;

class Series extends Model
{

    /**
     * Esse método retorna todas as series. Entretanto, ele deve ser usado para peencher a tag select na View.
     * 
     * @return array
     */
    public function listForSelect()
    {

        return $this->speedingUp(
            "SELECT serie.id_serie AS option_value , serie.nome AS option_text FROM serie
             ORDER BY CASE serie.etapa WHEN 'iniciais' THEN 1 WHEN 'finais' THEN 2 ELSE 3 END,
                      NULLIF(regexp_replace(serie.sigla, '\D', '', 'g'), '')::int"
        );
    }
}
