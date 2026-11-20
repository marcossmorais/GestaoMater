<?php

namespace App;

class Connection
{

	private static $conn = null;

	/**
	 * Retorna a conexão com o banco. Uma única conexão é reaproveitada por toda a requisição
	 * (antes cada Model abria a sua).
	 */
	public static function getDb()
	{
		if (self::$conn !== null) return self::$conn;

		try {

			$host = getenv('DB_HOST') ?: 'localhost';
			$port = getenv('DB_PORT') ?: '5432';
			$name = getenv('DB_NAME') ?: 'mater';
			$sslmode = getenv('DB_SSLMODE') ?: 'disable';

			$conn = new \PDO(
				"pgsql:host=$host;port=$port;dbname=$name;sslmode=$sslmode",
				// o app usa o mater_app (migração 40, só lê e grava dados); sem ele, o usuário das migrações
				getenv('DB_APP_USER') ?: (getenv('DB_USER') ?: 'postgres'),
				getenv('DB_APP_USER') ? (getenv('DB_APP_PASSWORD') ?: '') : (getenv('DB_PASSWORD') ?: ''),
				[
					\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
					\PDO::ATTR_EMULATE_PREPARES => true
				]
			);

			$conn->exec("SET TIME ZONE 'America/Sao_Paulo'");

			\App\Tools\Audit::bindActor($conn);

			return self::$conn = $conn;

		} catch (\PDOException $e) {
			// o motivo (host, usuário) fica só no log; quem acessa vê uma página genérica
			error_log('Banco indisponível: ' . $e->getMessage());
			if (!headers_sent()) {
				http_response_code(503);
				header('Retry-After: 60');
				header('Content-Type: text/html; charset=UTF-8');
			}
			echo '<!doctype html><meta charset="utf-8"><title>Mater</title><p style="font-family:sans-serif;margin:2rem">O sistema está temporariamente indisponível. Tente de novo em alguns minutos.</p>';
			exit;
		}
	}
}
