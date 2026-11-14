<?php

namespace App\Models\Admin;

use MF\Model\Model;

/**
 * Leitura da trilha de auditoria (database/10_auditoria.sql) para a tela do admin (/admin/auditoria).
 *
 * Filtra por período, quem fez, o quê, ação, turma e aluno, e traduz cada linha em texto legível
 * ("Nota de Fulano na 1ª avaliação · Matemática 3ª A: 6,0 → 8,5"). A tabela é só de inserção: aqui só se lê.
 */
class AuditLog extends Model
{

    const PER_PAGE = 50;
    const EXPORT_LIMIT = 20000;

    // o que foi alterado (auditoria.entidade)
    const ENTITIES = [
        'nota_avaliacao' => 'Nota',
        'avaliacoes' => 'Avaliação',
        'avaliacao_tipo' => 'Tipo de avaliação',
        'falta_aluno' => 'Falta (por disciplina)',
        'chamada' => 'Chamada (por disciplina)',
        'chamada_diaria' => 'Chamada do dia (Fund. 1)',
        'diario_dia' => 'Diário do dia (Fund. 1)',
        'aula' => 'Diário de aula',
        'aula_atividade' => 'Atividade da aula',
        'plano_aula' => 'Plano de aula',
        'atestado_medico' => 'Atestado',
        'ocorrencia_disciplinar' => 'Ocorrência',
        'solicitacao_ocorrencia' => 'Pedido de ocorrência',
        'observacao_aluno' => 'Observação do aluno',
        'aviso_turma' => 'Aviso da turma',
        'aviso_geral' => 'Aviso geral',
        'evento_escolar' => 'Evento',
        'documento_emitido' => 'Documento emitido',
        'boletim' => 'Boletim',
        'declaracao_matricula' => 'Declaração de matrícula',
        'declaracao_frequencia' => 'Declaração de frequência',
        'codigos_acesso' => 'Códigos de acesso',
        'regra_aprovacao' => 'Regra de aprovação',
        'turma' => 'Turma',
        'substituicao_titular' => 'Substituição do titular',
        'grade_horario' => 'Grade de horário',
        'matricula' => 'Matrícula',
        'periodo_letivo' => 'Período letivo',
        'administrador' => 'Conta de admin',
        'professor' => 'Conta de professor',
        'aluno' => 'Conta de aluno',
        'auditoria' => 'Auditoria',
    ];

    const ACTIONS = [
        'INSERT' => 'Criou',
        'UPDATE' => 'Alterou',
        'DELETE' => 'Excluiu',
        'LOGIN' => 'Entrou',
        'LOGOUT' => 'Saiu',
        'LOGIN_NEGADO' => 'Entrada recusada',
        'NEGADO' => 'Acesso negado',
        'DOWNLOAD' => 'Baixou',
        'IMPORTACAO' => 'Importou',
        'VIRADA_ANO' => 'Virou o ano letivo',
        'EXPORTAR' => 'Exportou',
    ];

    const ACTOR_TYPES = ['admin' => 'Admin', 'docente' => 'Professor', 'aluno' => 'Aluno', 'sistema' => 'Sistema'];

    // campos que mudam sozinhos e não interessam no "antes → depois"
    const NOISE = ['atualizado_em', 'criado_em', 'data_postagem', 'analisado_em'];

    // nomes legíveis dos campos mais comuns
    const FIELDS = [
        'valor_nota' => 'Nota',
        'total_faltas' => 'Faltas',
        'faltas_abonadas' => 'Faltas abonadas',
        'total_presentes' => 'Presentes',
        'total_ausentes' => 'Ausentes',
        'matriculas_ausentes' => 'Ausentes (matrículas)',
        'texto' => 'Texto',
        'habilidades_bncc' => 'Habilidades BNCC',
        'status' => 'Situação',
        'resposta' => 'Resposta',
        'motivo' => 'Motivo',
        'data_inicio' => 'Início',
        'data_fim' => 'Fim',
        'data_aula' => 'Data da aula',
        'data_realizada' => 'Data da avaliação',
        'descricao_avaliacao' => 'Avaliação',
        'valor_avaliacao' => 'Valor',
        'descricao' => 'Descrição',
        'titulo' => 'Título',
        'aviso' => 'Aviso',
        'fixado' => 'Fixado',
        'tipo' => 'Tipo',
        'categoria' => 'Categoria',
        'gravidade' => 'Gravidade',
        'ciencia_em' => 'Ciência em',
        'observacao' => 'Observação',
        'fk_id_professor_titular' => 'Professor titular',
        'fk_id_professor_substituto' => 'Substituto',
        'media_aprovacao' => 'Média de aprovação',
        'nota_recuperacao' => 'Nota da recuperação',
        'frequencia_minima' => 'Frequência mínima',
        'revogado_em' => 'Revogado em',
    ];

    // onde cada tabela guarda aluno, matrícula, turma, disciplina da turma e avaliação (no JSON antes/depois)
    const STUDENT_KEYS = ['fk_id_aluno'];
    const ENROLLMENT_KEYS = ['fk_id_matricula_aluno', 'fk_id_matricula_falta', 'fk_id_matricula_observacao', 'fk_id_matricula'];
    const CLASS_KEYS = ['fk_id_turma'];
    const DISCIPLINE_CLASS_KEYS = ['fk_id_turma_disciplina', 'fk_id_turma_disciplina_falta', 'fk_id_turma_disciplina_observacao', 'fk_id_turma_disciplina_avaliacao'];
    const EXAM_KEYS = ['fk_id_avaliacao'];
    // eventos de aplicação cujo entidade_id é a matrícula
    const ENROLLMENT_ENTITIES = ['boletim', 'declaracao_matricula', 'declaracao_frequencia', 'matricula'];
    // tentativas negadas (acao = NEGADO) dessas entidades guardam a turma em entidade_id
    const CLASS_ID_ON_DENIAL = ['chamada_diaria', 'diario_dia', 'grade_horario'];


    /**
     * Normaliza os filtros vindos da URL
     */
    public static function filters(array $get)
    {
        $date = function ($v) { return is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) ? $v : null; };
        $today = (new \DateTime('now', new \DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');

        $filters = [
            'de' => $date($get['de'] ?? null) ?? date('Y-m-d', strtotime($today . ' -6 day')),
            'ate' => $date($get['ate'] ?? null) ?? $today,
            'quem' => in_array($get['quem'] ?? '', array_keys(self::ACTOR_TYPES), true) ? $get['quem'] : '',
            'nome' => mb_substr(trim((string) ($get['nome'] ?? '')), 0, 80),
            'oque' => preg_match('/^[a-z_]{1,60}$/', (string) ($get['oque'] ?? '')) ? $get['oque'] : '',
            'acao' => array_key_exists($get['acao'] ?? '', self::ACTIONS) ? $get['acao'] : '',
            'turma' => max(0, (int) ($get['turma'] ?? 0)),
            'aluno' => max(0, (int) ($get['aluno'] ?? 0)),
            'pagina' => max(1, (int) ($get['pagina'] ?? 1)),
        ];

        if ($filters['ate'] < $filters['de']) [$filters['de'], $filters['ate']] = [$filters['ate'], $filters['de']];

        return $filters;
    }


    /**
     * WHERE e parâmetros dos filtros
     */
    private function where(array $f)
    {
        $sql = ["a.criado_em >= (CAST(:de AS date))::timestamp AT TIME ZONE 'America/Sao_Paulo'",
                "a.criado_em < (CAST(:ate AS date) + 1)::timestamp AT TIME ZONE 'America/Sao_Paulo'"];
        $params = [':de' => $f['de'], ':ate' => $f['ate']];

        if ($f['quem'] !== '') { $sql[] = 'a.ator_tipo = :quem'; $params[':quem'] = $f['quem']; }
        if ($f['nome'] !== '') { $sql[] = 'a.ator_nome ILIKE :nome'; $params[':nome'] = '%' . addcslashes($f['nome'], '%_\\') . '%'; }
        if ($f['oque'] !== '') { $sql[] = 'a.entidade = :oque'; $params[':oque'] = $f['oque']; }
        if ($f['acao'] !== '') { $sql[] = 'a.acao = :acao'; $params[':acao'] = $f['acao']; }

        $json = 'COALESCE(a.depois, a.antes)';
        $anyKey = function (array $keys, $param) use ($json) {
            return implode(' OR ', array_map(function ($k) use ($json, $param) { return "$json ->> '$k' = ANY(CAST($param AS text[]))"; }, $keys));
        };
        $absentIn = "(jsonb_typeof($json -> 'matriculas_ausentes') = 'array' AND EXISTS (SELECT 1 FROM jsonb_array_elements_text($json -> 'matriculas_ausentes') x WHERE x = ANY(CAST(%s AS text[]))))";

        if ($f['aluno']) {
            $enrollments = $this->ids("SELECT id_matricula FROM matricula WHERE fk_id_aluno = :id", $f['aluno']);
            $sql[] = '(' . $anyKey(self::STUDENT_KEYS, ':alunoIds')
                . " OR (a.entidade = 'aluno' AND a.entidade_id = :alunoId)"
                . ' OR ' . $anyKey(self::ENROLLMENT_KEYS, ':alunoMats')
                . " OR (a.entidade IN ('" . implode("','", self::ENROLLMENT_ENTITIES) . "') AND a.entidade_id = ANY(CAST(:alunoMats AS text[])))"
                . ' OR ' . sprintf($absentIn, ':alunoMats') . ')';
            $params[':alunoIds'] = '{' . $f['aluno'] . '}';
            $params[':alunoId'] = (string) $f['aluno'];
            $params[':alunoMats'] = $this->pgArray($enrollments);
        }

        if ($f['turma']) {
            $disciplineClasses = $this->ids("SELECT id_turma_disciplina FROM turma_disciplina WHERE fk_id_turma = :id", $f['turma']);
            $enrollments = $this->ids("SELECT id_matricula FROM matricula WHERE fk_id_turma_matricula = :id", $f['turma']);
            $exams = $this->ids("SELECT a.id_avaliacao FROM avaliacoes a INNER JOIN turma_disciplina td ON (td.id_turma_disciplina = a.fk_id_turma_disciplina_avaliacao) WHERE td.fk_id_turma = :id", $f['turma']);
            $sql[] = '(' . $anyKey(self::CLASS_KEYS, ':turmaIds')
                // entidade_id é a turma só na própria turma e nas tentativas negadas (nas outras linhas é o id do registro)
                . " OR (a.entidade = 'turma' AND a.entidade_id = :turmaId)"
                . " OR (a.acao = 'NEGADO' AND a.entidade IN ('" . implode("','", self::CLASS_ID_ON_DENIAL) . "') AND a.entidade_id = :turmaId)"
                . ' OR ' . $anyKey(self::DISCIPLINE_CLASS_KEYS, ':turmaTds')
                . ' OR ' . $anyKey(self::EXAM_KEYS, ':turmaExams')
                . ' OR ' . $anyKey(self::ENROLLMENT_KEYS, ':turmaMats')
                . " OR (a.entidade IN ('" . implode("','", self::ENROLLMENT_ENTITIES) . "') AND a.entidade_id = ANY(CAST(:turmaMats AS text[])))"
                . ')';
            $params[':turmaIds'] = '{' . $f['turma'] . '}';
            $params[':turmaId'] = (string) $f['turma'];
            $params[':turmaTds'] = $this->pgArray($disciplineClasses);
            $params[':turmaExams'] = $this->pgArray($exams);
            $params[':turmaMats'] = $this->pgArray($enrollments);
        }

        return [implode(' AND ', $sql), $params];
    }


    /**
     * Uma página de registros (mais recentes primeiro), já descritos; 'more' diz se há próxima página
     */
    public function search(array $f)
    {
        [$where, $params] = $this->where($f);

        $stmt = $this->db->prepare(
            "SELECT a.*, TO_CHAR(a.criado_em AT TIME ZONE 'America/Sao_Paulo', 'DD/MM/YYYY HH24:MI:SS') AS quando
             FROM auditoria a WHERE $where
             ORDER BY a.id_auditoria DESC
             LIMIT " . (self::PER_PAGE + 1) . ' OFFSET ' . (($f['pagina'] - 1) * self::PER_PAGE)
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $more = count($rows) > self::PER_PAGE;
        $rows = array_slice($rows, 0, self::PER_PAGE);

        return ['rows' => $this->describeAll($rows), 'more' => $more];
    }


    /**
     * Total do filtro (até 10.000, para não pesar)
     */
    public function count(array $f)
    {
        [$where, $params] = $this->where($f);

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM (SELECT 1 FROM auditoria a WHERE $where LIMIT 10001) x");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }


    /**
     * Todos os registros do filtro para o CSV (até EXPORT_LIMIT), em lotes para resolver os nomes
     */
    public function export(array $f, callable $write)
    {
        [$where, $params] = $this->where($f);

        $stmt = $this->db->prepare(
            "SELECT a.*, TO_CHAR(a.criado_em AT TIME ZONE 'America/Sao_Paulo', 'DD/MM/YYYY HH24:MI:SS') AS quando
             FROM auditoria a WHERE $where ORDER BY a.id_auditoria DESC LIMIT " . self::EXPORT_LIMIT
        );
        $stmt->execute($params);

        $total = 0;
        $batch = [];
        while ($row = $stmt->fetch(\PDO::FETCH_OBJ)) {
            $batch[] = $row;
            if (count($batch) === 500) { foreach ($this->describeAll($batch) as $r) $write($r); $total += 500; $batch = []; }
        }
        foreach ($this->describeAll($batch) as $r) $write($r);

        return $total + count($batch);
    }


    /**
     * Opções dos filtros: o que existe na auditoria, turmas do período ativo e o aluno escolhido
     */
    public function options($studentId)
    {
        $entities = [];
        foreach ($this->db->query("SELECT DISTINCT entidade FROM auditoria")->fetchAll(\PDO::FETCH_COLUMN) as $e) {
            $entities[$e] = self::ENTITIES[$e] ?? $e;
        }
        asort($entities);

        $classes = $this->db->query(
            "SELECT t.id_turma AS id, serie.sigla || 'ª ' || cedula_turma.cedula || ' · ' || curso.sigla || ' · ' || turno.nome_turno AS label
             FROM turma t
             INNER JOIN serie ON (serie.id_serie = t.fk_id_serie)
             INNER JOIN cedula_turma ON (cedula_turma.id_cedula_turma = t.fk_id_cedula)
             INNER JOIN curso ON (curso.id_curso = t.fk_id_curso)
             INNER JOIN turno ON (turno.id_turno = t.fk_id_turno)
             INNER JOIN periodo_letivo pl ON (pl.id_ano_letivo = t.fk_id_periodo_letivo)
             WHERE pl.fk_id_situacao_periodo_letivo = 1
             ORDER BY curso.sigla, serie.sigla, cedula_turma.cedula"
        )->fetchAll(\PDO::FETCH_OBJ);

        $student = null;
        if ($studentId) {
            $stmt = $this->db->prepare("SELECT nome_aluno FROM aluno WHERE id_aluno = :id");
            $stmt->execute([':id' => (int) $studentId]);
            $student = $stmt->fetchColumn() ?: null;
        }

        return ['entities' => $entities, 'classes' => $classes, 'student' => $student];
    }


    /**
     * Alunos pelo nome (busca do filtro): até 10
     */
    public function findStudents($name)
    {
        $name = trim((string) $name);
        if (mb_strlen($name) < 3) return [];

        $stmt = $this->db->prepare(
            "SELECT id_aluno AS id, nome_aluno AS name FROM aluno WHERE nome_aluno ILIKE :name ORDER BY nome_aluno LIMIT 10"
        );
        $stmt->execute([':name' => '%' . addcslashes($name, '%_\\') . '%']);

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    // ------------------------------------------------------------------ Texto legível

    /**
     * Acrescenta a cada linha: who, action, what, summary e changes [[campo, antes, depois], ...]
     */
    private function describeAll(array $rows)
    {
        if (!$rows) return [];

        $ctx = $this->context($rows);

        foreach ($rows as $r) {
            $before = $r->antes ? json_decode($r->antes, true) : null;
            $after = $r->depois ? json_decode($r->depois, true) : null;
            $data = $after ?? $before ?? [];
            if (!is_array($data)) $data = ['detalhe' => $data];

            $r->who = $r->ator_nome ?: (self::ACTOR_TYPES[$r->ator_tipo] ?? $r->ator_tipo);
            $r->who_type = self::ACTOR_TYPES[$r->ator_tipo] ?? $r->ator_tipo;
            $r->action = self::ACTIONS[$r->acao] ?? $r->acao;
            $r->what = self::ENTITIES[$r->entidade] ?? $r->entidade;
            $r->summary = $this->summary($r, $data, $ctx);
            $r->changes = $r->acao === 'UPDATE' && is_array($before) && is_array($after) ? $this->changes($r->entidade, $before, $after, $ctx) : [];
        }

        return $rows;
    }


    /**
     * Nomes de alunos, turmas, disciplinas, avaliações e professores citados nas linhas (uma consulta por tipo)
     */
    private function context(array $rows)
    {
        $want = ['student' => [], 'enrollment' => [], 'class' => [], 'dc' => [], 'exam' => [], 'teacher' => []];

        foreach ($rows as $r) {
            foreach ([$r->antes, $r->depois] as $json) {
                $d = $json ? json_decode($json, true) : null;
                if (!is_array($d)) continue;
                foreach (self::STUDENT_KEYS as $k) if (isset($d[$k])) $want['student'][] = (int) $d[$k];
                foreach (self::ENROLLMENT_KEYS as $k) if (isset($d[$k])) $want['enrollment'][] = (int) $d[$k];
                foreach (self::CLASS_KEYS as $k) if (isset($d[$k])) $want['class'][] = (int) $d[$k];
                foreach (self::DISCIPLINE_CLASS_KEYS as $k) if (isset($d[$k])) $want['dc'][] = (int) $d[$k];
                foreach (self::EXAM_KEYS as $k) if (isset($d[$k])) $want['exam'][] = (int) $d[$k];
                foreach (['fk_id_professor', 'fk_id_professor_titular', 'fk_id_professor_substituto'] as $k) if (isset($d[$k])) $want['teacher'][] = (int) $d[$k];
            }
            if (in_array($r->entidade, self::ENROLLMENT_ENTITIES, true) && ctype_digit((string) $r->entidade_id)) $want['enrollment'][] = (int) $r->entidade_id;
            $classInId = $r->entidade === 'turma' || ($r->acao === 'NEGADO' && in_array($r->entidade, self::CLASS_ID_ON_DENIAL, true));
            if ($classInId && ctype_digit((string) $r->entidade_id)) $want['class'][] = (int) $r->entidade_id;
            if ($r->entidade === 'aluno' && ctype_digit((string) $r->entidade_id)) $want['student'][] = (int) $r->entidade_id;
            if ($r->entidade === 'professor' && ctype_digit((string) $r->entidade_id)) $want['teacher'][] = (int) $r->entidade_id;
        }

        $classLabel = "serie.sigla || 'ª ' || cedula_turma.cedula || ' ' || curso.sigla";
        $classJoin = "INNER JOIN serie ON (serie.id_serie = t.fk_id_serie)
                      INNER JOIN cedula_turma ON (cedula_turma.id_cedula_turma = t.fk_id_cedula)
                      INNER JOIN curso ON (curso.id_curso = t.fk_id_curso)";

        return [
            'student' => $this->names("SELECT id_aluno, nome_aluno FROM aluno WHERE id_aluno = ANY(CAST(:ids AS int[]))", $want['student']),
            'enrollment' => $this->names(
                "SELECT m.id_matricula, a.nome_aluno || ' (' || $classLabel || ')' FROM matricula m
                 INNER JOIN aluno a ON (a.id_aluno = m.fk_id_aluno) INNER JOIN turma t ON (t.id_turma = m.fk_id_turma_matricula) $classJoin
                 WHERE m.id_matricula = ANY(CAST(:ids AS int[]))", $want['enrollment']),
            'class' => $this->names("SELECT t.id_turma, $classLabel FROM turma t $classJoin WHERE t.id_turma = ANY(CAST(:ids AS int[]))", $want['class']),
            'dc' => $this->names(
                "SELECT td.id_turma_disciplina, d.nome_disciplina || ' · ' || $classLabel FROM turma_disciplina td
                 INNER JOIN disciplina d ON (d.id_disciplina = td.fk_id_disciplina) INNER JOIN turma t ON (t.id_turma = td.fk_id_turma) $classJoin
                 WHERE td.id_turma_disciplina = ANY(CAST(:ids AS int[]))", $want['dc']),
            'exam' => $this->names(
                "SELECT av.id_avaliacao, av.descricao_avaliacao || ' · ' || d.nome_disciplina || ' · ' || $classLabel FROM avaliacoes av
                 INNER JOIN turma_disciplina td ON (td.id_turma_disciplina = av.fk_id_turma_disciplina_avaliacao)
                 INNER JOIN disciplina d ON (d.id_disciplina = td.fk_id_disciplina) INNER JOIN turma t ON (t.id_turma = td.fk_id_turma) $classJoin
                 WHERE av.id_avaliacao = ANY(CAST(:ids AS int[]))", $want['exam']),
            'teacher' => $this->names("SELECT id_professor, nome_professor FROM professor WHERE id_professor = ANY(CAST(:ids AS int[]))", $want['teacher']),
        ];
    }


    private function summary($r, array $d, array $ctx)
    {
        // registro que já não existe (ex.: avaliação apagada): diz o que era e o número
        $gone = ['student' => 'aluno', 'enrollment' => 'matrícula', 'class' => 'turma', 'dc' => 'disciplina', 'exam' => 'avaliação', 'teacher' => 'professor'];
        $n = function ($type, $id) use ($ctx, $gone) { return $id === null ? null : ($ctx[$type][(int) $id] ?? "{$gone[$type]} #$id (não existe mais)"); };
        $enrollment = null;
        foreach (self::ENROLLMENT_KEYS as $k) if (isset($d[$k])) { $enrollment = $n('enrollment', $d[$k]); break; }
        $dc = null;
        foreach (self::DISCIPLINE_CLASS_KEYS as $k) if (isset($d[$k])) { $dc = $n('dc', $d[$k]); break; }
        $date = function ($v) { return $v && strtotime($v) ? date('d/m/Y', strtotime($v)) : null; };
        $parts = function (...$p) { return implode(' · ', array_filter($p, function ($x) { return $x !== null && $x !== ''; })); };

        // entrada, saída e eventos de aplicação
        if (in_array($r->acao, ['LOGIN', 'LOGOUT'], true)) return $parts(self::ENTITIES[$r->entidade] ?? $r->entidade, $r->ip ? 'IP ' . $r->ip : null);
        if ($r->acao === 'LOGIN_NEGADO') return $parts(self::ENTITIES[$r->entidade] ?? $r->entidade, 'motivo: ' . ($d['motivo'] ?? '—'), $r->ip ? 'IP ' . $r->ip : null);
        if ($r->acao === 'NEGADO') {
            $detail = $d['motivo'] ?? $d['detalhe'] ?? null;
            $target = $r->entidade_id && in_array($r->entidade, self::CLASS_ID_ON_DENIAL, true) ? $n('class', $r->entidade_id) : ($r->entidade_id ? '#' . $r->entidade_id : null);
            return $parts('tentou ' . mb_strtolower(self::ENTITIES[$r->entidade] ?? $r->entidade), $target, isset($d['data']) ? $date($d['data']) : null, is_string($detail) ? $detail : null);
        }
        if ($r->acao === 'DOWNLOAD') {
            $who = in_array($r->entidade, self::ENROLLMENT_ENTITIES, true) && $r->entidade_id ? $n('enrollment', $r->entidade_id) : null;
            return $parts(self::ENTITIES[$r->entidade] ?? $r->entidade, $who, isset($d['codigo']) ? 'código ' . $d['codigo'] : (isset($d['total']) ? $d['total'] . ' códigos' : null));
        }
        if ($r->acao === 'EXPORTAR') return 'CSV da auditoria · ' . ($d['linhas'] ?? 0) . ' registros';

        switch ($r->entidade) {
            case 'nota_avaliacao':
                return $parts($enrollment, $n('exam', $d['fk_id_avaliacao'] ?? null), 'nota ' . $this->grade($d['valor_nota'] ?? null));
            case 'falta_aluno':
                return $parts($enrollment, $dc, ($d['fk_id_unidade_falta'] ?? '?') . 'º bim.', ($d['total_faltas'] ?? 0) . ' falta(s)');
            case 'chamada':
                return $parts($dc, $date($d['data_aula'] ?? null));
            case 'chamada_diaria':
                return $parts($n('class', $d['fk_id_turma'] ?? null), $date($d['data_aula'] ?? null), ($d['total_ausentes'] ?? 0) . ' ausente(s)');
            case 'diario_dia':
                return $parts($n('class', $d['fk_id_turma'] ?? null), $date($d['data_aula'] ?? null), $this->short($d['texto'] ?? null));
            case 'aula':
                return $parts($dc, $date($d['data_aula'] ?? null), $d['habilidade_bncc_codigo'] ?? null);
            case 'avaliacoes':
                return $parts($d['descricao_avaliacao'] ?? null, $dc, $date($d['data_realizada'] ?? null));
            case 'atestado_medico':
                return $parts($n('student', $d['fk_id_aluno'] ?? null), $date($d['data_inicio'] ?? null) . ' a ' . $date($d['data_fim'] ?? null), $d['status'] ?? null);
            case 'ocorrencia_disciplinar':
                return $parts($n('student', $d['fk_id_aluno'] ?? null), $d['categoria'] ?? null, $d['titulo'] ?? null);
            case 'observacao_aluno':
                return $parts($enrollment, $dc, $this->short($d['descricao'] ?? null));
            case 'aviso_turma':
                return $parts($dc, $this->short($d['aviso'] ?? null));
            case 'aviso_geral':
                return $parts(isset($d['fk_id_turma']) ? $n('class', $d['fk_id_turma']) : 'toda a escola', $this->short($d['aviso'] ?? null));
            case 'evento_escolar':
                return $parts($d['titulo'] ?? null, $date($d['data_inicio'] ?? null));
            case 'documento_emitido':
                return $parts($d['tipo'] ?? null, $d['aluno_nome'] ?? null, isset($d['codigo']) ? 'código ' . $d['codigo'] : null);
            case 'turma':
                return $parts($n('class', $r->entidade_id), isset($d['fk_id_professor_titular']) ? 'titular: ' . $n('teacher', $d['fk_id_professor_titular']) : null);
            case 'substituicao_titular':
                return $parts($n('class', $d['fk_id_turma'] ?? null), $n('teacher', $d['fk_id_professor_substituto'] ?? null), $date($d['data_inicio'] ?? null) . ' a ' . $date($d['data_fim'] ?? null));
        }

        return $parts($dc ?? $enrollment, $r->entidade_id ? 'registro #' . $r->entidade_id : null);
    }


    private function changes($entity, array $before, array $after, array $ctx)
    {
        $out = [];
        foreach ($after as $k => $new) {
            $old = $before[$k] ?? null;
            if ($old === $new || in_array($k, self::NOISE, true)) continue;
            if (str_starts_with($k, 'id_')) continue;

            $label = self::FIELDS[$k] ?? ucfirst(str_replace('_', ' ', preg_replace('/^fk_id_/', '', $k)));
            $format = function ($v) use ($k, $ctx) {
                if ($v === null || $v === '' || $v === []) return '—';
                if (in_array($k, ['fk_id_professor_titular', 'fk_id_professor_substituto'], true)) return $ctx['teacher'][(int) $v] ?? "#$v";
                if ($k === 'valor_nota') return $this->grade($v);
                if (is_bool($v)) return $v ? 'sim' : 'não';
                if (is_array($v)) return implode(', ', array_map('strval', $v));
                if (is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2})?$/', $v)) return date(strlen($v) > 10 ? 'd/m/Y H:i' : 'd/m/Y', strtotime($v));
                return $this->short((string) $v, 160);
            };
            $out[] = [$label, $format($old), $format($new)];
        }

        return $out;
    }


    // ------------------------------------------------------------------ apoio

    private function grade($v)
    {
        return $v === null ? '—' : number_format((float) $v, 1, ',', '');
    }

    private function short($text, $max = 80)
    {
        if ($text === null) return null;
        $text = trim(preg_replace('/\s+/', ' ', (string) $text));
        return mb_strlen($text) > $max ? mb_substr($text, 0, $max - 1) . '…' : $text;
    }

    private function pgArray(array $ids)
    {
        return '{' . implode(',', array_map('intval', $ids)) . '}';
    }

    private function ids($sql, $id)
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => (int) $id]);

        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    private function names($sql, array $ids)
    {
        $ids = array_values(array_unique(array_filter($ids)));
        if (!$ids) return [];

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ids' => $this->pgArray($ids)]);

        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
}
