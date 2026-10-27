<?php

namespace App\Controllers\TeacherPortal;

use MF\Controller\Action;
use MF\Model\Container;
use App\Tools\Tools;
use App\Tools\Audit;

class TeacherPortalController extends Action
{

    public function index()
    {

        $this->render("index/login", "SimpleLayout", "teacherPortal");
    }


    public function auth()
    {

        // com o Clerk ativo o login por nome + código fica desligado
        if (\App\Auth\Clerk::enabled()) {
            header('Location: /portal-docente');
            return;
        }

        $Teacher = Container::getModel('Teacher\\Teacher');
        $Tool = new Tools();

        $Teacher->__set('accessCode', preg_replace('/\D/', '', $_POST['accessCode'] ?? '') ?: '0');
        $Teacher->__set('name', $_POST['name']);

        $auth = $Teacher->login();
        $account_status = $Teacher->accountStatus();

        if (count($auth) != 1) {

            header('Location: /portal-docente?error=dados-incorretos');
        } else {

            if ($account_status[0]->account_status == 1) {

                if (!isset($_SESSION)) session_start();

                $_SESSION['Teacher'] = [

                    'id' => $auth[0]->teacher_id,
                    'name' => $auth[0]->teacher_name,
                    'profilePhoto' => $auth[0]->teacher_photo,
                    'hierarchyFunction' => $auth[0]->hierarchy_function

                ];

                session_cache_expire(60);

                header('Location: /portal-docente/home');
            } else {

                header("Location: /portal-docente?error=conta-desativada");
            }
        }
    }


    public function home()
    {

        $Settings = Container::getModel('Admin\\Settings');
        $SchoolTerm = Container::getModel('GeneralManagement\\SchoolTerm');
        $Teacher = Container::getModel('Teacher\\Teacher');
        $Exam = Container::getModel('TeacherStudent\\Exam');
        $ClasseWarning = Container::getModel('GeneralManagement\\ClasseWarning');
        $Tools = new Tools();

        if (!isset($_SESSION)) session_start();

        $Teacher->__set('teacherId', $_SESSION['Teacher']['id']);

        $this->view->unitControlCurrent = $Settings->unitControlCurrent();
        $this->view->SchoolTermActive = $SchoolTerm->active();
        $this->view->listExam = $Exam->readExamByIdTeacher($Teacher);
        $this->view->listNote = $Teacher->readNoteByIdTeacher();
        $this->view->listLack = $Teacher->readLackByIdTeacher();
        $this->view->listObservation = $Teacher->readObservationByIdTeacher();
        $this->view->listDisciplineAverage = $Teacher->readByIdTeacherAverage();
        $this->view->listWarning = $ClasseWarning->readByIdTeacher($Teacher);
        $this->view->tools = $Tools;

        $this->render('home', 'TeacherPortalLayout', 'teacherPortal');
    }


    public function teacherClasses()
    {

        $Teacher = Container::getModel('Teacher\\Teacher');
        $Course = Container::getModel('GeneralManagement\\Course');
        $ClassRoom = Container::getModel('GeneralManagement\\ClassRoom');
        $SchoolTerm = Container::getModel('GeneralManagement\\SchoolTerm');
        $Shift = Container::getModel('GeneralManagement\\Shift');
        $Series = Container::getModel('GeneralManagement\\Series');
        $Ballot = Container::getModel('GeneralManagement\\Ballot');

        if (!isset($_SESSION)) session_start();

        $Teacher->__set('teacherId', $_SESSION['Teacher']['id']);

        $this->view->teacherClasses = $Teacher->teacherClasses();
        $this->view->availableShift = $Shift->listForSelect();
        $this->view->availableBallot = $Ballot->listForSelect();
        $this->view->availableSeries = $Series->listForSelect();
        $this->view->availableCourse = $Course->listForSelect();
        $this->view->availableClassRoom = $ClassRoom->listForSelect();
        $this->view->activeSchoolTerm = $SchoolTerm->active();

        $this->render('management/pages/listClass', 'TeacherPortalLayout', 'teacherPortal');
    }


    public function seekTeacherClasses()
    {

        $Classe = Container::getModel('GeneralManagement\\Classe');
        $Teacher = Container::getModel('Teacher\\Teacher');

        $Classe->__set('fk_id_course', $_GET['course']);
        $Classe->__set('fk_id_shift', $_GET['shift']);
        $Classe->__set('fk_id_series', $_GET['series']);
        $Classe->__set('fk_id_series', $_GET['series']);

        if (!isset($_SESSION)) session_start();

        $Teacher->__set('teacherId', $_SESSION['Teacher']['id']);

        $this->view->teacherClasses = $Teacher->seekTeacherClasses($Classe);

        $this->render('management/components/classListing', 'SimpleLayout', 'teacherPortal');
    }


    public function settings()
    {

        $Teacher = Container::getModel('Teacher\\Teacher');

        if (!isset($_SESSION)) session_start();

        $Teacher->__set('teacherId', $_SESSION['Teacher']['id']);

        $this->view->Data = $Teacher->dataGeneral();
        $this->view->availableSex = $Teacher->availableSex();
        $this->view->pcd = $Teacher->pcd();
        $this->view->bloodType = $Teacher->availablebloodType();

        $this->render('settings', 'SimpleLayout', 'teacherPortal');
    }


    public function updateTeacherPortal()
    {

        $Teacher = Container::getModel('Teacher\\Teacher');
        $Address =  Container::getModel('People\\Address');
        $Telephone = Container::getModel('People\Telephone');

        $Tool = new Tools();

        if (!isset($_SESSION)) session_start();

        $Address->__set('addressId', $_POST['addressId']);
        $Address->__set('district', $_POST['district']);
        $Address->__set('address', $_POST['address']);
        $Address->__set('uf', $_POST['uf']);
        $Address->__set('county', $_POST['county']);
        $Address->__set('zipCode', $Tool->formatElement($_POST['zipCode']));

        $Telephone->__set('telephoneId', $_POST['telephoneId']);
        $Telephone->__set('telephoneNumber', $Tool->formatElement($_POST['telephoneNumber']));

        $Teacher->__set('email', $_POST['email']);
        $Teacher->__set('accessCode', $_POST['accessCode']);
        $Teacher->__set('fk_id_blood_type', $_POST['bloodType']);
        $Teacher->__set('fk_id_pcd', $_POST['pcd']);
        $Teacher->__set('teacherId', $_SESSION['Teacher']['id']);

        $Telephone->update();
        $Address->update();
        $Teacher->updatePath();
    }


    public function studentBasedFinalAverage()
    {

        $Teacher = Container::getModel('Teacher\\Teacher');

        if (!isset($_SESSION)) session_start();

        $Teacher->__set('teacherId', $_SESSION['Teacher']['id']);

        echo json_encode($Teacher->studentBasedFinalAverage());
    }


    /**
     * Chamada rápida: todos começam presentes e o professor marca só quem faltou
     */
    public function attendance()
    {

        $Attendance = Container::getModel('TeacherStudent\\Attendance');
        $Settings = Container::getModel('Admin\\Settings');

        if (!isset($_SESSION)) session_start();

        $this->view->disciplineClasses = $Attendance->disciplineClassesByTeacher($_SESSION['Teacher']['id']);
        $this->view->openUnits = $this->openUnits($Settings);

        $this->render('attendance', 'TeacherPortalLayout', 'teacherPortal');
    }


    /**
     * JSON com os alunos da turma/disciplina e as faltas já lançadas na unidade
     */
    public function attendanceStudents()
    {

        header('Content-Type: application/json');

        $Attendance = Container::getModel('TeacherStudent\\Attendance');
        $Settings = Container::getModel('Admin\\Settings');

        if (!isset($_SESSION)) session_start();

        $disciplineClassId = (int) ($_GET['disciplineClass'] ?? 0);
        $unity = (int) ($_GET['unity'] ?? 0);
        $date = trim($_GET['date'] ?? '') ?: date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        $openUnits = $this->openUnits($Settings);

        $disciplineClass = $Attendance->findForTeacher($disciplineClassId, $_SESSION['Teacher']['id']);

        if (!$disciplineClass) {
            http_response_code(403);
            echo json_encode(['error' => 'sem-permissao']);
            return;
        }

        if (!in_array($unity, $openUnits, true)) $unity = max($openUnits);

        $MedicalCertificate = Container::getModel('TeacherStudent\\MedicalCertificate');
        $activeCertificates = $MedicalCertificate->getActiveCertificatesForClassAndDate($disciplineClass->class_id, $date);

        $students = array_map(function ($s) use ($activeCertificates) {
            $cert = $activeCertificates[(int) $s->enrollment_id] ?? null;
            return [
                'id' => (int) $s->enrollment_id,
                'name' => $s->name,
                'photo' => $s->photo,
                'totalLack' => (int) $s->total_lack,
                'hasCertificate' => $cert !== null,
                'certificateReason' => $cert ? $cert->motivo : null,
                'certificateDoctor' => $cert ? $cert->medico_nome : null,
                'certificateDates' => $cert ? ($cert->data_inicio_fmt . ' até ' . $cert->data_fim_fmt) : null,
            ];
        }, $Attendance->students($disciplineClassId, $unity));

        $doneAtDate = $Attendance->doneAtDate($disciplineClassId, $date);

        echo json_encode([
            'unity' => $unity,
            'date' => $date,
            'doneTodayAt' => $doneAtDate,
            'students' => $students,
        ]);
    }


    /**
     * Salva a chamada (POST JSON: disciplineClass, unity, date, lessons, absent[], disciplineClasses[]?)
     *
     * disciplineClasses: nos Anos Iniciais, o polivalente marca em quais disciplinas da mesma turma a chamada vale.
     */
    public function attendanceSave()
    {

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'metodo-invalido']);
            return;
        }

        $Attendance = Container::getModel('TeacherStudent\\Attendance');
        $Settings = Container::getModel('Admin\\Settings');

        if (!isset($_SESSION)) session_start();

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $teacherId = (int) $_SESSION['Teacher']['id'];
        $disciplineClassId = (int) ($input['disciplineClass'] ?? 0);
        $unity = (int) ($input['unity'] ?? 0);
        $lessons = (int) ($input['lessons'] ?? 1);
        $date = trim($input['date'] ?? '') ?: date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date > date('Y-m-d')) {
            $date = date('Y-m-d');
        }

        $absent = array_values(array_unique(array_map('intval', (array) ($input['absent'] ?? []))));

        $targets = $this->editableTargets($Attendance, $teacherId, $disciplineClassId, $input['disciplineClasses'] ?? [], 'chamada');
        if ($targets === null) return;

        $disciplineClass = $targets[0];

        if (!in_array($unity, $this->openUnits($Settings), true)) {
            http_response_code(422);
            echo json_encode(['error' => 'unidade-fechada']);
            return;
        }

        if ($lessons < 1 || $lessons > 6) {
            http_response_code(422);
            echo json_encode(['error' => 'quantidade-aulas-invalida']);
            return;
        }

        // só aceita matrículas que são realmente da turma
        $enrolled = array_map(function ($s) { return (int) $s->enrollment_id; }, $Attendance->students($disciplineClassId, $unity));

        if (array_diff($absent, $enrolled)) {
            http_response_code(422);
            echo json_encode(['error' => 'aluno-fora-da-turma']);
            return;
        }

        $db = \App\Connection::getDb();

        try {
            $db->beginTransaction();
            foreach ($targets as $target) {
                $saved = $Attendance->save($target->discipline_class_id, $unity, $teacherId, $lessons, $absent, count($enrolled) - count($absent), $date);
                if ((int) $target->discipline_class_id === $disciplineClassId) $id = $saved;
            }
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('Chamada: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'falha-ao-salvar']);
            return;
        }

        echo json_encode([
            'id' => $id,
            'present' => count($enrolled) - count($absent),
            'absent' => count($absent),
            'lessons' => $lessons,
            'disciplines' => array_map(function ($t) { return $t->discipline_name; }, $targets),
            'unity' => $unity,
            'date' => $date,
            'formattedDate' => date('d/m/Y', strtotime($date)),
            'savedAt' => date('H:i'),
        ]);
    }


    /**
     * Turmas/disciplinas em que o lançamento vale (a principal primeiro). Todas precisam ser editáveis pelo professor;
     * mais de uma só nos Anos Iniciais e na mesma turma. Se negar, responde 403, audita e retorna null.
     *
     * @return array|null
     */
    private function editableTargets($Attendance, $teacherId, $disciplineClassId, $extraIds, $entity)
    {

        $ids = array_values(array_unique(array_merge([(int) $disciplineClassId], array_map('intval', (array) $extraIds))));

        $main = $Attendance->findForTeacher($disciplineClassId, $teacherId);
        $targets = [];

        foreach ($ids as $id) {
            $row = $Attendance->findForTeacher($id, $teacherId);
            if (!$row || !$main || $row->class_id !== $main->class_id) {
                $targets = null;
                break;
            }
            $targets[] = $row;
        }

        if (!$main || $targets === null || (count($targets) > 1 && $main->stage !== 'iniciais')) {
            Audit::log(\App\Connection::getDb(), 'NEGADO', $entity, $disciplineClassId, ['disciplineClasses' => $ids]);
            http_response_code(403);
            echo json_encode(['error' => 'sem-permissao']);
            return null;
        }

        return $targets;
    }


    /**
     * Diário de aula: habilidade BNCC, observação, atividades e exceções
     */
    public function lessons()
    {

        $Attendance = Container::getModel('TeacherStudent\\Attendance');
        $Lesson = Container::getModel('TeacherStudent\\Lesson');

        if (!isset($_SESSION)) session_start();

        $classes = $Attendance->disciplineClassesByTeacher($_SESSION['Teacher']['id']);

        $this->view->disciplineClasses = $classes;
        $this->view->activities = \App\Models\TeacherStudent\Lesson::ACTIVITIES;
        $this->view->teachers = $Lesson->teachers();
        $this->view->classDisciplines = $Lesson->classDisciplines(array_unique(array_map(function ($c) { return $c->class_id; }, $classes)));
        $this->view->recentLessons = $Lesson->recent(array_map(function ($c) { return $c->discipline_class_id; }, $classes));

        $this->render('lessons', 'TeacherPortalLayout', 'teacherPortal');
    }


    /**
     * Salva a aula (POST JSON: disciplineClass, disciplineClasses[]?, date, bnccCode, bnccSkill, notes, activities[],
     * substitute?, taughtDisciplineClass?, reason?)
     */
    public function lessonSave()
    {

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'metodo-invalido']);
            return;
        }

        $Attendance = Container::getModel('TeacherStudent\\Attendance');
        $Lesson = Container::getModel('TeacherStudent\\Lesson');

        if (!isset($_SESSION)) session_start();

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $teacherId = (int) $_SESSION['Teacher']['id'];

        $targets = $this->editableTargets($Attendance, $teacherId, (int) ($input['disciplineClass'] ?? 0), $input['disciplineClasses'] ?? [], 'aula');
        if ($targets === null) return;

        $fail = function ($error) {
            http_response_code(422);
            echo json_encode(['error' => $error]);
        };

        $date = trim($input['date'] ?? '') ?: date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date > date('Y-m-d')) return $fail('data-invalida');

        $bnccCode = strtoupper(preg_replace('/\s+/', '', (string) ($input['bnccCode'] ?? '')));
        if ($bnccCode !== '' && !preg_match(\App\Models\TeacherStudent\Lesson::BNCC_CODE, $bnccCode)) return $fail('codigo-bncc-invalido');

        $bnccSkill = trim((string) ($input['bnccSkill'] ?? ''));
        $notes = trim((string) ($input['notes'] ?? ''));
        if (mb_strlen($bnccSkill) > 1000 || mb_strlen($notes) > 2000) return $fail('texto-muito-longo');

        $activities = array_values(array_unique(array_filter((array) ($input['activities'] ?? []), 'is_string')));
        if (array_diff($activities, array_keys(\App\Models\TeacherStudent\Lesson::ACTIVITIES))) return $fail('atividade-invalida');

        if ($bnccCode === '' && $bnccSkill === '' && $notes === '' && !$activities) return $fail('aula-vazia');

        // exceções: substituto e/ou matéria trocada, sempre com motivo
        $substituteId = (int) ($input['substitute'] ?? 0);
        $taughtId = (int) ($input['taughtDisciplineClass'] ?? 0);
        $reason = trim((string) ($input['reason'] ?? ''));

        if ($substituteId && ($substituteId === $teacherId || !$Lesson->teacherExists($substituteId))) return $fail('substituto-invalido');

        if ($taughtId) {
            $taught = $Lesson->disciplineClass($taughtId);
            if (!$taught || $taught->class_id !== $targets[0]->class_id || $taughtId === (int) $targets[0]->discipline_class_id) return $fail('materia-invalida');
        }

        if (($substituteId || $taughtId) && ($reason === '' || mb_strlen($reason) > 255)) return $fail('motivo-obrigatorio');

        $db = \App\Connection::getDb();
        $ids = [];

        try {
            $db->beginTransaction();
            foreach ($targets as $target) {
                $ids[] = $Lesson->save([
                    'disciplineClassId' => $target->discipline_class_id,
                    'teacherId' => $teacherId,
                    'attendanceId' => $Lesson->attendanceFor($target->discipline_class_id, $date, $teacherId),
                    'date' => $date,
                    'bnccCode' => $bnccCode,
                    'bnccSkill' => $bnccSkill,
                    'notes' => $notes,
                    'activities' => $activities,
                    'substituteId' => $substituteId,
                    'taughtDisciplineClassId' => $taughtId,
                    'reason' => $reason,
                ]);
            }
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('Diário: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'falha-ao-salvar']);
            return;
        }

        echo json_encode([
            'ids' => $ids,
            'disciplines' => array_map(function ($t) { return $t->discipline_name; }, $targets),
            'formattedDate' => date('d/m/Y', strtotime($date)),
        ]);
    }


    /**
     * Unidades abertas para lançamento conforme a configuração (1 = só a 1ª, 2 = 1ª e 2ª, 3 = todas)
     *
     * @return int[]
     */
    private function openUnits($Settings)
    {

        $current = $Settings->unitControlCurrent();
        $max = isset($current[0]) ? max(1, min(3, (int) $current[0]->option_value)) : 1;

        return range(1, $max);
    }


    /**
     * Quadro de horários semanal do professor
     */
    public function schedule()
    {

        $ClassSchedule = Container::getModel('GeneralManagement\\ClassSchedule');

        if (!isset($_SESSION)) session_start();

        $teacherId = (int) $_SESSION['Teacher']['id'];
        $schedule = $ClassSchedule->getScheduleByTeacher($teacherId);

        // Agrupa por dia da semana (2=Segunda ... 6=Sexta)
        $scheduleByDay = [];
        for ($d = 2; $d <= 6; $d++) {
            $scheduleByDay[$d] = [];
        }

        foreach ($schedule as $slot) {
            $scheduleByDay[$slot->dia_semana][] = $slot;
        }

        $this->view->scheduleByDay = $scheduleByDay;
        $this->view->totalClasses = count($schedule);
        $this->view->dayNames = \App\Models\GeneralManagement\ClassSchedule::dayNames();
        $this->view->shortDayNames = \App\Models\GeneralManagement\ClassSchedule::shortDayNames();

        $this->render('schedule', 'TeacherPortalLayout', 'teacherPortal');
    }


    public function exit()
    {

        if (!isset($_SESSION)) session_start();

        unset($_SESSION['Teacher']);

        header('Location: /portal-docente' . (\App\Auth\Clerk::enabled() ? '?logout=1' : ''));
    }
}
