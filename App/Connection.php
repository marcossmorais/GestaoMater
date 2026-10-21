<?php

namespace App;

class Connection
{

	public static function getDb()
	{
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

			return $conn;

		} catch (\PDOException $e) {
			echo ($e->getMessage());
		}
	}
}
