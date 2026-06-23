<?php

/**
 * IMedia Registration — Front controller.
 *
 * Every HTTP request to the subpath app (/imedia-registration/*) lands here.
 * public/.htaccess rewrites all non-file paths to this file.
 *
 * Per php-pro: strict types, no globals for app logic.
 */

declare(strict_types=1);

require __DIR__ . '/../app/Core/Bootstrap.php';

use App\Core\{Bootstrap, Request, Response, Router};

Bootstrap::init();

$router = new Router();
(require __DIR__ . '/../routes.php')($router);

try {
    $response = $router->dispatch(Request::fromGlobals(), Response::make());
} catch (\Throwable $e) {
    \App\Core\Logger::error('unhandled.exception', [
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'trace'   => $e->getTraceAsString(),
    ]);
    $debug = (bool) \App\Core\Config::get('APP_DEBUG', false);
    $message = $debug
        ? sprintf('%s in %s:%d', $e->getMessage(), $e->getFile(), $e->getLine())
        : 'Internal server error.';
    $response = Response::make()->error(500, $message);
}

$response->send();
