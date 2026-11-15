<?php

namespace App\Controllers\Admin;

use MF\Controller\Action;
use MF\Model\Container;

/**
 * Admin: lista geral de ocorrências, com o painel das que aguardam a ciência da família.
 * O cadastro continua no perfil do aluno; aqui é acompanhamento.
 */
class AdminOccurrenceController extends Action
{

    public function index()
    {
        $Occurrence = Container::getModel('TeacherStudent\\Occurrence');
        $filters = \App\Models\TeacherStudent\Occurrence::filters($_GET);

        $this->view->filters = $filters;
        $this->view->result = $Occurrence->adminList($filters);
        $this->view->summary = $Occurrence->pendingSummary();
        $this->view->classes = Container::getModel('TeacherStudent\\GeneralBoard')->classes();

        $this->render('occurrences/index', 'AdminLayout');
    }
}
