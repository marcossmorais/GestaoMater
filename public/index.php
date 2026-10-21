<?php

	//ini_set('error_reporting', 'E_STRICT');

	require_once "../vendor/autoload.php";

	// carrega o .env só quando a app roda fora do Docker (no Docker as variáveis vêm do compose)
	if (getenv('DB_HOST') === false) {
		Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__))->safeLoad();
	}

	$route = new \App\Route;

?>
