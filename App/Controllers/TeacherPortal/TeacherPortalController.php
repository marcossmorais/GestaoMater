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

            \App\Auth\PortalAccount::logFailure('docente', 'dados-incorretos');
            header('Location: /portal-docente?error=dados-incorretos');
        } else {

            if ($account_status[0]->account_status == 1) {

                \App\Auth\PortalAccount::open('docente', [
                    'id' => $auth[0]->teacher_id,
                    'name' => $auth[0]->teacher_name,
                    'profilePhoto' => $auth[0]->teacher_photo,
                    'hierarchyFunction' => $auth[0]->hierarchy_function
                ]);

                session_cache_expire(60);

                header('Location: /portal-docente/home');
            } else {

                \App\Auth\PortalAccount::logFailure('docente', 'conta-desativada');
                header("Location: /portal-docente?error=conta-desativada");
            }
        }
    }


    public function home()
    {

        $Settings = Container::getModel('Admin\\Settings');
        $SchoolTerm = Container::getModel('GeneralManagement\\SchoolTerm');
        $Attendance = Container::getModel('TeacherStudent\\Attendance');
        $Dashboard = Container::getModel('Teacher\\TeacherDashboard');

        if (!isset($_SESSION)) session_start();

        $teacherId = (int) $_SESSION['Teacher']['id'];
        $openUnits = $this->openUnits($Settings);
        $disciplineClasses = $Attendance->disciplineClassesByTeacher($teacherId);
        $rule = Container::getModel('Student\\StudentPerformance')->rule();

        // aulas de hoje; sem aula hoje (fim de semana, folga), mostra o próximo dia com aulas
        $today = (new \DateTime('now', new \DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
        $lessonsDate = $today;
        $lessons = $Dashboard->lessonsOn($teacherId, $today);
        for ($i = 1; !$lessons && $i <= 7; $i++) {
            $lessonsDate = date('Y-m-d', strtotime("$today +$i day"));
            $lessons = $Dashboard->lessonsOn($teacherId, $lessonsDate);
        }

        $this->view->SchoolTermActive = $SchoolTerm->active();
        $this->view->openUnits = $openUnits;
        $this->view->disciplineClasses = $disciplineClasses;
        $this->view->today = $today;
        $this->view->lessonsDate = $lessonsDate;
        $this->view->lessons = $lessons;
        $pending = $Dashboard->pending(array_map(function ($c) { return (int) $c->discipline_class_id; }, $disciplineClasses), $openUnits);
        // Fund. 1: dia com chamada e sem diário do dia, nas turmas em que é titular
        $homerooms = [];
        foreach (Container::getModel('TeacherStudent\\DailyAttendance')->homeroomClasses($teacherId) as $h) $homerooms[(int) $h->class_id] = $h->label . ' · ' . $h->course . ' · ' . $h->shift;
        $pending['daily'] = Container::getModel('TeacherStudent\\DailyLog')->missing(array_keys($homerooms));
        $this->view->pending = $pending;
        $this->view->homerooms = $homerooms;
        $this->view->atRisk = $Dashboard->atRisk($disciplineClasses, max($openUnits), $rule);
        $this->view->topAbsences = $Dashboard->topAbsences($disciplineClasses, max($openUnits));
        $this->view->charts = $Dashboard->charts($disciplineClasses, max($openUnits), $rule, $this->view->atRisk);
        $this->view->activity = $Dashboard->activity(array_map(function ($c) { return (int) $c->discipline_class_id; }, $disciplineClasses));
        $this->view->rule = $rule;

        $this->render('home', 'TeacherPortalLayout', 'teacherPortal');
    }


    /**
     * Turmas e chamada são a mesma tela: abre direto na última turma vista ou na aula de agora
     */
    public function teacherClasses()
    {

        if (!isset($_SESSION)) session_start();

        $teacherId = (int) $_SESSION['Teacher']['id'];
        $classes = Container::getModel('TeacherStudent\\Attendance')->disciplineClassesByTeacher($teacherId);
        // Fund. 1: turma do titular ou da substituição de hoje, mesmo sem disciplina dele nela
        $homerooms = Container::getModel('TeacherStudent\\DailyAttendance')->homeroomClasses($teacherId);

        if ($classes || $homerooms) {
            $classIds = array_values(array_unique(array_merge(
                array_map(function ($c) { return (int) $c->class_id; }, $homerooms),
                array_map(function ($c) { return (int) $c->class_id; }, $classes)
            )));
            $classId = (int) ($_SESSION['Teacher']['lastClass'] ?? 0);

            if (!in_array($classId, $classIds, true)) {
                $classId = $classIds[0];
                foreach (Container::getModel('Teacher\\TeacherDashboard')->lessonsOn($teacherId, date('Y-m-d')) as $lesson) {
                    if ($lesson->finish >= date('H:i') && in_array((int) $lesson->class_id, $classIds, true)) {
                        $classId = (int) $lesson->class_id;
                        break;
                    }
                }
            }

            // Turmas abre sempre na chamada
            header('Location: /portal-docente/turma?id=' . $classId . '&ver=chamada');
            return;
        }

        $this->view->teacherClasses = [];

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

        $this->render('settings', 'TeacherPortalLayout', 'teacherPortal');
    }


    public function updateTeacherPortal()
    {

        $Teacher = Container::getModel('Teacher\\Teacher');
        $Address =  Container::getModel('People\\Address');
        $Telephone = Container::getModel('People\Telephone');

        $Tool = new Tools();

        if (!isset($_SESSION)) session_start();

        // endereço e telefone são sempre os do próprio professor (antes vinham do navegador)
        $Teacher->__set('teacherId', $_SESSION['Teacher']['id']);
        $own = $Teacher->dataGeneral()[0] ?? null;
        if (!$own) {
            http_response_code(404);
            return;
        }
        $_POST['addressId'] = $own->address_id;
        $_POST['telephoneId'] = $own->telephone_id;

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
    /**
     * A chamada agora é uma aba da turma; mantém os links antigos (?turma=ID da turma/disciplina)
     */
    public function attendance()
    {

        if (!isset($_SESSION)) session_start();

        $disciplineClass = Container::getModel('TeacherStudent\\Attendance')->findForTeacher((int) ($_GET['turma'] ?? 0), $_SESSION['Teacher']['id']);

        header('Location: ' . ($disciplineClass
            ? '/portal-docente/turma?id=' . (int) $disciplineClass->class_id . '&ver=chamada&disciplina=' . (int) $disciplineClass->discipline_class_id
            : '/portal-docente/turmas?ver=chamada'));
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

        // Fund. 1 conta a frequência por dia: a chamada é a diária, do titular
        if ($disciplineClass->stage === \App\Models\TeacherStudent\DailyAttendance::STAGE) {
            http_response_code(422);
            echo json_encode(['error' => 'chamada-diaria']);
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

        // Fund. 1 conta a frequência por dia: falta por disciplina deixaria a frequência em duas contas
        if ($disciplineClass->stage === \App\Models\TeacherStudent\DailyAttendance::STAGE) {
            http_response_code(422);
            echo json_encode(['error' => 'chamada-diaria']);
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
     * Fund. 1: data válida (YYYY-MM-DD, até hoje), ou hoje
     */
    private function attendanceDate($value)
    {
        $date = trim((string) $value);
        return (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && strtotime($date) && $date <= date('Y-m-d')) ? $date : date('Y-m-d');
    }


    /**
     * Fund. 1: JSON com os alunos da turma, os dias de falta no bimestre e quem já faltou na data (só o titular)
     */
    public function dailyAttendanceStudents()
    {

        header('Content-Type: application/json');

        if (!isset($_SESSION)) session_start();

        $Daily = Container::getModel('TeacherStudent\\DailyAttendance');
        $classId = (int) ($_GET['classId'] ?? 0);
        $date = $this->attendanceDate($_GET['date'] ?? '');
        $openUnits = $this->openUnits(Container::getModel('Admin\\Settings'));
        $unity = (int) ($_GET['unity'] ?? 0);
        if (!in_array($unity, $openUnits, true)) $unity = max($openUnits);

        $class = $Daily->homeroomFor($_SESSION['Teacher']['id'], $classId);
        if (!$class) {
            http_response_code(403);
            echo json_encode(['error' => 'sem-permissao']);
            return;
        }

        // substituto: só nas datas do período
        if (!$Daily->covers($class, $date)) {
            http_response_code(403);
            echo json_encode(['error' => 'fora-da-substituicao', 'from' => $class->substitute_from, 'until' => $class->substitute_until]);
            return;
        }

        $certificates = Container::getModel('TeacherStudent\\MedicalCertificate')->getActiveCertificatesForClassAndDate($classId, $date);

        $students = array_map(function ($s) use ($certificates) {
            $cert = $certificates[(int) $s->enrollment_id] ?? null;
            return [
                'id' => (int) $s->enrollment_id,
                'name' => $s->name,
                'photo' => $s->photo,
                'totalLack' => (int) $s->absent_days,
                'absentOnDate' => (bool) $s->absent_on_date,
                'hasCertificate' => $cert !== null,
                'certificateReason' => $cert ? $cert->motivo : null,
                'certificateDoctor' => $cert ? $cert->medico_nome : null,
                'certificateDates' => $cert ? ($cert->data_inicio_fmt . ' até ' . $cert->data_fim_fmt) : null,
            ];
        }, $Daily->students($classId, $unity, $date));

        echo json_encode([
            'unity' => $unity,
            'date' => $date,
            'doneTodayAt' => $Daily->doneAt($classId, $date),
            'students' => $students,
        ]);
    }


    /**
     * Fund. 1: grava a chamada do dia (POST JSON: classId, unity, date, absent[]). Só o titular; de novo no mesmo dia corrige.
     */
    public function dailyAttendanceSave()
    {

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'metodo-invalido']);
            return;
        }

        if (!isset($_SESSION)) session_start();

        $Daily = Container::getModel('TeacherStudent\\DailyAttendance');
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $teacherId = (int) $_SESSION['Teacher']['id'];
        $classId = (int) ($input['classId'] ?? 0);
        $unity = (int) ($input['unity'] ?? 0);
        $date = $this->attendanceDate($input['date'] ?? '');
        $absent = array_values(array_unique(array_map('intval', (array) ($input['absent'] ?? []))));

        $class = $Daily->homeroomFor($teacherId, $classId);
        if (!$class) {
            Audit::log(\App\Connection::getDb(), 'NEGADO', 'chamada_diaria', $classId, ['data' => $date]);
            http_response_code(403);
            echo json_encode(['error' => 'sem-permissao']);
            return;
        }

        // substituto: só nas datas do período
        if (!$Daily->covers($class, $date)) {
            Audit::log(\App\Connection::getDb(), 'NEGADO', 'chamada_diaria', $classId, ['data' => $date, 'motivo' => 'fora da substituição']);
            http_response_code(403);
            echo json_encode(['error' => 'fora-da-substituicao', 'from' => $class->substitute_from, 'until' => $class->substitute_until]);
            return;
        }

        if (!in_array($unity, $this->openUnits(Container::getModel('Admin\\Settings')), true)) {
            http_response_code(422);
            echo json_encode(['error' => 'unidade-fechada']);
            return;
        }

        // só aceita matrículas que são realmente da turma
        $enrolled = array_map(function ($s) { return (int) $s->enrollment_id; }, $Daily->students($classId, $unity, $date));

        if (array_diff($absent, $enrolled)) {
            http_response_code(422);
            echo json_encode(['error' => 'aluno-fora-da-turma']);
            return;
        }

        $corrected = $Daily->doneAt($classId, $date) !== null;

        try {
            $id = $Daily->save($classId, $unity, $teacherId, $absent, count($enrolled) - count($absent), $date);
        } catch (\Throwable $e) {
            error_log('Chamada diária: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'falha-ao-salvar']);
            return;
        }

        echo json_encode([
            'id' => $id,
            'daily' => true,
            'corrected' => $corrected,
            'present' => count($enrolled) - count($absent),
            'absent' => count($absent),
            'lessons' => 1,
            'disciplines' => [],
            'unity' => $unity,
            'date' => $date,
            'formattedDate' => date('d/m/Y', strtotime($date)),
            'savedAt' => date('H:i'),
        ]);
    }


    /**
     * Diário de aula: habilidade BNCC, observação, atividades e exceções
     */
    /**
     * Fund. 1: grava o diário do dia da turma (POST JSON: classId, date, text, codes[]). Só o titular; de novo no mesmo dia corrige.
     */
    public function dailyLogSave()
    {

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'metodo-invalido']);
            return;
        }

        if (!isset($_SESSION)) session_start();

        $DailyLog = Container::getModel('TeacherStudent\\DailyLog');
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $teacherId = (int) $_SESSION['Teacher']['id'];
        $classId = (int) ($input['classId'] ?? 0);
        $date = trim((string) ($input['date'] ?? ''));

        $Daily = Container::getModel('TeacherStudent\\DailyAttendance');
        $class = $Daily->homeroomFor($teacherId, $classId);
        if (!$class) {
            Audit::log(\App\Connection::getDb(), 'NEGADO', 'diario_dia', $classId, ['data' => $date]);
            http_response_code(403);
            echo json_encode(['error' => 'sem-permissao']);
            return;
        }

        // substituto: só nas datas do período
        if (!$Daily->covers($class, $date)) {
            Audit::log(\App\Connection::getDb(), 'NEGADO', 'diario_dia', $classId, ['data' => $date, 'motivo' => 'fora da substituição']);
            http_response_code(403);
            echo json_encode(['error' => 'fora-da-substituicao', 'from' => $class->substitute_from, 'until' => $class->substitute_until]);
            return;
        }

        $fail = function ($error) {
            http_response_code(422);
            echo json_encode(['error' => $error]);
        };

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date) || $date > date('Y-m-d')) return $fail('data-invalida');

        $text = trim(str_replace("\r\n", "\n", (string) ($input['text'] ?? '')));
        if ($text === '') return $fail('diario-vazio');
        if (mb_strlen($text) > \App\Models\TeacherStudent\DailyLog::MAX_TEXT) return $fail('texto-muito-longo');

        $codes = [];
        foreach ((array) ($input['codes'] ?? []) as $code) {
            $code = strtoupper(preg_replace('/\s+/', '', (string) $code));
            if (!preg_match(\App\Models\TeacherStudent\Lesson::BNCC_CODE, $code)) return $fail('codigo-bncc-invalido');
            $codes[$code] = $code;
        }
        if (count($codes) > \App\Models\TeacherStudent\DailyLog::MAX_CODES) return $fail('codigos-demais');

        $corrected = $DailyLog->find($classId, $date) !== null;

        try {
            $id = $DailyLog->save($classId, $teacherId, $date, $text, array_values($codes));
        } catch (\Throwable $e) {
            error_log('Diário do dia: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'falha-ao-salvar']);
            return;
        }

        echo json_encode(['id' => $id, 'date' => $date, 'corrected' => $corrected]);
    }


    /**
     * Fund. 1: busca BNCC do diário do dia, em todos os componentes do ano da turma (só o titular)
     */
    public function dailyLogBncc()
    {

        header('Content-Type: application/json');

        if (!isset($_SESSION)) session_start();

        $class = Container::getModel('TeacherStudent\\DailyAttendance')->homeroomFor($_SESSION['Teacher']['id'], (int) ($_GET['turma'] ?? 0));

        if (!$class) {
            http_response_code(403);
            echo json_encode(['error' => 'sem-permissao']);
            return;
        }

        $query = mb_substr((string) ($_GET['q'] ?? ''), 0, 80);
        $skills = Container::getModel('TeacherStudent\\Bncc')->search($query, \App\Models\TeacherStudent\DailyAttendance::STAGE, (int) $class->series_acronym, '');

        echo json_encode(['skills' => $skills], JSON_UNESCAPED_UNICODE);
    }


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
        $this->view->pageTab = ($_GET['aba'] ?? '') === 'horario' ? 'horario' : 'planejamento';
        // Fund. 1: o titular registra o diário do dia na página da turma
        $this->view->homerooms = Container::getModel('TeacherStudent\\DailyAttendance')->homeroomClasses((int) $_SESSION['Teacher']['id']);

        // quadro de horários semanal do professor: agrupado por dia (2=Segunda ... 6=Sexta)
        $schedule = Container::getModel('GeneralManagement\\ClassSchedule')->getScheduleByTeacher((int) $_SESSION['Teacher']['id']);
        $scheduleByDay = array_fill(2, 5, []);
        foreach ($schedule as $slot) $scheduleByDay[$slot->dia_semana][] = $slot;

        $this->view->scheduleByDay = $scheduleByDay;
        $this->view->totalClasses = count($schedule);
        $this->view->dayNames = \App\Models\GeneralManagement\ClassSchedule::dayNames();
        $this->view->shortDayNames = \App\Models\GeneralManagement\ClassSchedule::shortDayNames();

        $this->render('lessons', 'TeacherPortalLayout', 'teacherPortal');
    }


    /**
     * Salva a aula (POST JSON: disciplineClass, disciplineClasses[]?, date, bnccCode, bnccSkill, notes, activities[],
     * substitute?, taughtDisciplineClass?, reason?, plan?)
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
            // aula dada a partir de um plano do próprio professor, na mesma turma/disciplina
            $Plan = Container::getModel('TeacherStudent\\LessonPlan');
            $plan = $Plan->find((int) ($input['plan'] ?? 0));
            if ($plan && (int) $plan->teacher_id === $teacherId && (int) $plan->discipline_class_id === (int) $targets[0]->discipline_class_id) {
                $Plan->markGiven($plan->id, $ids[0]);
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
            'isoDate' => $date,
        ]);
    }


    /**
     * Unidades abertas para lançamento conforme a configuração (1 = só a 1ª, 2 = 1ª e 2ª, 3 = todas)
     *
     * @return int[]
     */
    /**
     * Habilidades da BNCC para a turma/disciplina do diário (?turma=ID&q=texto ou código)
     */
    public function bnccSearch()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION)) session_start();

        $id = (int) ($_GET['turma'] ?? 0);
        $class = null;
        foreach (Container::getModel('TeacherStudent\\Attendance')->disciplineClassesByTeacher($_SESSION['Teacher']['id']) as $c) {
            if ((int) $c->discipline_class_id === $id) $class = $c;
        }

        if (!$class) {
            http_response_code(403);
            echo json_encode(['error' => 'sem-permissao']);
            return;
        }

        $query = mb_substr((string) ($_GET['q'] ?? ''), 0, 80);
        $skills = Container::getModel('TeacherStudent\\Bncc')->search($query, $class->stage, (int) $class->series_acronym, $class->discipline_name);

        echo json_encode(['skills' => $skills], JSON_UNESCAPED_UNICODE);
    }


    private function openUnits($Settings)
    {

        $current = $Settings->unitControlCurrent();
        $max = isset($current[0]) ? max(1, min(4, (int) $current[0]->option_value)) : 1;

        return range(1, $max);
    }


    /**
     * O horário agora é uma aba de Planejamento e Horário; links antigos seguem funcionando
     */
    public function schedule()
    {
        header('Location: /portal-docente/diario?aba=horario');
    }


    public function exit()
    {

        \App\Auth\PortalAccount::close('docente');

        header('Location: /portal-docente' . (\App\Auth\Clerk::enabled() ? '?logout=1' : ''));
    }
}
