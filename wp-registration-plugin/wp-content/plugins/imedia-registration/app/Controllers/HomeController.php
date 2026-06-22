<?php

/**
 * IMedia Registration — HomeController.
 *
 * Public landing stub for /. Phase 6 may render a real form here.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Request, Response, View};

final readonly class HomeController
{
    public function index(Request $req, Response $res): Response
    {
        return $res->view('home', [
            '__title' => 'Inventive Media Registration',
        ], 'public');
    }
}
