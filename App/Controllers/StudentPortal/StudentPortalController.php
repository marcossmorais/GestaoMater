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

            \App\Auth\PortalAccount::open('aluno', [
                'id' => $auth[0]->student_id,
                'name' => $auth[0]->student_name,
                'profilePhoto' => $auth[0]->student_photo,
                'hierarchyFunction' => $auth[0]->hierarchy_function,
                'enrollmentId' => $auth[0]->enrollment_id,
                'class' => $auth[0]->acronym_series . 'ª ' . $auth[0]->ballot . ' - ' . $auth[0]->course . ' - ' . $auth[0]->shift,
                'classId' => $auth[0]->class_id
            ]);

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
        // aba Turma: colegas só com nome e foto; professores com e-mail. A comparação com a média fica em Desempenho
        $teachers = [];
        foreach ($ClassDiscipline->listTeachersClass() as $t) {
            $teachers[(int) $t->id] = $teachers[(int) $t->id] ?? (object) ['name' => $t->name, 'photo' => $t->profile_photo, 'email' => $t->email, 'disciplines' => []];
            $teachers[(int) $t->id]->disciplines[] = $t->discipline_name;
        }
        $this->view->listTeacher = array_values($teachers);
        $this->view->classmates = Container::getModel('TeacherStudent\\StudentFile')->classmates($_SESSION['Student']['classId'], $_SESSION['Student']['enrollmentId']);
        $this->view->classAverages = Container::getModel('TeacherStudent\\StudentFile')->classAverages($_SESSION['Student']['classId'], Container::getModel('Student\\StudentPerformance')->rule(), 5);
        $this->view->linkedDisciplines = $ClassDiscipline->classLinkedSubjects();
        $this->view->unity = ( $this->view->schoolTermActive[0]->option_value == $this->view->studentDataGeneral[0]->school_term_id ? $Unity->readOpenUnits() : $Unity->searchSpecificUniy() );

        $ClassSchedule = Container::getModel('GeneralManagement\\ClassSchedule');
        $this->view->classSchedule = $ClassSchedule->getScheduleByClass($_SESSION['Student']['classId']);
        // Fund. 1: os horários sem especialista são do titular
        $this->view->homeroomTeacher = $ClassSchedule->homeroomTeacherName($_SESSION['Student']['classId']);
        $this->view->scheduleSlots = $this->view->homeroomTeacher ? $ClassSchedule->slotsForClass($_SESSION['Student']['classId']) : [];
        $this->view->dayNames = \App\Models\GeneralManagement\ClassSchedule::dayNames();
        $this->view->shortDayNames = \App\Models\GeneralManagement\ClassSchedule::shortDayNames();

        $this->view->board = Container::getModel('TeacherStudent\ClassBoard')->forClass($_SESSION['Student']['classId'], $_SESSION['Student']['enrollmentId']);
        $this->view->generalBoard = Container::getModel('TeacherStudent\GeneralBoard')->forClass($_SESSION['Student']['classId'], $_SESSION['Student']['enrollmentId']);
        $this->view->examSheet = Container::getModel('TeacherStudent\Gradebook')->examSheetForClass($_SESSION['Student']['classId']);

        $Performance = Container::getModel('Student\StudentPerformance');
        $this->view->performanceRule = $Performance->rule();
        $this->view->performance = $Performance->byDiscipline($_SESSION['Student']['enrollmentId'], $_SESSION['Student']['classId']);

        // aba Frequência
        $StudentAttendance = Container::getModel('Student\\StudentAttendance');
        $this->view->submissions = $StudentAttendance->submissions($_SESSION['Student']['enrollmentId']);

        // Fund. 1: frequência em dias (chamada diária); demais etapas: por matéria
        $Daily = Container::getModel('TeacherStudent\\DailyAttendance');
        $this->view->dailyAttendance = $Daily->isDaily($_SESSION['Student']['classId'])
            ? $Daily->summary($_SESSION['Student']['enrollmentId'], $_SESSION['Student']['classId'])
            : null;
        $this->view->missedDays = $this->view->dailyAttendance !== null
            ? $Daily->missedDays($_SESSION['Student']['enrollmentId'])
            : $StudentAttendance->missedDays($_SESSION['Student']['enrollmentId']);

        $this->render("pages/home", "SimpleLayout", "studentPortal");
    }




    /**
     * Notas de cada avaliação do próprio aluno, para os simuladores do bimestre
     */
    private function studentExams(array $rows)
    {
        $studentExams = [];
        foreach (Container::getModel('TeacherStudent\\StudentFile')->exams($_SESSION['Student']['enrollmentId'], array_column($rows, 'id')) as $e) {
            if ($e->recovery) continue;
            $studentExams[(int) $e->discipline_class_id][(int) $e->unity][] = $e->grade === null ? null : round((float) $e->grade, 2);
        }

        return $studentExams;
    }


    /**
     * Atestado ou justificativa do próprio aluno: fica pendente até a coordenação analisar
     */
    public function certificateSend()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION)) session_start();

        $fail = function ($error, $code = 422) {
            http_response_code($code);
            echo json_encode(['error' => $error]);
        };

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return $fail('metodo-invalido', 405);

        $Attendance = '\\App\\Models\\Student\\StudentAttendance';
        $enrollmentId = (int) $_SESSION['Student']['enrollmentId'];
        $type = (string) ($_POST['type'] ?? '');
        $start = trim((string) ($_POST['start'] ?? ''));
        $end = trim((string) ($_POST['end'] ?? '')) ?: $start;
        $reason = trim((string) ($_POST['reason'] ?? ''));
        $isDate = function ($d) { return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && \DateTime::createFromFormat('Y-m-d', $d) !== false; };

        if (!isset($Attendance::TYPES[$type])) return $fail('tipo-invalido');
        if (!$isDate($start) || !$isDate($end) || $start > $end || $end > date('Y-m-d')) return $fail('data-invalida');
        if ((new \DateTime($start))->diff(new \DateTime($end))->days >= $Attendance::MAX_DAYS) return $fail('periodo-longo');
        if ($reason === '' || mb_strlen($reason) > 255) return $fail('motivo-obrigatorio');

        $upload = $_FILES['file'] ?? null;
        $hasFile = $upload && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if ($type === 'atestado' && !$hasFile) return $fail('arquivo-obrigatorio');

        $StudentAttendance = Container::getModel('Student\\StudentAttendance');
        if ($StudentAttendance->hasPendingOverlap($enrollmentId, $start, $end)) return $fail('ja-enviado', 409);

        $fileName = null;
        if ($hasFile) {
            if (in_array($upload['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $upload['size'] > $Attendance::MAX_BYTES) return $fail('arquivo-grande');
            if ($upload['error'] !== UPLOAD_ERR_OK) return $fail('arquivo-invalido');

            // o tipo vem do conteúdo, não do nome; o nome no disco é aleatório
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
            if (!isset($Attendance::MIME_TYPES[$mime])) return $fail('arquivo-invalido');

            $dir = $Attendance::storageDir();
            if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
                error_log('atestado: não foi possível criar o armazenamento');
                return $fail('falha-ao-salvar', 500);
            }

            $fileName = bin2hex(random_bytes(16)) . '.' . $Attendance::MIME_TYPES[$mime];
            if (!move_uploaded_file($upload['tmp_name'], $dir . $fileName)) {
                error_log('atestado: não foi possível gravar o anexo');
                return $fail('falha-ao-salvar', 500);
            }
        }

        $id = $StudentAttendance->submit([
            'enrollmentId' => $enrollmentId,
            'studentId' => (int) $_SESSION['Student']['id'],
            'type' => $type,
            'start' => $start,
            'end' => $end,
            'reason' => $reason,
            'file' => $fileName,
        ]);

        echo json_encode(['id' => $id]);
    }


    public function certificateFile()
    {
        if (!isset($_SESSION)) session_start();

        $file = Container::getModel('Student\\StudentAttendance')->privateFile((int) ($_GET['id'] ?? 0), (int) $_SESSION['Student']['enrollmentId']);

        if (!$file) {
            http_response_code(404);
            echo 'Arquivo não encontrado.';
            return;
        }

        \App\Models\Student\StudentAttendance::sendFile($file);
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

        // só troca para uma matrícula do próprio aluno (antes aceitava qualquer id vindo do navegador)
        $StudentEnrollment->__set('fk_id_student', $_SESSION['Student']['id']);
        $own = array_map(function ($r) { return (int) $r->enrollment_id; }, $StudentEnrollment->allRegistrations());
        if (!in_array((int) ($_POST['enrollmentId'] ?? 0), $own, true)) {
            \App\Tools\Audit::log(\App\Connection::getDb(), 'NEGADO', 'matricula', $_POST['enrollmentId'] ?? null, 'troca de matrícula do aluno');
            http_response_code(403);
            return;
        }

        $_SESSION['Student']['enrollmentId'] = (int) $_POST['enrollmentId'];
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

        // endereço e telefone são sempre os do próprio aluno (ignora ids vindos do navegador)
        $StudentEnrollment = Container::getModel('Student\\StudentEnrollment');
        $StudentEnrollment->__set('studentEnrollmentId', $_SESSION['Student']['enrollmentId']);
        $StudentEnrollment->__set('fk_id_student', $_SESSION['Student']['id']);
        $Student->__set('studentId', $_SESSION['Student']['id']);
        $own = $Student->dataGeneral($StudentEnrollment)[0] ?? null;
        if (!$own) {
            http_response_code(404);
            return;
        }

        $Address->__set('addressId', $own->address_id);
        $Address->__set('district', $_POST['district']);
        $Address->__set('address', $_POST['address']);
        $Address->__set('uf', $_POST['uf']);
        $Address->__set('county', $_POST['county']);
        $Address->__set('zipCode', $Tool->formatElement($_POST['zipCode']));

        $Telephone->__set('telephoneId', $own->telephone_id);
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
     * Boletim na tela (aba Boletim): bimestres de 0 a 10 com recuperação, média do ano e faltas — só do próprio aluno
     */
    public function bulletin()
    {
        if (!isset($_SESSION)) session_start();

        $Performance = Container::getModel('Student\StudentPerformance');
        $this->view->rule = $Performance->rule();
        $this->view->rows = $Performance->byDiscipline((int) $_SESSION['Student']['enrollmentId'], (int) $_SESSION['Student']['classId']);
        $this->view->studentExams = $this->studentExams($this->view->rows);
        $this->view->dailyAttendance = $this->dailyAttendance((int) $_SESSION['Student']['enrollmentId'], (int) $_SESSION['Student']['classId']);

        // cada avaliação com tipo e data, para o detalhe por matéria
        $exams = [];
        foreach (Container::getModel('TeacherStudent\\StudentFile')->exams((int) $_SESSION['Student']['enrollmentId'], array_column($this->view->rows, 'id')) as $e) {
            $exams[(int) $e->discipline_class_id][(int) $e->unity][] = $e;
        }
        $this->view->exams = $exams;

        $this->render('components/bulletin', 'SimpleLayout', 'studentPortal');
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
        $daily = $this->dailyAttendance($enrollmentId, (int) $enrollment->class_id);

        $content = ['matricula' => $enrollmentId, 'nome' => $enrollment->name, 'ano' => $enrollment->school_year, 'regra' => $rule, 'disciplinas' => array_map(function ($d) {
            return [$d['discipline'], array_column($d['units'], 'grade'), $d['absences'], $d['excused'], $d['simulation']['status']];
        }, $rows)];
        // Fund. 1: a frequência impressa é a do dia (entra no que o código de validação garante)
        if ($daily !== null) $content['frequencia_dias'] = $daily;

        $code = Container::getModel('Student\IssuedDocument')->issue('boletim', $enrollmentId, $enrollment->name, $content, 'aluno', $_SESSION['Student']['id'] ?? null);
        $url = rtrim(getenv('APP_URL') ?: '', '/') . '/validar?codigo=' . $code;

        \App\Tools\Audit::log(\App\Connection::getDb(), 'DOWNLOAD', 'boletim', $enrollmentId, ['codigo' => $code]);

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
     * Fund. 1: frequência em dias do aluno (chamada diária), ou null nas demais etapas
     */
    private function dailyAttendance($enrollmentId, $classId)
    {
        $Daily = Container::getModel('TeacherStudent\\DailyAttendance');
        return $Daily->isDaily($classId) ? $Daily->summary($enrollmentId, $classId) : null;
    }

    /**
     * Roteiro de provas em folha A4, de um bimestre, só da turma do aluno
     */
    public function examSheetDocument()
    {
        if (!isset($_SESSION)) session_start();

        $rule = Container::getModel('Student\StudentPerformance')->rule();

        $this->view->unit = max(1, min((int) $rule['units'], (int) ($_GET['bimestre'] ?? 1)));
        $this->view->examFilter = (string) ($_GET['avaliacao'] ?? '');
        $this->view->subjectFilter = (string) ($_GET['materia'] ?? '');
        $this->view->examSheet = Container::getModel('TeacherStudent\Gradebook')->examSheetForClass($_SESSION['Student']['classId']);

        require __DIR__ . '/../../Views/studentPortal/examSheetDocument.php';
    }

    /**
     * Quadro de horários em folha A4, só da turma do aluno
     */
    public function scheduleDocument()
    {
        if (!isset($_SESSION)) session_start();

        $ClassSchedule = Container::getModel('GeneralManagement\ClassSchedule');
        $this->view->classSchedule = $ClassSchedule->getScheduleByClass($_SESSION['Student']['classId']);
        // Fund. 1: os horários sem especialista são do titular
        $this->view->homeroomTeacher = $ClassSchedule->homeroomTeacherName($_SESSION['Student']['classId']);
        $this->view->scheduleSlots = $this->view->homeroomTeacher ? $ClassSchedule->slotsForClass($_SESSION['Student']['classId']) : [];
        $this->view->dayNames = \App\Models\GeneralManagement\ClassSchedule::dayNames();

        require __DIR__ . '/../../Views/studentPortal/scheduleDocument.php';
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

    /**
     * Confirma a leitura de um aviso geral (da escola ou da turma do aluno)
     */
    public function generalBoardRead()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION)) session_start();

        $ok = Container::getModel('TeacherStudent\GeneralBoard')->markRead(
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