<?php

/**
 * IMedia Registration — PHPMailer SMTP transport.
 *
 * Vendored minimal version of PHPMailer's SMTP class. Implements
 * the SMTP wire protocol (HELO/EHLO, STARTTLS, AUTH LOGIN, MAIL FROM,
 * RCPT TO, DATA, QUIT) with explicit timeouts and a debug hook.
 *
 * @see https://github.com/PHPMailer/PHPMailer
 */

declare(strict_types=1);

namespace PHPMailer\PHPMailer;

use PHPMailer\PHPMailer\Exception;

class SMTP
{
    public const VERSION = '6.9.1-vendored';

    public const CRLF = "\r\n";

    public const DEFAULT_PORT = 25;

    public const MAX_LINE_LENGTH = 998;

    public const DEBUG_OFF   = 0;
    public const DEBUG_CLIENT = 1;
    public const DEBUG_SERVER = 2;
    public const DEBUG_CONNECTION = 3;
    public const DEBUG_LOWLEVEL = 4;

    private string $server_host = '';
    private int    $server_port = 0;
    private string $timeout     = '';
    private string $helo        = 'localhost';

    /** @var resource|null */
    private $socket = null;

    private int    $debug_level   = self::DEBUG_OFF;
    /** @var callable|null */
    private $debug_callback = null;

    public function connect($host, int $port = self::DEFAULT_PORT, int $timeout = 30, string $hello = 'localhost'): bool
    {
        $this->server_host = (string) $host;
        $this->server_port = $port;
        $this->timeout     = (string) $timeout;
        $this->helo        = $hello;

        $errno  = 0;
        $errstr = '';
        // Suppress PHP warning; we rethrow as Exception on failure.
        $sock = @stream_socket_client(
            'tcp://' . $host . ':' . $port,
            $errno,
            $errstr,
            (float) $timeout
        );
        if ($sock === false) {
            throw new Exception(
                sprintf('SMTP connect() failed to %s:%d. Error %d: %s', $host, $port, $errno, $errstr),
                (int) $errno
            );
        }
        stream_set_timeout($sock, (int) $timeout);
        $this->socket = $sock;

        // Read the server greeting.
        $response = $this->get_lines();
        if (strncmp($response, '220', 3) !== 0) {
            $this->quit();
            throw new Exception('SMTP server did not greet with 220: ' . trim($response));
        }

        return true;
    }

    public function starttls(): bool
    {
        $this->send_command('STARTTLS', 'STARTTLS', 220);
        // crypto method 'STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT' requires PHP 7.4+.
        $method = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $method = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT | STREAM_CRYPTO_METHOD_TLS_CLIENT;
        }
        if (!@stream_socket_enable_crypto($this->socket, true, $method)) {
            throw new Exception('STARTTLS failed. Check that the server supports TLS and that openssl is available.');
        }
        return true;
    }

    public function authenticate(string $username, string $password, string $type = 'LOGIN'): bool
    {
        if (strtoupper($type) === 'LOGIN') {
            $this->send_command('AUTH LOGIN', 'AUTH LOGIN', 334);
            $this->send_command('Username', base64_encode($username), 334);
            $this->send_command('Password', base64_encode($password), 235);
        } else {
            $this->send_command('AUTH', 'AUTH ' . $type, 334);
            $this->send_command('Username', base64_encode($username), 334);
            $this->send_command('Password', base64_encode($password), 235);
        }
        return true;
    }

    public function mail(string $from): bool
    {
        $this->send_command('MAIL FROM', 'MAIL FROM:<' . $from . '>', 250);
        return true;
    }

    public function recipient(string $to): bool
    {
        $this->send_command('RCPT TO', 'RCPT TO:<' . $to . '>', [250, 251]);
        return true;
    }

    public function data(string $msg_data): bool
    {
        $this->send_command('DATA', 'DATA', 354);
        // SMTP data must end with CRLF.CRLF. Normalize any bare LFs.
        $data = preg_replace('/(?<!\r)\n/', "\r\n", $msg_data);
        if (is_string($data) && substr($data, -2) !== "\r\n") {
            $data .= "\r\n";
        }
        $this->send_command('DATA END', $data . "\r\n.", [250]);
        return true;
    }

    public function quit(): bool
    {
        if ($this->socket === null) {
            return false;
        }
        try {
            $this->send_command('QUIT', 'QUIT', 221);
        } catch (\Throwable) {
            // Best-effort close.
        }
        @fclose($this->socket);
        $this->socket = null;
        return true;
    }

    public function close(): void
    {
        $this->quit();
    }

    public function hello($hello = 'localhost'): bool
    {
        $this->helo = (string) $hello;
        $this->send_command('EHLO', 'EHLO ' . $hello, [250, 220]);
        return true;
    }

    public function reset(): bool
    {
        $this->send_command('RSET', 'RSET', 250);
        return true;
    }

    public function setDebugLevel(int $level): void
    {
        $this->debug_level = max(0, min(4, $level));
    }

    /**
     * @param callable(string $str, int $level): void $callback
     */
    public function setDebugOutput(callable $callback): void
    {
        $this->debug_callback = $callback;
    }

    /**
     * @param int|array<int, int> $expect
     */
    private function send_command(string $cmd_name, string $command_line, $expect): void
    {
        if ($this->socket === null) {
            throw new Exception('No SMTP connection.');
        }
        $this->debug("C: $command_line", self::DEBUG_CLIENT);
        $written = @fwrite($this->socket, $command_line . self::CRLF);
        if ($written === false || $written === 0) {
            throw new Exception('SMTP write failed for command: ' . $cmd_name);
        }
        $response = $this->get_lines();
        $code     = (int) substr($response, 0, 3);
        $expect   = is_array($expect) ? $expect : [$expect];
        if (!in_array($code, $expect, true)) {
            throw new Exception(
                sprintf("SMTP %s failed. Server reply: %s", $cmd_name, trim($response))
            );
        }
    }

    private function get_lines(): string
    {
        if ($this->socket === null) {
            throw new Exception('No SMTP connection.');
        }
        $data = '';
        $endtime = time() + (int) $this->timeout;
        $len     = 0;
        stream_set_timeout($this->socket, (int) $this->timeout);
        while (!feof($this->socket)) {
            $str = @fgets($this->socket, 515);
            if ($str === false) {
                $info = stream_get_meta_data($this->socket);
                if (!empty($info['timed_out'])) {
                    throw new Exception('SMTP read timed out.');
                }
                break;
            }
            $this->debug('S: ' . trim($str), self::DEBUG_SERVER);
            $data .= $str;
            $len  += strlen($str);
            if ($len > self::MAX_LINE_LENGTH * 10) {
                throw new Exception('SMTP response too long.');
            }
            // Per RFC 5321, the last line of a multi-line reply has a space
            // after the status code. If we see that, we're done.
            if (strlen($str) > 3 && $str[3] === ' ') {
                break;
            }
            if (time() > $endtime) {
                throw new Exception('SMTP read deadline exceeded.');
            }
        }
        return $data;
    }

    private function debug(string $str, int $level): void
    {
        if ($level > $this->debug_level) {
            return;
        }
        if ($this->debug_callback !== null) {
            ($this->debug_callback)($str, $level);
        }
    }
}
