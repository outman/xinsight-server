<?php

declare(strict_types=1);

use App\Application\Middleware\AuthMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->group('/api/v1/user', function (Group $group) {
        $group->post('/login', \App\Application\Actions\User\LoginAction::class);
    });

    $app->group('/api/v1/xhprof', function (Group $group) {
        $group->get('/index', \App\Application\Actions\Xhprof\IndexAction::class);
        $group->get('/item', \App\Application\Actions\Xhprof\XhprofItemAction::class);
    })->add(AuthMiddleware::class);
};
