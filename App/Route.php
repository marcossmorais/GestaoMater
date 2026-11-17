<?php

namespace App;

use MF\Init\Bootstrap;

class Route extends Bootstrap
{

	protected function initRoutes()
	{

		// Página inicial do sistema

		$routes['index'] = array(
			'route' => '/',
			'controller' => 'Index\\IndexController',
			'action' => 'index'
		);

		// Rotas do portal do administrador 

		$routes['admin'] = array(
			'route' => '/admin',
			'controller' => 'Admin\\AdminController',
			'action' => 'index'
		);

		$routes['adminHome'] = array(
			'route' => '/admin/home',
			'controller' => 'Admin\\AdminController',
			'action' => 'home'
		);

		$routes['adminLogin'] = array(
			'route' => '/admin/login',
			'controller' => 'Admin\\AdminController',
			'action' => 'auth',
			'method' => 'POST'
		);

		$routes['adminExit'] = array(
			'route' => '/admin/sair',
			'controller' => 'Admin\\AdminController',
			'action' => 'exit'
		);

		// Observabilidade 360°: turmas, disciplinas e professores

		$routes['adminObservability'] = array(
			'route' => '/admin/observabilidade',
			'controller' => 'Admin\\AdminObservabilityController',
			'action' => 'index'
		);

		$routes['adminObservabilityClass'] = array(
			'route' => '/admin/observabilidade/turma',
			'controller' => 'Admin\\AdminObservabilityController',
			'action' => 'classDetail'
		);

		// Importação por CSV/JSON e virada de ano

		$routes['adminImport'] = array(
			'route' => '/admin/importacao',
			'controller' => 'Admin\\AdminImportController',
			'action' => 'index'
		);

		$routes['adminAudit'] = array(
			'route' => '/admin/auditoria',
			'controller' => 'Admin\\AdminAuditController',
			'action' => 'index'
		);

		$routes['adminAuditExport'] = array(
			'route' => '/admin/auditoria/exportar',
			'controller' => 'Admin\\AdminAuditController',
			'action' => 'export'
		);

		$routes['adminImportTemplate'] = array(
			'route' => '/admin/importacao/modelo',
			'controller' => 'Admin\\AdminImportController',
			'action' => 'template'
		);

		$routes['adminImportPreview'] = array(
			'route' => '/admin/importacao/previa',
			'controller' => 'Admin\\AdminImportController',
			'action' => 'preview',
			'method' => 'POST'
		);

		$routes['adminImportConfirm'] = array(
			'route' => '/admin/importacao/confirmar',
			'controller' => 'Admin\\AdminImportController',
			'action' => 'confirm',
			'method' => 'POST'
		);

		$routes['adminImportCodes'] = array(
			'route' => '/admin/importacao/codigos',
			'controller' => 'Admin\\AdminImportController',
			'action' => 'codes'
		);

		$routes['adminRolloverConfirm'] = array(
			'route' => '/admin/importacao/virada/confirmar',
			'controller' => 'Admin\\AdminImportController',
			'action' => 'rolloverConfirm',
			'method' => 'POST'
		);

		// Rotas referente aos controles do aluno no portal do administrador

		$routes['adminStudentRegistration'] = array(
			'route' => '/admin/aluno/cadastro',
			'controller' => 'Admin\\AdminStudentController',
			'action' => 'studentRegistration'
		);

		$routes['adminStudentInsert'] = array(
			'route' => '/admin/aluno/cadastro/inserir',
			'controller' => 'Admin\\AdminStudentController',
			'action' => 'studentInsert',
			'method' => 'POST'
		);

		$routes['adminStudentList'] = array(
			'route' => '/admin/aluno/lista',
			'controller' => 'Admin\\AdminStudentController',
			'action' => 'studentList'
		);

		$routes['adminStudentListing'] = array(
			'route' => '/admin/aluno/lista/listagem',
			'controller' => 'Admin\\AdminStudentController',
			'action' => 'studentListing'
		);

		$routes['adminStudentSeek'] = array(
			'route' => '/admin/aluno/lista/buscar',
			'controller' => 'Admin\\AdminStudentController',
			'action' => 'studentSeek'
		);

		$routes['adminStudentProfile'] = array(
			'route' => '/admin/aluno/lista/perfil-aluno',
			'controller' => 'Admin\\AdminStudentController',
			'action' => 'studentProfile'
		);

		$routes['adminUpdateStudentProfile'] = array(
			'route' => '/admin/aluno/lista/perfil-aluno/atualizar',
			'controller' => 'Admin\\AdminStudentController',
			'action' => 'updateStudentProfile',
			'method' => 'POST'
		);

		$routes['adminUpdateStudentProfilePicture'] = array(
			'route' => '/admin/aluno/lista/perfil-aluno/atualizar-foto',
			'controller' => 'Admin\\AdminStudentController',
			'action' => 'updateStudentProfilePicture',
			'method' => 'POST'
		);

		$routes['adminListMedicalCertificates'] = array(
			'route' => '/admin/aluno/atestados/listar',
			'controller' => 'Admin\\AdminStudentController',
			'action' => 'listMedicalCertificates'
		);

		$routes['adminSaveMedicalCertificate'] = array(
			'route' => '/admin/aluno/atestados/salvar',
			'controller' => 'Admin\\AdminStudentController',
			'action' => 'saveMedicalCertificate',
			'method' => 'POST'
		);

		$routes['adminDeleteMedicalCertificate'] = array(
			'route' => '/admin/aluno/atestados/deletar',
			'controller' => 'Admin\\AdminStudentController',
			'action' => 'deleteMedicalCertificate',
			'method' => 'POST'
		);

		$routes['adminStudentDeclarationEnrollment'] = array(
			'route' => '/admin/aluno/declaracao/matricula',
			'controller' => 'Admin\\AdminStudentController',
			'action' => 'declarationEnrollment'
		);

		$routes['adminStudentDeclarationAttendance'] = array(
			'route' => '/admin/aluno/declaracao/frequencia',
			'controller' => 'Admin\\AdminStudentController',
			'action' => 'declarationAttendance'
		);

		$routes['studentDeclarationEnrollment'] = array(
			'route' => '/portal-aluno/declaracao/matricula',
			'controller' => 'StudentPortal\\StudentPortalController',
			'action' => 'declarationEnrollment'
		);

		$routes['studentDeclarationAttendance'] = array(
			'route' => '/portal-aluno/declaracao/frequencia',
			'controller' => 'StudentPortal\\StudentPortalController',
			'action' => 'declarationAttendance'
		);

		$routes['adminOccurrences'] = array(
			'route' => '/admin/ocorrencias',
			'controller' => 'Admin\\AdminOccurrenceController',
			'action' => 'index'
		);

		$routes['adminListOccurrences'] = array(
			'route' => '/admin/aluno/ocorrencias/listar',
			'controller' => 'Admin\\AdminStudentController',
			'action' => 'listOccurrences'
		);

		$routes['adminSaveOccurrence'] = array(
			'route' => '/admin/aluno/ocorrencias/salvar',
			'controller' => 'Admin\\AdminStudentController',
			'action' => 'saveOccurrence',
			'method' => 'POST'
		);

		$routes['adminDeleteOccurrence'] = array(
			'route' => '/admin/aluno/ocorrencias/deletar',
			'controller' => 'Admin\\AdminStudentController',
			'action' => 'deleteOccurrence',
			'method' => 'POST'
		);

		$routes['studentAcknowledgeOccurrence'] = array(
			'route' => '/portal-aluno/ocorrencias/dar-ciencia',
			'controller' => 'StudentPortal\\StudentPortalController',
			'action' => 'acknowledgeOccurrence',
			'method' => 'POST'
		);

		$routes['adminListGuardians'] = array(
			'route' => '/admin/aluno/responsaveis/listar',
			'controller' => 'Admin\\AdminStudentController',
			'action' => 'listGuardians'
		);

		$routes['adminSaveGuardian'] = array(
			'route' => '/admin/aluno/responsaveis/salvar',
			'controller' => 'Admin\\AdminStudentController',
			'action' => 'saveGuardian',
			'method' => 'POST'
		);

		$routes['adminDeleteGuardian'] = array(
			'route' => '/admin/aluno/responsaveis/deletar',
			'controller' => 'Admin\\AdminStudentController',
			'action' => 'deleteGuardian',
			'method' => 'POST'
		);

		$routes['adminToggleGuardian'] = array(
			'route' => '/admin/aluno/responsaveis/toggle',
			'controller' => 'Admin\\AdminStudentController',
			'action' => 'toggleGuardianPickup',
			'method' => 'POST'
		);

		$routes['adminUpdateTeacherProfilePicture'] = array(
			'route' => '/admin/professor/lista/perfil-professor/atualizar-foto',
			'controller' => 'Admin\\AdminTeacherController',
			'action' => 'updateTeacherProfilePicture',
			'method' => 'POST'
		);

		$routes['adminStudentProfileBulletin'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aluno/boletim',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'bulletin'
		);

		$routes['adminDivisionStudentsBySex'] = array(
			'route' => '/admin/gestao/home/divisao-alunos-sexo',
			'controller' => 'Admin\\AdminStudentController',
			'action' => 'divisionStudentsBySex'
		);

		// Rota referente a verificação de redundância do Cpf 

		$routes['checkCpf'] = array(
			'route' => '/verificar-dados/cpf',
			'controller' => 'Admin\\AdminPeopleController',
			'action' => 'checkCpf'
		);

		// Rotas referente aos controles do docente no portal do administrador

		$routes['adminTeacherRegistration'] = array(
			'route' => '/admin/professor/cadastro',
			'controller' => 'Admin\\AdminTeacherController',
			'action' => 'teacherRegistration'
		);

		$routes['adminTeacherInsert'] = array(
			'route' => '/admin/professor/cadastro/inserir',
			'controller' => 'Admin\\AdminTeacherController',
			'action' => 'teacherInsert',
			'method' => 'POST'
		);

		$routes['adminTeacherList'] = array(
			'route' => '/admin/professor/lista',
			'controller' => 'Admin\\AdminTeacherController',
			'action' => 'teacherList'
		);

		$routes['adminTeacherListing'] = array(
			'route' => '/admin/professor/lista/listagem',
			'controller' => 'Admin\\AdminTeacherController',
			'action' => 'teacherListing'
		);

		$routes['adminTeacherSeek'] = array(
			'route' => '/admin/professor/lista/buscar',
			'controller' => 'Admin\\AdminTeacherController',
			'action' => 'seek'
		);

		$routes['adminTeacherProfile'] = array(
			'route' => '/admin/professor/lista/perfil-professor',
			'controller' => 'Admin\\AdminTeacherController',
			'action' => 'teacherProfile'
		);

		$routes['adminTeacherUpdate'] = array(
			'route' => '/admin/professor/lista/perfil-professor/atualizar',
			'controller' => 'Admin\\AdminTeacherController',
			'action' => 'updateTeacherProfile',
			'method' => 'POST'
		);

		// Rota da gestão geral no portal do administrador

		$routes['adminManagementGeneral'] = array(
			'route' => '/admin/gestao',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'managementGeneral'
		);

		// Rotas referente ao período letivo no portal do administrador

		$routes['adminSchoolTermManagement'] = array(
			'route' => '/admin/gestao/periodo-letivo',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'schoolTermManagement'
		);

		$routes['adminSchoolTermInsert'] = array(
			'route' => '/admin/gestao/periodo-letivo/inserir',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'schoolTermInsert',
			'method' => 'POST'
		);

		$routes['adminSchoolTermList'] = array(
			'route' => '/admin/gestao/periodo-letivo/lista',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'schoolTermList'
		);

		$routes['adminSchoolTermUpdate'] = array(
			'route' => '/admin/gestao/periodo-letivo/atualizar',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'schoolTermUpdate',
			'method' => 'POST'
		);

		$routes['adminSchoolTermDelete'] = array(
			'route' => '/admin/gestao/periodo-letivo/deletar',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'schoolTermDelete',
			'method' => 'POST'
		);

		$routes['adminSchoolTermAvailable'] = array(
			'route' => '/admin/gestao/periodo-letivo/lista-anos-disponiveis',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'schoolTermAvailable'
		);

		// Rotas referente a sala de aula no portal do administrador

		$routes['adminClassRoomManagement'] = array(
			'route' => '/admin/gestao/sala',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'classRoomManagement'
		);

		$routes['adminClassRoomInsert'] = array(
			'route' => '/admin/gestao/sala/inserir',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'classRoomInsert',
			'method' => 'POST'
		);

		$routes['adminClassRoomList'] = array(
			'route' => '/admin/gestao/sala/lista',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'classRoomList'
		);

		$routes['adminAvailableRoomNumbers'] = array(
			'route' => '/admin/gestao/sala/lista-numeros-disponiveis',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'availableRoomNumbers'
		);

		$routes['adminClassRoomDelete'] = array(
			'route' => '/admin/gestao/sala/deletar',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'classRoomDelete',
			'method' => 'POST'
		);

		$routes['adminClassRoomUpdate'] = array(
			'route' => '/admin/gestao/sala/atualizar',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'classRoomUpdate',
			'method' => 'POST'
		);

		$routes['adminActiveClassRoom'] = array(
			'route' => '/admin/gestao/turma/lista-salas',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'activeClassRoom'
		);

		// Rotas referente ao curso no portal do administrador

		$routes['adminCourseManagement'] = array(
			'route' => '/admin/gestao/curso',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'courseManagement'
		);

		$routes['adminCourseInsert'] = array(
			'route' => '/admin/gestao/curso/inserir',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'courseInsert',
			'method' => 'POST'
		);

		$routes['adminCourseList'] = array(
			'route' => '/admin/gestao/curso/lista',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'courseList'
		);

		$routes['adminCourseUpdate'] = array(
			'route' => '/admin/gestao/curso/atualizar',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'courseUpdate',
			'method' => 'POST'
		);

		$routes['adminCourseDelete'] = array(
			'route' => '/admin/gestao/curso/deletar',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'courseDelete',
			'method' => 'POST'
		);

		$routes['adminAvailableCourse'] = array(
			'route' => '/admin/gestao/turma/lista-cursos',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'availableCourse'
		);

		$routes['adminTotalStudentsCourse'] = array(
			'route' => '/admin/gestao/curso/total-alunos-curso',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'totalStudentsCourse'
		);

		$routes['adminTotalStudentSituationSchoolYear'] = array(
			'route' => '/admin/gestao/home/situacao-alunos-periodo-letivo',
			'controller' => 'Admin\\AdminStudentController',
			'action' => 'studentSituationSchoolYear'
		);

		// Rotas referente a disciplina no portal do administrador

		$routes['adminDisciplineManagement'] = array(
			'route' => '/admin/gestao/disciplina',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'disciplineManagement'
		);

		$routes['adminDisciplineInsert'] = array(
			'route' => '/admin/gestao/disciplina/inserir',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'disciplineInsert',
			'method' => 'POST'
		);

		$routes['adminDisciplineList'] = array(
			'route' => '/admin/gestao/disciplina/lista',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'disciplineList'
		);

		$routes['adminDisciplineUpdate'] = array(
			'route' => '/admin/gestao/disciplina/atualizar',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'disciplineUpdate',
			'method' => 'POST'
		);

		$routes['adminDisciplineDelete'] = array(
			'route' => '/admin/gestao/disciplina/deletar',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'disciplineDelete',
			'method' => 'POST'
		);

		$routes['adminDisciplineSeek'] = array(
			'route' => '/admin/gestao/disciplina/buscar',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'disciplineSeek'
		);

		$routes['adminDisciplineData'] = array(
			'route' => '/admin/gestao/disciplina/dados',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'disciplineData'
		);

		$routes['adminDisciplineAvailable'] = array(
			'route' => '/admin/gestao/disciplina/disponiveis',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'disciplineAvailable',
			'method' => 'POST'
		);

		// Rotas referente a turma no portal do administrador

		$routes['adminClassManagement'] = array(
			'route' => '/admin/gestao/turma',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'classManagement'
		);

		$routes['adminClassInsert'] = array(
			'route' => '/admin/gestao/turma/inserir',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'classInsert',
			'method' => 'POST'
		);

		$routes['adminClassUpdate'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/atualizar',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'classUpdate',
			'method' => 'POST'
		);

		$routes['adminStudentsAverage'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/medias-alunos',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'studentsAverage'
		);

		$routes['adminStudentsAverageSeek'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/medias-alunos/buscar',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'studentsAverageSeek'
		);

		$routes['adminClassDelete'] = array(
			'route' => '/admin/gestao/turma/deletar',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'classDelete'
		);

		$routes['adminClassData'] = array(
			'route' => '/admin/gestao/turma/dados',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'classData'
		);

		$routes['adminClassList'] = array(
			'route' => '/admin/gestao/turma/lista',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'classList'
		);

		$routes['adminClassCheck'] = array(
			'route' => '/admin/gestao/turma/verificar-dados',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'classCheck'
		);

		$routes['adminClassSeek'] = array(
			'route' => '/admin/gestao/turma/buscar',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'classSeek'
		);

		$routes['adminAvailableListClass'] = array(
			'route' => '/admin/gestao/turma/turmas-disponiveis',
			'controller' => 'Admin\\AdminStudentController',
			'action' => 'classAvailable'
		);

		$routes['adminClassProfile'] = array(
			'route' => '/admin/gestao/turma/perfil-turma',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'classProfile'
		);

		// Fund. 1: professor titular da turma
		$routes['adminClassHomeroomSave'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/titular/salvar',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'classHomeroomSave',
			'method' => 'POST'
		);

		$routes['adminClassSubstitutionList'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/substituicao/lista',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'classSubstitutionList'
		);

		$routes['adminClassSubstitutionSave'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/substituicao/salvar',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'classSubstitutionSave',
			'method' => 'POST'
		);

		$routes['adminClassSubstitutionDelete'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/substituicao/deletar',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'classSubstitutionDelete',
			'method' => 'POST'
		);

		$routes['adminClassScheduleEditor'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/horario',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'classScheduleEditor'
		);

		$routes['adminClassScheduleSave'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/horario/salvar',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'classScheduleSave',
			'method' => 'POST'
		);

		$routes['adminClassDisciplineInsert'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/turma-disciplina/inserir',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'classDisciplineInsert',
			'method' => 'POST'
		);

		$routes['adminListTeacherClass'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/turma-disciplina/professores-turma',
			'controller' => 'Admin\\AdminTeacherController',
			'action' => 'listTeacherClass'
		);

		$routes['adminListTeachersDisciplineClass'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/turma-disciplina/professores-disciplina-turma',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'listTeachersDisciplineClass'
		);

		$routes['adminUpdateClassDiscipline'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/turma-disciplina/atualizar',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'updateClassDiscipline',
			'method' => 'POST'
		);

		$routes['adminSubjectAvailableClass'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/turma-disciplina/select-disciplinas',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'subjectAvailableClass'
		);

		$routes['adminDisciplinesClassAlreadyAdded'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/turma-disciplina/disciplinas-adicionadas',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'disciplinesClassAlreadyAdded'
		);

		$routes['adminDisciplineClassDelete'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/turma-disciplina/deletar',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'disciplineClassDelete',
			'method' => 'POST'
		);

		$routes['adminExamInsert'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/avaliacoes/inserir',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'examInsert',
			'method' => 'POST'
		);

		$routes['adminSumUnitGrades'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/avaliacoes/soma-notas-unidade',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'sumUnitGrades'
		);

		$routes['adminExamList'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/avaliacoes/lista',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'examList'
		);

		$routes['adminRecentsExam'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/avaliacoes/lista-recentes',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'recentsExam'
		);

		$routes['adminExamData'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/avaliacoes/dados',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'examData'
		);

		$routes['adminExamUpdate'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/avaliacoes/atualizar',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'examUpdate',
			'method' => 'POST'
		);

		$routes['adminExamDeletar'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/avaliacoes/deletar',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'examDelete',
			'method' => 'POST'
		);

		$routes['adminExamSeek'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/avaliacoes/buscar',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'examSeek'
		);

		$routes['adminCheckExamName'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/avaliacoes/verificar-nome',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'checkExamName'
		);

		$routes['adminInsertExamNote'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aluno/boletim/adicionar-nota-avaliacao',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'insertExamNote',
			'method' => 'POST'
		);

		$routes['adminNotesNotAddedYet'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aluno/notas-disponiveis',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'notesNotAddedYet'
		);

		$routes['adminOverallStudentAverages'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aluno/medias-gerais',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'overallStudentAverages'
		);

		$routes['adminSeekOverallStudentAverages'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aluno/medias-gerais/buscar',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'seekOverallStudentAverages'
		);

		$routes['adminSwitchClasses'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aluno/troca-turma',
			'controller' => 'Admin\\AdminStudentController',
			'action' => 'switchClasses',
			'method' => 'POST'
		);

		$routes['adminNoteList'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aluno/lista-notas',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'noteListStudent'
		);

		$routes['adminNoteListClass'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/lista-notas',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'noteListClass'
		);

		$routes['adminNoteSeek'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aluno/lista-avaliacoes/buscar',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'noteSeek'
		);

		$routes['adminNoteSeekClass'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/lista-notas/buscar',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'noteSeekClass'
		);

		$routes['adminNoteData'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aluno/lista-avaliacoes/dados',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'noteData'
		);

		$routes['adminNoteUpdate'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aluno/avaliacoes/dados/atualizar',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'noteUpdate',
			'method' => 'POST'
		);

		$routes['adminNoteDelete'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aluno/avaliacoes/dados/deletar',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'noteDelete',
			'method' => 'POST'
		);

		$routes['adminObservationInsert'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aluno/obervacoes/inserir',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'observationInsert',
			'method' => 'POST'
		);

		$routes['adminObservationList'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aluno/obervacoes/lista',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'observationList'
		);

		$routes['adminObservationUpdate'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aluno/obervacoes/atualizar',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'observationUpdate',
			'method' => 'POST'
		);

		$routes['adminObservationDelete'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aluno/obervacoes/deletar',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'observationDelete',
			'method' => 'POST'
		);

		$routes['adminLackInsert'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aluno/faltas/inserir',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'lackInsert',
			'method' => 'POST'
		);

		$routes['adminLackAvailable'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aluno/faltas/disponiveis',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'lackAvailable'
		);

		$routes['adminLackList'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aluno/faltas/lista',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'lackList'
		);

		$routes['adminLackData'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aluno/faltas/dados',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'lackData'
		);

		$routes['adminLackUpdate'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aluno/faltas/atualizar',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'lackUpdate',
			'method' => 'POST'
		);

		$routes['adminLackSeek'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aluno/faltas/buscar',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'lackSeek'
		);

		$routes['adminDisciplineFinalData'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aluno/medias-finais',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'disciplineFinalData'
		);

		$routes['adminDisciplineFinalDataInsert'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aluno/medias-finais/inserir',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'disciplineFinalDataInsert',
			'method' => 'POST'
		);

		$routes['adminDisciplineAverageList'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aluno/medias-finais/lista',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'disciplineAverageList'
		);

		$routes['adminDisciplineMediaAlreadyAdded'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aluno/medias-finais/disponiveis',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'disciplineMediaAlreadyAdded'
		);

		$routes['adminDisciplineMediaUpdate'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aluno/medias-finais/atualizar',
			'controller' => 'Admin\\AdminTeacherStudentController',
			'action' => 'disciplineMediaUpdate',
			'method' => 'POST'
		);

		$routes['adminListRematrugRequests'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/solicitacoes-rematricula',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'listRematrugRequests'
		);

		$routes['adminStudentsAlreadyRegisteredNextYear'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/rematricula/alunos-ja-matriculados',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'studentsAlreadyRegisteredNextYear'
		);

		$routes['adminRematrug'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/rematricular',
			'controller' => 'Admin\\AdminStudentController',
			'action' => 'rematrug',
			'method' => 'POST'
		);

		$routes['adminInsertWarning'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aviso/inserir',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'warningInsert',
			'method' => 'POST'
		);

		$routes['adminListWarning'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aviso/listagem',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'warningList'
		);

		$routes['adminUpdateWarning'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aviso/atualizar',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'warningUpdate',
			'method' => 'POST'
		);

		$routes['adminDeleteWarning'] = array(
			'route' => '/admin/gestao/turma/perfil-turma/aviso/deletar',
			'controller' => 'Admin\\AdminManagementController',
			'action' => 'warningDelete',
			'method' => 'POST'
		);

		// Rotas do portal do docente

		$routes['IndexTeacherPortal'] = array(
			'route' => '/portal-docente',
			'controller' => 'TeacherPortal\\TeacherPortalController',
			'action' => 'index'
		);

		$routes['loginTeacherPortal'] = array(
			'route' => '/portal-docente/login',
			'controller' => 'TeacherPortal\\TeacherPortalController',
			'action' => 'auth',
			'method' => 'POST'
		);

		$routes['homeTeacherPortal'] = array(
			'route' => '/portal-docente/home',
			'controller' => 'TeacherPortal\\TeacherPortalController',
			'action' => 'home'
		);

		$routes['teacherClassesTeacherPortal'] = array(
			'route' => '/portal-docente/turmas',
			'controller' => 'TeacherPortal\\TeacherPortalController',
			'action' => 'teacherClasses'
		);

		$routes['seekTeacherClasses'] = array(
			'route' => '/portal-docente/turmas/buscar',
			'controller' => 'TeacherPortal\\TeacherPortalController',
			'action' => 'seekTeacherClasses'
		);

		$routes['teacherPortalSettings'] = array(
			'route' => '/portal-docente/perfil',
			'controller' => 'TeacherPortal\\TeacherPortalController',
			'action' => 'settings'
		);

		$routes['teacherPortalUpdate'] = array(
			'route' => '/portal-docente/perfil/atualizar',
			'controller' => 'TeacherPortal\\TeacherPortalController',
			'action' => 'updateTeacherPortal',
			'method' => 'POST'
		);

		$routes['teacherPortalHome'] = array(
			'route' => '/portal-docente/home',
			'controller' => 'TeacherPortal\\TeacherPortalController',
			'action' => 'home'
		);

		$routes['teacherPortalStudentBasedFinalAverage'] = array(
			'route' => '/portal-docente/aproveitamento-alunos-base-media-final',
			'controller' => 'TeacherPortal\\TeacherPortalController',
			'action' => 'studentBasedFinalAverage'
		);

		$routes['attendanceTeacherPortal'] = array(
			'route' => '/portal-docente/chamada',
			'controller' => 'TeacherPortal\\TeacherPortalController',
			'action' => 'attendance'
		);

		$routes['attendanceStudentsTeacherPortal'] = array(
			'route' => '/portal-docente/chamada/alunos',
			'controller' => 'TeacherPortal\\TeacherPortalController',
			'action' => 'attendanceStudents'
		);

		$routes['attendanceSaveTeacherPortal'] = array(
			'route' => '/portal-docente/chamada/salvar',
			'controller' => 'TeacherPortal\\TeacherPortalController',
			'action' => 'attendanceSave',
			'method' => 'POST'
		);

		// Fund. 1: chamada diária do professor titular
		$routes['dailyAttendanceStudentsTeacherPortal'] = array(
			'route' => '/portal-docente/chamada-diaria/alunos',
			'controller' => 'TeacherPortal\\TeacherPortalController',
			'action' => 'dailyAttendanceStudents'
		);

		$routes['dailyAttendanceSaveTeacherPortal'] = array(
			'route' => '/portal-docente/chamada-diaria/salvar',
			'controller' => 'TeacherPortal\\TeacherPortalController',
			'action' => 'dailyAttendanceSave',
			'method' => 'POST'
		);

		$routes['dailyLogSaveTeacherPortal'] = array(
			'route' => '/portal-docente/diario-do-dia/salvar',
			'controller' => 'TeacherPortal\\TeacherPortalController',
			'action' => 'dailyLogSave',
			'method' => 'POST'
		);

		$routes['dailyLogBnccTeacherPortal'] = array(
			'route' => '/portal-docente/diario-do-dia/bncc',
			'controller' => 'TeacherPortal\\TeacherPortalController',
			'action' => 'dailyLogBncc'
		);

		$routes['lessonsTeacherPortal'] = array(
			'route' => '/portal-docente/diario',
			'controller' => 'TeacherPortal\TeacherPortalController',
			'action' => 'lessons'
		);

		$routes['bnccSearchTeacherPortal'] = array(
			'route' => '/portal-docente/bncc',
			'controller' => 'TeacherPortal\TeacherPortalController',
			'action' => 'bnccSearch'
		);

		$routes['lessonCalendarTeacherPortal'] = array(
			'route' => '/portal-docente/diario/calendario',
			'controller' => 'TeacherPortal\\TeacherPlanningController',
			'action' => 'calendar'
		);

		$routes['lessonPlanSaveTeacherPortal'] = array(
			'route' => '/portal-docente/planejamento/salvar',
			'controller' => 'TeacherPortal\\TeacherPlanningController',
			'action' => 'planSave',
			'method' => 'POST'
		);

		$routes['lessonPlanDeleteTeacherPortal'] = array(
			'route' => '/portal-docente/planejamento/excluir',
			'controller' => 'TeacherPortal\\TeacherPlanningController',
			'action' => 'planDelete',
			'method' => 'POST'
		);

		$routes['lessonSaveTeacherPortal'] = array(
			'route' => '/portal-docente/diario/salvar',
			'controller' => 'TeacherPortal\TeacherPortalController',
			'action' => 'lessonSave',
			'method' => 'POST'
		);

		$routes['teacherBoard'] = array(
			'route' => '/portal-docente/mural',
			'controller' => 'TeacherPortal\TeacherClassController',
			'action' => 'board'
		);

		$routes['teacherBoardList'] = array(
			'route' => '/portal-docente/mural/avisos',
			'controller' => 'TeacherPortal\TeacherClassController',
			'action' => 'boardList'
		);

		$routes['teacherBoardPost'] = array(
			'route' => '/portal-docente/mural/publicar',
			'controller' => 'TeacherPortal\TeacherClassController',
			'action' => 'boardPost',
			'method' => 'POST'
		);

		$routes['teacherBoardPin'] = array(
			'route' => '/portal-docente/mural/fixar',
			'controller' => 'TeacherPortal\TeacherClassController',
			'action' => 'boardPin',
			'method' => 'POST'
		);

		$routes['teacherBoardDelete'] = array(
			'route' => '/portal-docente/mural/excluir',
			'controller' => 'TeacherPortal\TeacherClassController',
			'action' => 'boardDelete',
			'method' => 'POST'
		);

		$routes['teacherGeneralBoardRead'] = array(
			'route' => '/portal-docente/mural/geral/lido',
			'controller' => 'TeacherPortal\TeacherClassController',
			'action' => 'generalBoardRead',
			'method' => 'POST'
		);

		$routes['teacherGrades'] = array(
			'route' => '/portal-docente/notas',
			'controller' => 'TeacherPortal\TeacherClassController',
			'action' => 'grades'
		);

		$routes['teacherGradesExams'] = array(
			'route' => '/portal-docente/notas/avaliacoes',
			'controller' => 'TeacherPortal\TeacherClassController',
			'action' => 'gradesExams'
		);

		$routes['teacherExamSave'] = array(
			'route' => '/portal-docente/notas/avaliacao/salvar',
			'controller' => 'TeacherPortal\TeacherClassController',
			'action' => 'examSave',
			'method' => 'POST'
		);

		$routes['teacherExamDelete'] = array(
			'route' => '/portal-docente/notas/avaliacao/excluir',
			'controller' => 'TeacherPortal\TeacherClassController',
			'action' => 'examDelete',
			'method' => 'POST'
		);

		$routes['teacherGradesStudents'] = array(
			'route' => '/portal-docente/notas/alunos',
			'controller' => 'TeacherPortal\TeacherClassController',
			'action' => 'gradesStudents'
		);

		$routes['teacherGradesSave'] = array(
			'route' => '/portal-docente/notas/salvar',
			'controller' => 'TeacherPortal\TeacherClassController',
			'action' => 'gradesSave',
			'method' => 'POST'
		);

		$routes['teacherClassOverview'] = array(
			'route' => '/portal-docente/turma',
			'controller' => 'TeacherPortal\TeacherClassController',
			'action' => 'classOverview'
		);

		$routes['teacherStudentFile'] = array(
			'route' => '/portal-docente/aluno',
			'controller' => 'TeacherPortal\TeacherClassController',
			'action' => 'studentFile'
		);

		$routes['teacherStudentNoteSave'] = array(
			'route' => '/portal-docente/aluno/observacao',
			'controller' => 'TeacherPortal\TeacherClassController',
			'action' => 'studentNoteSave',
			'method' => 'POST'
		);

		$routes['teacherGuides'] = array(
			'route' => '/portal-docente/roteiros',
			'controller' => 'TeacherPortal\\TeacherClassController',
			'action' => 'guides'
		);

		$routes['teacherGuideSave'] = array(
			'route' => '/portal-docente/roteiros/salvar',
			'controller' => 'TeacherPortal\\TeacherClassController',
			'action' => 'guideSave',
			'method' => 'POST'
		);

		$routes['teacherOccurrenceRequest'] = array(
			'route' => '/portal-docente/aluno/ocorrencia',
			'controller' => 'TeacherPortal\\TeacherClassController',
			'action' => 'occurrenceRequest',
			'method' => 'POST'
		);

		$routes['studentCertificateSend'] = array(
			'route' => '/portal-aluno/frequencia/atestado',
			'controller' => 'StudentPortal\\StudentPortalController',
			'action' => 'certificateSend',
			'method' => 'POST'
		);

		$routes['studentCertificateFile'] = array(
			'route' => '/portal-aluno/frequencia/anexo',
			'controller' => 'StudentPortal\\StudentPortalController',
			'action' => 'certificateFile'
		);

		$routes['adminCertificateAnswer'] = array(
			'route' => '/admin/pedagogico/atestado/responder',
			'controller' => 'Admin\\AdminPedagogicalController',
			'action' => 'certificateAnswer',
			'method' => 'POST'
		);

		$routes['adminCertificateFile'] = array(
			'route' => '/admin/pedagogico/atestado/anexo',
			'controller' => 'Admin\\AdminPedagogicalController',
			'action' => 'certificateFile'
		);

		$routes['adminBoard'] = array(
			'route' => '/admin/mural',
			'controller' => 'Admin\\AdminBoardController',
			'action' => 'index'
		);

		$routes['adminBoardPost'] = array(
			'route' => '/admin/mural/publicar',
			'controller' => 'Admin\\AdminBoardController',
			'action' => 'post',
			'method' => 'POST'
		);

		$routes['adminCalendar'] = array(
			'route' => '/admin/cronograma',
			'controller' => 'Admin\\AdminCalendarController',
			'action' => 'index'
		);

		$routes['adminCalendarSave'] = array(
			'route' => '/admin/cronograma/salvar',
			'controller' => 'Admin\\AdminCalendarController',
			'action' => 'save',
			'method' => 'POST'
		);

		$routes['adminCalendarDelete'] = array(
			'route' => '/admin/cronograma/excluir',
			'controller' => 'Admin\\AdminCalendarController',
			'action' => 'delete',
			'method' => 'POST'
		);

		$routes['adminCalendarNotify'] = array(
			'route' => '/admin/cronograma/notificar',
			'controller' => 'Admin\\AdminCalendarController',
			'action' => 'notify',
			'method' => 'POST'
		);

		$routes['adminBoardPin'] = array(
			'route' => '/admin/mural/fixar',
			'controller' => 'Admin\\AdminBoardController',
			'action' => 'pin',
			'method' => 'POST'
		);

		$routes['adminBoardDelete'] = array(
			'route' => '/admin/mural/excluir',
			'controller' => 'Admin\\AdminBoardController',
			'action' => 'delete',
			'method' => 'POST'
		);

		$routes['studentGeneralBoardRead'] = array(
			'route' => '/portal-aluno/mural/geral/lido',
			'controller' => 'StudentPortal\\StudentPortalController',
			'action' => 'generalBoardRead',
			'method' => 'POST'
		);

		$routes['adminPedagogical'] = array(
			'route' => '/admin/pedagogico',
			'controller' => 'Admin\\AdminPedagogicalController',
			'action' => 'index'
		);

		$routes['adminGuideSeen'] = array(
			'route' => '/admin/pedagogico/roteiro/visto',
			'controller' => 'Admin\\AdminPedagogicalController',
			'action' => 'guideSeen',
			'method' => 'POST'
		);

		$routes['adminOccurrenceRequestAnswer'] = array(
			'route' => '/admin/pedagogico/solicitacao/responder',
			'controller' => 'Admin\\AdminPedagogicalController',
			'action' => 'requestAnswer',
			'method' => 'POST'
		);

		$routes['teacherSchedule'] = array(
			'route' => '/portal-docente/horario',
			'controller' => 'TeacherPortal\\TeacherPortalController',
			'action' => 'schedule'
		);

		$routes['exitTeacherPortal'] = array(
			'route' => '/portal-docente/sair',
			'controller' => 'TeacherPortal\\TeacherPortalController',
			'action' => 'exit'
		);

		// Rotas do portal do aluno

		$routes['IndexStudentPortal'] = array(
			'route' => '/portal-aluno',
			'controller' => 'StudentPortal\\StudentPortalController',
			'action' => 'index'
		);

		$routes['HomeStudentPortal'] = array(
			'route' => '/portal-aluno/home',
			'controller' => 'StudentPortal\\StudentPortalController',
			'action' => 'home'
		);

		$routes['authStudentPortal'] = array(
			'route' => '/portal-aluno/login',
			'controller' => 'StudentPortal\\StudentPortalController',
			'action' => 'auth',
			'method' => 'POST'
		);

		$routes['exitStudentPortal'] = array(
			'route' => '/portal-aluno/sair',
			'controller' => 'StudentPortal\\StudentPortalController',
			'action' => 'exit'
		);

		$routes['StudentBulletin'] = array(
			'route' => '/portal-aluno/boletim',
			'controller' => 'StudentPortal\StudentPortalController',
			'action' => 'bulletin'
		);

		$routes['StudentBulletinDocument'] = array(
			'route' => '/portal-aluno/boletim/documento',
			'controller' => 'StudentPortal\StudentPortalController',
			'action' => 'bulletinDocument'
		);

		$routes['StudentExamSheetDocument'] = array(
			'route' => '/portal-aluno/roteiro/documento',
			'controller' => 'StudentPortal\StudentPortalController',
			'action' => 'examSheetDocument'
		);

		$routes['StudentScheduleDocument'] = array(
			'route' => '/portal-aluno/horarios/documento',
			'controller' => 'StudentPortal\StudentPortalController',
			'action' => 'scheduleDocument'
		);

		$routes['validateDocument'] = array(
			'route' => '/validar',
			'controller' => 'Index\DocumentController',
			'action' => 'validate'
		);

		$routes['StudentBoardRead'] = array(
			'route' => '/portal-aluno/mural/lido',
			'controller' => 'StudentPortal\StudentPortalController',
			'action' => 'boardRead',
			'method' => 'POST'
		);

		$routes['StudentPortalSettings'] = array(
			'route' => '/portal-aluno/configuracoes',
			'controller' => 'StudentPortal\\StudentPortalController',
			'action' => 'settings'
		);

		$routes['StudentPortalUpdateSettings'] = array(
			'route' => '/portal-aluno/configuracoes/atualizar',
			'controller' => 'StudentPortal\\StudentPortalController',
			'action' => 'updateSettings',
			'method' => 'POST'
		);

		// Rotas referente aos controles dos administradores no portal do administrador 

		$routes['adminSettings'] = array(
			'route' => '/admin/configuracoes',
			'controller' => 'Admin\\AdminController',
			'action' => 'settings'
		);

		$routes['adminSettingsUpdate'] = array(
			'route' => '/admin/configuracoes/atualizar',
			'controller' => 'Admin\\AdminController',
			'action' => 'settingsUpdate',
			'method' => 'POST'
		);

		$routes['adminUpdateAdminProfilePicture'] = array(
			'route' => '/admin/configuracoes/atualizar-foto',
			'controller' => 'Admin\\AdminController',
			'action' => 'updateAdminProfilePicture',
			'method' => 'POST'
		);

		$routes['adminUpdate'] = array(
			'route' => '/admin/configuracoes/perfil/atualizar',
			'controller' => 'Admin\\AdminController',
			'action' => 'adminUpdate',
			'method' => 'POST'
		);

		$routes['adminRegistration'] = array(
			'route' => '/admin/administrador/cadastro',
			'controller' => 'Admin\\AdminController',
			'action' => 'adminRegistration'
		);

		$routes['adminInsert'] = array(
			'route' => '/admin/administrador/cadastro/inserir',
			'controller' => 'Admin\\AdminController',
			'action' => 'adminInsert',
			'method' => 'POST'
		);

		$routes['adminList'] = array(
			'route' => '/admin/administrador/lista',
			'controller' => 'Admin\\AdminController',
			'action' => 'adminList'
		);

		$routes['adminProfile'] = array(
			'route' => '/admin/administrador/lista/perfil',
			'controller' => 'Admin\\AdminController',
			'action' => 'profile'
		);

		// Autenticação (Clerk)

		$routes['clerkSession'] = array(
			'route' => '/auth/clerk/sessao',
			'controller' => 'Auth\\AuthController',
			'action' => 'clerkSession',
			'method' => 'POST'
		);


		$this->setRoutes($routes);
	}


	/**
	 * Rotas acessíveis sem login
	 */
	const PUBLIC_ROUTES = [
		'/',
		'/admin', '/admin/login', '/admin/sair',
		'/portal-docente', '/portal-docente/login', '/portal-docente/sair',
		'/portal-aluno', '/portal-aluno/login', '/portal-aluno/sair',
		'/auth/clerk/sessao',
		'/validar',
	];

	/**
	 * Rotas do admin que o portal do aluno também usa
	 */
	const STUDENT_SHARED_ROUTES = [];

	protected function run($url)
	{
		// escrita só por POST e só a partir do próprio site (antes um link GET ou um form de outro site alterava dados)
		$blocked = $this->blockedRequest($url);
		if ($blocked) {
			http_response_code($blocked[0]);
			if ($blocked[0] === 405) header('Allow: POST');
			header('Content-Type: application/json');
			echo json_encode(['error' => $blocked[1]]);
			exit;
		}

		if (!$this->authorized($url)) {

			$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
				|| $_SERVER['REQUEST_METHOD'] !== 'GET';

			if ($isAjax) {
				http_response_code(401);
				header('Content-Type: application/json');
				echo json_encode(['error' => 'nao-autenticado']);
			} else {
				$login = strpos($url, '/portal-docente') === 0 ? '/portal-docente' : (strpos($url, '/portal-aluno') === 0 ? '/portal-aluno' : '/admin');
				header('Location: ' . $login . ($login === '/admin' && $this->adminExpired ? '?error=sessao-expirada' : ''));
			}

			exit;
		}

		parent::run($url);
	}

	/**
	 * Recusa a requisição antes de chegar ao controller. Retorna [status, erro] ou null.
	 *  - rota marcada com 'method' => 'POST' chamada por outro método → 405
	 *  - qualquer requisição que não seja GET/HEAD vinda de outro site (Sec-Fetch-Site, Origin ou Referer) → 403
	 */
	private function blockedRequest($url)
	{
		$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

		foreach ($this->getRoutes() as $route) {
			if ($route['route'] === $url && ($route['method'] ?? null) === 'POST' && $method !== 'POST') {
				return [405, 'metodo-invalido'];
			}
		}

		if ($method === 'GET' || $method === 'HEAD') return null;

		if (!self::sameOrigin($_SERVER)) {
			// com a sessão aberta a auditoria registra quem foi o alvo
			if (session_status() === PHP_SESSION_NONE) session_start();
			\App\Tools\Audit::log(\App\Connection::getDb(), 'NEGADO', 'requisicao', null, ['motivo' => 'veio de outro site (' . $url . ')']);
			return [403, 'origem-invalida'];
		}

		return null;
	}

	/**
	 * A requisição veio de uma página do próprio site? Navegadores atuais mandam Sec-Fetch-Site;
	 * os antigos, Origin ou Referer. Sem nenhum dos três (curl, scripts) não há navegador a enganar: aceita.
	 */
	public static function sameOrigin(array $server)
	{
		$site = strtolower($server['HTTP_SEC_FETCH_SITE'] ?? '');
		if ($site !== '') return in_array($site, ['same-origin', 'none'], true);

		$host = strtolower($server['HTTP_HOST'] ?? '');
		$source = $server['HTTP_ORIGIN'] ?? ($server['HTTP_REFERER'] ?? '');
		if ($source === '' || $source === 'null') return $source === '';

		$parts = parse_url($source);
		if (!$parts || empty($parts['host'])) return false;

		$origin = strtolower($parts['host']) . (isset($parts['port']) ? ':' . $parts['port'] : '');

		return $origin === $host;
	}

	// admin: minutos sem uso até a sessão expirar (docente e aluno seguem o session.gc_maxlifetime, 8 h)
	const ADMIN_IDLE_SECONDS = 30 * 60;

	private $adminExpired = false;

	/**
	 * Encerra a sessão do admin parada há mais de 30 minutos (LOGOUT na auditoria); cada acesso renova o prazo
	 */
	private function expireIdleAdmin()
	{
		if (!isset($_SESSION['Admin'])) return;

		$last = (int) ($_SESSION['Admin']['lastSeen'] ?? time());

		if (time() - $last > self::ADMIN_IDLE_SECONDS) {
			\App\Auth\PortalAccount::close('admin');
			$this->adminExpired = true;
			return;
		}

		$_SESSION['Admin']['lastSeen'] = time();
	}

	private function authorized($url)
	{
		$url = rtrim($url, '/') ?: '/';

		// as views chamam session_start() depois do layout já ter enviado HTML
		if (session_status() === PHP_SESSION_NONE) session_start();

		$this->expireIdleAdmin();

		if (in_array($url, self::PUBLIC_ROUTES, true)) return true;

		$admin = isset($_SESSION['Admin']);
		$teacher = isset($_SESSION['Teacher']);
		$student = isset($_SESSION['Student']);

		if (strpos($url, '/portal-aluno') === 0) return $student;
		if (strpos($url, '/portal-docente') === 0) return $teacher;

		// gestão é só do admin; o docente usa as rotas próprias de /portal-docente (com checagem de turma/disciplina)
		if (strpos($url, '/admin') === 0 || strpos($url, '/verificar-dados') === 0) {
			return $admin || ($student && in_array($url, self::STUDENT_SHARED_ROUTES, true));
		}

		return $admin || $teacher || $student;
	}
}
