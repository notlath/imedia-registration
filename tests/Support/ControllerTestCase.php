<?php
declare(strict_types=1);

namespace IMF\Tests\Support;

use App\Core\{Bootstrap, Config, Request, Response, Router, Session};

/**
 * Base class for Tier 3 controller tests.
 *
 * Provides helpers to fabricate Request/Response objects and to
 * dispatch requests through the full Router (for E2E smoke tests).
 *
 * The DB connection is transactional (via DatabaseTestCase).
 */
abstract class ControllerTestCase extends DatabaseTestCase
{
    /** @var array<string, string> Session values set before each test. */
    protected array $sessionData = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach ($this->sessionData as $key => $value) {
            Session::put($key, $value);
        }
    }

    /**
     * Fabricate a Request for the given method, path, and body.
     */
    protected function createRequest(
        string $method = 'GET',
        string $path = '/',
        array  $body = [],
        array  $query = [],
        array  $headers = [],
        array  $params = [],
    ): Request {
        return new Request($method, $path, $query, $body, [], $headers, $params);
    }

    /**
     * Return a fresh Response.
     */
    protected function createResponse(): Response
    {
        return new Response();
    }

    /**
     * Build the HMAC signature header value for a given body and secret.
     */
    protected function signBody(string $body, string $secret): string
    {
        return 'sha256=' . hash_hmac('sha256', $body, $secret);
    }

    /**
     * Dispatch a request through the Router and return the Response.
     * Bootstraps routing from routes.php if not already done.
     */
    protected function dispatchRequest(Request $req): Response
    {
        static $router = null;

        if ($router === null) {
            $router = new Router();
            (require dirname(__DIR__, 2) . '/routes.php')($router);
        }

        $res = new Response();
        return $router->dispatch($req, $res);
    }

    /**
     * Shortcut: create a GET request, dispatch, return response.
     */
    protected function get(string $path, array $query = []): Response
    {
        return $this->dispatchRequest($this->createRequest('GET', $path, [], $query));
    }

    /**
     * Shortcut: create a POST request, dispatch, return response.
     */
    protected function post(string $path, array $body = []): Response
    {
        return $this->dispatchRequest($this->createRequest('POST', $path, $body));
    }
}
