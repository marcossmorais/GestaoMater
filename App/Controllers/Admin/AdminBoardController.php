<?php

namespace App\Controllers\Admin;

use MF\Controller\Action;
use MF\Model\Container;
use App\Models\TeacherStudent\ClassBoard;
use App\Models\TeacherStudent\GeneralBoard;

/**
 * Admin: avisos gerais por público (alunos, responsáveis, professores), para toda a escola ou turmas escolhidas.
 * Alunos e responsáveis veem no portal do aluno, acima dos avisos por matéria; professores, no início do portal docente.
 */
class AdminBoardController extends Action
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


    private function ruleError(\PDOException $e)
    {
        foreach (['limite-fixados', 'professores-sem-turma'] as $rule) {
            if (strpos($e->getMessage(), $rule) !== false) return $this->json(['error' => $rule], 422);
        }

        throw $e;
    }


    public function index()
    {
        $Board = Container::getModel('TeacherStudent\\GeneralBoard');
        $audience = (string) ($_GET['publico'] ?? '');

        $this->view->audience = isset(GeneralBoard::AUDIENCES[$audience]) ? $audience : '';
        $this->view->posts = $Board->all($this->view->audience);
        $this->view->classes = $Board->classes();

        $this->render('board', 'AdminLayout');
    }


    public function post()
    {
        if (($input = $this->input()) === null) return;

        $text = trim((string) ($input['text'] ?? ''));
        $type = (string) ($input['type'] ?? 'aviso');
        $audience = (string) ($input['audience'] ?? '');
        $classIds = array_values(array_unique(array_filter(array_map('intval', (array) ($input['classes'] ?? [])))));

        if ($text === '' || mb_strlen($text) > 2000) return $this->json(['error' => 'texto-invalido'], 422);
        if (!isset(ClassBoard::TYPES[$type])) return $this->json(['error' => 'tipo-invalido'], 422);
        if (!isset(GeneralBoard::AUDIENCES[$audience])) return $this->json(['error' => 'publico-invalido'], 422);
        if ($audience === 'professores') $classIds = [];

        $Board = Container::getModel('TeacherStudent\\GeneralBoard');

        if (array_diff($classIds, array_map(function ($c) { return (int) $c->id; }, $Board->classes()))) {
            return $this->json(['error' => 'turma-invalida'], 422);
        }

        if (!isset($_SESSION)) session_start();

        try {
            $id = $Board->post($_SESSION['Admin']['id'] ?? null, $text, $type, !empty($input['pinned']), $audience, $classIds);
        } catch (\PDOException $e) {
            return $this->ruleError($e);
        }

        $this->json(['id' => $id]);
    }


    public function pin()
    {
        if (($input = $this->input()) === null) return;

        try {
            Container::getModel('TeacherStudent\\GeneralBoard')->setPinned((int) ($input['id'] ?? 0), !empty($input['pinned']));
        } catch (\PDOException $e) {
            return $this->ruleError($e);
        }

        $this->json(['status' => 'success']);
    }


    public function delete()
    {
        if (($input = $this->input()) === null) return;

        Container::getModel('TeacherStudent\\GeneralBoard')->delete((int) ($input['id'] ?? 0));

        $this->json(['status' => 'success']);
    }
}
