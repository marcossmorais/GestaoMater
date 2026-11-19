<?php

namespace App\Models\Student;

use MF\Model\Model;

class StudentRematrug extends Model
{

    private $fk_id_rematrug_situation;
    private $fk_id_enrollment;


    public function __get($att)
    {
        return $this->$att;
    }


    public function __set($att, $newValue)
    {
        return $this->$att = $newValue;
    }


    /**
     * Criar uma solicitação de rematrícula
     * 
     * @return void
     */
    public function insert()
    {

        $query = "INSERT INTO rematricula(fk_id_rematricula_aluno, fk_id_situacao_rematricula) VALUES (:enrollmentId, :rematrugSituation)";

        $stmt = $this->db->prepare($query);

        $stmt->bindValue(':enrollmentId', $this->__get('fk_id_enrollment'));
        $stmt->bindValue(':rematrugSituation', $this->__get('fk_id_rematrug_situation'));

        $stmt->execute();
    }


    /**
     * Retorna os estados que uma solicitação de rematrícula pode está. Entretanto, ele deve ser usado para peencher a tag select na View.
     * 
     * @return array
     */
    public function rematrugSituation()
    {

        return $this->speedingUp(
            "SELECT id_situacao_rematricula AS option_value , situacao AS option_text FROM situacao_rematricula ORDER BY id_situacao_rematricula"
        );
    }


    /**
     * Verifica se o aluno já enviou uma solicitação de rematrícula no período letivo atual.
     * 
     * @return array
     */
    public function checkForRegistration()
    {

        $query = "SELECT rematricula.id_rematricula FROM rematricula WHERE rematricula.fk_id_rematricula_aluno = :enrollmentId;";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':enrollmentId', $this->__get('fk_id_enrollment'));
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
}
