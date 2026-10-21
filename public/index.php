<?php

	//ini_set('error_reporting', 'E_STRICT');

	require_once "../vendor/autoload.php";

	// carrega o .env quando a app roda fora do Docker (variáveis já definidas têm prioridade)
	Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__))->safeLoad();

	$route = new \App\Route;
	
?>
