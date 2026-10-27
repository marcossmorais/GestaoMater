<?php

namespace App\Controllers;

use MF\Model\Container;
use App\Tools\Audit;
use App\Tools\DocumentSigner;

/**
 * Declarações de matrícula/frequência com assinatura eletrônica (aluno e admin usam a mesma emissão).
 * A frequência vem das chamadas registradas; sem aulas registradas, o percentual não é impresso.
 */
trait DeclarationTrait
{

    protected function renderDeclaration($enrollmentId, $type, $actorType, $actorId)
    {
        $type = $type === 'frequencia' ? 'frequencia' : 'matricula';

        $StudentEnrollment = Container::getModel('Student\\StudentEnrollment');
        $Student = Container::getModel('Student\\Student');
        $MedicalCertificate = Container::getModel('TeacherStudent\\MedicalCertificate');

        $StudentEnrollment->__set('studentEnrollmentId', $enrollmentId);
        $enrollmentData = $StudentEnrollment->dataGeneral();

        if (empty($enrollmentData)) {
            http_response_code(404);
            echo "Matrícula não encontrada.";
            return;
        }

        $Student->__set('studentId', $enrollmentData[0]->id);
        $studentData = $Student->dataGeneral($StudentEnrollment);

        $student = $studentData[0] ?? $enrollmentData[0];
        $enrollment = $enrollmentData[0];
        $summary = $MedicalCertificate->getSummaryByEnrollment($enrollmentId);

        // frequência real: aulas dadas (chamadas) menos faltas não abonadas
        $lessons = 0;
        $counted = 0;
        foreach (Container::getModel('Student\\StudentPerformance')->byDiscipline($enrollmentId, $enrollment->class_id) as $d) {
            $lessons += $d['lessons'];
            $counted += max(0, $d['absences'] - $d['excused']);
        }
        $attendance = $lessons > 0 ? round(100 * max(0, $lessons - $counted) / $lessons, 1) : null;

        $docType = 'declaracao_' . $type;
        $content = [
            'tipo' => $docType,
            'matricula' => (int) $enrollmentId,
            'nome' => $student->name,
            'turma' => $enrollment->acronym_series . ' ' . $enrollment->ballot,
            'curso' => $enrollment->course_name,
            'ano' => $enrollment->school_year,
            'situacao' => $enrollment->situation,
            'faltas' => (int) $summary->total_faltas,
            'abonadas' => (int) $summary->faltas_abonadas,
            'frequencia' => $attendance,
        ];

        $code = Container::getModel('Student\\IssuedDocument')->issue($docType, $enrollmentId, $student->name, $content, $actorType, $actorId);
        $validationUrl = rtrim(getenv('APP_URL') ?: '', '/') . '/validar?codigo=' . $code;

        Audit::log(\App\Connection::getDb(), 'DOWNLOAD', $docType, $enrollmentId, ['codigo' => $code]);

        $this->view->student = $student;
        $this->view->enrollment = $enrollment;
        $this->view->summary = $summary;
        $this->view->type = $type;
        $this->view->attendance = $attendance;
        $this->view->authCode = $code;
        $this->view->validationUrl = $validationUrl;
        $this->view->qrSvg = DocumentSigner::qrSvg($validationUrl);

        require __DIR__ . '/../Views/admin/student/declarations/declaration.php';
    }
}
