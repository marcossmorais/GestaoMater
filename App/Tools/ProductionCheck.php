<?php

namespace App\Tools;

/**
 * Configuração mínima para rodar em produção (APP_ENV=production, ligado no docker-compose.prod.yml).
 * Faltou algo: o site não abre (503) e o motivo vai só para o log, nunca para a tela.
 *
 * Sem o Clerk vale o login por nome + código, e o código do admin do seed é público no README.
 * Sem chave forte, qualquer um com a chave de dev assina documentos que passam na validação.
 */
class ProductionCheck
{

    // chave fixa do docker-compose.yml de desenvolvimento
    const DEV_SIGNING_KEY = 'dev-only-signing-key-change-me-in-production-0000';

    /**
     * @param array $env variáveis de ambiente (nome => valor)
     * @return string[] problemas encontrados; vazio = pode rodar
     */
    public static function problems(array $env)
    {
        if (($env['APP_ENV'] ?? '') !== 'production') return [];

        $problems = [];

        $key = (string) ($env['APP_SIGNING_KEY'] ?? '');
        if (strlen($key) < 32) {
            $problems[] = 'APP_SIGNING_KEY ausente ou curta (mínimo 32 caracteres; gere com openssl rand -hex 32)';
        } elseif ($key === self::DEV_SIGNING_KEY || stripos($key, 'change-me') !== false || stripos($key, 'dev-only') !== false) {
            $problems[] = 'APP_SIGNING_KEY é a chave de desenvolvimento';
        } elseif (count(array_unique(str_split($key))) < 10) {
            $problems[] = 'APP_SIGNING_KEY sem variação suficiente (use openssl rand -hex 32)';
        }

        if (($env['CLERK_PUBLISHABLE_KEY'] ?? '') === '' || ($env['CLERK_SECRET_KEY'] ?? '') === '') {
            $problems[] = 'Clerk desligado (CLERK_PUBLISHABLE_KEY e CLERK_SECRET_KEY): o login por código não vale em produção';
        }

        if (strpos((string) ($env['APP_URL'] ?? ''), 'https://') !== 0) {
            $problems[] = 'APP_URL precisa começar com https://';
        }

        return $problems;
    }


    /**
     * Chamado no public/index.php antes das rotas
     */
    public static function enforce()
    {
        // getenv(nome), como o resto da app lê (no Apache, getenv() sem argumento pode não ver as mesmas)
        $env = [];
        foreach (['APP_ENV', 'APP_SIGNING_KEY', 'CLERK_PUBLISHABLE_KEY', 'CLERK_SECRET_KEY', 'APP_URL'] as $name) {
            $env[$name] = (string) getenv($name);
        }
        // Clerk aceita também o nome dos exemplos de Next.js (App/Auth/Clerk.php)
        if ($env['CLERK_PUBLISHABLE_KEY'] === '') $env['CLERK_PUBLISHABLE_KEY'] = (string) getenv('NEXT_PUBLIC_CLERK_PUBLISHABLE_KEY');

        $problems = self::problems($env);

        if (!$problems) return;

        // sem acentos: o log do Apache escapa bytes fora do ASCII (configura\xc3\xa7...)
        $message = 'mater: configuracao de producao incompleta: ' . implode(' | ', $problems);
        error_log(iconv('UTF-8', 'ASCII//TRANSLIT', $message) ?: $message);

        http_response_code(503);
        header('Content-Type: text/plain; charset=utf-8');
        header('Retry-After: 300');
        echo 'Sistema temporariamente indisponível.';
        exit;
    }
}
