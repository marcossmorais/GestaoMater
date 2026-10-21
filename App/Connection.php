<?php

namespace App;

class Connection
{

	public static function getDb()
	{
		try {

			$host = getenv('DB_HOST') ?: 'localhost';
			$port = getenv('DB_PORT') ?: '3306';
			$name = getenv('DB_NAME') ?: 'mater';

			$conn = new \PDO(
				"mysql:host=$host;port=$port;dbname=$name;charset=utf8",
				getenv('DB_USER') ?: 'root',
				getenv('DB_PASSWORD') ?: ''
			);

			return $conn;

		} catch (\PDOException $e) {
			echo ($e->getMessage());
		}
	}
}
