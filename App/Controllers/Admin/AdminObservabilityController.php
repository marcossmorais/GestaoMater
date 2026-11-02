<?php

namespace App\Controllers\Admin;

use MF\Controller\Action;
use MF\Model\Container;

/**
 * Observabilidade 360°: visão por turma, disciplina e professor (issue #18)
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
     * Uma turma: disciplinas e alunos (com risco), abrindo o perfil do aluno
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

        $students = array_values($this->view->summary['students']);
        // em risco primeiro, depois pela menor média
        usort($students, function ($a, $b) {
            if ($a['atRisk'] !== $b['atRisk']) return $a['atRisk'] ? -1 : 1;
            return ($a['average'] ?? 99) <=> ($b['average'] ?? 99) ?: strcmp($a['name'], $b['name']);
        });
        $this->view->students = $students;

        $this->render('observability/class', 'AdminLayout');
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
            ? $Observability->summarize($Observability->collect($filters['period'], $filters['unit'], $filters['shift'], $classId), $rule, $filters['unit'], $openUnits)
            : ['classes' => [], 'disciplines' => [], 'teachers' => [], 'disciplineClasses' => [], 'students' => []];
    }


    /**
     * Período (padrão: o ativo), bimestre (padrão: ano inteiro) e turno, validados contra o banco
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

        $this->view->periods = $periods;
        $this->view->shifts = $shifts;

        return ['period' => $period, 'unit' => $unit, 'shift' => $shift];
    }


    private function query(array $filters)
    {

        return array_filter([
            'periodo' => $filters['period'] ?: null,
            'bimestre' => $filters['unit'],
            'turno' => $filters['shift'],
        ]);
    }
}
