<?php

namespace App\Tools;

class Tools
{

	protected $url;
	

	public function __get($att)
	{
		return $this->$att;
	}

	public function __set($att, $newValue)
	{
		return $this->$att = $newValue;
	}


	public function formatElement($value)
	{

		return  preg_replace("/[^a-zA-Z0-9\s]/", "", $value);
	}


	// foto de perfil: só imagem, conferida pelo conteúdo (o nome e a extensão enviados não valem)
	const PHOTO_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
	const PHOTO_MAX_BYTES = 5 * 1024 * 1024;
	const PHOTO_PLACEHOLDER = 'foto-vazia.jpg';

	/**
	 * Grava a foto enviada em $dir com nome aleatório e define 'profilePhoto' no model.
	 * null = nenhum arquivo enviado · false = recusado (tipo, tamanho ou falha ao gravar) · true = gravado
	 */
	public function image($model, $dir)
	{

		$upload = $_FILES['profilePhoto'] ?? null;

		if (!$upload || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;

		if ($upload['error'] !== UPLOAD_ERR_OK || $upload['size'] > self::PHOTO_MAX_BYTES) return false;

		$mime = (new \finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);

		// getimagesize recusa o que não abre como imagem
		if (!isset(self::PHOTO_TYPES[$mime]) || @getimagesize($upload['tmp_name']) === false) return false;

		// a extensão vem do tipo real (.jpg/.png/.webp): mesmo um arquivo com código embutido nunca vira .php
		$name = bin2hex(random_bytes(16)) . '.' . self::PHOTO_TYPES[$mime];

		if ((!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) || !move_uploaded_file($upload['tmp_name'], $dir . $name)) {
			error_log('foto de perfil: não foi possível gravar');
			return false;
		}

		$model->__set('profilePhoto', $name);

		return true;
	}


	/**
	 * Apaga a foto antiga de $dir. O nome vem do banco, nunca do formulário; basename impede sair da pasta.
	 */
	public function removeImage($dir, $fileName)
	{

		$fileName = basename((string) $fileName);

		if ($fileName === '' || $fileName === self::PHOTO_PLACEHOLDER) return;

		$path = $dir . $fileName;

		if (is_file($path) && !unlink($path)) error_log('foto de perfil: foto antiga não removida');
	}


	function currentDate($array)
    {

        date_default_timezone_set('America/Sao_Paulo');
        $today = date('d-m');

        $data = explode(' ', $array);
        $data = explode('-', $data[0]);
        $data = $data[2] . '/' . $data[1];

        $data = ($data == $today ? 'Hoje' : $data);

        return $data;
    }

}
