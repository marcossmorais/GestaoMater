<?php

namespace App\Controllers\Admin;

use MF\Controller\Action;
use MF\Model\Container;
use App\Models\Admin\AuditLog;
use App\Tools\Audit;

/**
 * Auditoria (/admin/auditoria): quem lançou ou alterou o quê e quando, entradas nos portais e downloads.
 * Só o admin de nível 1 (Administrador): a tela mostra notas, nomes e acessos de toda a escola.
 */
class AdminAuditController extends Action
{

    private function allowed()
    {
        if (!isset($_SESSION)) session_start();

        if (isset($_SESSION['Admin']) && (int) $_SESSION['Admin']['hierarchyFunction'] === 1) return true;

        Audit::log(\App\Connection::getDb(), 'NEGADO', 'auditoria', null, 'tela de auditoria');
        http_response_code(403);
        echo 'Só o administrador principal acessa a auditoria.';
        return false;
    }


    /**
     * Filtros da URL; o aluno pode vir pelo nome (alunoNome): um só resultado já filtra, vários viram opções
     */
    private function resolveFilters(AuditLog $Log)
    {
        $filters = AuditLog::filters($_GET);
        $this->view->studentChoices = [];
        $this->view->studentName = mb_substr(trim((string) ($_GET['alunoNome'] ?? '')), 0, 80);

        if (!$filters['aluno'] && $this->view->studentName !== '') {
            $found = $Log->findStudents($this->view->studentName);
            if (count($found) === 1) $filters['aluno'] = (int) $found[0]->id;
            else $this->view->studentChoices = $found;
        }

        return $filters;
    }


    public function index()
    {
        if (!$this->allowed()) return;

        $Log = Container::getModel('Admin\\AuditLog');
        $filters = $this->resolveFilters($Log);

        $this->view->filters = $filters;
        $this->view->options = $Log->options($filters['aluno']);
        $this->view->result = $Log->search($filters);
        $this->view->total = $Log->count($filters);

        $this->render('audit/index', 'AdminLayout');
    }


    /**
     * CSV do filtro atual (abre no Excel). A própria exportação vai para a auditoria.
     */
    public function export()
    {
        if (!$this->allowed()) return;

        $Log = Container::getModel('Admin\\AuditLog');
        $filters = $this->resolveFilters($Log);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="auditoria-' . $filters['de'] . '-a-' . $filters['ate'] . '.csv"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM: o Excel abre os acentos certo
        fputcsv($out, ['Data e hora', 'Tipo', 'Quem', 'Ação', 'O quê', 'Resumo', 'Alterações', 'IP'], ';');

        // CSV aberto no Excel: célula que começa com = + - @ vira fórmula; prefixa com apóstrofo
        $cell = function ($v) { $v = (string) $v; return preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v; };

        $lines = $Log->export($filters, function ($r) use ($out, $cell) {
            $changes = implode('; ', array_map(function ($c) { return "$c[0]: $c[1] → $c[2]"; }, $r->changes));
            fputcsv($out, array_map($cell, [$r->quando, $r->who_type, $r->who, $r->action, $r->what, $r->summary, $changes, $r->ip]), ';');
        });
        fclose($out);

        Audit::log(\App\Connection::getDb(), 'EXPORTAR', 'auditoria', null, ['filtros' => array_diff_key($filters, ['pagina' => 1]), 'linhas' => $lines]);
    }
}
