<?php

namespace App\Tools;

/**
 * Trilha de auditoria (database/10_auditoria.sql).
 *
 * Alterações em notas, faltas, chamadas, atestados e ocorrências são gravadas por trigger no banco;
 * aqui só identificamos o ator na conexão. Eventos sem alteração de linha (ex.: download de documento)
 * são gravados com log().
 */
class Audit
{

	/**
	 * Quem está logado nesta requisição: [tipo, id, nome]
	 */
	public static function actor()
	{
		foreach (['Admin' => 'admin', 'Teacher' => 'docente', 'Student' => 'aluno'] as $key => $type) {
			if (!empty($_SESSION[$key]['id'])) {
				return [$type, (int) $_SESSION[$key]['id'], $_SESSION[$key]['name'] ?? null];
			}
		}

		return ['sistema', null, null];
	}


	/**
	 * Grava o ator e o IP na sessão do Postgres para os triggers de auditoria
	 */
	public static function bindActor(\PDO $db)
	{
		[$type, $id, $name] = self::actor();

		$ip = $_SERVER['REMOTE_ADDR'] ?? '';
		if (!filter_var($ip, FILTER_VALIDATE_IP)) $ip = '';

		$stmt = $db->prepare(
			"SELECT set_config('mater.ator_tipo', :type, false),
			        set_config('mater.ator_id', :id, false),
			        set_config('mater.ator_nome', :name, false),
			        set_config('mater.ip', :ip, false)"
		);

		$stmt->execute([
			':type' => $type,
			':id' => $id === null ? '' : (string) $id,
			':name' => (string) $name,
			':ip' => $ip,
		]);
	}


	/**
	 * Registra um evento de aplicação (ex.: 'DOWNLOAD', 'declaracao_matricula', $enrollmentId)
	 */
	public static function log(\PDO $db, $action, $entity, $entityId = null, $after = null, $before = null)
	{
		self::bindActor($db);

		$stmt = $db->prepare(
			"INSERT INTO auditoria (ator_tipo, ator_id, ator_nome, acao, entidade, entidade_id, antes, depois, ip)
			 VALUES (
				current_setting('mater.ator_tipo'),
				NULLIF(current_setting('mater.ator_id'), '')::integer,
				NULLIF(current_setting('mater.ator_nome'), ''),
				:action, :entity, :entityId, :before, :after,
				NULLIF(current_setting('mater.ip'), '')::inet
			 )"
		);

		$stmt->execute([
			':action' => $action,
			':entity' => $entity,
			':entityId' => $entityId === null ? null : (string) $entityId,
			':before' => $before === null ? null : json_encode($before, JSON_UNESCAPED_UNICODE),
			':after' => $after === null ? null : json_encode($after, JSON_UNESCAPED_UNICODE),
		]);
	}
}
