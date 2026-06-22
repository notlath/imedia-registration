<?php

/**
 * IMedia Registration — PHPMailer (vendored minimal).
 *
 * Vendored minimal version of PHPMailer. Implements the subset of the
 * public API used by App\Services\Mailer:
 *   - isSMTP() / isHTML() / CharSet
 *   - Timeout, Host, Port, SMTPAuth, Username, Password, SMTPSecure
 *   - setFrom(), addAddress(), Subject, Body
 *   - send()
 *
 * Other PHPMailer features (CC, BCC, attachments, DKIM signing, etc.)
 * are not implemented. If you need them, replace this vendored copy
 * with the upstream PHPMailer 6.x release.
 *
 * @see https://github.com/PHPMailer/PHPMailer
 */

declare(strict_types=1);

namespace PHPMailer\PHPMailer;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class PHPMailer
{
    public const VERSION = '6.9.1-vendored';

    public const CHARSET_UTF8 = 'utf-8';

    public const ENCODING_BASE64 = 'base64';
    public const ENCODING_QUOTED_PRINTABLE = 'quoted-printable';
    public const ENCODING_7BIT = '7bit';
    public const ENCODING_8BIT = '8bit';

    public const CONTENT_TYPE_PLAINTEXT = 'text/plain';
    public const CONTENT_TYPE_TEXT_HTML = 'text/html';

    public string $CharSet       = self::CHARSET_UTF8;
    public string $ContentType   = self::CONTENT_TYPE_TEXT_HTML;
    public string $Encoding      = self::ENCODING_8BIT;
    public string $Subject       = '';
    public string $Body          = '';
    public string $AltBody       = '';

    public string $Host          = 'localhost';
    public int    $Port          = 587;
    public string $Helo          = 'localhost';
    public int    $Timeout       = 10;
    public bool   $SMTPAuth      = false;
    public string $Username      = '';
    public string $Password      = '';
    public string $SMTPSecure    = '';
    public string $AuthType      = 'LOGIN';
    public string $SMTPOptions   = '';

    private string $Mailer       = 'mail';
    private ?SMTP  $smtp         = null;

    /** @var array<int, array{email: string, name: string}> */
    private array $from = [];

    /** @var array<int, array{email: string, name: string}> */
    private array $to = [];

    public function isSMTP(): bool
    {
        $this->Mailer = 'smtp';
        return true;
    }

    public function isMail(): bool
    {
        $this->Mailer = 'mail';
        return true;
    }

    public function isHTML(bool $isHtml = true): void
    {
        $this->ContentType = $isHtml ? self::CONTENT_TYPE_TEXT_HTML : self::CONTENT_TYPE_PLAINTEXT;
    }

    public function setFrom(string $address, string $name = '', bool $auto = true): bool
    {
        $this->from[] = ['email' => $address, 'name' => $name];
        return true;
    }

    public function addAddress(string $address, string $name = ''): bool
    {
        $this->to[] = ['email' => $address, 'name' => $name];
        return true;
    }

    public function send(): bool
    {
        try {
            if ($this->Mailer === 'smtp') {
                return $this->smtpSend();
            }
            return $this->mailSend();
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function smtpSend(): bool
    {
        if ($this->from === [] || $this->to === []) {
            throw new Exception('From and at least one To address are required.');
        }
        if (!extension_loaded('openssl') && $this->SMTPSecure !== '') {
            throw new Exception('openssl PHP extension is required for TLS/SSL SMTP.');
        }

        $this->smtp = new SMTP();
        $this->smtp->setDebugLevel(SMTP::DEBUG_OFF);
        $port = $this->resolvePort();
        $this->smtp->connect($this->Host, $port, $this->Timeout, $this->Helo);
        $this->smtp->hello($this->Helo);

        if ($this->SMTPSecure === 'tls' || $this->SMTPSecure === 'starttls') {
            $this->smtp->starttls();
            // Re-EHLO after STARTTLS.
            $this->smtp->hello($this->Helo);
        }

        if ($this->SMTPAuth) {
            $this->smtp->authenticate($this->Username, $this->Password, $this->AuthType);
        }

        $fromEmail = (string) $this->from[0]['email'];
        $this->smtp->mail($fromEmail);
        foreach ($this->to as $rcpt) {
            $this->smtp->recipient((string) $rcpt['email']);
        }

        $data = $this->buildMessage();
        $this->smtp->data($data);
        $this->smtp->quit();
        return true;
    }

    private function mailSend(): bool
    {
        // We never use the mail() path in this app, but support it for completeness.
        $to = $this->to;
        $headers = $this->buildHeaders();
        $toHeader = '';
        foreach ($to as $r) {
            $toHeader .= ($toHeader === '' ? '' : ', ') . $r['name'] . ' <' . $r['email'] . '>';
        }
        $result = @mail($toHeader, $this->Subject, $this->Body, $headers);
        if (!$result) {
            throw new Exception('mail() returned false.');
        }
        return true;
    }

    private function resolvePort(): int
    {
        if ($this->SMTPSecure === 'ssl') {
            return $this->Port === 0 ? 465 : $this->Port;
        }
        return $this->Port === 0 ? 25 : $this->Port;
    }

    private function buildMessage(): string
    {
        $boundary = '=_' . bin2hex(random_bytes(8));
        $headers  = $this->buildHeaders($boundary);
        $body     = '';
        if ($this->AltBody !== '') {
            $body .= '--' . $boundary . SMTP::CRLF
                  . 'Content-Type: text/plain; charset=' . $this->CharSet . SMTP::CRLF
                  . 'Content-Transfer-Encoding: ' . $this->Encoding . SMTP::CRLF . SMTP::CRLF
                  . $this->normalizeEol($this->AltBody) . SMTP::CRLF;
        }
        $body .= '--' . $boundary . SMTP::CRLF
               . 'Content-Type: ' . $this->ContentType . '; charset=' . $this->CharSet . SMTP::CRLF
               . 'Content-Transfer-Encoding: ' . $this->Encoding . SMTP::CRLF . SMTP::CRLF
               . $this->normalizeEol($this->Body) . SMTP::CRLF;
        $body .= '--' . $boundary . '--' . SMTP::CRLF;
        return $headers . SMTP::CRLF . $body;
    }

    private function buildHeaders(?string $boundary = null): string
    {
        $lines = [];
        if ($this->from !== []) {
            $from = $this->from[0];
            $name = $from['name'] !== '' ? $this->encodeHeader($from['name']) . ' ' : '';
            $lines[] = 'From: ' . $name . '<' . $from['email'] . '>';
        }
        foreach ($this->to as $r) {
            $name = $r['name'] !== '' ? $this->encodeHeader($r['name']) . ' ' : '';
            $lines[] = 'To: ' . $name . '<' . $r['email'] . '>';
        }
        $lines[] = 'Subject: ' . $this->encodeHeader($this->Subject);
        $lines[] = 'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $this->Host . '>';
        $lines[] = 'Date: ' . date('r');
        $lines[] = 'MIME-Version: 1.0';
        if ($boundary !== null) {
            $lines[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
        } else {
            $lines[] = 'Content-Type: ' . $this->ContentType . '; charset=' . $this->CharSet;
        }
        $lines[] = 'Content-Transfer-Encoding: ' . $this->Encoding;
        return implode(SMTP::CRLF, $lines);
    }

    private function encodeHeader(string $str): string
    {
        if (preg_match('/[^\x20-\x7E]/', $str) === 1) {
            return '=?utf-8?B?' . base64_encode($str) . '?=';
        }
        return $str;
    }

    private function normalizeEol(string $str): string
    {
        return preg_replace('/\r\n|\r|\n/', SMTP::CRLF, $str) ?? $str;
    }
}
