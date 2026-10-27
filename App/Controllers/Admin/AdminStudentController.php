<?php

namespace App\Controllers\Admin;

use App\Tools\Tools;
use MF\Controller\Action;
use MF\Model\Container;


class AdminStudentController extends Action
{

    use \App\Controllers\DeclarationTrait;

    public function studentRegistration()
    {

        $Student = Container::getModel('Student\\Student');
        $Classe = Container::getModel('GeneralManagement\\Classe');

        $this->view->availableSex = $Student->availableSex();
        $this->view->pcd = $Student->pcd();
        $this->view->bloodType = $Student->availablebloodType();
        $this->view->availableClass = $Classe->firstGradeClasses();

        $this->render('student/studentRegistration', 'AdminLayout');
    }


    public function studentList()
    {

        $Student = Container::getModel('Student\\Student');
        $Course = Container::getModel('GeneralManagement\\Course');
        $Classe = Container::getModel('GeneralManagement\\Classe');
        $Student = Container::getModel('Student\\Student');
        $Shift = Container::getModel('GeneralManagement\\Shift');
        $Series = Container::getModel('GeneralManagement\\Series');
        $SchoolTerm = Container::getModel('GeneralManagement\\SchoolTerm');

        $this->view->listStudent = $Student->readAll();
        $this->view->availableCourse = $Course->listForSelect();
        $this->view->availableClass = $Classe->firstGradeClasses();
        $this->view->availableSex = $Student->availableSex();
        $this->view->availableShift = $Shift->listForSelect();
        $this->view->availableSeries = $Series->listForSelect();
        $this->view->typeStudentList = 'normal';
        $this->view->allSchoolTerm = $SchoolTerm->allSchoolTerm();

        $this->render('student/studentList', 'AdminLayout');
    }


    public function studentListing()
    {

        $Student = Container::getModel('Student\\Student');

        $this->view->listStudent = $Student->readAll();
        $this->view->typeStudentList = 'normal';

        $this->render('student/components/studentListing', 'SimpleLayout');
    }


    public function availableListClass()
    {

        $Classe = Container::getModel('GeneralManagement\\Classe');
        $this->view->listClass = $Classe->firstGradeClasses();
        $this->render('student/components/classesList', 'SimpleLayout');
    }


    public function studentSeek()
    {

        $Student = Container::getModel('Student\\Student');
        $Classe = Container::getModel('GeneralManagement\\Classe');

        $Student->__set('name', $_GET['name']);
        $Student->__set('fk_id_sex', $_GET['sex']);
        $Classe->__set('fk_id_course', $_GET['course']);
        $Classe->__set('fk_id_series', $_GET['series']);
        $Classe->__set('fk_id_shift', $_GET['shift']);
        $Classe->__set('fk_id_school_term', $_GET['schoolTerm']);

        $this->view->listStudent = $Student->seek($Classe);
        $this->view->typeStudentList = 'normal';

        $this->render('student/components/studentListing', 'SimpleLayout');
    }


    public function studentProfile()
    {

        $Student = Container::getModel('Student\\Student');
        $ClassDiscipline = Container::getModel('GeneralManagement\\ClassDiscipline');
        $Classe = Container::getModel('GeneralManagement\\Classe');
        $SchoolTerm = Container::getModel('GeneralManagement\\SchoolTerm');
        $StudentEnrollment = Container::getModel('Student\\StudentEnrollment');
        $DisciplineAverage = Container::getModel('TeacherStudent\\DisciplineAverage');
        $Unity = Container::getModel('GeneralManagement\\Unity');

        $StudentEnrollment->__set('studentEnrollmentId', empty($_GET['enrollmentId']) ? $_GET['id'] : $_GET['enrollmentId']);

        $this->view->studentDataEnrollment = $StudentEnrollment->dataGeneral();

        $Student->__set('studentId', $this->view->studentDataEnrollment[0]->id);

        $this->view->studentDataGeneral = $Student->dataGeneral($StudentEnrollment);
        $this->view->availableSex = $Student->availableSex();
        $this->view->pcd = $Student->pcd();
        $this->view->unity = $Unity->readOpenUnits();
        $this->view->bloodType = $Student->availablebloodType();

        if (!isset($_SESSION)) session_start();
        if (isset($_SESSION['Teacher']['id'])) $ClassDiscipline->__set("fk_id_teacher", $_SESSION['Teacher']['id']);

        $ClassDiscipline->__set("fk_id_class", $this->view->studentDataEnrollment[0]->class_id);

        $Classe->__set('fk_id_series', $this->view->studentDataEnrollment[0]->series_id);
        $Classe->__set('fk_id_course', $this->view->studentDataEnrollment[0]->course_id);

        $this->view->linkedDisciplines = isset($_SESSION['Teacher']['id']) ?  $ClassDiscipline->teacherLinkedDisciplines() :  $ClassDiscipline->classLinkedSubjects();
        $this->view->listSubtitles = $DisciplineAverage->availableSubtitles();
        $this->view->studentSituation = $StudentEnrollment->studentSituation();
        $this->view->schoolTermActive = $SchoolTerm->active();
        $this->view->generalSituationStudent = $Student->generalSituationStudent();
        $this->view->classesWhereStudentEnrolledSameYear = $Classe->classesWhereStudentEnrolledSameYear();

        $MedicalCertificate = Container::getModel('TeacherStudent\\MedicalCertificate');
        $enrollmentId = $this->view->studentDataEnrollment[0]->enrollment_id;
        $this->view->medicalCertificates = $MedicalCertificate->getByEnrollment($enrollmentId);
        $this->view->absenceSummary = $MedicalCertificate->getSummaryByEnrollment($enrollmentId);



        $Occurrence = Container::getModel('TeacherStudent\\Occurrence');

        $this->view->occurrences = $Occurrence->getByEnrollment($enrollmentId);

        $this->view->occurrenceSummary = $Occurrence->getSummaryByEnrollment($enrollmentId);

        $Guardian = Container::getModel('TeacherStudent\\Guardian');
        $this->view->guardians = $Guardian->getByStudent($this->view->studentDataEnrollment[0]->id);

        $this->render('student/components/modalStudentProfile', 'SimpleLayout');
    }


    public function updateStudentProfile()
    {

        if (!isset($_SESSION)) session_start();

        if ($_SESSION['Admin']['hierarchyFunction'] <= 2) {

            $Address =  Container::getModel('People\\Address');
            $Telephone = Container::getModel('People\Telephone');
            $Student = Container::getModel('Student\\Student');
            $StudentEnrollment = Container::getModel('Student\\StudentEnrollment');

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
            $Student->__set('studentId', $_POST['studentId']);

            $Student->__set('name', $_POST['name']);
            $Student->__set('birthDate', $_POST['birthDate']);
            $Student->__set('cpf', $Tool->formatElement($_POST['cpf']));
            $Student->__set('naturalness', $_POST['naturalness']);
            $Student->__set('nationality', $_POST['nationality']);
            $Student->__set('motherName', $_POST['motherName']);
            $Student->__set('fatherName', $_POST['fatherName']);
            $Student->__set('fk_id_sex', $_POST['sex']);
            $Student->__set('fk_id_pcd', $_POST['pcd']);
            $Student->__set('fk_id_general_situation', $_POST['situationStudentGeneral']);
            $StudentEnrollment->__set('studentEnrollmentId', $_POST['enrollmentId']);
            $StudentEnrollment->__set('fk_id_student_situation', $_POST['situationStudent']);


            $Telephone->update();
            $Address->update();
            $Student->update();
        } else {
            header('Location: portal-aluno/home');
        }
    }


    public function enrollStudent($classId, $studentId, $schoolTermId, $studentSituation = 1)
    {

        $Enrollment = Container::getModel('Student\\StudentEnrollment');

        $Enrollment->__set('fk_id_student_situation', $studentSituation);
        $Enrollment->__set('fk_id_student', $studentId);
        $Enrollment->__set('fk_id_class', $classId);
        $Enrollment->__set('fk_id_school_term', $schoolTermId);

        $Enrollment->insert();
    }


    public function studentInsert()
    {

        $SchoolTerm = Container::getModel('GeneralManagement\\SchoolTerm');
        $activeSchoolTerm = $SchoolTerm->active();

        $Tool = new Tools();

        $Address =  Container::getModel('People\\Address');
        $Telephone = Container::getModel('People\Telephone');
        $Student = Container::getModel('Student\\Student');
        $Enrollment = Container::getModel('Student\\StudentEnrollment');

        $Address->__set('district', $_POST['district']);
        $Address->__set('address', $_POST['address']);
        $Address->__set('uf', $_POST['uf']);
        $Address->__set('county', $_POST['county']);
        $Address->__set('zipCode', $Tool->formatElement($_POST['zipCode']));

        $Telephone->__set('telephoneNumber', $Tool->formatElement($_POST['telephoneNumber']));
        $Student->__set('accessCode', $Tool->formatElement($_POST['accessCode']));

        $Student->__set('name', $_POST['name']);
        $Student->__set('birthDate', $_POST['birthDate']);
        $Student->__set('cpf', $Tool->formatElement($_POST['cpf']));
        $Tool->image($Student, '../public/assets/img/studentProfilePhotos/');
        $Student->__set('naturalness', $_POST['naturalness']);
        $Student->__set('nationality', $_POST['nationality']);
        $Student->__set('motherName', $_POST['motherName']);
        $Student->__set('fatherName', $_POST['fatherName']);
        $Student->__set('email', $_POST['email']);
        $Student->__set('fk_id_hierarchy_function', 4);
        $Student->__set('fk_id_sex', $_POST['sex']);
        $Student->__set('fk_id_blood_type', $_POST['bloodType']);
        $Student->__set('fk_id_pcd', $_POST['pcd']);
        $Student->__set('fk_id_telephone', $Telephone->insert());
        $Student->__set('fk_id_address', $Address->insert());

        $Enrollment->__set('fk_id_student_situation', 1);
        $Enrollment->__set('fk_id_student', $Student->insert());
        $Enrollment->__set('fk_id_class', $_POST['class']);
        $Enrollment->__set('fk_id_school_term', $activeSchoolTerm[0]->option_value);

        $Enrollment->insert();

        header('Location: /admin/aluno/cadastro');
    }


    public function updateStudentProfilePicture()
    {

        $Student = Container::getModel('Student\\Student');
        $Tool = new Tools();

        empty($_GET['oldPhoto']) ? '' : unlink('../public/assets/img/studentProfilePhotos/' . $_POST['oldPhoto']);

        $Tool->image($Student, '../public/assets/img/studentProfilePhotos/');

        $Student->__set('studentId', $_POST['id']);

        $Student->updateProfilePicture();
    }


    public function rematrug()
    {

        $StudentEnrollment = Container::getModel('Student\\StudentEnrollment');

        $StudentEnrollment->__set('fk_id_student_situation', 1);
        $StudentEnrollment->__set('fk_id_student', $_POST['studentId']);
        $StudentEnrollment->__set('fk_id_class', $_POST['newClass']);
        $StudentEnrollment->__set('fk_id_school_term', $_POST['schoolTermId']);

        $StudentEnrollment->insert();
    }


    public function studentSituationSchoolYear()
    {

        $StudentEnrollment = Container::getModel('Student\\StudentEnrollment');
        echo json_encode($StudentEnrollment->studentSituationSchoolYear());
    }


    public function totalRematriumMarkedWithYesAndNo()
    {

        $StudentRematrug = Container::getModel('Student\\StudentRematrug');
        echo json_encode($StudentRematrug->totalRematriumMarkedWithYesAndNo());
    }


    public function divisionStudentsBySex()
    {

        $Student = Container::getModel('Student\\Student');
        echo json_encode($Student->divisionStudentsBySex());
    }


    public function switchClasses()
    {

        $this->enrollStudent($_POST['classId'], $_POST['studentId'], $_POST['schoolTermId'], 1);

        $StudentEnrollment = Container::getModel('Student\\StudentEnrollment');

        $StudentEnrollment->__set('fk_id_student_situation', 4);
        $StudentEnrollment->__set('studentEnrollmentId', $_POST['enrollmentId']);

        $StudentEnrollment->changeStudentFromClass();
    }


    public function listMedicalCertificates()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION)) session_start();
        $enrollmentId = (int) ($_GET['enrollmentId'] ?? 0);
        $MedicalCertificate = Container::getModel('TeacherStudent\\MedicalCertificate');
        $list = $MedicalCertificate->getByEnrollment($enrollmentId);
        $summary = $MedicalCertificate->getSummaryByEnrollment($enrollmentId);
        echo json_encode(['certificates' => $list, 'summary' => $summary]);
    }


    public function saveMedicalCertificate()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION)) session_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'metodo-invalido']);
            return;
        }

        $MedicalCertificate = Container::getModel('TeacherStudent\\MedicalCertificate');

        $enrollmentId = (int) ($_POST['enrollmentId'] ?? 0);
        $studentId = (int) ($_POST['studentId'] ?? 0);
        $startDate = trim($_POST['startDate'] ?? '');
        $endDate = trim($_POST['endDate'] ?? '') ?: $startDate;
        $reason = trim($_POST['reason'] ?? '');
        $cid = trim($_POST['cid'] ?? '');
        $doctorName = trim($_POST['doctorName'] ?? '');
        $doctorCrm = trim($_POST['doctorCrm'] ?? '');
        $healthInstitution = trim($_POST['healthInstitution'] ?? '');
        $faltasAbonadas = (int) ($_POST['aboneLacks'] ?? 0);
        $disciplineClass = (int) ($_POST['disciplineClass'] ?? 0);
        $unity = (int) ($_POST['unity'] ?? 0);
        $observation = trim($_POST['observation'] ?? '');

        if (!$enrollmentId || !$studentId || !$startDate || !$reason) {
            http_response_code(422);
            echo json_encode(['error' => 'Preencha os campos obrigatorios (aluno, datas e motivo).']);
            return;
        }

        $fileName = null;
        if (!empty($_FILES['attachment']['name'])) {
            $uploadDir = dirname(__DIR__, 2) . '/public/assets/uploads/atestados/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'webp'])) {
                $fileName = 'atestado_' . $enrollmentId . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . $fileName);
            }
        }

        $d1 = new \DateTime($startDate);
        $d2 = new \DateTime($endDate);
        $dias = max(1, $d2->diff($d1)->days + 1);

        $registeredBy = isset($_SESSION['Admin']) 
            ? ($_SESSION['Admin']['name'] ?? 'Secretaria Escolar') 
            : (isset($_SESSION['Teacher']) ? $_SESSION['Teacher']['name'] : 'Secretaria Escolar');

        try {
            $id = $MedicalCertificate->create([
                'fk_id_matricula' => $enrollmentId,
                'fk_id_aluno' => $studentId,
                'data_inicio' => $startDate,
                'data_fim' => $endDate,
                'dias' => $dias,
                'motivo' => $reason,
                'cid' => $cid,
                'medico_nome' => $doctorName,
                'medico_crm' => $doctorCrm,
                'instituicao_saude' => $healthInstitution,
                'status' => 'Aprovado',
                'faltas_abonadas' => $faltasAbonadas,
                'fk_id_turma_disciplina_falta' => $disciplineClass,
                'fk_id_unidade_falta' => $unity,
                'observacao' => $observation,
                'anexo_documento' => $fileName,
                'registrado_por' => $registeredBy,
            ]);

            echo json_encode([
                'status' => 'success',
                'id' => $id,
                'message' => 'Atestado registrado com sucesso!'
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao salvar atestado: ' . $e->getMessage()]);
        }
    }


    public function deleteMedicalCertificate()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION)) session_start();
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) {
            http_response_code(422);
            echo json_encode(['error' => 'ID invalido']);
            return;
        }
        $MedicalCertificate = Container::getModel('TeacherStudent\\MedicalCertificate');
        $MedicalCertificate->delete($id);
        echo json_encode(['status' => 'success']);
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

        $this->renderDeclaration((int) ($_GET['enrollmentId'] ?? 0), $_GET['type'] ?? $type, 'admin', $_SESSION['Admin']['id'] ?? null);
    }

    public function listOccurrences()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION)) session_start();
        $enrollmentId = (int) ($_GET['enrollmentId'] ?? 0);
        $Occurrence = Container::getModel('TeacherStudent\\Occurrence');
        echo json_encode([
            'occurrences' => $Occurrence->getByEnrollment($enrollmentId),
            'summary' => $Occurrence->getSummaryByEnrollment($enrollmentId)
        ]);
    }

    public function saveOccurrence()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION)) session_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'metodo-invalido']);
            return;
        }

        $enrollmentId = (int) ($_POST['enrollmentId'] ?? 0);
        $studentId = (int) ($_POST['studentId'] ?? 0);
        $category = trim($_POST['category'] ?? 'Disciplinar');
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $severity = trim($_POST['severity'] ?? 'Média');
        $measure = trim($_POST['measure'] ?? '');
        $disciplineClass = !empty($_POST['disciplineClass']) ? (int) $_POST['disciplineClass'] : null;
        $date = trim($_POST['date'] ?? '') ?: date('Y-m-d');
        $visible = !empty($_POST['visible']);
        $reqAck = !empty($_POST['requireAck']);

        if (!$enrollmentId || !$studentId || !$title || !$description) {
            http_response_code(422);
            echo json_encode(['error' => 'Preencha título e descrição da ocorrência.']);
            return;
        }

        $registeredBy = isset($_SESSION['Admin']) 
            ? ($_SESSION['Admin']['name'] ?? 'Coordenação Pedagógica') 
            : (isset($_SESSION['Teacher']) ? $_SESSION['Teacher']['name'] : 'Coordenação Pedagógica');

        try {
            $Occurrence = Container::getModel('TeacherStudent\\Occurrence');
            $id = $Occurrence->create([
                'fk_id_matricula' => $enrollmentId,
                'fk_id_aluno' => $studentId,
                'fk_id_turma_disciplina' => $disciplineClass,
                'categoria' => $category,
                'titulo' => $title,
                'descricao' => $description,
                'gravidade' => $severity,
                'medida_tomada' => $measure,
                'visivel_responsavel' => $visible,
                'requer_ciencia' => $reqAck,
                'registrado_por' => $registeredBy,
                'data_ocorrencia' => $date
            ]);

            echo json_encode(['status' => 'success', 'id' => $id, 'message' => 'Ocorrência registrada com sucesso!']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao salvar ocorrência: ' . $e->getMessage()]);
        }
    }

    public function deleteOccurrence()
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
        $Occurrence->delete($id);
        echo json_encode(['status' => 'success']);
    }

    public function listGuardians()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION)) session_start();
        $studentId = (int) ($_GET['studentId'] ?? 0);
        $Guardian = Container::getModel('TeacherStudent\\Guardian');
        echo json_encode($Guardian->getByStudent($studentId));
    }

    public function saveGuardian()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION)) session_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'metodo-invalido']);
            return;
        }

        $studentId = (int) ($_POST['studentId'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $relationship = trim($_POST['relationship'] ?? 'Outro Autorizado');
        $cpf = trim($_POST['cpf'] ?? '');
        $rg = trim($_POST['rg'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $authorizedPickup = !empty($_POST['authorizedPickup']);
        $financialResp = !empty($_POST['financialResp']);
        $pedagogicalResp = !empty($_POST['pedagogicalResp']);
        $obs = trim($_POST['observations'] ?? '');

        if (!$studentId || !$name || !$phone) {
            http_response_code(422);
            echo json_encode(['error' => 'Preencha nome e telefone do responsável/autorizado.']);
            return;
        }

        try {
            $Guardian = Container::getModel('TeacherStudent\\Guardian');
            $id = $Guardian->create([
                'fk_id_aluno' => $studentId,
                'nome' => $name,
                'parentesco' => $relationship,
                'cpf' => $cpf,
                'rg' => $rg,
                'telefone' => $phone,
                'email' => $email,
                'autorizado_buscar' => $authorizedPickup,
                'responsavel_financeiro' => $financialResp,
                'responsavel_pedagogico' => $pedagogicalResp,
                'observacao_restricao' => $obs
            ]);

            echo json_encode(['status' => 'success', 'id' => $id, 'message' => 'Responsável registrado com sucesso!']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao salvar responsável: ' . $e->getMessage()]);
        }
    }

    public function deleteGuardian()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION)) session_start();
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) {
            http_response_code(422);
            echo json_encode(['error' => 'ID inválido']);
            return;
        }
        $Guardian = Container::getModel('TeacherStudent\\Guardian');
        $Guardian->delete($id);
        echo json_encode(['status' => 'success']);
    }

    public function toggleGuardianPickup()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION)) session_start();
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) {
            http_response_code(422);
            echo json_encode(['error' => 'ID inválido']);
            return;
        }
        $Guardian = Container::getModel('TeacherStudent\\Guardian');
        $Guardian->toggleAuthorized($id);
        echo json_encode(['status' => 'success']);
    }
}