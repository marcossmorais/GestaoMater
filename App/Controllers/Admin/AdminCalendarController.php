<?php

namespace App\Controllers\Admin;

use MF\Controller\Action;
use MF\Model\Container;
use App\Models\GeneralManagement\SchoolCalendar;
use App\Models\TeacherStudent\GeneralBoard;

/**
 * Admin: cronograma escolar com visões anual, mensal, semanal e diária, cadastro de eventos
 * e "Notificar no mural" (aviso geral para o público escolhido).
 */
class AdminCalendarController extends Action
{

    const VIEWS = ['ano' => 'Ano', 'mes' => 'Mês', 'semana' => 'Semana', 'dia' => 'Dia'];


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


    private static function validDate($value)
    {
        $d = \DateTime::createFromFormat('!Y-m-d', (string) $value);

        return $d && $d->format('Y-m-d') === $value && $d->format('Y') >= 2000 && $d->format('Y') <= 2100;
    }


    /**
     * Período mostrado por cada visão, navegação e rótulo
     *
     * @return array [from, to, prev, next, title]
     */
    public static function range($view, $date)
    {
        $d = new \DateTimeImmutable($date);
        $months = ['', 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
        $short = function (\DateTimeImmutable $x) use ($months) { return $x->format('j') . ' de ' . $months[(int) $x->format('n')]; };

        switch ($view) {
            case 'ano':
                $from = $d->modify('first day of january this year');
                return [$from, $from->modify('last day of december this year'), $d->modify('-1 year'), $d->modify('+1 year'), $d->format('Y')];
            case 'semana':
                $from = $d->modify('-' . ((int) $d->format('N') - 1) . ' days');
                $to = $from->modify('+6 days');
                return [$from, $to, $d->modify('-7 days'), $d->modify('+7 days'), $short($from) . ' a ' . $short($to) . ' de ' . $to->format('Y')];
            case 'dia':
                $weekdays = ['', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado', 'domingo'];
                return [$d, $d, $d->modify('-1 day'), $d->modify('+1 day'), ucfirst($weekdays[(int) $d->format('N')]) . ', ' . $short($d) . ' de ' . $d->format('Y')];
            default:
                $from = $d->modify('first day of this month');
                return [$from, $d->modify('last day of this month'), $from->modify('-1 month'), $from->modify('+1 month'), ucfirst($months[(int) $d->format('n')]) . ' de ' . $d->format('Y')];
        }
    }


    public function index()
    {
        $today = (new \DateTime('now', new \DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
        $view = isset(self::VIEWS[$_GET['visao'] ?? '']) ? $_GET['visao'] : 'mes';
        $date = self::validDate($_GET['data'] ?? '') ? $_GET['data'] : $today;
        $class = (string) ($_GET['turma'] ?? '');
        if ($class !== 'escola') $class = (string) max(0, (int) $class) ?: '';

        [$from, $to, $prev, $next, $title] = self::range($view, $date);

        $Calendar = Container::getModel('GeneralManagement\\SchoolCalendar');

        $this->view->calendar = [
            'view' => $view,
            'date' => $date,
            'today' => $today,
            'class' => $class,
            'from' => $from,
            'to' => $to,
            'prev' => $prev->format('Y-m-d'),
            'next' => $next->format('Y-m-d'),
            'title' => $title,
        ];
        $this->view->events = $Calendar->between($from->format('Y-m-d'), $to->format('Y-m-d'), $class);
        $this->view->classes = Container::getModel('TeacherStudent\\GeneralBoard')->classes();

        $this->render('calendar/index', 'AdminLayout');
    }


    public function save()
    {
        if (($input = $this->input()) === null) return;

        $id = (int) ($input['id'] ?? 0);
        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $type = (string) ($input['type'] ?? '');
        $start = (string) ($input['start'] ?? '');
        $finish = (string) ($input['finish'] ?? '');
        $classId = (int) ($input['class'] ?? 0);

        if ($title === '' || mb_strlen($title) > 120) return $this->json(['error' => 'titulo-invalido'], 422);
        if (mb_strlen($description) > 2000) return $this->json(['error' => 'descricao-invalida'], 422);
        if (!isset(SchoolCalendar::TYPES[$type])) return $this->json(['error' => 'tipo-invalido'], 422);
        if (!self::validDate($start)) return $this->json(['error' => 'data-invalida'], 422);
        if ($finish !== '' && (!self::validDate($finish) || $finish < $start)) return $this->json(['error' => 'fim-antes-do-inicio'], 422);

        if ($classId && !in_array($classId, array_map(function ($c) { return (int) $c->id; }, Container::getModel('TeacherStudent\\GeneralBoard')->classes()), true)) {
            return $this->json(['error' => 'turma-invalida'], 422);
        }

        $saved = Container::getModel('GeneralManagement\\SchoolCalendar')->save($id, $title, $description, $type, $start, $finish, $classId);

        if (!$saved) return $this->json(['error' => 'evento-nao-encontrado'], 404);

        $this->json(['id' => $saved]);
    }


    public function delete()
    {
        if (($input = $this->input()) === null) return;

        Container::getModel('GeneralManagement\\SchoolCalendar')->delete((int) ($input['id'] ?? 0));

        $this->json(['status' => 'success']);
    }


    /**
     * Publica o evento no mural geral, um aviso por público escolhido.
     * Alunos e responsáveis recebem só a turma do evento (ou a escola toda); professores, todos.
     */
    public function notify()
    {
        if (($input = $this->input()) === null) return;

        $Calendar = Container::getModel('GeneralManagement\\SchoolCalendar');
        $event = $Calendar->find((int) ($input['id'] ?? 0));

        if (!$event) return $this->json(['error' => 'evento-nao-encontrado'], 404);

        $audiences = array_values(array_unique(array_filter((array) ($input['audiences'] ?? []), function ($a) {
            return is_string($a) && isset(GeneralBoard::AUDIENCES[$a]);
        })));

        if (!$audiences) return $this->json(['error' => 'publico-invalido'], 422);

        if (!isset($_SESSION)) session_start();

        $Board = Container::getModel('TeacherStudent\\GeneralBoard');
        $text = SchoolCalendar::noticeText($event, (bool) $event->changed_after_notice);
        $noticeType = SchoolCalendar::TYPES[$event->type]['notice'] ?? 'aviso';
        $classIds = $event->class_id ? [(int) $event->class_id] : [];
        $db = \App\Connection::getDb();

        // tudo ou nada: se um público falhar, nenhum aviso fica publicado
        $db->beginTransaction();

        try {
            $ids = [];
            foreach ($audiences as $audience) {
                $ids[] = $Board->post($_SESSION['Admin']['id'] ?? null, $text, $noticeType, false, $audience, $classIds);
            }
            $Calendar->markNotified($event->id);
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }

        $this->json(['notices' => $ids]);
    }
}
