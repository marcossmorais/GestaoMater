<?php
// Roteador do servidor embutido do PHP (equivale ao .htaccess do Apache):
// arquivos estáticos existentes são servidos direto, o resto vai para o index.php.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file(__DIR__ . '/../public' . $path)) {
	return false;
}
chdir(__DIR__ . '/../public');
require 'index.php';
