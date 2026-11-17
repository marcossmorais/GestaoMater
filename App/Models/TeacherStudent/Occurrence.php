<?php

namespace App\Models\TeacherStudent;

use MF\Model\Model;

/**
 * Gestão de Ocorrências Disciplinares, Pedagógicas e Elogios
 */
class Occurrence extends Model
{

    /**
     * Retorna todas as ocorrências de um aluno por matrícula
     *
     * @param int $enrollmentId
     * @return array
     */
    public function getByEnrollment($enrollmentId)
    {
        $stmt = $this->db->prepare(
            "SELECT
                od.id_ocorrencia,
                od.fk_id_matricula,
                od.fk_id_aluno,
                od.categoria,
                od.titulo,
                od.descricao,
                od.gravidade,
                od.medida_tomada,
                od.visivel_responsavel,
                od.requer_ciencia,
                od.ciencia_responsavel,
                TO_CHAR(od.ciencia_em, 'DD/MM/YYYY HH24:MI') AS ciencia_em_fmt,
                od.registrado_por,
                TO_CHAR(od.data_ocorrencia, 'DD/MM/YYYY') AS data_ocorrencia_fmt,
                TO_CHAR(od.criado_em, 'DD/MM/YYYY HH24:MI') AS criado_em_fmt
            FROM ocorrencia_disciplinar od
            WHERE od.fk_id_matricula = :enrollmentId
            ORDER BY od.data_ocorrencia DESC, od.id_ocorrencia DESC"
        );

        $stmt->bindValue(':enrollmentId', (int) $enrollmentId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Retorna ocorrências visíveis para o Portal do Aluno/Família
     *
     * @param int $enrollmentId
     * @return array
     */
    public function getForPortal($enrollmentId)
    {
        $stmt = $this->db->prepare(
            "SELECT
                od.id_ocorrencia,
                od.categoria,
                od.titulo,
                od.descricao,
                od.gravidade,
                od.medida_tomada,
                od.requer_ciencia,
                od.ciencia_responsavel,
                od.registrado_por,
                TO_CHAR(od.data_ocorrencia, 'DD/MM/YYYY') AS data_ocorrencia_fmt
            FROM ocorrencia_disciplinar od
            WHERE od.fk_id_matricula = :enrollmentId
              AND od.visivel_responsavel = TRUE
            ORDER BY od.data_ocorrencia DESC, od.id_ocorrencia DESC"
        );

        $stmt->bindValue(':enrollmentId', (int) $enrollmentId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Categoria e gravidade de cada ocorrência (índice pedagógico)
     *
     * @return array [['category' => ..., 'severity' => ...]]
     */
    public function severities($enrollmentId)
    {
        $stmt = $this->db->prepare(
            "SELECT categoria AS category, gravidade AS severity FROM ocorrencia_disciplinar WHERE fk_id_matricula = :enrollmentId"
        );

        $stmt->bindValue(':enrollmentId', (int) $enrollmentId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Resumo de ocorrências por categoria
     *
     * @param int $enrollmentId
     * @return object
     */
    public function getSummaryByEnrollment($enrollmentId)
    {
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*)::int AS total,
                COUNT(CASE WHEN categoria = 'Disciplinar' THEN 1 END)::int AS disciplinares,
                COUNT(CASE WHEN categoria = 'Pedagógica' THEN 1 END)::int AS pedagogicas,
                COUNT(CASE WHEN categoria = 'Elogio' THEN 1 END)::int AS elogios,
                COUNT(CASE WHEN categoria = 'Saúde' THEN 1 END)::int AS saude,
                COUNT(CASE WHEN requer_ciencia = TRUE AND ciencia_responsavel = FALSE THEN 1 END)::int AS pendentes_ciencia
            FROM ocorrencia_disciplinar
            WHERE fk_id_matricula = :enrollmentId"
        );

        $stmt->bindValue(':enrollmentId', (int) $enrollmentId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(\PDO::FETCH_OBJ) ?: (object) [
            'total' => 0,
            'disciplinares' => 0,
            'pedagogicas' => 0,
            'elogios' => 0,
            'saude' => 0,
            'pendentes_ciencia' => 0
        ];
    }

    /**
     * Cadastra nova ocorrência
     *
     * @param array $data
     * @return int
     */
    public function create(array $data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO ocorrencia_disciplinar (
                fk_id_matricula,
                fk_id_aluno,
                fk_id_turma_disciplina,
                categoria,
                titulo,
                descricao,
                gravidade,
                medida_tomada,
                visivel_responsavel,
                requer_ciencia,
                registrado_por,
                data_ocorrencia
            ) VALUES (
                :fk_id_matricula,
                :fk_id_aluno,
                :fk_id_turma_disciplina,
                :categoria,
                :titulo,
                :descricao,
                :gravidade,
                :medida_tomada,
                :visivel_responsavel,
                :requer_ciencia,
                :registrado_por,
                :data_ocorrencia
            ) RETURNING id_ocorrencia"
        );

        $stmt->bindValue(':fk_id_matricula', (int) $data['fk_id_matricula'], \PDO::PARAM_INT);
        $stmt->bindValue(':fk_id_aluno', (int) $data['fk_id_aluno'], \PDO::PARAM_INT);
        $stmt->bindValue(':fk_id_turma_disciplina', !empty($data['fk_id_turma_disciplina']) ? (int) $data['fk_id_turma_disciplina'] : null, \PDO::PARAM_INT);
        $stmt->bindValue(':categoria', $data['categoria'] ?? 'Disciplinar');
        $stmt->bindValue(':titulo', $data['titulo']);
        $stmt->bindValue(':descricao', $data['descricao']);
        $stmt->bindValue(':gravidade', $data['gravidade'] ?? 'Média');
        $stmt->bindValue(':medida_tomada', !empty($data['medida_tomada']) ? $data['medida_tomada'] : null);
        // pedir ciência de algo que a família não vê deixaria a ocorrência pendente para sempre
        $stmt->bindValue(':visivel_responsavel', !empty($data['visivel_responsavel']) || !empty($data['requer_ciencia']), \PDO::PARAM_BOOL);
        $stmt->bindValue(':requer_ciencia', !empty($data['requer_ciencia']), \PDO::PARAM_BOOL);
        $stmt->bindValue(':registrado_por', $data['registrado_por'] ?? 'Coordenação Pedagógica');
        $stmt->bindValue(':data_ocorrencia', $data['data_ocorrencia'] ?? date('Y-m-d'));

        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Registra ciência do responsável
     *
     * @param int $id
     * @return bool
     */
    public function acknowledge($id, $enrollmentId)
    {
        $stmt = $this->db->prepare(
            "UPDATE ocorrencia_disciplinar
             SET ciencia_responsavel = TRUE, ciencia_em = CURRENT_TIMESTAMP
             WHERE id_ocorrencia = :id AND fk_id_matricula = :enrollment
               AND visivel_responsavel AND requer_ciencia AND NOT ciencia_responsavel"
        );
        $stmt->bindValue(':id', (int) $id, \PDO::PARAM_INT);
        $stmt->bindValue(':enrollment', (int) $enrollmentId, \PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() > 0) return true;

        // clique repetido: já estava ciente
        $done = $this->db->prepare(
            "SELECT 1 FROM ocorrencia_disciplinar WHERE id_ocorrencia = :id AND fk_id_matricula = :enrollment AND ciencia_responsavel"
        );
        $done->execute([':id' => (int) $id, ':enrollment' => (int) $enrollmentId]);

        return (bool) $done->fetchColumn();
    }


    const CATEGORIES = ['Disciplinar', 'Pedagógica', 'Elogio', 'Saúde'];
    const SEVERITIES = ['Baixa', 'Média', 'Alta', 'Gravíssima'];
    const PER_PAGE = 50;


    /**
     * Filtros da lista geral do admin, já validados.
     * situacao: pendente (exige ciência e ainda não teve) | ciente | sem-ciencia | '' (todas)
     */
    public static function filters(array $get)
    {
        $date = function ($v) { $d = \DateTime::createFromFormat('!Y-m-d', (string) $v); return $d && $d->format('Y-m-d') === $v ? $v : ''; };
        $today = (new \DateTime('now', new \DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');

        $f = [
            'situacao' => in_array($get['situacao'] ?? 'pendente', ['', 'pendente', 'ciente', 'sem-ciencia'], true) ? ($get['situacao'] ?? 'pendente') : 'pendente',
            'categoria' => in_array($get['categoria'] ?? '', self::CATEGORIES, true) ? $get['categoria'] : '',
            'gravidade' => in_array($get['gravidade'] ?? '', self::SEVERITIES, true) ? $get['gravidade'] : '',
            'turma' => max(0, (int) ($get['turma'] ?? 0)),
            'aluno' => mb_substr(trim((string) ($get['aluno'] ?? '')), 0, 60),
            'de' => $date($get['de'] ?? '') ?: substr($today, 0, 4) . '-01-01',
            'ate' => $date($get['ate'] ?? '') ?: $today,
            'pagina' => max(1, (int) ($get['pagina'] ?? 1)),
        ];

        if ($f['ate'] < $f['de']) [$f['de'], $f['ate']] = [$f['ate'], $f['de']];

        return $f;
    }


    private function where(array $f, array &$params)
    {
        $sql = ['od.data_ocorrencia BETWEEN :de AND :ate'];
        $params = [':de' => $f['de'], ':ate' => $f['ate']];

        if ($f['situacao'] === 'pendente') $sql[] = 'od.requer_ciencia AND NOT od.ciencia_responsavel';
        if ($f['situacao'] === 'ciente') $sql[] = 'od.requer_ciencia AND od.ciencia_responsavel';
        if ($f['situacao'] === 'sem-ciencia') $sql[] = 'NOT od.requer_ciencia';
        if ($f['categoria'] !== '') { $sql[] = 'od.categoria = :categoria'; $params[':categoria'] = $f['categoria']; }
        if ($f['gravidade'] !== '') { $sql[] = 'od.gravidade = :gravidade'; $params[':gravidade'] = $f['gravidade']; }
        if ($f['turma']) { $sql[] = 'matricula.fk_id_turma_matricula = :turma'; $params[':turma'] = $f['turma']; }
        if (mb_strlen($f['aluno']) >= 2) {
            $sql[] = "aluno.nome_aluno ILIKE :aluno";
            $params[':aluno'] = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $f['aluno']) . '%';
        }

        return 'WHERE ' . implode(' AND ', $sql);
    }


    /**
     * Lista geral do admin: pendentes mais antigas primeiro; as demais, mais recentes primeiro
     *
     * @return array ['rows' => [...], 'more' => bool, 'total' => int]
     */
    public function adminList(array $f)
    {
        $params = [];
        $where = $this->where($f, $params);
        $from = "FROM ocorrencia_disciplinar od
                 INNER JOIN aluno ON (od.fk_id_aluno = aluno.id_aluno)
                 INNER JOIN matricula ON (od.fk_id_matricula = matricula.id_matricula)
                 LEFT JOIN turma ON (matricula.fk_id_turma_matricula = turma.id_turma)
                 LEFT JOIN serie ON (turma.fk_id_serie = serie.id_serie)
                 LEFT JOIN cedula_turma ON (turma.fk_id_cedula = cedula_turma.id_cedula_turma)
                 LEFT JOIN curso ON (turma.fk_id_curso = curso.id_curso)";

        $order = $f['situacao'] === 'pendente'
            ? 'od.criado_em ASC, od.id_ocorrencia ASC'
            : 'od.data_ocorrencia DESC, od.id_ocorrencia DESC';

        $stmt = $this->db->prepare(
            "SELECT od.id_ocorrencia AS id, aluno.id_aluno AS student_id, aluno.nome_aluno AS student,
                    serie.sigla || ' ' || cedula_turma.cedula || ' ' || curso.sigla AS class_label,
                    od.categoria AS category, od.gravidade AS severity, od.titulo AS title, od.registrado_por AS author,
                    TO_CHAR(od.data_ocorrencia, 'DD/MM/YYYY') AS date,
                    od.requer_ciencia AS requires_ack, od.ciencia_responsavel AS acknowledged, od.visivel_responsavel AS visible,
                    TO_CHAR(od.ciencia_em, 'DD/MM/YYYY HH24:MI') AS acknowledged_at,
                    (CURRENT_DATE - od.criado_em::date) AS waiting_days
             $from $where
             ORDER BY $order
             LIMIT " . (self::PER_PAGE + 1) . " OFFSET " . (($f['pagina'] - 1) * self::PER_PAGE)
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $count = $this->db->prepare("SELECT COUNT(*) $from $where");
        $count->execute($params);

        return ['rows' => array_slice($rows, 0, self::PER_PAGE), 'more' => count($rows) > self::PER_PAGE, 'total' => (int) $count->fetchColumn()];
    }


    /**
     * Painel do topo: quantas aguardam ciência, há mais de 7 dias, e a mais antiga (sem filtro de período)
     */
    public function pendingSummary()
    {
        return $this->db->query(
            "SELECT COUNT(*)::int AS total,
                    COUNT(*) FILTER (WHERE criado_em < CURRENT_DATE - 7)::int AS late,
                    COALESCE(MAX(CURRENT_DATE - criado_em::date), 0)::int AS oldest_days
             FROM ocorrencia_disciplinar
             WHERE requer_ciencia AND NOT ciencia_responsavel"
        )->fetch(\PDO::FETCH_OBJ);
    }


    /**
     * Quantas o aluno/família ainda precisa confirmar (aviso no topo do portal do aluno)
     */
    public function pendingForPortal($enrollmentId)
    {
        $stmt = $this->db->prepare(
            "SELECT id_ocorrencia FROM ocorrencia_disciplinar
             WHERE fk_id_matricula = :enrollment AND visivel_responsavel AND requer_ciencia AND NOT ciencia_responsavel
             ORDER BY data_ocorrencia, id_ocorrencia"
        );
        $stmt->execute([':enrollment' => (int) $enrollmentId]);

        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /**
     * Remove ocorrência
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM ocorrencia_disciplinar WHERE id_ocorrencia = :id");
        $stmt->bindValue(':id', (int) $id, \PDO::PARAM_INT);
        return $stmt->execute();
    }
}
