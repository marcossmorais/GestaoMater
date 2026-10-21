<?php

$app_url = '';

if (!function_exists('asset')) {

	/**
	 * URL de um arquivo de public/ com a versão (data de modificação) na query string:
	 * o navegador pode guardar em cache por dias e mesmo assim pega a versão nova quando o arquivo muda.
	 */
	function asset($path)
	{
		$file = __DIR__ . '/../../public' . $path;

		return $path . (is_file($file) ? '?v=' . filemtime($file) : '');
	}
}
