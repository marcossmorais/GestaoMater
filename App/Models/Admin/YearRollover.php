<?php

namespace App\Models\Admin;

use MF\Model\Model;
use App\Tools\Audit;

/**
 * Virada de ano (issue #19): copia a estrutura de turmas e disciplinas para o novo período
 * e rematricula em lote. Aprovado sobe de série, reprovado repete, quem está na última série conclui.
 *
 * plan() só calcula; execute() grava o mesmo plano numa transação única.
 */
class YearRollover extends Model
{

    // situacao_aluno_ano_letivo
    const ENROLLED = 1;
    const APPROVED = 2;
    const FAILED = 3;

    // o que fazer com quem ainda não tem resultado final (situação "Matriculado")
    const PENDING_OPTIONS = [
        'ignorar' => 'Não rematricular agora',
        'promover' => 'Promover para a próxima série',
        'manter' => 'Manter na mesma série',
    ];


    public function periods()
    {

        return $this->speedingUp(
            "SELECT periodo_letivo.id_ano_letivo AS id, periodo_disponivel.ano_letivo AS year,
                    situacao_periodo_letivo.situacao_periodo_letivo AS situation, periodo_letivo.fk_id_situacao_periodo_letivo AS situation_id
             FROM periodo_letivo
             INNER JOIN periodo_disponivel ON (periodo_letivo.fk_id_ano_letivo = periodo_disponivel.id_periodo_disponivel)
             INNER JOIN situacao_periodo_letivo ON (periodo_letivo.fk_id_situacao_periodo_letivo = situacao_periodo_letivo.id_situacao_periodo_letivo)
             ORDER BY periodo_disponivel.ano_letivo"
        );
    }


    /**
     * @param string $pending chave de PENDING_OPTIONS
     */
    public function plan($originId, $targetId, $pending)
    {

        $classes = $this->classes([$originId, $targetId]);
        $origin = array_filter($classes, function ($c) use ($originId) { return $c['period'] === (int) $originId; });
        $target = [];
        foreach ($classes as $c) {
            if ($c['period'] === (int) $targetId) $target[$c['key']] = $c;
        }

        $series = $this->series();
        $nextSerie = [];
        foreach ($series as $i => $id) $nextSerie[$id] = $series[$i + 1] ?? null;

        // 1) turmas do ano de origem que ainda não existem no destino
        $toCreate = [];
        foreach ($origin as $c) {
            if (!isset($target[$c['key']]) && !isset($toCreate[$c['key']])) {
                $toCreate[$c['key']] = $c + ['copyFrom' => $c['id'], 'reason' => 'cópia'];
            }
        }

        // 2) destino de cada aluno ativo do ano de origem
        $alreadyTarget = $this->enrolledStudents($targetId);
        $students = $this->students($originId);

        $moves = [];
        $graduates = [];
        $counts = ['promovidos' => 0, 'retidos' => 0, 'concluintes' => 0, 'sem_resultado' => 0, 'ja_matriculados' => 0];

        foreach ($students as $s) {
            $class = $origin[$s['class_id']] ?? null;
            if (!$class) continue;

            if (isset($alreadyTarget[$s['student_id']])) {
                $counts['ja_matriculados']++;
                continue;
            }

            $situation = (int) $s['situation'];
            if ($situation === self::ENROLLED) {
                if ($pending === 'ignorar') { $counts['sem_resultado']++; continue; }
                $situation = $pending === 'promover' ? self::APPROVED : self::FAILED;
            }

            $serie = $situation === self::APPROVED ? $nextSerie[$class['serie']] : $class['serie'];

            if ($serie === null) {
                $counts['concluintes']++;
                $graduates[] = (int) $s['student_id'];
                continue;
            }

            $key = $this->key($serie, $class['letter'], $class['course'], $class['shift']);

            // turma necessária que não existe na origem nem no destino (ex.: só havia 2ª B, então falta a 3ª B)
            if (!isset($target[$key]) && !isset($toCreate[$key])) {
                $toCreate[$key] = ['key' => $key, 'serie' => $serie, 'letter' => $class['letter'], 'course' => $class['course'],
                                   'shift' => $class['shift'], 'room' => $class['room'], 'label' => $this->label($serie, $class),
                                   'copyFrom' => null, 'reason' => 'nova série'];
            }

            $moves[] = ['student' => (int) $s['student_id'], 'key' => $key];
            $counts[$situation === self::APPROVED ? 'promovidos' : 'retidos']++;
        }

        // alunos por turma de destino, para a prévia
        $byClass = [];
        foreach ($moves as $m) $byClass[$m['key']] = ($byClass[$m['key']] ?? 0) + 1;

        $destinations = [];
        foreach ($byClass as $key => $total) {
            $destinations[] = ['label' => $target[$key]['label'] ?? $toCreate[$key]['label'], 'total' => $total, 'new' => !isset($target[$key])];
        }
        usort($destinations, function ($a, $b) { return strnatcmp($a['label'], $b['label']); });

        return [
            'toCreate' => array_values($toCreate),
            'moves' => $moves,
            'graduates' => $graduates,
            'counts' => $counts,
            'destinations' => $destinations,
            'disciplines' => $this->disciplineCount(array_filter(array_column($toCreate, 'copyFrom'))),
            // impressão digital do plano: a confirmação só grava se nada mudou desde a prévia
            'hash' => hash('sha256', json_encode([$originId, $targetId, $pending, array_keys($toCreate), $moves, $graduates])),
        ];
    }


    public function execute($originId, $targetId, $pending, $hash)
    {

        $plan = $this->plan($originId, $targetId, $pending);
        if (!hash_equals($plan['hash'], (string) $hash)) {
            throw new \RuntimeException('Os dados mudaram desde a prévia. Gere a prévia de novo antes de confirmar.');
        }

        $this->db->beginTransaction();

        try {
            $ids = [];
            foreach ($this->classes([$targetId]) as $c) $ids[$c['key']] = $c['id'];

            $insertClass = $this->db->prepare(
                "INSERT INTO turma (fk_id_turno, fk_id_sala, fk_id_periodo_letivo, fk_id_cedula, fk_id_curso, fk_id_serie)
                 VALUES (:shift, :room, :period, :letter, :course, :serie) RETURNING id_turma"
            );
            // mesmas disciplinas e professores da turma de origem
            $copyDisciplines = $this->db->prepare(
                "INSERT INTO turma_disciplina (fk_id_disciplina, fk_id_turma, fk_id_professor)
                 SELECT fk_id_disciplina, :new, fk_id_professor FROM turma_disciplina WHERE fk_id_turma = :old"
            );

            $disciplines = 0;
            foreach ($plan['toCreate'] as $c) {
                $insertClass->execute([':shift' => $c['shift'], ':room' => $c['room'], ':period' => (int) $targetId,
                                       ':letter' => $c['letter'], ':course' => $c['course'], ':serie' => $c['serie']]);
                $ids[$c['key']] = (int) $insertClass->fetchColumn();

                if ($c['copyFrom']) {
                    $copyDisciplines->execute([':new' => $ids[$c['key']], ':old' => $c['copyFrom']]);
                    $disciplines += $copyDisciplines->rowCount();
                }
            }

            $enroll = $this->db->prepare(
                "INSERT INTO matricula (fk_id_aluno, fk_id_turma_matricula, fk_id_situacao_aluno, fk_id_periodo_letivo_matricula, data_hora_matricula)
                 VALUES (:student, :class, 1, :period, CURRENT_TIMESTAMP)"
            );
            foreach ($plan['moves'] as $m) {
                $enroll->execute([':student' => $m['student'], ':class' => $ids[$m['key']], ':period' => (int) $targetId]);
            }

            // quem terminou a última série sai como concluído
            if ($plan['graduates']) {
                $this->db->exec(
                    "UPDATE aluno SET fk_id_situacao_geral_aluno = 3 WHERE id_aluno IN (" . implode(',', array_map('intval', $plan['graduates'])) . ")"
                );
            }

            Audit::log($this->db, 'VIRADA_ANO', 'periodo_letivo', (int) $targetId, [
                'origem' => (int) $originId,
                'destino' => (int) $targetId,
                'sem_resultado' => $pending,
                'turmas_criadas' => count($plan['toCreate']),
                'disciplinas_copiadas' => $disciplines,
                'matriculas' => count($plan['moves']),
            ] + $plan['counts']);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return ['classes' => count($plan['toCreate']), 'disciplines' => $disciplines, 'enrollments' => count($plan['moves'])] + $plan['counts'];
    }


    private function classes(array $periods)
    {

        $in = implode(',', array_map('intval', $periods));

        $rows = $this->db->query(
            "SELECT turma.id_turma, turma.fk_id_periodo_letivo, turma.fk_id_serie, turma.fk_id_cedula, turma.fk_id_curso, turma.fk_id_turno, turma.fk_id_sala,
                    serie.sigla AS serie_label, cedula_turma.cedula, curso.sigla AS course_label, turno.nome_turno
             FROM turma
             INNER JOIN serie ON (turma.fk_id_serie = serie.id_serie)
             INNER JOIN cedula_turma ON (turma.fk_id_cedula = cedula_turma.id_cedula_turma)
             INNER JOIN curso ON (turma.fk_id_curso = curso.id_curso)
             INNER JOIN turno ON (turma.fk_id_turno = turno.id_turno)
             WHERE turma.fk_id_periodo_letivo IN ($in)
             ORDER BY serie.sigla, cedula_turma.cedula"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $classes = [];
        foreach ($rows as $r) {
            $c = [
                'id' => (int) $r['id_turma'], 'period' => (int) $r['fk_id_periodo_letivo'], 'serie' => (int) $r['fk_id_serie'],
                'letter' => (int) $r['fk_id_cedula'], 'course' => (int) $r['fk_id_curso'], 'shift' => (int) $r['fk_id_turno'], 'room' => (int) $r['fk_id_sala'],
                'letterLabel' => $r['cedula'], 'courseLabel' => $r['course_label'], 'shiftLabel' => $r['nome_turno'],
                'label' => $r['serie_label'] . 'ª ' . $r['cedula'] . ' · ' . $r['course_label'] . ' · ' . $r['nome_turno'],
            ];
            $c['key'] = $this->key($c['serie'], $c['letter'], $c['course'], $c['shift']);
            $classes[$c['id']] = $c;
        }

        return $classes;
    }


    private function key($serie, $letter, $course, $shift)
    {

        return "$serie-$letter-$course-$shift";
    }


    private function label($serie, array $class)
    {

        $sigla = $this->db->prepare("SELECT sigla FROM serie WHERE id_serie = :id");
        $sigla->execute([':id' => $serie]);

        return $sigla->fetchColumn() . 'ª ' . $class['letterLabel'] . ' · ' . $class['courseLabel'] . ' · ' . $class['shiftLabel'];
    }


    /**
     * Séries em ordem; a seguinte da última não existe (aluno conclui)
     */
    private function series()
    {

        return array_map('intval', $this->db->query("SELECT id_serie FROM serie ORDER BY id_serie")->fetchAll(\PDO::FETCH_COLUMN));
    }


    private function students($periodId)
    {

        $stmt = $this->db->prepare(
            "SELECT matricula.fk_id_aluno AS student_id, matricula.fk_id_turma_matricula AS class_id, matricula.fk_id_situacao_aluno AS situation
             FROM matricula
             INNER JOIN aluno ON (matricula.fk_id_aluno = aluno.id_aluno)
             WHERE matricula.fk_id_periodo_letivo_matricula = :period
             AND matricula.fk_id_situacao_aluno IN (1, 2, 3)
             AND aluno.fk_id_situacao_geral_aluno = 1"
        );
        $stmt->execute([':period' => (int) $periodId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }


    private function enrolledStudents($periodId)
    {

        $stmt = $this->db->prepare(
            "SELECT DISTINCT fk_id_aluno FROM matricula WHERE fk_id_periodo_letivo_matricula = :period AND fk_id_situacao_aluno <> 4"
        );
        $stmt->execute([':period' => (int) $periodId]);

        return array_flip(array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN)));
    }


    private function disciplineCount(array $classIds)
    {

        if (!$classIds) return 0;

        return (int) $this->db->query(
            "SELECT COUNT(*) FROM turma_disciplina WHERE fk_id_turma IN (" . implode(',', array_map('intval', $classIds)) . ")"
        )->fetchColumn();
    }
}
