<?php

/**
 * IMedia Registration — Response builder.
 *
 * Per php-pro: fluent builder, idempotent send() guard, all status codes
 * and content-types set explicitly. The Router calls send() once.
 */

declare(strict_types=1);

namespace App\Core;

final class Response {
    private int $status        = 200;
    private string $contentType   = 'text/html; charset=utf-8';
    /** @var array<string, string> */
    private array $headers       = array();
    private string $body          = '';
    private bool $sent          = false;

    public static function make(): self {
        return new self();
    }

    public function header( string $name, string $value ): self {
        $this->headers[ $name ] = $value;
        return $this;
    }

    /**
     * Send a JSON response.
     */
    public function json( mixed $data, int $status = 200 ): self {
        $this->status      = $status;
        $this->contentType = 'application/json; charset=utf-8';
        $this->body        = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($this->body === false) {
            $this->body        = '{"error":"json_encode_failed"}';
            $this->status      = 500;
        }
        return $this;
    }

    /**
     * Render a view through the View renderer and send the resulting HTML.
     *
     * @param array<string, mixed> $data
     */
    public function view( string $name, array $data = array(), ?string $layout = 'public' ): self {
        $this->status      = 200;
        $this->contentType = 'text/html; charset=utf-8';
        $this->body        = View::render($name, $data, $layout);
        return $this;
    }

    /**
     * Send a 302 redirect to $url.
     */
    public function redirect( string $url, int $status = 302 ): self {
        $this->status = $status;
        $this->headers['Location'] = $url;
        $this->body   = '';
        return $this;
    }

    /**
     * Send a plain-text body.
     */
    public function text( string $text, int $status = 200 ): self {
        $this->status      = $status;
        $this->contentType = 'text/plain; charset=utf-8';
        $this->body        = $text;
        return $this;
    }

    /**
     * Stream a file to the client with no PHP-side buffering. Used by
     * downloads (e.g. resume files) so a 5 MB PDF doesn't allocate 5 MB
     * of PHP heap. Caller is responsible for calling $res->send() then
     * this method's caller exits the script.
     *
     * @param string $path Absolute path to a readable file.
     * @param string $mime Content-Type to send.
     * @param string $disposition Content-Disposition value (e.g. "inline; filename=\"x.pdf\"").
     */
    public function streamFile( string $path, string $mime, string $disposition ): void {
        $size = filesize($path);
        $this->status = 200;
        $this->headers['Content-Type']        = $mime;
        $this->headers['Content-Length']      = (string) $size;
        $this->headers['Content-Disposition'] = $disposition;
        $this->headers['X-Content-Type-Options'] = 'nosniff';
        $this->headers['Cache-Control']       = 'private, max-age=300';
        $this->send();
        // Discard any output buffer the framework left behind so the
        // file bytes go straight to the client.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        readfile($path);
        exit;
    }

    /**
     * Send an error response. JSON if the request expects JSON, else HTML.
     * The HTML branch renders a minimal design-system-styled page so 404 /
     * 500s feel like the rest of the app instead of a 1995 server error.
     */
    public function error( int $status, string $message, array $extra = array() ): self {
        // Best-effort: try the Content-Accept header from the request.
        $accept = isset($_SERVER['HTTP_ACCEPT']) ? (string) $_SERVER['HTTP_ACCEPT'] : '';
        if (str_contains(strtolower($accept), 'application/json')) {
            return $this->json(
                array_merge(
                    array(
                        'success' => false,
                        'error'   => $message,
                    ),
                    $extra
                ),
                $status
            );
        }

        $this->status      = $status;
        $this->contentType = 'text/html; charset=utf-8';
        $reason            = self::reasonPhrase($status);
        $safeMessage       = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $baseUrl           = rtrim((string) \App\Core\Config::get('BASE_URL', ''), '/');
        $cssUrl            = $baseUrl . '/assets/css/app.css';
        $homeUrl           = $baseUrl . '/';
        $this->body        = '<!doctype html><html lang="en"><head><meta charset="utf-8">'
                           . '<meta name="viewport" content="width=device-width,initial-scale=1">'
                           . "<title>{$status} {$reason}</title>"
                           . '<link rel="preconnect" href="https://fonts.googleapis.com">'
                           . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
                           . '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@600;700&display=swap">'
                           . "<link rel=\"stylesheet\" href=\"{$cssUrl}\">"
                           . "<script>(function(){try{var s=localStorage.getItem('imreg-theme')||'auto';var p=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches;if(s==='dark'||(s==='auto'&&p))document.documentElement.classList.add('dark');}catch(e){}})();</script>"
                           . '</head><body class="imreg-flex-col" style="min-height:100dvh;"><main class="imreg-flex imreg-flex-col" style="flex:1;align-items:center;justify-content:center;padding:1.5rem;">'
                           . '<div class="imreg-card" style="max-width:32rem;text-align:center;">'
                           . "<div class=\"imreg-text-display\" style=\"font-size:3.75rem;font-weight:700;line-height:1;color:var(--color-primary);letter-spacing:-0.04em;\">{$status}</div>"
                           . "<h1 class=\"imreg-text-display\" style=\"font-size:1.25rem;font-weight:700;margin:0.5rem 0 0.25rem;\">{$reason}</h1>"
                           . "<p class=\"imreg-text-muted\" style=\"font-size:0.9375rem;margin:0 0 1.5rem;\">{$safeMessage}</p>"
                           . "<a href=\"{$homeUrl}\" class=\"imreg-btn imreg-btn--primary\">Back to home</a>"
                           . '</div></main></body></html>';
        return $this;
    }

    /**
     * Actually write the response. Idempotent.
     */
    public function send(): void {
        if ($this->sent) {
            return;
        }
        $this->sent = true;

        if (headers_sent()) {
            echo $this->body;
            return;
        }

        http_response_code($this->status);
        header('Content-Type: ' . $this->contentType);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        // No-cache by default for admin pages — callers can override.
        if (! isset($this->headers['Cache-Control'])) {
            header('Cache-Control: no-store, no-cache, must-revalidate');
        }
        echo $this->body;
    }

    public function getStatus(): int {
        return $this->status;
    }

    public function getBody(): string {
        return $this->body;
    }

    private static function reasonPhrase( int $status ): string {
        return match ($status) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            419 => 'Page Expired',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            default => 'Error',
        };
    }
}
