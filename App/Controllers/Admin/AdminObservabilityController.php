<?php

namespace App\Controllers\Admin;

use MF\Controller\Action;
use MF\Model\Container;
use App\Models\Admin\Observability;
use App\Tools\Audit;

/**
 * Observabilidade 360°: visão por etapa (Fund. 1, Fund. 2, Médio), turma, disciplina e professor (issue #18),
 * perfil pedagógico de cada aluno (o mesmo que o professor vê, só leitura), boletim e carômetro
 */
class AdminObservabilityController extends Action
{

    const TABS = ['turmas', 'disciplinas', 'professores'];


    public function index()
    {

        $Observability = Container::getModel('Admin\\Observability');

        $filters = $this->filters($Observability);
        $this->load($Observability, $filters);

        $this->view->tab = in_array($_GET['aba'] ?? '', self::TABS, true) ? $_GET['aba'] : 'turmas';

        $this->render('observability/index', 'AdminLayout');
    }


    /**
     * Uma turma: disciplinas e alunos (com risco), abrindo o perfil do aluno; ?ver=carometro mostra as fotos
     */
    public function classDetail()
    {

        $Observability = Container::getModel('Admin\\Observability');

        $filters = $this->filters($Observability);
        $classId = (int) ($_GET['id'] ?? 0);

        $this->load($Observability, $filters, $classId);

        if (!$classId || !$this->view->summary['classes']) {
            header('Location: /admin/observabilidade?' . http_build_query($this->query($filters)));
            return;
        }

        $this->view->class = $this->view->summary['classes'][0];
        $this->view->tab = ($_GET['ver'] ?? '') === 'carometro' ? 'carometro' : 'alunos';

        $students = array_values($this->view->summary['students']);
        // em risco primeiro, depois pela menor média
        usort($students, function ($a, $b) {
            if ($a['atRisk'] !== $b['atRisk']) return $a['atRisk'] ? -1 : 1;
            return ($a['average'] ?? 99) <=> ($b['average'] ?? 99) ?: strcmp($a['name'], $b['name']);
        });
        $this->view->students = $students;
        $this->view->roster = $this->view->tab === 'carometro' ? $this->roster($classId) : [];

        $this->render('observability/class', 'AdminLayout');
    }


    /**
     * Carômetro: escolhe a turma (agrupada por etapa) e mostra as fotos dos alunos
     */
    public function faceBook()
    {

        $Observability = Container::getModel('Admin\\Observability');
        $filters = $this->filters($Observability);

        $classes = $filters['period'] ? $Observability->classList($filters['period'], $filters['shift'], $filters['stage']) : [];

        $classId = (int) ($_GET['turma'] ?? 0);
        $ids = array_map(function ($c) { return (int) $c->id; }, $classes);
        if (!in_array($classId, $ids, true)) $classId = (int) ($ids[0] ?? 0);

        $this->view->filters = $filters;
        $this->view->query = $this->query($filters);
        $this->view->rule = $Observability->rule();
        $this->view->openUnits = $Observability->openUnits();
        $this->view->classes = $classes;
        $this->view->classId = $classId;
        $this->view->roster = $classId ? $this->roster($classId) : [];

        $this->render('observability/faceBook', 'AdminLayout');
    }


    /**
     * Perfil do aluno, como o professor vê na ficha (todas as disciplinas, só leitura), com o boletim
     */
    public function student()
    {

        $StudentFile = Container::getModel('TeacherStudent\\StudentFile');
        $student = $StudentFile->enrollment((int) ($_GET['id'] ?? 0));

        if (!$student) {
            header('Location: /admin/observabilidade');
            return;
        }

        $Performance = Container::getModel('Student\\StudentPerformance');
        $Occurrence = Container::getModel('TeacherStudent\\Occurrence');
        $Daily = Container::getModel('TeacherStudent\\DailyAttendance');

        $this->view->admin = true;
        $this->view->student = $student;
        $this->view->classLabel = Container::getModel('TeacherStudent\\Gradebook')->classLabel($student->class_id);
        $this->view->rule = $Performance->rule();
        $this->view->performance = $Performance->byDiscipline($student->id, $student->class_id);
        // o admin só consulta: nenhuma disciplina "sua" (sem lançar nota, observação ou pedido de ocorrência)
        $this->view->mine = [];
        $this->view->exams = $StudentFile->exams($student->id, array_map(function ($p) { return (int) $p['id']; }, $this->view->performance));
        $this->view->examTypes = \App\Models\TeacherStudent\Gradebook::EXAM_TYPES + \App\Models\TeacherStudent\Gradebook::EXAM_TYPES_INICIAIS;
        $this->view->notes = $StudentFile->notes($student->id);
        $this->view->guardians = $StudentFile->guardians($student->student_id);
        $this->view->classAverages = $StudentFile->classAverages($student->class_id, $this->view->rule);
        $this->view->occurrences = $Occurrence->getSummaryByEnrollment($student->id);
        $this->view->requests = Container::getModel('TeacherStudent\\OccurrenceRequest')->forEnrollment($student->id);
        $this->view->dailyAttendance = $Daily->isDaily($student->class_id) ? $Daily->summary($student->id, $student->class_id) : null;

        // índice pedagógico: o mesmo cálculo da ficha do professor
        $grades = [];
        $attendance = [];
        foreach ($this->view->performance as $p) {
            foreach ($p['units'] as $unit) if ($unit['grade'] !== null) $grades[] = (float) $unit['grade'];
            if ($p['attendance'] !== null) $attendance[] = (float) $p['attendance'];
        }
        if ($this->view->dailyAttendance !== null) {
            $attendance = $this->view->dailyAttendance['attendance'] === null ? [] : [(float) $this->view->dailyAttendance['attendance']];
        }
        $tones = \App\Tools\PedagogicalIndex::countTones(array_map(function ($n) { return $n->text; }, $this->view->notes));
        $this->view->tones = $tones;
        $this->view->index = \App\Tools\PedagogicalIndex::compute($grades, $attendance, $tones['positive'], $tones['negative'], $Occurrence->severities($student->id));

        $this->view->quickNotes = \App\Models\TeacherStudent\StudentFile::QUICK_NOTES;
        $this->view->unity = Container::getModel('Admin\\Observability')->openUnits();
        $this->view->backUrl = '/admin/observabilidade/turma?id=' . (int) $student->class_id;

        Audit::log(\App\Connection::getDb(), 'CONSULTA', 'matricula', $student->id, 'perfil pedagógico (admin)');

        $this->render('../teacherPortal/studentFile', 'AdminLayout');
    }


    /**
     * Boletim para imprimir/salvar em PDF, com código de validação e QR, emitido pela secretaria
     */
    public function bulletin()
    {

        $enrollmentId = (int) ($_GET['id'] ?? 0);

        $StudentEnrollment = Container::getModel('Student\\StudentEnrollment');
        $StudentEnrollment->__set('studentEnrollmentId', $enrollmentId);
        $enrollment = $enrollmentId ? ($StudentEnrollment->dataGeneral()[0] ?? null) : null;

        if (!$enrollment) {
            http_response_code(404);
            echo 'Matrícula não encontrada.';
            return;
        }

        $Performance = Container::getModel('Student\\StudentPerformance');
        $rule = $Performance->rule();
        $rows = $Performance->byDiscipline($enrollmentId, $enrollment->class_id);
        $Daily = Container::getModel('TeacherStudent\\DailyAttendance');
        $daily = $Daily->isDaily((int) $enrollment->class_id) ? $Daily->summary($enrollmentId, (int) $enrollment->class_id) : null;

        // mesmo conteúdo assinado do boletim emitido pelo portal do aluno
        $content = ['matricula' => $enrollmentId, 'nome' => $enrollment->name, 'ano' => $enrollment->school_year, 'regra' => $rule, 'disciplinas' => array_map(function ($d) {
            return [$d['discipline'], array_column($d['units'], 'grade'), $d['absences'], $d['excused'], $d['simulation']['status']];
        }, $rows)];
        if ($daily !== null) $content['frequencia_dias'] = $daily;

        if (!isset($_SESSION)) session_start();

        $code = Container::getModel('Student\\IssuedDocument')->issue('boletim', $enrollmentId, $enrollment->name, $content, 'admin', $_SESSION['Admin']['id'] ?? null);
        $url = rtrim(getenv('APP_URL') ?: '', '/') . '/validar?codigo=' . $code;

        Audit::log(\App\Connection::getDb(), 'DOWNLOAD', 'boletim', $enrollmentId, ['codigo' => $code, 'por' => 'admin']);

        $this->view->enrollment = $enrollment;
        $this->view->rule = $rule;
        $this->view->rows = $rows;
        $this->view->dailyAttendance = $daily;
        $this->view->authCode = $code;
        $this->view->validationUrl = $url;
        $this->view->qrSvg = \App\Tools\DocumentSigner::qrSvg($url);

        require __DIR__ . '/../../Views/studentPortal/bulletinDocument.php';
    }


    /**
     * Alunos ativos da turma com foto e faltas do bimestre atual (todas as disciplinas)
     */
    private function roster($classId)
    {

        $dcs = Container::getModel('Admin\\Observability')->disciplineClassIds($classId);
        $unity = Container::getModel('Admin\\Observability')->openUnits();

        return Container::getModel('TeacherStudent\\StudentFile')->classRoster($classId, $dcs, $unity);
    }


    private function load($Observability, array $filters, $classId = null)
    {

        $rule = $Observability->rule();
        $openUnits = $Observability->openUnits();

        $this->view->filters = $filters;
        $this->view->rule = $rule;
        $this->view->openUnits = $openUnits;
        $this->view->query = $this->query($filters);
        $this->view->summary = $filters['period']
            ? $Observability->summarize($Observability->collect($filters['period'], $filters['unit'], $filters['shift'], $classId, $classId ? null : $filters['stage']), $rule, $filters['unit'], $openUnits)
            : ['classes' => [], 'stages' => [], 'charts' => null, 'disciplines' => [], 'teachers' => [], 'disciplineClasses' => [], 'students' => []];
    }


    /**
     * Período (padrão: o ativo), bimestre (padrão: ano inteiro), turno e etapa, validados contra o banco
     */
    private function filters($Observability)
    {

        $periods = $Observability->periods();
        $shifts = $Observability->shifts();
        $rule = $Observability->rule();

        $periodIds = array_map(function ($p) { return (int) $p->id; }, $periods);
        $shiftIds = array_map(function ($s) { return (int) $s->id; }, $shifts);

        $period = (int) ($_GET['periodo'] ?? 0);
        if (!in_array($period, $periodIds, true)) $period = $periodIds[0] ?? 0;

        $unit = (int) ($_GET['bimestre'] ?? 0);
        if ($unit < 1 || $unit > $rule['units']) $unit = null;

        $shift = (int) ($_GET['turno'] ?? 0);
        if (!in_array($shift, $shiftIds, true)) $shift = null;

        $stage = (string) ($_GET['etapa'] ?? '');
        if (!isset(Observability::STAGES[$stage])) $stage = null;

        $this->view->periods = $periods;
        $this->view->shifts = $shifts;
        $this->view->stages = Observability::STAGES;

        return ['period' => $period, 'unit' => $unit, 'shift' => $shift, 'stage' => $stage];
    }


    private function query(array $filters)
    {

        return array_filter([
            'periodo' => $filters['period'] ?: null,
            'bimestre' => $filters['unit'],
            'turno' => $filters['shift'],
            'etapa' => $filters['stage'],
        ]);
    }
}
