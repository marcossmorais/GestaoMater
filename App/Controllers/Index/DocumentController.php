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
        $this->view->result = $code === '' ? null : Container::getModel('Student\\IssuedDocument')->validate($code);

        header('X-Robots-Tag: noindex');
        require __DIR__ . '/../../Views/index/validateDocument.php';
    }
}
