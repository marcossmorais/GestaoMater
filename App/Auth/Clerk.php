<?php

namespace App\Auth;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

/**
 * Integração com o Clerk (https://clerk.com).
 *
 * O login acontece no navegador com o clerk-js. O token de sessão (JWT) é
 * enviado ao servidor, que valida a assinatura com o JWKS da instância e busca
 * o usuário na Backend API para descobrir os e-mails verificados.
 */
class Clerk
{
    const API_URL = 'https://api.clerk.com/v1';
    const JWKS_CACHE_TTL = 3600;

    public static function enabled()
    {
        return self::publishableKey() !== '' && self::secretKey() !== '';
    }

    public static function publishableKey()
    {
        return getenv('CLERK_PUBLISHABLE_KEY') ?: '';
    }

    private static function secretKey()
    {
        return getenv('CLERK_SECRET_KEY') ?: '';
    }

    /**
     * Domínio do Frontend API, embutido na publishable key (pk_test_<base64(dominio$)>)
     */
    public static function frontendApi()
    {
        $parts = explode('_', self::publishableKey(), 3);

        if (count($parts) !== 3) return '';

        return rtrim(base64_decode($parts[2]), '$');
    }

    /**
     * Valida o token de sessão e retorna as claims
     *
     * @throws \Exception quando o token é inválido
     */
    public static function verifySessionToken($token)
    {
        JWT::$leeway = 5;

        $claims = JWT::decode($token, self::keySet());

        // azp: origem que gerou o token, precisa ser uma das origens permitidas
        if (isset($claims->azp) && !in_array($claims->azp, self::authorizedParties(), true)) {
            throw new \Exception('Origem do token não autorizada');
        }

        if (empty($claims->sub)) {
            throw new \Exception('Token sem usuário');
        }

        return $claims;
    }

    /**
     * Retorna os e-mails verificados de um usuário do Clerk
     */
    public static function verifiedEmails($userId)
    {
        $user = self::request('/users/' . rawurlencode($userId));

        $emails = [];

        foreach ($user['email_addresses'] ?? [] as $email) {
            if (($email['verification']['status'] ?? '') === 'verified') {
                $emails[] = strtolower($email['email_address']);
            }
        }

        return $emails;
    }

    private static function authorizedParties()
    {
        $origins = getenv('CLERK_AUTHORIZED_PARTIES') ?: (getenv('APP_URL') ?: 'http://localhost:8000');

        return array_map(function ($origin) {
            return rtrim(trim($origin), '/');
        }, explode(',', $origins));
    }

    private static function keySet()
    {
        $cacheFile = sys_get_temp_dir() . '/mater_clerk_jwks_' . md5(self::secretKey()) . '.json';

        if (is_file($cacheFile) && filemtime($cacheFile) > time() - self::JWKS_CACHE_TTL) {
            $jwks = json_decode(file_get_contents($cacheFile), true);
        } else {
            $jwks = self::request('/jwks');
            file_put_contents($cacheFile, json_encode($jwks));
        }

        return JWK::parseKeySet($jwks, 'RS256');
    }

    private static function request($path)
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer " . self::secretKey() . "\r\nAccept: application/json\r\n",
                'timeout' => 10,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents(self::API_URL . $path, false, $context);
        $status = isset($http_response_header[0]) ? (int) explode(' ', $http_response_header[0])[1] : 0;

        if ($body === false || $status !== 200) {
            throw new \Exception("Falha ao consultar o Clerk ($path): HTTP $status");
        }

        return json_decode($body, true);
    }
}
