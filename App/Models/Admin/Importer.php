<?php

namespace App\Models\Admin;

use MF\Model\Model;
use App\Tools\Audit;
use App\Tools\ImportParser as P;

/**
 * Importação de alunos, professores e turmas por CSV/JSON (issue #19).
 *
 * preview() valida cada linha sem gravar nada; commit() grava as linhas válidas numa transação única
 * (se uma falhar, nada fica gravado) e registra um resumo na auditoria (sem dados pessoais).
 * O período de destino é sempre o período letivo ativo.
 */
class Importer extends Model
{

    /**
     * Colunas aceitas por tipo: chave => [rótulo, obrigatória, outros nomes aceitos no cabeçalho]
     */
    const PERSON = [
        'nome' => ['Nome completo', true, ['nome_completo']],
        'cpf' => ['CPF', true, []],
        'data_nascimento' => ['Data de nascimento', true, ['data_de_nascimento', 'nascimento']],
        'sexo' => ['Sexo (M ou F)', true, []],
        'email' => ['E-mail', false, ['e_mail']],
        'telefone' => ['Telefone', false, ['celular', 'fone']],
        'naturalidade' => ['Naturalidade', false, ['cidade_natal']],
        'nacionalidade' => ['Nacionalidade', false, []],
        'tipo_sanguineo' => ['Tipo sanguíneo', false, ['tipo_sangue']],
        'pcd' => ['PcD (sim ou não)', false, ['deficiencia']],
        'cep' => ['CEP', false, []],
        'endereco' => ['Endereço', false, ['logradouro', 'rua']],
        'bairro' => ['Bairro', false, []],
        'municipio' => ['Município', false, ['cidade']],
        'uf' => ['UF', false, ['estado']],
        'codigo_acesso' => ['Código de acesso (gerado se vazio)', false, ['codigo', 'codigo_de_acesso']],
    ];

    const TYPES = [
        'alunos' => 'Alunos',
        'professores' => 'Professores',
        'turmas' => 'Turmas',
    ];

    private $lookups;


    public static function columns($type)
    {

        if ($type === 'turmas') {
            return [
                'serie' => ['Série (1, 2, 3…)', true, ['ano']],
                'turma' => ['Turma (A, B, C…)', true, ['letra', 'cedula']],
                'curso' => ['Curso (sigla ou nome)', true, ['etapa']],
                'turno' => ['Turno', true, []],
                'sala' => ['Número da sala', true, ['numero_sala']],
            ];
        }

        $columns = self::PERSON;

        if ($type === 'alunos') {
            $columns = array_slice($columns, 0, 4, true)
                + ['turma' => ['Turma (ex.: 1ª A EM Matutino)', true, ['classe']]]
                + ['nome_mae' => ['Nome da mãe', false, ['mae', 'nome_da_mae']], 'nome_pai' => ['Nome do pai', false, ['pai', 'nome_do_pai']]]
                + array_slice($columns, 4, null, true);
        }

        return $columns;
    }


    /**
     * Período letivo ativo: [id, ano] ou null
     */
    public function activePeriod()
    {

        $row = $this->db->query(
            "SELECT periodo_letivo.id_ano_letivo AS id, periodo_disponivel.ano_letivo AS year
             FROM periodo_letivo
             INNER JOIN periodo_disponivel ON (periodo_letivo.fk_id_ano_letivo = periodo_disponivel.id_periodo_disponivel)
             WHERE periodo_letivo.fk_id_situacao_periodo_letivo = 1
             ORDER BY periodo_letivo.id_ano_letivo DESC LIMIT 1"
        )->fetch(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }


    /**
     * Turmas do período ativo com o nome que o admin vê ("1ª A · EM · Matutino")
     */
    public function activeClasses()
    {

        $period = $this->activePeriod();
        if (!$period) return [];

        $stmt = $this->db->prepare(
            "SELECT turma.id_turma AS id, serie.sigla AS serie, cedula_turma.cedula AS letter, curso.sigla AS course, turno.nome_turno AS shift,
                    serie.sigla || 'ª ' || cedula_turma.cedula || ' · ' || curso.sigla || ' · ' || turno.nome_turno AS label
             FROM turma
             INNER JOIN serie ON (turma.fk_id_serie = serie.id_serie)
             INNER JOIN cedula_turma ON (turma.fk_id_cedula = cedula_turma.id_cedula_turma)
             INNER JOIN curso ON (turma.fk_id_curso = curso.id_curso)
             INNER JOIN turno ON (turma.fk_id_turno = turno.id_turno)
             WHERE turma.fk_id_periodo_letivo = :period
             ORDER BY serie.sigla, cedula_turma.cedula, curso.sigla"
        );
        $stmt->execute([':period' => (int) $period['id']]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }


    /**
     * Valida as linhas. Cada item: line, status (novo | matricular | ignorar | erro), messages, data, summary
     */
    public function preview($type, array $rows)
    {

        $columns = self::columns($type);
        $result = ['type' => $type, 'rows' => [], 'unknown' => [], 'missing' => [], 'counts' => []];

        // cabeçalho: aceita os nomes alternativos e avisa das colunas que serão ignoradas
        $aliases = [];
        foreach ($columns as $key => [, , $others]) {
            $aliases[$key] = $key;
            foreach ($others as $other) $aliases[$other] = $key;
        }

        $present = [];
        foreach ($rows as $row) foreach (array_keys($row) as $k) $present[$k] = true;
        foreach (array_keys($present) as $k) {
            if (!isset($aliases[$k])) $result['unknown'][] = $k;
        }

        $mapped = [];
        foreach ($rows as $row) {
            $item = [];
            foreach ($row as $k => $v) {
                if (isset($aliases[$k]) && (!isset($item[$aliases[$k]]) || $item[$aliases[$k]] === '')) $item[$aliases[$k]] = $v;
            }
            $mapped[] = $item;
        }

        $found = [];
        foreach ($mapped as $item) foreach ($item as $k => $v) $found[$k] = true;
        foreach ($columns as $key => [$label, $required]) {
            if ($required && !isset($found[$key])) $result['missing'][] = $label;
        }

        $seen = [];
        foreach ($mapped as $i => $item) {
            $line = $i + 2; // linha 1 é o cabeçalho
            $checked = $type === 'turmas' ? $this->checkClass($item) : $this->checkPerson($type, $item);

            // repetido no próprio arquivo
            if ($checked['key'] !== null && $checked['status'] !== 'erro') {
                if (isset($seen[$checked['key']])) {
                    $checked['status'] = 'erro';
                    $checked['messages'][] = 'Repetido no arquivo (igual à linha ' . $seen[$checked['key']] . ').';
                } else {
                    $seen[$checked['key']] = $line;
                }
            }

            $result['rows'][] = ['line' => $line] + $checked;
        }

        foreach (['novo', 'matricular', 'ignorar', 'erro'] as $status) {
            $result['counts'][$status] = count(array_filter($result['rows'], function ($r) use ($status) { return $r['status'] === $status; }));
        }

        return $result;
    }


    private function checkPerson($type, array $item)
    {

        $l = $this->lookups();
        $messages = [];
        $get = function ($k) use ($item) { return trim((string) ($item[$k] ?? '')); };

        $name = preg_replace('/\s+/', ' ', $get('nome'));
        if (mb_strlen($name) < 3) $messages[] = 'Nome obrigatório.';

        $cpf = P::cpf($get('cpf'));
        if ($cpf === null) $messages[] = $get('cpf') === '' ? 'CPF obrigatório.' : 'CPF inválido.';

        $birth = P::date($get('data_nascimento'));
        if ($birth === null) $messages[] = $get('data_nascimento') === '' ? 'Data de nascimento obrigatória.' : 'Data de nascimento inválida (use dd/mm/aaaa).';

        $sex = $l['sex'][P::compact($get('sexo'))] ?? null;
        if ($sex === null) $messages[] = 'Sexo deve ser M ou F.';

        $email = null;
        if ($get('email') !== '') {
            $email = P::email($get('email'));
            if ($email === null) $messages[] = 'E-mail inválido.';
        }

        $blood = 1;
        if ($get('tipo_sanguineo') !== '') {
            $blood = $l['blood'][self::bloodKey($get('tipo_sanguineo'))] ?? null;
            if ($blood === null) $messages[] = 'Tipo sanguíneo inválido (ex.: O+, AB-).';
        }

        $pcd = in_array(P::compact($get('pcd')), ['sim', 's', '1', 'true', 'yes'], true) ? 2 : 1;

        $uf = strtoupper($get('uf'));
        if ($uf !== '' && !preg_match('/^[A-Z]{2}$/', $uf)) $messages[] = 'UF deve ter 2 letras.';

        $code = P::digits($get('codigo_acesso'));
        if ($get('codigo_acesso') !== '' && (strlen($code) < 4 || strlen($code) > 12)) $messages[] = 'Código de acesso deve ter de 4 a 12 números.';

        $classId = null;
        $classLabel = '';
        if ($type === 'alunos') {
            $class = $l['classes'][P::compact($get('turma'))] ?? null;
            if ($class === null) {
                $messages[] = $get('turma') === '' ? 'Turma obrigatória.' : 'Turma "' . $get('turma') . '" não existe no período ativo.';
            } else {
                [$classId, $classLabel] = $class;
            }
        }

        $data = [
            'name' => $name, 'cpf' => $cpf, 'birth' => $birth, 'sex' => $sex, 'email' => $email,
            'phone' => P::digits($get('telefone')) ?: null, 'naturalness' => $get('naturalidade'), 'nationality' => $get('nacionalidade'),
            'blood' => $blood, 'pcd' => $pcd, 'zip' => (int) P::digits($get('cep')), 'address' => $get('endereco'),
            'district' => $get('bairro'), 'county' => $get('municipio'), 'uf' => $uf, 'code' => $code ?: null,
            'mother' => $get('nome_mae'), 'father' => $get('nome_pai'), 'classId' => $classId,
        ];

        $summary = ['Nome' => $name, 'CPF' => $cpf ? substr($cpf, 0, 3) . '.***.***-' . substr($cpf, 9) : $get('cpf')];
        if ($type === 'alunos') $summary['Turma'] = $classLabel ?: $get('turma');

        if ($messages) return ['status' => 'erro', 'messages' => $messages, 'data' => null, 'summary' => $summary, 'key' => $cpf];

        // já cadastrado: professor é ignorado; aluno é só matriculado na turma (se ainda não estiver no período)
        $existing = $type === 'alunos' ? ($l['students'][$cpf] ?? null) : ($l['teachers'][$cpf] ?? null);

        if ($existing !== null && $type === 'professores') {
            return ['status' => 'ignorar', 'messages' => ['Professor já cadastrado com este CPF.'], 'data' => null, 'summary' => $summary, 'key' => $cpf];
        }

        if ($existing !== null) {
            if (isset($l['enrolled'][$existing])) {
                return ['status' => 'ignorar', 'messages' => ['Aluno já matriculado no período ativo (' . $l['enrolled'][$existing] . ').'], 'data' => null, 'summary' => $summary, 'key' => $cpf];
            }
            $data['studentId'] = $existing;
            return ['status' => 'matricular', 'messages' => ['Aluno já cadastrado: os dados pessoais são mantidos e ele é matriculado na turma.'], 'data' => $data, 'summary' => $summary, 'key' => $cpf];
        }

        return ['status' => 'novo', 'messages' => [], 'data' => $data, 'summary' => $summary, 'key' => $cpf];
    }


    private function checkClass(array $item)
    {

        $l = $this->lookups();
        $messages = [];
        $get = function ($k) use ($item) { return trim((string) ($item[$k] ?? '')); };

        $serie = $l['series'][P::compact(rtrim($get('serie'), 'ªº°'))] ?? $l['series'][P::compact($get('serie'))] ?? null;
        if ($serie === null) $messages[] = 'Série "' . $get('serie') . '" não existe.';

        $letter = $l['letters'][P::compact($get('turma'))] ?? null;
        if ($letter === null) $messages[] = 'Turma (letra) "' . $get('turma') . '" não existe.';

        $course = $l['courses'][P::compact($get('curso'))] ?? null;
        if ($course === null) $messages[] = 'Curso "' . $get('curso') . '" não existe.';

        $shift = $l['shifts'][P::compact($get('turno'))] ?? null;
        if ($shift === null) $messages[] = 'Turno "' . $get('turno') . '" não existe.';

        $room = $l['rooms'][P::digits($get('sala'))] ?? null;
        if ($room === null) $messages[] = 'Sala "' . $get('sala') . '" não cadastrada.';

        $summary = ['Turma' => trim($get('serie') . ' ' . $get('turma') . ' · ' . $get('curso') . ' · ' . $get('turno')), 'Sala' => $get('sala')];

        if ($messages) return ['status' => 'erro', 'messages' => $messages, 'data' => null, 'summary' => $summary, 'key' => null];

        $key = "$serie-$letter-$course-$shift";
        if (isset($l['existingClasses'][$key])) {
            return ['status' => 'ignorar', 'messages' => ['Turma já existe no período ativo.'], 'data' => null, 'summary' => $summary, 'key' => $key];
        }

        return ['status' => 'novo', 'messages' => [], 'data' => ['serie' => $serie, 'letter' => $letter, 'course' => $course, 'shift' => $shift, 'room' => $room], 'summary' => $summary, 'key' => $key];
    }


    /**
     * Grava as linhas válidas numa transação. Devolve [created, enrolled, codes => [[nome, código]]]
     */
    public function commit($type, array $rows, $fileName)
    {

        $period = $this->activePeriod();
        if (!$period) throw new \RuntimeException('Nenhum período letivo ativo.');

        $created = 0;
        $enrolled = 0;
        $codes = [];

        $this->db->beginTransaction();

        try {
            foreach ($rows as $row) {
                $data = $row['data'];

                if ($type === 'turmas') {
                    $this->insertClass($data, (int) $period['id']);
                    $created++;
                    continue;
                }

                if ($row['status'] === 'matricular') {
                    $this->enroll((int) $data['studentId'], (int) $data['classId'], (int) $period['id']);
                    $enrolled++;
                    continue;
                }

                $generated = $data['code'] === null;
                $data['code'] = $data['code'] ?? $this->newAccessCode($type);

                $addressId = $this->insertReturning(
                    "INSERT INTO endereco (cep, bairro, endereco, uf, municipio) VALUES (:zip, :district, :address, :uf, :county) RETURNING id_endereco",
                    [':zip' => $data['zip'], ':district' => $data['district'], ':address' => $data['address'], ':uf' => $data['uf'], ':county' => $data['county']]
                );
                $phoneId = $this->insertReturning(
                    "INSERT INTO telefone (numero_telefone) VALUES (:phone) RETURNING id_telefone",
                    [':phone' => $data['phone']]
                );

                $common = [
                    ':name' => $data['name'], ':cpf' => $data['cpf'], ':birth' => $data['birth'], ':naturalness' => $data['naturalness'],
                    ':nationality' => $data['nationality'], ':sex' => $data['sex'], ':blood' => $data['blood'], ':pcd' => $data['pcd'],
                    ':address' => $addressId, ':phone' => $phoneId, ':code' => $data['code'], ':email' => $data['email'],
                ];

                if ($type === 'alunos') {
                    $studentId = $this->insertReturning(
                        "INSERT INTO aluno (nome_aluno, cpf_aluno, data_nascimento_aluno, naturalidade_aluno, nacionalidade_aluno, nome_mae, nome_pai,
                                            fk_id_sexo_aluno, fk_id_tipo_sanguineo_aluno, fk_id_aluno_pcd, fk_id_endereco_aluno, fk_id_telefone_aluno,
                                            codigo_acesso, fk_id_aluno_hierarquia_funcao, email_aluno, fk_id_situacao_geral_aluno)
                         VALUES (:name, :cpf, :birth, :naturalness, :nationality, :mother, :father, :sex, :blood, :pcd, :address, :phone, :code, 4, :email, 1)
                         RETURNING id_aluno",
                        $common + [':mother' => $data['mother'], ':father' => $data['father']]
                    );
                    $this->enroll($studentId, (int) $data['classId'], (int) $period['id']);
                    $enrolled++;
                } else {
                    $this->insertReturning(
                        "INSERT INTO professor (nome_professor, cpf_professor, data_nascimento_professor, naturalidade_professor, nacionalidade_professor,
                                                fk_id_sexo_professor, fk_id_tipo_sanguineo_professor, fk_id_pcd_professor, fk_id_endereco_professor,
                                                fk_id_telefone_professor, codigo_acesso, email_professor, fk_id_professor_hierarquia_funcao, fk_id_situacao_conta_professor)
                         VALUES (:name, :cpf, :birth, :naturalness, :nationality, :sex, :blood, :pcd, :address, :phone, :code, :email, 3, 1)
                         RETURNING id_professor",
                        $common
                    );
                }

                $created++;
                if ($generated) $codes[] = [$data['name'], $data['code']];
            }

            // trilha de auditoria: só o resumo, sem nome nem CPF
            Audit::log($this->db, 'IMPORTACAO', $type === 'turmas' ? 'turma' : ($type === 'alunos' ? 'aluno' : 'professor'), null, [
                'arquivo' => mb_substr((string) $fileName, 0, 120),
                'periodo' => (int) $period['id'],
                'criados' => $created,
                'matriculados' => $enrolled,
            ]);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return ['created' => $created, 'enrolled' => $enrolled, 'codes' => $codes];
    }


    private function enroll($studentId, $classId, $periodId)
    {

        $stmt = $this->db->prepare(
            "INSERT INTO matricula (fk_id_aluno, fk_id_turma_matricula, fk_id_situacao_aluno, fk_id_periodo_letivo_matricula, data_hora_matricula)
             VALUES (:student, :class, 1, :period, CURRENT_TIMESTAMP)"
        );
        $stmt->execute([':student' => $studentId, ':class' => $classId, ':period' => $periodId]);
    }


    private function insertClass(array $d, $periodId)
    {

        $stmt = $this->db->prepare(
            "INSERT INTO turma (fk_id_turno, fk_id_sala, fk_id_periodo_letivo, fk_id_cedula, fk_id_curso, fk_id_serie)
             VALUES (:shift, :room, :period, :letter, :course, :serie)"
        );
        $stmt->execute([':shift' => $d['shift'], ':room' => $d['room'], ':period' => $periodId, ':letter' => $d['letter'], ':course' => $d['course'], ':serie' => $d['serie']]);
    }


    private function insertReturning($sql, array $params)
    {

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }


    /**
     * Código de 6 dígitos que ainda não existe na tabela (o login é por nome + código)
     */
    private function newAccessCode($type)
    {

        $table = $type === 'alunos' ? 'aluno' : 'professor';
        $stmt = $this->db->prepare("SELECT 1 FROM $table WHERE codigo_acesso = :code");

        do {
            $code = (string) random_int(100000, 999999);
            $stmt->execute([':code' => $code]);
        } while ($stmt->fetchColumn());

        return $code;
    }


    /**
     * "O+" e "O-" só diferem pelo sinal, que compact() removeria
     */
    private static function bloodKey($text)
    {

        return P::compact(str_replace(['+', '-'], ['pos', 'neg'], (string) $text));
    }


    /**
     * Tabelas de apoio, carregadas uma vez por requisição
     */
    private function lookups()
    {

        if ($this->lookups !== null) return $this->lookups;

        $l = ['sex' => [], 'blood' => [], 'classes' => [], 'students' => [], 'teachers' => [], 'enrolled' => [],
              'series' => [], 'letters' => [], 'courses' => [], 'shifts' => [], 'rooms' => [], 'existingClasses' => []];

        foreach ($this->db->query("SELECT id_sexo, sexo FROM sexo")->fetchAll(\PDO::FETCH_NUM) as [$id, $name]) {
            $l['sex'][P::compact($name)] = (int) $id;
            $l['sex'][P::compact(mb_substr($name, 0, 1))] = (int) $id;
        }

        foreach ($this->db->query("SELECT id_tipo_sanguineo, tipo_sanguineo FROM tipo_sanguineo")->fetchAll(\PDO::FETCH_NUM) as [$id, $name]) {
            $l['blood'][self::bloodKey($name)] = (int) $id;
        }

        foreach ($this->activeClasses() as $c) {
            $id = [(int) $c['id'], $c['label']];
            foreach (['ª', 'o', ''] as $ordinal) {
                $l['classes'][P::compact($c['serie'] . $ordinal . ' ' . $c['letter'] . ' ' . $c['course'] . ' ' . $c['shift'])] = $id;
            }
        }

        foreach ($this->db->query("SELECT id_aluno, cpf_aluno FROM aluno")->fetchAll(\PDO::FETCH_NUM) as [$id, $cpf]) {
            $l['students'][str_pad((string) $cpf, 11, '0', STR_PAD_LEFT)] = (int) $id;
        }

        foreach ($this->db->query("SELECT id_professor, cpf_professor FROM professor")->fetchAll(\PDO::FETCH_NUM) as [$id, $cpf]) {
            $l['teachers'][str_pad((string) $cpf, 11, '0', STR_PAD_LEFT)] = (int) $id;
        }

        $period = $this->activePeriod();
        if ($period) {
            $stmt = $this->db->prepare(
                "SELECT matricula.fk_id_aluno, serie.sigla || 'ª ' || cedula_turma.cedula || ' · ' || curso.sigla || ' · ' || turno.nome_turno
                 FROM matricula
                 INNER JOIN turma ON (matricula.fk_id_turma_matricula = turma.id_turma)
                 INNER JOIN serie ON (turma.fk_id_serie = serie.id_serie)
                 INNER JOIN cedula_turma ON (turma.fk_id_cedula = cedula_turma.id_cedula_turma)
                 INNER JOIN curso ON (turma.fk_id_curso = curso.id_curso)
                 INNER JOIN turno ON (turma.fk_id_turno = turno.id_turno)
                 WHERE matricula.fk_id_periodo_letivo_matricula = :period AND matricula.fk_id_situacao_aluno <> 4"
            );
            $stmt->execute([':period' => (int) $period['id']]);
            foreach ($stmt->fetchAll(\PDO::FETCH_NUM) as [$id, $label]) $l['enrolled'][(int) $id] = $label;

            $stmt = $this->db->prepare("SELECT fk_id_serie, fk_id_cedula, fk_id_curso, fk_id_turno FROM turma WHERE fk_id_periodo_letivo = :period");
            $stmt->execute([':period' => (int) $period['id']]);
            foreach ($stmt->fetchAll(\PDO::FETCH_NUM) as $c) $l['existingClasses'][implode('-', $c)] = true;
        }

        foreach ($this->db->query("SELECT id_serie, sigla, nome FROM serie")->fetchAll(\PDO::FETCH_NUM) as [$id, $sigla, $name]) {
            $l['series'][P::compact($sigla)] = (int) $id;
            $l['series'][P::compact($name)] = (int) $id;
        }

        foreach ($this->db->query("SELECT id_cedula_turma, cedula FROM cedula_turma")->fetchAll(\PDO::FETCH_NUM) as [$id, $letter]) {
            $l['letters'][P::compact($letter)] = (int) $id;
        }

        foreach ($this->db->query("SELECT id_curso, sigla, nome_curso FROM curso")->fetchAll(\PDO::FETCH_NUM) as [$id, $sigla, $name]) {
            $l['courses'][P::compact($sigla)] = (int) $id;
            $l['courses'][P::compact($name)] = (int) $id;
        }

        foreach ($this->db->query("SELECT id_turno, nome_turno FROM turno")->fetchAll(\PDO::FETCH_NUM) as [$id, $name]) {
            $l['shifts'][P::compact($name)] = (int) $id;
        }

        foreach ($this->db->query(
            "SELECT sala.id_sala, numero_sala_aula.numero_sala_aula FROM sala
             INNER JOIN numero_sala_aula ON (sala.fk_id_numero_sala = numero_sala_aula.id_numero_sala_aula)"
        )->fetchAll(\PDO::FETCH_NUM) as [$id, $number]) {
            $l['rooms'][P::digits($number)] = (int) $id;
        }

        return $this->lookups = $l;
    }
}
