<?php

namespace App\Controllers\TeacherPortal;

use MF\Controller\Action;
use MF\Model\Container;
use App\Tools\Tools;

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

        $this->render('home', 'TeacherPortalLayout', 'TeacherPortal');
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

        $this->render('management/pages/listClass', 'TeacherPortalLayout', 'TeacherPortal');
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

        $this->render('management/components/classListing', 'SimpleLayout', 'TeacherPortal');
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

        $this->render('settings', 'SimpleLayout', 'TeacherPortal');
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
            http_response_code(404);
            echo json_encode(['error' => 'turma-nao-encontrada']);
            return;
        }

        if (!in_array($unity, $openUnits, true)) $unity = max($openUnits);

        $students = array_map(function ($s) {
            return [
                'id' => (int) $s->enrollment_id,
                'name' => $s->name,
                'photo' => $s->photo,
                'totalLack' => (int) $s->total_lack,
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
     * Salva a chamada (POST JSON: disciplineClass, unity, date, lessons, absent[])
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

        $disciplineClass = $Attendance->findForTeacher($disciplineClassId, $teacherId);

        if (!$disciplineClass) {
            http_response_code(404);
            echo json_encode(['error' => 'turma-nao-encontrada']);
            return;
        }

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

        try {
            $id = $Attendance->save($disciplineClassId, $unity, $teacherId, $lessons, $absent, count($enrolled) - count($absent), $date);
        } catch (\Throwable $e) {
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
            'unity' => $unity,
            'date' => $date,
            'formattedDate' => date('d/m/Y', strtotime($date)),
            'savedAt' => date('H:i'),
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


    public function exit()
    {

        if (!isset($_SESSION)) session_start();

        unset($_SESSION['Teacher']);

        header('Location: /portal-docente' . (\App\Auth\Clerk::enabled() ? '?logout=1' : ''));
    }
}
