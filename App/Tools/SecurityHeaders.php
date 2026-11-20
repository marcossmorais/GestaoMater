<?php

namespace App\Tools;

/**
 * Content-Security-Policy das páginas do app (segunda barreira contra XSS e contra script de terceiros).
 *
 * Telas internas: só scripts, estilos e imagens do próprio site; a única chamada externa é o ViaCEP (endereço
 * pelo CEP). 'unsafe-inline' fica porque as telas ainda têm script e onclick embutidos no HTML.
 *
 * Telas de login: o Clerk carrega o próprio script, imagens e o desafio anti-robô (Cloudflare). Lá a política
 * completa vai como Report-Only (o navegador só avisa no console) e só as regras que não tocam no Clerk são
 * aplicadas; depois de conferir o login em produção sem avisos, dá para aplicar a completa também.
 */
class SecurityHeaders
{
    // portais onde o Clerk monta o login (com ?logout=1 é a mesma tela)
    const LOGIN_PAGES = ['/admin', '/admin/login', '/portal-docente', '/portal-docente/login', '/portal-aluno', '/portal-aluno/login'];

    // aplicadas em toda página, inclusive no login: não interferem no Clerk
    const BASE = [
        "object-src 'none'",
        "base-uri 'self'",
        "frame-ancestors 'self'",
    ];

    /**
     * Política das telas internas
     */
    public static function appPolicy()
    {
        return implode('; ', array_merge([
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "connect-src 'self' https://viacep.com.br",
            "frame-src 'self'",
            "worker-src 'self'",
            "manifest-src 'self'",
            "form-action 'self'",
        ], self::BASE));
    }

    /**
     * Política completa do login com o Clerk ($clerkHost: o Frontend API, ex. clerk.colegio.com.br)
     */
    public static function loginPolicy($clerkHost)
    {
        $clerk = preg_match('/^[a-z0-9.-]+$/i', (string) $clerkHost) ? ' https://' . $clerkHost : '';

        return implode('; ', array_merge([
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline'$clerk https://challenges.cloudflare.com",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob: https://img.clerk.com",
            "font-src 'self' data:",
            "connect-src 'self'$clerk",
            "frame-src 'self' https://challenges.cloudflare.com",
            "worker-src 'self' blob:",
            "manifest-src 'self'",
            "form-action 'self'",
        ], self::BASE));
    }

    /**
     * Cabeçalhos para a requisição atual: [nome => valor]
     */
    public static function forPath($path, $clerkHost)
    {
        $path = rtrim((string) $path, '/') ?: '/';

        if (in_array($path, self::LOGIN_PAGES, true)) {
            return [
                'Content-Security-Policy' => implode('; ', self::BASE),
                'Content-Security-Policy-Report-Only' => self::loginPolicy($clerkHost),
            ];
        }

        return ['Content-Security-Policy' => self::appPolicy()];
    }

    public static function send()
    {
        if (headers_sent()) return;

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        foreach (self::forPath($path, \App\Auth\Clerk::frontendApi()) as $name => $value) {
            header("$name: $value");
        }
    }
}
