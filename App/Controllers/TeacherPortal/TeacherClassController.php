<?php

namespace App\Controllers\TeacherPortal;

use MF\Controller\Action;
use MF\Model\Container;
use App\Tools\Audit;

/**
 * Portal do docente: mural da turma, avaliações/roteiro de provas, notas e desempenho geral da turma.
 * Toda escrita passa por TeacherAccess (Anos Iniciais = turma inteira; demais = própria disciplina).
 */
class TeacherClassController extends Action
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


    /**
     * 403 + linha NEGADO na auditoria
     */
    private function deny($entity, $id, $detail = null)
    {
        Audit::log(\App\Connection::getDb(), 'NEGADO', $entity, $id, $detail);
        $this->json(['error' => 'sem-permissao'], 403);
    }


    private function canEdit($disciplineClassId)
    {
        return Container::getModel('Teacher\\TeacherAccess')->canEdit($this->teacherId(), $disciplineClassId);
    }


    private function openUnits()
    {
        $current = Container::getModel('Admin\\Settings')->unitControlCurrent();
        $max = isset($current[0]) ? max(1, min(4, (int) $current[0]->option_value)) : 1;

        return range(1, $max);
    }


    /**
     * Turmas/disciplinas editáveis (cards de escolha)
     */
    private function loadClasses()
    {
        $this->view->disciplineClasses = Container::getModel('TeacherStudent\\Attendance')->disciplineClassesByTeacher($this->teacherId());
    }


    // ------------------------------------------------------------------ Mural

    public function board()
    {
        $this->loadClasses();
        $this->render('board', 'TeacherPortalLayout', 'teacherPortal');
    }


    public function boardList()
    {
        $classId = (int) ($_GET['turma'] ?? 0);

        if (!Container::getModel('Teacher\\TeacherAccess')->canViewClass($this->teacherId(), $classId)) {
            return $this->json(['error' => 'sem-permissao'], 403);
        }

        $posts = Container::getModel('TeacherStudent\\ClassBoard')->forClass($classId);
        $access = Container::getModel('Teacher\\TeacherAccess');
        $teacherId = $this->teacherId();

        $this->json(['posts' => array_map(function ($p) use ($access, $teacherId) {
            return [
                'id' => (int) $p->id,
                'text' => $p->text,
                'pinned' => (bool) $p->pinned,
                'discipline' => $p->discipline,
                'teacher' => $p->teacher,
                'postedAt' => $p->posted_at,
                'readTotal' => (int) $p->read_total,
                'canManage' => $access->canEdit($teacherId, $p->discipline_class_id),
            ];
        }, $posts)]);
    }


    public function boardPost()
    {
        if (($input = $this->input()) === null) return;

        $disciplineClassId = (int) ($input['disciplineClass'] ?? 0);
        $text = trim((string) ($input['text'] ?? ''));
        $pinned = !empty($input['pinned']);

        if (!$this->canEdit($disciplineClassId)) return $this->deny('aviso_turma', $disciplineClassId);
        if ($text === '' || mb_strlen($text) > 2000) return $this->json(['error' => 'texto-invalido'], 422);

        try {
            $id = Container::getModel('TeacherStudent\\ClassBoard')->post($disciplineClassId, $this->teacherId(), $text, $pinned);
        } catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'limite-fixados') !== false) return $this->json(['error' => 'limite-fixados'], 422);
            throw $e;
        }

        $this->json(['id' => $id]);
    }


    public function boardPin()
    {
        if (($input = $this->input()) === null) return;

        $Board = Container::getModel('TeacherStudent\\ClassBoard');
        $post = $Board->find((int) ($input['id'] ?? 0));

        if (!$post || !$this->canEdit($post->discipline_class_id)) return $this->deny('aviso_turma', $input['id'] ?? null);

        try {
            $Board->setPinned($post->id, !empty($input['pinned']));
        } catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'limite-fixados') !== false) return $this->json(['error' => 'limite-fixados'], 422);
            throw $e;
        }

        $this->json(['status' => 'success']);
    }


    public function boardDelete()
    {
        if (($input = $this->input()) === null) return;

        $Board = Container::getModel('TeacherStudent\\ClassBoard');
        $post = $Board->find((int) ($input['id'] ?? 0));

        if (!$post || !$this->canEdit($post->discipline_class_id)) return $this->deny('aviso_turma', $input['id'] ?? null);

        $Board->delete($post->id);
        $this->json(['status' => 'success']);
    }


    // ------------------------------------------------------------------ Avaliações, roteiro e notas

    public function grades()
    {
        $this->loadClasses();
        $this->view->openUnits = $this->openUnits();
        $this->render('grades', 'TeacherPortalLayout', 'teacherPortal');
    }


    public function gradesExams()
    {
        $disciplineClassId = (int) ($_GET['disciplineClass'] ?? 0);

        if (!$this->canEdit($disciplineClassId)) return $this->json(['error' => 'sem-permissao'], 403);

        $Gradebook = Container::getModel('TeacherStudent\\Gradebook');
        $rule = Container::getModel('Student\\StudentPerformance')->rule();
        $units = [];
        foreach ($this->openUnits() as $u) $units[$u] = $Gradebook->examCount($disciplineClassId, $u);

        $this->json([
            'exams' => array_map(function ($e) {
                return [
                    'id' => (int) $e->id,
                    'description' => $e->description,
                    'value' => (float) $e->value,
                    'unity' => (int) $e->unity,
                    'date' => $e->date,
                    'guide' => $e->guide,
                    'recovery' => (bool) $e->recovery,
                    'graded' => (int) $e->graded,
                ];
            }, $Gradebook->exams($disciplineClassId)),
            'openUnits' => $this->openUnits(),
            'units' => $units,
            'perBimester' => $rule['perBimester'],
            'passing' => $rule['passing'],
        ]);
    }


    /**
     * Cria avaliação de 0 a 10 (até 3 por bimestre) ou a recuperação do bimestre; com "exam", atualiza só o roteiro
     */
    public function examSave()
    {
        if (($input = $this->input()) === null) return;

        $Gradebook = Container::getModel('TeacherStudent\\Gradebook');
        $guide = trim((string) ($input['guide'] ?? ''));
        if (mb_strlen($guide) > 3000) return $this->json(['error' => 'texto-muito-longo'], 422);

        if (!empty($input['exam'])) {
            $exam = $Gradebook->exam((int) $input['exam']);
            if (!$exam || !$this->canEdit($exam->discipline_class_id)) return $this->deny('avaliacoes', $input['exam']);

            $Gradebook->updateGuide($exam->id, $guide);
            return $this->json(['id' => (int) $exam->id]);
        }

        $disciplineClassId = (int) ($input['disciplineClass'] ?? 0);
        if (!$this->canEdit($disciplineClassId)) return $this->deny('avaliacoes', $disciplineClassId);

        $unity = (int) ($input['unity'] ?? 0);
        $description = trim((string) ($input['description'] ?? ''));
        $date = trim((string) ($input['date'] ?? ''));
        $recovery = !empty($input['recovery']);

        if (!in_array($unity, $this->openUnits(), true)) return $this->json(['error' => 'unidade-fechada'], 422);
        if ($description === '' || mb_strlen($description) > 180) return $this->json(['error' => 'descricao-invalida'], 422);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return $this->json(['error' => 'data-invalida'], 422);

        $count = $Gradebook->examCount($disciplineClassId, $unity);
        $perBimester = Container::getModel('Student\\StudentPerformance')->rule()['perBimester'];

        if ($recovery && $count['recovery']) return $this->json(['error' => 'recuperacao-ja-existe'], 422);
        if (!$recovery && $count['regular'] >= $perBimester) return $this->json(['error' => 'limite-avaliacoes', 'max' => $perBimester], 422);

        $this->json(['id' => $Gradebook->createExam($disciplineClassId, $unity, $description, $date, $guide, $recovery)]);
    }


    public function gradesStudents()
    {
        $Gradebook = Container::getModel('TeacherStudent\\Gradebook');
        $exam = $Gradebook->exam((int) ($_GET['exam'] ?? 0));

        if (!$exam || !$this->canEdit($exam->discipline_class_id)) return $this->json(['error' => 'sem-permissao'], 403);

        $rule = Container::getModel('Student\\StudentPerformance')->rule();

        $this->json([
            'exam' => ['id' => (int) $exam->id, 'value' => (float) $exam->value, 'unity' => (int) $exam->unity, 'description' => $exam->description, 'recovery' => (bool) $exam->recovery],
            'editable' => in_array((int) $exam->unity, $this->openUnits(), true),
            'passing' => $rule['passing'],
            'students' => array_map(function ($s) use ($exam, $rule) {
                $eligible = $this->recoveryEligible($s, $exam, $rule);
                return [
                    'id' => (int) $s->enrollment_id,
                    'name' => $s->name,
                    'photo' => $s->photo,
                    'grade' => $s->grade === null ? null : (float) $s->grade,
                    'bimesterAverage' => $s->bimester_average === null ? null : round((float) $s->bimester_average, 2),
                    'eligible' => $eligible,
                ];
            }, $Gradebook->students($exam->id)),
        ]);
    }


    /**
     * Na recuperação só lança nota quem fechou o bimestre abaixo da média; nas demais avaliações, todos
     */
    private function recoveryEligible($student, $exam, array $rule)
    {
        if (!$exam->recovery) return true;

        $b = \App\Tools\GradeSimulator::bimester(
            $student->bimester_average === null ? null : (float) $student->bimester_average,
            (int) $student->bimester_graded, $rule['perBimester'], null, $rule['passing']
        );

        return $b['closed'] && $b['average'] < $rule['passing'];
    }


    public function gradesSave()
    {
        if (($input = $this->input()) === null) return;

        $Gradebook = Container::getModel('TeacherStudent\\Gradebook');
        $exam = $Gradebook->exam((int) ($input['exam'] ?? 0));

        if (!$exam || !$this->canEdit($exam->discipline_class_id)) return $this->deny('nota_avaliacao', $input['exam'] ?? null);
        if (!in_array((int) $exam->unity, $this->openUnits(), true)) return $this->json(['error' => 'unidade-fechada'], 422);

        $rule = Container::getModel('Student\\StudentPerformance')->rule();
        $enrolled = [];
        foreach ($Gradebook->students($exam->id) as $s) $enrolled[(int) $s->enrollment_id] = $this->recoveryEligible($s, $exam, $rule);
        $grades = [];

        foreach ((array) ($input['grades'] ?? []) as $enrollment => $grade) {
            $enrollment = (int) $enrollment;
            if (!isset($enrolled[$enrollment])) return $this->json(['error' => 'aluno-fora-da-turma'], 422);
            if (!$enrolled[$enrollment] && $grade !== null && $grade !== '') return $this->json(['error' => 'sem-recuperacao', 'student' => $enrollment], 422);

            if ($grade === null || $grade === '') {
                $grades[$enrollment] = null;
                continue;
            }

            if (!is_numeric($grade)) return $this->json(['error' => 'nota-invalida', 'student' => $enrollment], 422);
            $grade = round((float) $grade, 2);
            if ($grade < 0 || $grade > (float) $exam->value + 0.001) return $this->json(['error' => 'nota-acima-do-valor', 'student' => $enrollment], 422);

            $grades[$enrollment] = $grade;
        }

        $db = \App\Connection::getDb();

        try {
            $db->beginTransaction();
            $Gradebook->saveGrades($exam->id, $grades);
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('Notas: ' . $e->getMessage());
            return $this->json(['error' => 'falha-ao-salvar'], 500);
        }

        $this->json(['saved' => count($grades)]);
    }


    // ------------------------------------------------------------------ Desempenho geral da turma

    /**
     * Professor do EM vê a turma inteira (todas as disciplinas), mas edita só a dele
     */
    public function classOverview()
    {
        $classId = (int) ($_GET['id'] ?? 0);

        if (!Container::getModel('Teacher\\TeacherAccess')->canViewClass($this->teacherId(), $classId)) {
            header('Location: /portal-docente/turmas');
            return;
        }

        $unity = (int) ($_GET['unidade'] ?? 0);
        if ($unity < 1 || $unity > 4) $unity = max($this->openUnits());

        $Gradebook = Container::getModel('TeacherStudent\\Gradebook');

        $this->view->classId = $classId;
        $this->view->unity = $unity;
        $this->view->rule = Container::getModel('Student\\StudentPerformance')->rule();
        $this->view->overview = $Gradebook->classOverview($classId, $unity, $this->view->rule);
        $this->view->classLabel = $Gradebook->classLabel($classId);
        $this->view->editable = array_map(function ($c) { return (int) $c->discipline_class_id; }, Container::getModel('TeacherStudent\\Attendance')->disciplineClassesByTeacher($this->teacherId()));

        $this->render('classOverview', 'TeacherPortalLayout', 'teacherPortal');
    }

}
