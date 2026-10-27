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
				getenv('DB_USER') ?: 'postgres',
				getenv('DB_PASSWORD') ?: '',
				[
					\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
					\PDO::ATTR_EMULATE_PREPARES => true
				]
			);

			$conn->exec("SET TIME ZONE 'America/Sao_Paulo'");

			\App\Tools\Audit::bindActor($conn);

			return self::$conn = $conn;

		} catch (\PDOException $e) {
			echo ($e->getMessage());
		}
	}
}
