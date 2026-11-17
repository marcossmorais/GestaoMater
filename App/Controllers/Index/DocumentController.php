<?php

namespace App\Controllers\Index;

use MF\Controller\Action;
use MF\Model\Container;

/**
 * Validação pública de documentos emitidos (/validar?codigo=XXXX-XXXX-XXXX).
 * Mostra só tipo, nome do aluno e data de emissão — nenhum outro dado pessoal.
 */
class DocumentController extends Action
{

    public function validate()
    {
        $code = trim((string) ($_GET['codigo'] ?? ''));

        $this->view->code = $code;
        $this->view->result = null;

        if ($code !== '') {
            // página pública: limita as consultas por IP para ninguém varrer códigos (App/Tools/RateLimit.php)
            $RateLimit = new \App\Tools\RateLimit(\App\Connection::getDb());
            if ($RateLimit->hit(\App\Tools\RateLimit::VALIDATE, \App\Tools\RateLimit::ip())) {
                $this->view->result = Container::getModel('Student\\IssuedDocument')->validate($code);
            } else {
                http_response_code(429);
                header('Retry-After: ' . \App\Tools\RateLimit::VALIDATE['window']);
                $this->view->result = ['status' => 'too_many'];
            }
        }

        header('X-Robots-Tag: noindex');
        require __DIR__ . '/../../Views/index/validateDocument.php';
    }
}
