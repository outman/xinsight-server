<?php

namespace App\Application\Actions\User;

use App\Application\Actions\Action;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;

class LoginAction extends Action
{
    /**
     * @return Response
     */
    protected function action(): Response
    {
        $username = $this->getParam('username');
        $password = $this->getParam('password');

        $users = str_split_to_options($_ENV['SYSTEM_USERS'] ?? '');
        if (isset($users[$username]) && $users[$username] === $password) {
            return $this->respondWithData([
                'token' => $this->makeToken($username),
                'username' => $username,
            ]);
        }
        return $this->respondWithData([
            'token' => '',
            'username' => '',
        ]);
    }

    /**
     * @param $username
     * @return string
     */
    protected function makeToken($username): string
    {
        $days = (int)($_ENV['JWT_EXP'] ?? 7);

        return JWT::encode([
            'iss' => $_ENV['JWT_DOMAIN'] ?? 'xinsight',
            'sub' => $_ENV['JWT_DOMAIN'] ?? 'xinsight',
            'aud' => $_ENV['JWT_DOMAIN'] ?? 'xinsight',
            'exp' => strtotime("+{$days}day"),
            'iat' => time(),
            'name' => $username,
        ], $_ENV['JWT_KEY'] ?? '', $_ENV['JWT_ALG']);
    }
}
