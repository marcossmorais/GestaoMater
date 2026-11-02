<?php

namespace App\Controllers\Admin;

use MF\Controller\Action;
use MF\Model\Container;

/**
 * Admin: o que chega dos professores. Roteiros de prova enviados e solicitações de ocorrência.
 */
class AdminPedagogicalController extends Action
{

    private function json($data, $code = 200)
    {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }


    private function input()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'metodo-invalido'], 405);
            return null;
        }

        return json_decode(file_get_contents('php://input'), true) ?: [];
    }


    public function index()
    {
        $unity = (int) ($_GET['unidade'] ?? 0);
        if ($unity < 1 || $unity > 4) $unity = 0;

        $tab = ($_GET['ver'] ?? '') === 'ocorrencias' ? 'ocorrencias' : 'roteiros';

        $this->view->tab = $tab;
        $this->view->unity = $unity;
        $this->view->guides = Container::getModel('TeacherStudent\\Gradebook')->sentGuides($unity);
        $this->view->requests = Container::getModel('TeacherStudent\\OccurrenceRequest')->queue();

        $this->render('pedagogical', 'AdminLayout');
    }


    public function guideSeen()
    {
        if (($input = $this->input()) === null) return;

        Container::getModel('TeacherStudent\\Gradebook')->markGuideSeen((int) ($input['id'] ?? 0));

        $this->json(['status' => 'success']);
    }


    public function requestAnswer()
    {
        if (($input = $this->input()) === null) return;

        $id = (int) ($input['id'] ?? 0);
        $answer = trim((string) ($input['answer'] ?? ''));

        if (mb_strlen($answer) > 255) return $this->json(['error' => 'texto-muito-longo'], 422);

        $Request = Container::getModel('TeacherStudent\\OccurrenceRequest');

        if (!empty($input['accept'])) {
            $done = $Request->accept($id, $answer, !empty($input['visible'])) !== null;
        } else {
            if ($answer === '') return $this->json(['error' => 'motivo-obrigatorio'], 422);
            $done = $Request->reject($id, $answer);
        }

        if (!$done) return $this->json(['error' => 'ja-analisada'], 409);

        $this->json(['status' => 'success']);
    }
}
