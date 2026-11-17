<?php

namespace App\Tools;

/**
 * Limite de tentativas por IP numa janela de tempo (tabela limite_tentativa, migração 34).
 * Usado na validação pública de documentos e no login por nome + código.
 * O IP entra só como hash na chave (LGPD); atrás do Caddy, o REMOTE_ADDR já é o IP real (mod_remoteip).
 */
class RateLimit
{

    // validação pública: 30 consultas a cada 10 minutos por IP
    const VALIDATE = ['scope' => 'validar', 'max' => 30, 'window' => 600];

    // login por código: 10 erros a cada 15 minutos por IP (só os erros contam)
    const LOGIN = ['scope' => 'login', 'max' => 10, 'window' => 900];

    private $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    public static function key($scope, $ip)
    {
        return $scope . '|' . hash('sha256', $scope . '|' . (string) $ip);
    }

    /**
     * Conta uma tentativa e diz se ainda está dentro do limite (true = pode seguir)
     */
    public function hit(array $rule, $ip)
    {

        $stmt = $this->db->prepare(
            "INSERT INTO limite_tentativa (chave, inicio, total) VALUES (:key, now(), 1)
             ON CONFLICT (chave) DO UPDATE SET
               inicio = CASE WHEN limite_tentativa.inicio < now() - make_interval(secs => :window) THEN now() ELSE limite_tentativa.inicio END,
               total = CASE WHEN limite_tentativa.inicio < now() - make_interval(secs => :window) THEN 1 ELSE limite_tentativa.total + 1 END
             RETURNING total"
        );
        $stmt->execute([':key' => self::key($rule['scope'], $ip), ':window' => (int) $rule['window']]);
        $total = (int) $stmt->fetchColumn();

        // limpeza de vez em quando (1 a cada 100 tentativas)
        if (random_int(1, 100) === 1) {
            $this->db->exec("DELETE FROM limite_tentativa WHERE inicio < now() - interval '1 day'");
        }

        return $total <= (int) $rule['max'];
    }

    /**
     * Já passou do limite nesta janela? (só lê; não conta)
     */
    public function exceeded(array $rule, $ip)
    {

        $stmt = $this->db->prepare(
            "SELECT total FROM limite_tentativa
             WHERE chave = :key AND inicio >= now() - make_interval(secs => :window)"
        );
        $stmt->execute([':key' => self::key($rule['scope'], $ip), ':window' => (int) $rule['window']]);

        return (int) $stmt->fetchColumn() >= (int) $rule['max'];
    }

    public static function ip()
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    }
}
