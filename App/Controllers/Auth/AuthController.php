<?php

namespace App\Controllers\Auth;

use App\Auth\Clerk;
use App\Auth\PortalAccount;
use MF\Controller\Action;

class AuthController extends Action
{

    /**
     * Recebe o token de sessão do Clerk (enviado pelo navegador após o login),
     * valida e abre a sessão do portal correspondente.
     */
    public function clerkSession()
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $portal = $input['portal'] ?? '';
        $config = PortalAccount::config($portal);

        if (!Clerk::enabled() || !$config || empty($input['token'])) {
            http_response_code(400);
            echo json_encode(['error' => 'requisicao-invalida']);
            return;
        }

        try {
            $claims = Clerk::verifySessionToken($input['token']);
            $emails = Clerk::verifiedEmails($claims->sub);
        } catch (\Exception $e) {
            error_log('Clerk: ' . $e->getMessage());
            http_response_code(401);
            echo json_encode(['error' => 'token-invalido']);
            return;
        }

        $id = PortalAccount::findId($portal, $claims->sub, $emails);

        if ($id === null) {
            http_response_code(403);
            echo json_encode(['error' => 'sem-cadastro']);
            return;
        }

        $error = PortalAccount::startSession($portal, $id);

        if ($error) {
            http_response_code(403);
            echo json_encode(['error' => $error]);
            return;
        }

        echo json_encode(['redirect' => $config['home']]);
    }
}
