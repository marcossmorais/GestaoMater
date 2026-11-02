<?php

namespace App\Controllers\TeacherPortal;

use MF\Controller\Action;
use MF\Model\Container;
use App\Models\TeacherStudent\Lesson;
use App\Tools\Audit;

/**
 * Portal do docente: calendário do diário (o que foi dado e o que está planejado) e planos de aula.
 * Toda escrita passa por TeacherAccess; o plano só pode ser alterado por quem o criou.
 */
class TeacherPlanningController extends Action
{

    private function teacherId()
    {
        if (!isset($_SESSION)) session_start();

        return (int) $_SESSION['Teacher']['id'];
    }


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


    private function deny($id, $detail = null)
    {
        Audit::log(\App\Connection::getDb(), 'NEGADO', 'plano_aula', $id, $detail);
        $this->json(['error' => 'sem-permissao'], 403);
    }


    private function canEdit($disciplineClassId)
    {
        return Container::getModel('Teacher\\TeacherAccess')->canEdit($this->teacherId(), $disciplineClassId);
    }


    private function validDate($date)
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4));
    }


    /**
     * Calendário: aulas dadas, planos, avaliações, eventos e horário semanal (?inicio=YYYY-MM-DD&fim=YYYY-MM-DD, até 62 dias)
     */
    public function calendar()
    {
        $from = (string) ($_GET['inicio'] ?? '');
        $to = (string) ($_GET['fim'] ?? '');

        if (!$this->validDate($from) || !$this->validDate($to)) {
            $from = date('Y-m-01');
            $to = date('Y-m-t');
        }

        if ($to < $from || (strtotime($to) - strtotime($from)) / 86400 > 62) return $this->json(['error' => 'periodo-invalido'], 422);

        $teacherId = $this->teacherId();
        $classes = Container::getModel('TeacherStudent\\Attendance')->disciplineClassesByTeacher($teacherId);
        $ids = array_map(function ($c) { return (int) $c->discipline_class_id; }, $classes);
        $classIds = array_values(array_unique(array_map(function ($c) { return (int) $c->class_id; }, $classes)));

        $Lesson = Container::getModel('TeacherStudent\\Lesson');
        $Plan = Container::getModel('TeacherStudent\\LessonPlan');
        $split = function ($csv) { return array_values(array_filter(explode(',', $csv))); };

        $this->json([
            'lessons' => array_map(function ($l) use ($split) {
                return [
                    'id' => (int) $l->id,
                    'dc' => (int) $l->discipline_class_id,
                    'date' => $l->iso_date,
                    'bnccCode' => $l->bncc_code,
                    'bnccSkill' => $l->bncc_skill,
                    'notes' => $l->notes,
                    'activities' => array_map(function ($a) { return Lesson::ACTIVITIES[$a] ?? $a; }, $split($l->activities)),
                    'substitute' => $l->substitute,
                    'taught' => $l->taught_discipline,
                    'reason' => $l->reason,
                ];
            }, $Lesson->between($ids, $from, $to)),
            'plans' => array_map(function ($p) use ($split) {
                return [
                    'id' => (int) $p->id,
                    'dc' => (int) $p->discipline_class_id,
                    'given' => (bool) $p->lesson_id,
                    'date' => $p->date,
                    'title' => $p->title,
                    'lessons' => (int) $p->lessons,
                    'bnccCode' => $p->bncc_code,
                    'bnccSkill' => $p->bncc_skill,
                    'objectives' => $p->objectives,
                    'development' => $p->development,
                    'resources' => $p->resources,
                    'assessment' => $p->assessment,
                    'homework' => $p->homework,
                    'activities' => $split($p->activities),
                ];
            }, $Plan->between($teacherId, $ids, $from, $to)),
            'exams' => array_map(function ($e) {
                return ['id' => (int) $e->id, 'dc' => (int) $e->discipline_class_id, 'date' => $e->date, 'description' => $e->description];
            }, $Plan->examsBetween($ids, $from, $to)),
            'events' => $Plan->eventsBetween($classIds, $from, $to),
            'slots' => array_map(function ($s) {
                return ['dc' => (int) $s->discipline_class_id, 'weekday' => (int) $s->weekday, 'start' => $s->start, 'finish' => $s->finish, 'count' => (int) $s->count];
            }, $Plan->weeklySlots($teacherId)),
        ]);
    }


    /**
     * Cria ou altera um plano (POST JSON: id?, disciplineClass, date, title, lessons, bnccCode, bnccSkill,
     * objectives, development, resources, assessment, homework, activities[])
     */
    public function planSave()
    {
        if (($input = $this->input()) === null) return;

        $Plan = Container::getModel('TeacherStudent\\LessonPlan');
        $teacherId = $this->teacherId();
        $disciplineClassId = (int) ($input['disciplineClass'] ?? 0);
        $id = (int) ($input['id'] ?? 0);

        if (!$this->canEdit($disciplineClassId)) return $this->deny($id ?: null, ['disciplineClass' => $disciplineClassId]);

        if ($id) {
            $plan = $Plan->find($id);
            if (!$plan || (int) $plan->teacher_id !== $teacherId) return $this->deny($id);
        }

        $date = trim((string) ($input['date'] ?? ''));
        if (!$this->validDate($date)) return $this->json(['error' => 'data-invalida'], 422);

        $title = trim((string) ($input['title'] ?? ''));
        if ($title === '') return $this->json(['error' => 'titulo-obrigatorio'], 422);

        $lessons = (int) ($input['lessons'] ?? 1);
        if ($lessons < 1 || $lessons > 10) return $this->json(['error' => 'quantidade-invalida'], 422);

        $bnccCode = strtoupper(preg_replace('/\s+/', '', (string) ($input['bnccCode'] ?? '')));
        if ($bnccCode !== '' && !preg_match(Lesson::BNCC_CODE, $bnccCode)) return $this->json(['error' => 'codigo-bncc-invalido'], 422);

        $text = [];
        foreach (['bnccSkill' => 1000, 'objectives' => 2000, 'development' => 4000, 'resources' => 1000, 'assessment' => 1000, 'homework' => 1000] as $key => $max) {
            $text[$key] = trim((string) ($input[$key] ?? ''));
            if (mb_strlen($text[$key]) > $max) return $this->json(['error' => 'texto-muito-longo'], 422);
        }
        if (mb_strlen($title) > 160) return $this->json(['error' => 'texto-muito-longo'], 422);

        $activities = array_values(array_unique(array_filter((array) ($input['activities'] ?? []), 'is_string')));
        if (array_diff($activities, array_keys(Lesson::ACTIVITIES))) return $this->json(['error' => 'atividade-invalida'], 422);

        try {
            $savedId = $Plan->save($text + [
                'id' => $id,
                'disciplineClassId' => $disciplineClassId,
                'teacherId' => $teacherId,
                'date' => $date,
                'title' => $title,
                'lessons' => $lessons,
                'bnccCode' => $bnccCode,
                'activities' => $activities,
            ]);
        } catch (\Throwable $e) {
            error_log('Plano de aula: ' . $e->getMessage());
            return $this->json(['error' => 'falha-ao-salvar'], 500);
        }

        $this->json(['id' => $savedId, 'date' => $date]);
    }


    public function planDelete()
    {
        if (($input = $this->input()) === null) return;

        $Plan = Container::getModel('TeacherStudent\\LessonPlan');
        $id = (int) ($input['id'] ?? 0);
        $plan = $Plan->find($id);

        if (!$plan || (int) $plan->teacher_id !== $this->teacherId() || !$this->canEdit($plan->discipline_class_id)) return $this->deny($id);

        $Plan->delete($id);

        $this->json(['ok' => true]);
    }
}
