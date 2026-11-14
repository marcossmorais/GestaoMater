<?php

namespace App\Models\GeneralManagement;

use MF\Model\Model;

/**
 * Cronograma escolar (evento_escolar, database/14_desempenho_cronograma.sql e 31_cronograma_admin.sql).
 * O admin cadastra; o portal docente lê no planejamento. Sem turma = toda a escola.
 */
class SchoolCalendar extends Model
{

    // rótulo, ícone e tipo de aviso usado ao notificar no mural
    const TYPES = [
        'evento' => ['label' => 'Evento', 'icon' => 'fa-star', 'notice' => 'evento'],
        'prova' => ['label' => 'Prova', 'icon' => 'fa-file-alt', 'notice' => 'prova'],
        'reuniao' => ['label' => 'Reunião', 'icon' => 'fa-users', 'notice' => 'evento'],
        'feriado' => ['label' => 'Feriado', 'icon' => 'fa-umbrella-beach', 'notice' => 'aviso'],
        'recesso' => ['label' => 'Recesso', 'icon' => 'fa-bed', 'notice' => 'aviso'],
        'outro' => ['label' => 'Outro', 'icon' => 'fa-calendar-day', 'notice' => 'aviso'],
    ];

    const SELECT = "SELECT e.id_evento AS id, e.titulo AS title, e.descricao AS description, e.tipo AS type,
                           TO_CHAR(e.data_inicio, 'YYYY-MM-DD') AS start, TO_CHAR(COALESCE(e.data_fim, e.data_inicio), 'YYYY-MM-DD') AS finish,
                           e.fk_id_turma AS class_id,
                           serie.sigla || 'ª ' || cedula_turma.cedula || ' ' || curso.sigla AS class_label,
                           TO_CHAR(e.notificado_em, 'DD/MM/YYYY HH24:MI') AS notified_at,
                           (e.notificado_em IS NOT NULL AND e.atualizado_em > e.notificado_em) AS changed_after_notice
                    FROM evento_escolar e
                    LEFT JOIN turma ON (e.fk_id_turma = turma.id_turma)
                    LEFT JOIN serie ON (turma.fk_id_serie = serie.id_serie)
                    LEFT JOIN cedula_turma ON (turma.fk_id_cedula = cedula_turma.id_cedula_turma)
                    LEFT JOIN curso ON (turma.fk_id_curso = curso.id_curso)";


    /**
     * Eventos que tocam o período [$from, $to]
     *
     * @param string|int $class '' = todos; 'escola' = só os da escola toda; id = os da escola e os daquela turma
     */
    public function between($from, $to, $class = '')
    {

        $where = '';
        $params = [':from' => $from, ':to' => $to];

        if ($class === 'escola') {
            $where = 'AND e.fk_id_turma IS NULL';
        } elseif ((int) $class > 0) {
            $where = 'AND (e.fk_id_turma IS NULL OR e.fk_id_turma = :class)';
            $params[':class'] = (int) $class;
        }

        $stmt = $this->db->prepare(
            self::SELECT . "
             WHERE e.data_inicio <= :to AND COALESCE(e.data_fim, e.data_inicio) >= :from $where
             ORDER BY e.data_inicio, COALESCE(e.data_fim, e.data_inicio), e.id_evento"
        );
        $stmt->execute($params);

        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    public function find($id)
    {

        $stmt = $this->db->prepare(self::SELECT . " WHERE e.id_evento = :id");
        $stmt->execute([':id' => (int) $id]);

        return $stmt->fetch(\PDO::FETCH_OBJ) ?: null;
    }


    /**
     * Cria ($id = 0) ou altera o evento
     *
     * @return int id do evento; 0 se não existe mais
     */
    public function save($id, $title, $description, $type, $start, $finish, $classId)
    {

        $values = [
            ':title' => $title,
            ':description' => $description === '' ? null : $description,
            ':type' => $type,
            ':start' => $start,
            ':finish' => $finish === '' || $finish === $start ? null : $finish,
            ':class' => $classId ?: null,
        ];

        if ($id) {
            $stmt = $this->db->prepare(
                "UPDATE evento_escolar
                 SET titulo = :title, descricao = :description, tipo = :type, data_inicio = :start, data_fim = :finish,
                     fk_id_turma = :class, atualizado_em = CURRENT_TIMESTAMP
                 WHERE id_evento = :id
                 RETURNING id_evento"
            );
            $values[':id'] = (int) $id;
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO evento_escolar (titulo, descricao, tipo, data_inicio, data_fim, fk_id_turma)
                 VALUES (:title, :description, :type, :start, :finish, :class)
                 RETURNING id_evento"
            );
        }

        $stmt->execute($values);

        return (int) $stmt->fetchColumn();
    }


    public function delete($id)
    {

        return $this->db->prepare("DELETE FROM evento_escolar WHERE id_evento = :id")->execute([':id' => (int) $id]);
    }


    public function markNotified($id)
    {

        return $this->db->prepare("UPDATE evento_escolar SET notificado_em = CURRENT_TIMESTAMP WHERE id_evento = :id")
            ->execute([':id' => (int) $id]);
    }


    /**
     * Texto do aviso no mural: título, data por extenso curta e descrição
     */
    public static function noticeText($event, $changed = false)
    {

        $date = function ($ymd) { return date('d/m/Y', strtotime($ymd)); };
        $when = $event->start === $event->finish ? $date($event->start) : $date($event->start) . ' a ' . $date($event->finish);
        $label = self::TYPES[$event->type]['label'] ?? 'Evento';

        $text = ($changed ? 'Cronograma atualizado — ' : '') . $label . ': ' . $event->title . ' · ' . $when;

        if (trim((string) $event->description) !== '') $text .= "\n" . trim($event->description);

        return $text;
    }
}
