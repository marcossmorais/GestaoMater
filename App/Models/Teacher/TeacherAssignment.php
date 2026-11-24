<?php

namespace App\Models\Teacher;

use MF\Model\Model;
use App\Models\TeacherStudent\DailyAttendance;

/**
 * Atuação do professor informada no cadastro (database/42_atuacao_professor.sql):
 * polivalente = titular de uma turma do Fund. 1 com as disciplinas de base; especialista = as matérias marcadas,
 * cada uma nas turmas escolhidas. Os vínculos viram turma_disciplina (a permissão do docente), como no perfil da turma.
 */
class TeacherAssignment extends Model
{

    const ROLES = ['polivalente' => 'Polivalente (anos iniciais)', 'especialista' => 'Especialista'];

    // disciplinas que já vêm marcadas para o titular do Fund. 1 (as de 3 AVs da migração 32); o admin pode mudar
    const BASE_SUBJECTS = ['língua portuguesa', 'matemática', 'história', 'geografia', 'ciências'];


    public static function isBaseSubject($name)
    {

        return in_array(mb_strtolower(trim((string) $name), 'UTF-8'), self::BASE_SUBJECTS, true);
    }


    /**
     * Turmas do período ativo (com etapa e titular) e disciplinas, para montar a tela
     */
    public function options()
    {

        return ['classes' => $this->activeClasses(), 'disciplines' => $this->disciplines()];
    }


    /**
     * Lê o que veio do formulário: atuacao, materias[], turmaTitular e turmas[disciplina][] = turma
     */
    public static function input(array $post)
    {

        $links = [];
        foreach ((array) ($post['turmas'] ?? []) as $discipline => $classes) {
            foreach ((array) $classes as $class) $links[] = [(int) $class, (int) $discipline];
        }

        // polivalente e especialista têm listas próprias (o painel escondido também é enviado pelo formulário)
        $subjects = ($post['atuacao'] ?? '') === 'polivalente' ? ($post['materiasTitular'] ?? []) : ($post['materias'] ?? []);

        return [
            'role' => is_string($post['atuacao'] ?? null) ? $post['atuacao'] : '',
            'subjects' => array_map('intval', (array) $subjects),
            'homeroomClass' => (int) ($post['turmaTitular'] ?? 0),
            'links' => $links,
        ];
    }


    /**
     * Confere a escolha com o banco travando as turmas envolvidas (chamar dentro da transação do cadastro)
     */
    public function prepare(array $input)
    {

        $classes = $this->activeClasses();
        $involved = $input['role'] === 'polivalente' ? [$input['homeroomClass']] : array_column($input['links'], 0);
        $involved = array_values(array_filter(array_unique($involved), function ($id) use ($classes) { return isset($classes[$id]); }));

        $taken = [];
        if ($involved) {
            $marks = implode(',', array_fill(0, count($involved), '?'));

            // dois cadastros ao mesmo tempo na mesma turma: o segundo espera e já vê o vínculo do primeiro
            $this->db->prepare("SELECT 1 FROM turma WHERE id_turma IN ($marks) ORDER BY id_turma FOR UPDATE")->execute($involved);

            $stmt = $this->db->prepare(
                "SELECT turma_disciplina.fk_id_turma AS class_id, turma_disciplina.fk_id_disciplina AS discipline_id, professor.nome_professor AS teacher
                 FROM turma_disciplina
                 INNER JOIN professor ON (turma_disciplina.fk_id_professor = professor.id_professor)
                 WHERE turma_disciplina.fk_id_turma IN ($marks)"
            );
            $stmt->execute($involved);
            foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $row) $taken[$row->class_id . '-' . $row->discipline_id] = $row->teacher;

            // o titular pode ter mudado depois da leitura acima
            $stmt = $this->db->prepare(
                "SELECT turma.id_turma AS id, professor.nome_professor AS teacher FROM turma
                 LEFT JOIN professor ON (turma.fk_id_professor_titular = professor.id_professor)
                 WHERE turma.id_turma IN ($marks)"
            );
            $stmt->execute($involved);
            foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $row) $classes[(int) $row->id]['homeroom'] = $row->teacher;
        }

        return self::plan($input, $classes, $this->disciplines(), $taken);
    }


    /**
     * Regra pura (testada em tests/TeacherAssignmentTest.php). Devolve error (texto para o admin) ou o que gravar:
     * role, subjects (ids), links ([turma, disciplina]) e homeroom (turma em que vira titular)
     *
     * @param array $classes     id => ['label', 'stage', 'homeroom' (nome do titular ou null)]
     * @param array $disciplines id => nome
     * @param array $taken       "turma-disciplina" => nome do professor que já tem o vínculo
     */
    public static function plan(array $input, array $classes, array $disciplines, array $taken)
    {

        $none = ['error' => null, 'role' => null, 'subjects' => [], 'links' => [], 'homeroom' => null];
        $fail = function ($message) use ($none) { return ['error' => $message] + $none; };

        $role = $input['role'] ?? '';
        if ($role === '') return $none;
        if (!isset(self::ROLES[$role])) return $fail('Escolha a atuação do professor.');

        $subjects = array_values(array_unique(array_filter((array) ($input['subjects'] ?? []), function ($id) use ($disciplines) { return isset($disciplines[$id]); })));

        if ($role === 'polivalente') {

            // a tela já traz as de base marcadas; o admin tira ou acrescenta (ex.: disciplina criada depois)
            if (!$subjects) return $fail('Marque ao menos uma matéria do professor polivalente.');

            $class = (int) ($input['homeroomClass'] ?? 0);
            if (!$class) return ['role' => $role, 'subjects' => $subjects] + $none;

            if (!isset($classes[$class]) || $classes[$class]['stage'] !== DailyAttendance::STAGE) {
                return $fail('Escolha uma turma do Fund. 1 do período letivo ativo.');
            }
            if (!empty($classes[$class]['homeroom'])) {
                return $fail('A turma ' . $classes[$class]['label'] . ' já tem titular (' . $classes[$class]['homeroom'] . '). Troque o titular no perfil da turma.');
            }

            $links = array_map(function ($subject) use ($class) { return [$class, $subject]; }, $subjects);
            $conflict = self::conflicts($links, $classes, $disciplines, $taken);
            if ($conflict) return $fail($conflict);

            return ['error' => null, 'role' => $role, 'subjects' => $subjects, 'links' => $links, 'homeroom' => $class];
        }

        if (!$subjects) return $fail('Marque ao menos uma matéria que o professor leciona.');

        $links = [];
        foreach ((array) ($input['links'] ?? []) as [$class, $subject]) {
            // turma de matéria desmarcada depois: fica de fora
            if (!in_array($subject, $subjects, true)) continue;
            if (!isset($classes[$class])) return $fail('Uma das turmas escolhidas não é do período letivo ativo.');
            $links[$class . '-' . $subject] = [$class, $subject];
        }
        $links = array_values($links);

        $conflict = self::conflicts($links, $classes, $disciplines, $taken);
        if ($conflict) return $fail($conflict);

        return ['error' => null, 'role' => $role, 'subjects' => $subjects, 'links' => $links, 'homeroom' => null];
    }


    /**
     * Grava a atuação, as matérias e os vínculos do professor recém-cadastrado (mesma transação do cadastro)
     */
    public function save($teacherId, array $plan)
    {

        if ($plan['role'] === null) return;

        $stmt = $this->db->prepare("UPDATE professor SET atuacao = :role WHERE id_professor = :teacher");
        $stmt->execute([':role' => $plan['role'], ':teacher' => (int) $teacherId]);

        $stmt = $this->db->prepare(
            "INSERT INTO professor_disciplina (fk_id_professor, fk_id_disciplina) VALUES (:teacher, :discipline) ON CONFLICT DO NOTHING"
        );
        foreach ($plan['subjects'] as $subject) $stmt->execute([':teacher' => (int) $teacherId, ':discipline' => (int) $subject]);

        $stmt = $this->db->prepare(
            "INSERT INTO turma_disciplina (fk_id_professor, fk_id_disciplina, fk_id_turma) VALUES (:teacher, :discipline, :class)"
        );
        foreach ($plan['links'] as [$class, $subject]) {
            $stmt->execute([':teacher' => (int) $teacherId, ':discipline' => (int) $subject, ':class' => (int) $class]);
        }

        if ($plan['homeroom']) {
            $stmt = $this->db->prepare("UPDATE turma SET fk_id_professor_titular = :teacher WHERE id_turma = :class");
            $stmt->execute([':teacher' => (int) $teacherId, ':class' => (int) $plan['homeroom']]);
        }
    }


    /**
     * Atuação e matérias de um professor, para o perfil
     */
    public function forTeacher($teacherId)
    {

        $stmt = $this->db->prepare("SELECT atuacao FROM professor WHERE id_professor = :teacher");
        $stmt->execute([':teacher' => (int) $teacherId]);
        $role = $stmt->fetchColumn() ?: null;

        $stmt = $this->db->prepare(
            "SELECT disciplina.nome_disciplina FROM professor_disciplina
             INNER JOIN disciplina ON (professor_disciplina.fk_id_disciplina = disciplina.id_disciplina)
             WHERE professor_disciplina.fk_id_professor = :teacher ORDER BY disciplina.nome_disciplina"
        );
        $stmt->execute([':teacher' => (int) $teacherId]);

        return ['role' => $role ? self::ROLES[$role] : null, 'subjects' => $stmt->fetchAll(\PDO::FETCH_COLUMN)];
    }


    private static function conflicts(array $links, array $classes, array $disciplines, array $taken)
    {

        $messages = [];
        foreach ($links as [$class, $subject]) {
            if (isset($taken[$class . '-' . $subject])) {
                $messages[] = $disciplines[$subject] . ' na ' . $classes[$class]['label'] . ' já é de ' . $taken[$class . '-' . $subject];
            }
        }

        return $messages ? 'Nada foi gravado: ' . implode('; ', $messages) . '.' : null;
    }


    private function activeClasses()
    {

        $rows = $this->db->query(
            "SELECT turma.id_turma AS id, curso.etapa AS stage, professor.nome_professor AS homeroom,
                    serie.sigla || ' ' || cedula_turma.cedula || ' · ' || curso.sigla || ' · ' || turno.nome_turno AS label
             FROM turma
             INNER JOIN periodo_letivo ON (turma.fk_id_periodo_letivo = periodo_letivo.id_ano_letivo)
             INNER JOIN curso ON (turma.fk_id_curso = curso.id_curso)
             INNER JOIN serie ON (turma.fk_id_serie = serie.id_serie)
             INNER JOIN cedula_turma ON (turma.fk_id_cedula = cedula_turma.id_cedula_turma)
             INNER JOIN turno ON (turma.fk_id_turno = turno.id_turno)
             LEFT JOIN professor ON (turma.fk_id_professor_titular = professor.id_professor)
             WHERE periodo_letivo.fk_id_situacao_periodo_letivo = 1
             ORDER BY CASE curso.etapa WHEN 'iniciais' THEN 1 WHEN 'finais' THEN 2 ELSE 3 END, serie.sigla, cedula_turma.cedula, turno.nome_turno"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $classes = [];
        foreach ($rows as $row) $classes[(int) $row['id']] = ['label' => $row['label'], 'stage' => $row['stage'], 'homeroom' => $row['homeroom']];

        return $classes;
    }


    private function disciplines()
    {

        $rows = $this->db->query("SELECT id_disciplina AS id, nome_disciplina AS name FROM disciplina ORDER BY nome_disciplina")->fetchAll(\PDO::FETCH_ASSOC);

        return array_column($rows, 'name', 'id');
    }
}
