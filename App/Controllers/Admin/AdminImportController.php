<?php

namespace App\Controllers\Admin;

use MF\Controller\Action;
use MF\Model\Container;
use App\Models\Admin\Importer;
use App\Models\Admin\YearRollover;
use App\Tools\ImportParser;

/**
 * Importação por CSV/JSON com prévia e virada de ano (issue #19)
 */
class AdminImportController extends Action
{

    const MAX_BYTES = 2 * 1024 * 1024;


    public function index()
    {

        $Importer = Container::getModel('Admin\\Importer');

        $this->view->tab = ($_GET['aba'] ?? '') === 'virada' ? 'virada' : 'arquivo';
        $this->view->activePeriod = $Importer->activePeriod();
        $this->view->classes = $Importer->activeClasses();
        $this->view->type = array_key_exists($_GET['tipo'] ?? '', Importer::TYPES) ? $_GET['tipo'] : 'alunos';
        $this->view->error = $_GET['erro'] ?? null;

        if ($this->view->tab === 'virada') $this->rolloverView();

        $this->render('import/index', 'AdminLayout');
    }


    /**
     * Planilha-modelo com o cabeçalho e uma linha de exemplo (separador ; e BOM para abrir certo no Excel)
     */
    public function template()
    {

        $type = array_key_exists($_GET['tipo'] ?? '', Importer::TYPES) ? $_GET['tipo'] : 'alunos';
        $Importer = Container::getModel('Admin\\Importer');

        $classes = $Importer->activeClasses();
        $classLabel = $classes ? str_replace(' · ', ' ', $classes[0]['label']) : '1ª A EM Matutino';

        $examples = [
            'nome' => 'Nome Sobrenome', 'cpf' => '000.000.000-00', 'data_nascimento' => '15/03/2010', 'sexo' => 'F',
            'turma' => $classLabel, 'email' => 'nome@exemplo.com', 'telefone' => '(75) 99999-0000', 'tipo_sanguineo' => 'O+',
            'pcd' => 'não', 'uf' => 'BA', 'serie' => '1', 'curso' => 'EM', 'turno' => 'Matutino', 'sala' => '1',
        ];
        if ($type === 'turmas') $examples['turma'] = 'A';

        $columns = array_keys(Importer::columns($type));

        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $columns, ';', '"', '');
        fputcsv($out, array_map(function ($c) use ($examples) { return $examples[$c] ?? ''; }, $columns), ';', '"', '');
        rewind($out);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="modelo-' . $type . '.csv"');
        echo stream_get_contents($out);
    }


    public function preview()
    {

        if (session_status() === PHP_SESSION_NONE) session_start();

        $type = $_POST['tipo'] ?? '';
        if (!array_key_exists($type, Importer::TYPES)) return $this->back('Escolha o que importar.');

        $file = $_FILES['arquivo'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return $this->back('Selecione um arquivo CSV ou JSON.', $type);
        if ($file['size'] > self::MAX_BYTES) return $this->back('O arquivo passa de 2 MB.', $type);

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['csv', 'txt', 'json'], true)) return $this->back('Use um arquivo .csv ou .json.', $type);

        $parsed = ImportParser::parse(file_get_contents($file['tmp_name']), $extension);
        if ($parsed['error']) return $this->back($parsed['error'], $type);

        $Importer = Container::getModel('Admin\\Importer');
        if (!$Importer->activePeriod()) return $this->back('Não há período letivo ativo para receber a importação.', $type);

        $preview = $Importer->preview($type, $parsed['rows']);
        $fileName = basename($file['name']);

        // só as linhas que serão gravadas ficam na sessão, presas a um token de uso único
        $token = bin2hex(random_bytes(16));
        $_SESSION['adminImport'] = [
            'token' => $token,
            'type' => $type,
            'file' => $fileName,
            'rows' => array_values(array_filter($preview['rows'], function ($r) { return in_array($r['status'], ['novo', 'matricular'], true); })),
            'blocked' => $preview['counts']['erro'] > 0 || $preview['missing'],
        ];

        $this->view->preview = $preview;
        $this->view->token = $token;
        $this->view->fileName = $fileName;
        $this->view->typeLabel = Importer::TYPES[$type];

        $this->render('import/preview', 'AdminLayout');
    }


    public function confirm()
    {

        if (session_status() === PHP_SESSION_NONE) session_start();

        $pending = $_SESSION['adminImport'] ?? null;
        unset($_SESSION['adminImport']);

        if (!$pending || !hash_equals($pending['token'], (string) ($_POST['token'] ?? ''))) {
            return $this->back('A prévia expirou. Envie o arquivo de novo.');
        }

        // tudo ou nada: com erro no arquivo, nada é gravado
        if ($pending['blocked']) return $this->back('Corrija os erros do arquivo antes de importar.', $pending['type']);
        if (!$pending['rows']) return $this->back('Nenhuma linha nova para importar.', $pending['type']);

        try {
            $result = Container::getModel('Admin\\Importer')->commit($pending['type'], $pending['rows'], $pending['file']);
        } catch (\Throwable $e) {
            error_log('importacao: ' . $e->getMessage());
            return $this->back('Nada foi gravado: houve um erro ao salvar. Tente de novo.', $pending['type']);
        }

        $_SESSION['adminImportCodes'] = $result['codes'];

        $this->view->result = $result;
        $this->view->type = $pending['type'];
        $this->view->typeLabel = Importer::TYPES[$pending['type']];
        $this->view->fileName = $pending['file'];

        $this->render('import/result', 'AdminLayout');
    }


    /**
     * Códigos de acesso gerados na última importação (só nesta sessão, uma vez)
     */
    public function codes()
    {

        if (session_status() === PHP_SESSION_NONE) session_start();

        $codes = $_SESSION['adminImportCodes'] ?? [];
        unset($_SESSION['adminImportCodes']);

        if (!$codes) {
            header('Location: /admin/importacao');
            return;
        }

        \App\Tools\Audit::log(\App\Connection::getDb(), 'DOWNLOAD', 'codigos_acesso', null, ['total' => count($codes)]);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="codigos-de-acesso.csv"');
        header('Cache-Control: no-store');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['nome', 'codigo_acesso'], ';', '"', '');
        foreach ($codes as $row) fputcsv($out, $row, ';', '"', '');
        fclose($out);
    }


    public function rolloverConfirm()
    {

        $Rollover = Container::getModel('Admin\\YearRollover');
        [$origin, $target, $pending] = $this->rolloverParams($Rollover, $_POST);

        $query = ['aba' => 'virada', 'origem' => $origin, 'destino' => $target, 'sem_resultado' => $pending];

        if (!$origin || !$target || $origin === $target) {
            header('Location: /admin/importacao?' . http_build_query($query + ['erro' => 'Escolha períodos de origem e destino diferentes.']));
            return;
        }

        try {
            $result = $Rollover->execute($origin, $target, $pending, $_POST['hash'] ?? '');
        } catch (\RuntimeException $e) {
            header('Location: /admin/importacao?' . http_build_query($query + ['erro' => $e->getMessage()]));
            return;
        } catch (\Throwable $e) {
            error_log('virada de ano: ' . $e->getMessage());
            header('Location: /admin/importacao?' . http_build_query($query + ['erro' => 'Nada foi gravado: houve um erro ao salvar.']));
            return;
        }

        header('Location: /admin/importacao?' . http_build_query($query + ['feito' => http_build_query($result)]));
    }


    private function rolloverView()
    {

        $Rollover = Container::getModel('Admin\\YearRollover');
        [$origin, $target, $pending] = $this->rolloverParams($Rollover, $_GET);

        $this->view->periods = $Rollover->periods();
        $this->view->origin = $origin;
        $this->view->target = $target;
        $this->view->pending = $pending;
        $this->view->pendingOptions = YearRollover::PENDING_OPTIONS;
        $this->view->plan = ($origin && $target && $origin !== $target && isset($_GET['previa'])) ? $Rollover->plan($origin, $target, $pending) : null;

        parse_str((string) ($_GET['feito'] ?? ''), $done);
        $this->view->done = $done ?: null;
    }


    /**
     * Origem padrão: período ativo; destino padrão: o primeiro agendado depois dele
     */
    private function rolloverParams($Rollover, array $input)
    {

        $periods = $Rollover->periods();
        $ids = array_map(function ($p) { return (int) $p->id; }, $periods);

        $origin = (int) ($input['origem'] ?? 0);
        $target = (int) ($input['destino'] ?? 0);

        if (!in_array($origin, $ids, true)) {
            $active = array_values(array_filter($periods, function ($p) { return (int) $p->situation_id === 1; }));
            $origin = $active ? (int) $active[0]->id : 0;
        }

        if (!in_array($target, $ids, true)) {
            $originYear = 0;
            foreach ($periods as $p) if ((int) $p->id === $origin) $originYear = (int) $p->year;
            $next = array_values(array_filter($periods, function ($p) use ($originYear) { return (int) $p->year > $originYear; }));
            $target = $next ? (int) $next[0]->id : 0;
        }

        $pending = array_key_exists($input['sem_resultado'] ?? '', YearRollover::PENDING_OPTIONS) ? $input['sem_resultado'] : 'ignorar';

        return [$origin, $target, $pending];
    }


    private function back($message, $type = null)
    {

        header('Location: /admin/importacao?' . http_build_query(array_filter(['tipo' => $type, 'erro' => $message])));
    }
}
