<?php

/**
 * IMedia Registration — HTTP router.
 *
 * Per php-pro: typed, simple, supports both Closure and string handlers
 * ("App\\Controllers\\X@method"). Middleware chain is run in order; each
 * middleware may short-circuit by returning a Response without calling $next.
 *
 * Path patterns:
 *   /                              literal
 *   /admin/registrations/{id}      captures a single path segment
 *   /admin/registrations/{id}/edit captures a single path segment
 *
 * Path matching: exact match on segments. Trailing slashes are ignored.
 */

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<string, array<int, array{0: string|array, 1: array<int, string>}>> */
    private array $routes = [];

    /**
     * Register a route. $handler is either a Closure or a [ClassName, methodName]
     * tuple. $middleware is a list of class names that implement __invoke.
     *
     * @param callable|array{0: class-string, 1: string} $handler
     * @param array<int, class-string>                   $middleware
     */
    public function add(string $method, string $path, callable|array $handler, array $middleware = []): void
    {
        $method = strtoupper($method);
        $this->routes[$method][] = [$path, $handler, $middleware];
    }

    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    /**
     * Dispatch the request to a registered route. Returns the Response
     * (caller is responsible for ->send()).
     */
    public function dispatch(Request $req, Response $res): Response
    {
        $method = strtoupper($req->method);
        $candidates = $this->routes[$method] ?? [];

        foreach ($candidates as [$pattern, $handler, $middleware]) {
            $params = $this->matchPath($pattern, $req->path);
            if ($params === null) {
                continue;
            }
            // Bind path params onto the request for downstream handlers.
            $req = new Request(
                $req->method, $req->path, $req->query, $req->body, $req->files, $req->headers, $params
            );
            return $this->runChain($req, $res, $handler, $middleware, 0);
        }

        // 405: maybe a different method matches the same path?
        $allowed = [];
        foreach ($this->routes as $m => $list) {
            foreach ($list as [$pattern, ,]) {
                if ($this->matchPath($pattern, $req->path) !== null) {
                    $allowed[] = $m;
                }
            }
        }
        if ($allowed !== []) {
            $res->header('Allow', implode(', ', array_unique($allowed)));
            return $res->error(405, 'Method not allowed.');
        }
        return $res->error(404, 'Page not found.');
    }

    /**
     * Try to match a path pattern. Returns an array of {name: value} on
     * success, or null on failure.
     *
     * @return array<string, string>|null
     */
    private function matchPath(string $pattern, string $path): ?array
    {
        $a = $this->split($pattern);
        $b = $this->split($path);
        if (count($a) !== count($b)) {
            return null;
        }
        $params = [];
        foreach ($a as $i => $seg) {
            $target = $b[$i];
            if (str_starts_with($seg, '{') && str_ends_with($seg, '}')) {
                $name = substr($seg, 1, -1);
                if ($target === '') {
                    return null;
                }
                $params[$name] = $target;
            } elseif ($seg !== $target) {
                return null;
            }
        }
        return $params;
    }

    /**
     * @return array<int, string>
     */
    private function split(string $path): array
    {
        $path = trim($path, '/');
        if ($path === '') {
            return [];
        }
        return explode('/', $path);
    }

    /**
     * @param callable|array{0: class-string, 1: string} $handler
     * @param array<int, class-string>                   $middleware
     */
    private function runChain(Request $req, Response $res, callable|array $handler, array $middleware, int $index): Response
    {
        if ($index >= count($middleware)) {
            return $this->invoke($handler, $req, $res);
        }
        $class = $middleware[$index];
        $instance = new $class();
        return $instance($req, $res, function (Request $r, Response $rr) use ($handler, $middleware, $index): Response {
            return $this->runChain($r, $rr, $handler, $middleware, $index + 1);
        });
    }

    /**
     * @param callable|array{0: class-string, 1: string} $handler
     */
    private function invoke(callable|array $handler, Request $req, Response $res): Response
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $instance = new $class();
            $result = $instance->{$method}($req, $res);
            // Handlers may return a Response, an array (auto-wrapped to json),
            // a string (echoed), or null (no-op, the existing Response is used).
            if ($result instanceof Response) {
                return $result;
            }
            if (is_array($result)) {
                return $res->json($result);
            }
            if (is_string($result)) {
                return $res->text($result);
            }
            return $res;
        }
        // Closure
        $result = $handler($req, $res);
        if ($result instanceof Response) {
            return $result;
        }
        return $res;
    }
}
