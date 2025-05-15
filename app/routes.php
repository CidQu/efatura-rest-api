<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use App\Application\Controllers\EfaturaController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('<h1>Versiyon 1.1.3 EFatura API.</h1><br><p>Made by @cidqu</p><br><a href="https://github.com/cidqu">https://github.com/cidqu</a>');
        return $response;
    });

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });

    $app->post('/efatura', [EfaturaController::class, 'create']);
    $app->get('/efatura/pdf/{uuid}', [EfaturaController::class, 'downloadPdf']);
    $app->delete('/efatura/sil/{uuid}', [EfaturaController::class, 'deleteDraft']);
    $app->post('/efatura/start-sms-verification', [EfaturaController::class, 'startSmsVerification']);
    $app->post('/efatura/sign-draft', [EfaturaController::class, 'signDraft']);
    $app->post('/efatura/save-pdf-to-disk', [EfaturaController::class, 'savePdfToDisk']);
    $app->get('/efatura/html', [EfaturaController::class, 'getHtml']);
    $app->post('/efatura/login', [EfaturaController::class, 'login']);
};
