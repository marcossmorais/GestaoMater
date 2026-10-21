<?php


namespace App\Controllers\Admin;

use MF\Controller\Action;
use MF\Model\Container;


class AdminPeopleController extends Action
{


    public function availableSex()
    {

        $People = Container::getModel('People\\People');
        echo json_encode($People->availableSex());
    }


    public function pcd()
    {

        $People = Container::getModel('People\\People');
        echo json_encode($People->pcd());
    }


    public function bloodType()
    {

        $People = Container::getModel('People\\People');
        echo json_encode($People->availablebloodType());
    }


    public function checkCpf()
    {

        $People = Container::getModel('People\\People');

        $cpf = preg_replace('/[^0-9]/', '', $_GET['cpf'] ?? '');

        if ($cpf === '') {
            echo json_encode([]);
            return;
        }

        $People->__set('cpf', $cpf);

        echo json_encode($People->checkCpf()); 
    }
}
