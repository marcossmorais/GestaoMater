<?php

namespace App\Controllers\StudentPortal;

use MF\Controller\Action;
use MF\Model\Container;
use App\Tools\Tools;

class StudentPortalController extends Action
{

    use \App\Controllers\DeclarationTrait;

    public function index()
    {

        $this->render("index/login", "SimpleLayout", "studentPortal");
    }


    public function auth()
    {

        // com o Clerk ativo o login por nome + código fica desligado
        if (\App\Auth\Clerk::enabled()) {
            header('Location: /portal-aluno');
            return;
        }

        $Student = Container::getModel('Student\\Student');
        $Tool = new Tools();

        $Student->__set('accessCode', preg_replace('/\D/', '', $_POST['accessCode'] ?? '') ?: '0');
        $Student->__set('name', $_POST['name']);

        $auth = $Student->login();

        if (count($auth) != 1) {

            header('Location: /portal-aluno?error=dados-incorretos');

        } else {

            if (!isset($_SESSION)) session_start();

            $_SESSION['Student'] = [

                'id' => $auth[0]->student_id,
                'name' => $auth[0]->student_name,
                'profilePhoto' => $auth[0]->student_photo,
                'hierarchyFunction' => $auth[0]->hierarchy_function,
                'enrollmentId' => $auth[0]->enrollment_id,
                'class' => $auth[0]->acronym_series . 'ª ' . $auth[0]->ballot . ' - ' . $auth[0]->course . ' - ' . $auth[0]->shift,
                'classId' => $auth[0]->class_id
            ];

            session_cache_expire(400);

            header('Location: /portal-aluno/home');
        }
    }

    public function home()
    {

        $Student = Container::getModel('Student\\Student');
        $StudentEnrollment = Container::getModel('Student\\StudentEnrollment');
        $Note = Container::getModel('TeacherStudent\\Note');
        $Observation = Container::getModel('TeacherStudent\\Observation');
        $Lack = Container::getModel('TeacherStudent\\Lack');
        $DisciplineAverage = Container::getModel('TeacherStudent\\DisciplineAverage');
        $SchoolTerm = Container::getModel('GeneralManagement\\SchoolTerm');
        $Exam = Container::getModel('TeacherStudent\\Exam');
        $ClassDiscipline = Container::getModel('GeneralManagement\\ClassDiscipline');
        $Classe = Container::getModel('GeneralManagement\\Classe');
        $ClasseWarning = Container::getModel('GeneralManagement\\ClasseWarning');
        $Unity = Container::getModel('GeneralManagement\\Unity');

        if (!isset($_SESSION)) session_start();

        $Note->__set('fk_id_student_enrollment', $_SESSION['Student']['enrollmentId']);
        $Observation->__set('fk_id_enrollment', $_SESSION['Student']['enrollmentId']);
        $Lack->__set('fk_id_enrollment', $_SESSION['Student']['enrollmentId']);
        $DisciplineAverage->__set('fk_id_enrollment', $_SESSION['Student']['enrollmentId']);
        $StudentEnrollment->__set('studentEnrollmentId', $_SESSION['Student']['enrollmentId']);
        $Classe->__set('classId', $_SESSION['Student']['classId']);
        $ClassDiscipline->__set("fk_id_class", $_SESSION['Student']['classId']);
        $Exam->__set("fk_id_class", $_SESSION['Student']['classId']);
        $Unity->__set('unityId', 0);

        $this->view->listWarning = $ClasseWarning->readByIdClasse($Classe);
        $this->view->listNote = $Note->readByIdStudent();
        $this->view->listObservation = $Observation->readByIdStudent();



        $Occurrence = Container::getModel('TeacherStudent\\Occurrence');

        $this->view->listOccurrences = $Occurrence->getForPortal($_SESSION['Student']['enrollmentId']);

        $this->view->lackList = $Lack->readByIdStudent();
        $this->view->disciplineAverageList = $DisciplineAverage->readByIdStudent();
        $this->view->schoolTermActive = $SchoolTerm->active();
        $this->view->studentDataGeneral = $StudentEnrollment->dataGeneral('<> 0');
        $this->view->listExam = $Exam->readExamByIdClass($Classe);
        $Student->__set('fk_id_enrollmentId', 0);
        $this->view->listStudent = $Student->readStudentsLinkedClass($Classe);
        $this->view->typeStudentList = "class";
        $this->view->typeTeacherList = 'class';
        $this->view->listTeacher = $ClassDiscipline->listTeachersClass();
        $this->view->linkedDisciplines = $ClassDiscipline->classLinkedSubjects();
        $this->view->unity = ( $this->view->schoolTermActive[0]->option_value == $this->view->studentDataGeneral[0]->school_term_id ? $Unity->readOpenUnits() : $Unity->searchSpecificUniy() );

        $ClassSchedule = Container::getModel('GeneralManagement\\ClassSchedule');
        $this->view->classSchedule = $ClassSchedule->getScheduleByClass($_SESSION['Student']['classId']);
        $this->view->dayNames = \App\Models\GeneralManagement\ClassSchedule::dayNames();
        $this->view->shortDayNames = \App\Models\GeneralManagement\ClassSchedule::shortDayNames();

        $this->view->board = Container::getModel('TeacherStudent\ClassBoard')->forClass($_SESSION['Student']['classId'], $_SESSION['Student']['enrollmentId']);
        $this->view->guides = Container::getModel('TeacherStudent\Gradebook')->guidesForClass($_SESSION['Student']['classId']);

        $Performance = Container::getModel('Student\StudentPerformance');
        $this->view->performanceRule = $Performance->rule();
        $this->view->performance = $Performance->byDiscipline($_SESSION['Student']['enrollmentId'], $_SESSION['Student']['classId']);
        $this->view->agenda = $Performance->agenda($_SESSION['Student']['classId']);

        $this->render("pages/home", "SimpleLayout", "studentPortal");
    }




    public function settings()
    {


        $Student = Container::getModel('Student\\Student');
        $StudentEnrollment = Container::getModel('Student\\StudentEnrollment');

        if (!isset($_SESSION)) session_start();

        $Student->__set('studentId', $_SESSION['Student']['id']);
        $StudentEnrollment->__set('studentEnrollmentId', $_SESSION['Student']['enrollmentId']);
        $StudentEnrollment->__set('fk_id_student', $_SESSION['Student']['id']);

        $this->view->studentProfile = $Student->dataGeneral($StudentEnrollment);
        $this->view->availableSex = $Student->availableSex();
        $this->view->pcd = $Student->pcd();
        $this->view->bloodType = $Student->availablebloodType();
        $this->view->studentEnrollment = $StudentEnrollment->allRegistrations();

        $this->render('pages/settings', 'SimpleLayout', 'studentPortal');
    }


    public function updateSettings()
    {

        $StudentEnrollment = Container::getModel('Student\\StudentEnrollment');
        $Classe = Container::getModel('GeneralManagement\\Classe');

        if (!isset($_SESSION)) session_start();

        $_SESSION['Student']['enrollmentId'] = $_POST['enrollmentId'];
        $StudentEnrollment->__set('studentEnrollmentId',  $_SESSION['Student']['enrollmentId']);
        $class = $StudentEnrollment->dataGeneral('<> 0');

        $_SESSION['Student']['classId'] = $class[0]->class_id;
        $Classe->__set('classId', $class[0]->class_id);
        $class0 = $Classe->dataGeneral("<> 0");

        $_SESSION['Student']['class'] = $class0[0]->series_id . 'ª ' . $class0[0]->ballot . ' - ' . $class0[0]->course_name . ' - ' . $class0[0]->shift;
    }

    public function updateStudentPortal()
    {

        if (!isset($_SESSION)) session_start();

        $Address =  Container::getModel('People\\Address');
        $Telephone = Container::getModel('People\Telephone');
        $Student = Container::getModel('Student\\Student');

        $Tool = new Tools();

        $Address->__set('addressId', $_POST['addressId']);
        $Address->__set('district', $_POST['district']);
        $Address->__set('address', $_POST['address']);
        $Address->__set('uf', $_POST['uf']);
        $Address->__set('county', $_POST['county']);
        $Address->__set('zipCode', $Tool->formatElement($_POST['zipCode']));

        $Telephone->__set('telephoneId', $_POST['telephoneId']);
        $Telephone->__set('telephoneNumber', $Tool->formatElement($_POST['telephoneNumber']));

        $Student->__set('accessCode', $_POST['accessCode']);
        $Student->__set('email', $_POST['email']);
        $Student->__set('fk_id_blood_type', $_POST['bloodType']);
        $Student->__set('fk_id_pcd', $_POST['pcd']);
        $Student->__set('studentId', $_SESSION['Student']['id']);

        $Telephone->update();
        $Address->update();
        $Student->updatePath();

    }


    public function exit()
    {

        if (!isset($_SESSION)) session_start();

        unset($_SESSION['Student']);

        header('Location: /portal-aluno' . (\App\Auth\Clerk::enabled() ? '?logout=1' : ''));
    }

    public function declarationEnrollment()
    {
        $this->loadDeclarationData('matricula');
    }

    public function declarationAttendance()
    {
        $this->loadDeclarationData('frequencia');
    }

    private function loadDeclarationData($type = 'matricula')
    {
        if (!isset($_SESSION)) session_start();

        $this->renderDeclaration((int) ($_SESSION['Student']['enrollmentId'] ?? 0), $type, 'aluno', $_SESSION['Student']['id'] ?? null);
    }

    /**
     * Boletim para imprimir, com código de validação e QR (só do próprio aluno)
     */
    public function bulletinDocument()
    {
        if (!isset($_SESSION)) session_start();

        $enrollmentId = (int) $_SESSION['Student']['enrollmentId'];

        $StudentEnrollment = Container::getModel('Student\StudentEnrollment');
        $StudentEnrollment->__set('studentEnrollmentId', $enrollmentId);
        $enrollment = $StudentEnrollment->dataGeneral()[0] ?? null;

        if (!$enrollment) {
            http_response_code(404);
            echo 'Matrícula não encontrada.';
            return;
        }

        $Performance = Container::getModel('Student\StudentPerformance');
        $rule = $Performance->rule();
        $rows = $Performance->byDiscipline($enrollmentId, $enrollment->class_id);

        $content = ['matricula' => $enrollmentId, 'nome' => $enrollment->name, 'ano' => $enrollment->school_year, 'regra' => $rule, 'disciplinas' => array_map(function ($d) {
            return [$d['discipline'], array_column($d['units'], 'grade'), $d['absences'], $d['excused'], $d['simulation']['status']];
        }, $rows)];

        $code = Container::getModel('Student\IssuedDocument')->issue('boletim', $enrollmentId, $enrollment->name, $content, 'aluno', $_SESSION['Student']['id'] ?? null);
        $url = rtrim(getenv('APP_URL') ?: '', '/') . '/validar?codigo=' . $code;

        \App\Tools\Audit::log(\App\Connection::getDb(), 'DOWNLOAD', 'boletim', $enrollmentId, ['codigo' => $code]);

        $this->view->enrollment = $enrollment;
        $this->view->rule = $rule;
        $this->view->rows = $rows;
        $this->view->authCode = $code;
        $this->view->validationUrl = $url;
        $this->view->qrSvg = \App\Tools\DocumentSigner::qrSvg($url);

        require __DIR__ . '/../../Views/studentPortal/bulletinDocument.php';
    }

    /**
     * Confirma a leitura de um aviso do mural (só da turma do aluno)
     */
    public function boardRead()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION)) session_start();

        $ok = Container::getModel('TeacherStudent\ClassBoard')->markRead(
            (int) ($_POST['id'] ?? 0),
            $_SESSION['Student']['enrollmentId'] ?? 0,
            $_SESSION['Student']['classId'] ?? 0
        );

        if (!$ok) {
            http_response_code(404);
            echo json_encode(['error' => 'aviso-nao-encontrado']);
            return;
        }

        echo json_encode(['status' => 'success']);
    }

    public function acknowledgeOccurrence()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION)) session_start();
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) {
            http_response_code(422);
            echo json_encode(['error' => 'ID inválido']);
            return;
        }
        $Occurrence = Container::getModel('TeacherStudent\\Occurrence');
        if (!$Occurrence->acknowledge($id, $_SESSION['Student']['enrollmentId'] ?? 0)) {
            http_response_code(404);
            echo json_encode(['error' => 'Ocorrência não encontrada']);
            return;
        }
        echo json_encode(['status' => 'success']);
    }
}