<?php

/**
 * IMedia Registration — PHPMailer Exception.
 *
 * Vendored minimal version of PHPMailer's exception class. The class
 * name, namespace, and shape (extends \Exception) match PHPMailer 6.x
 * so application code that catches \PHPMailer\PHPMailer\Exception
 * works against this vendor.
 *
 * @see https://github.com/PHPMailer/PHPMailer
 */

declare(strict_types=1);

namespace PHPMailer\PHPMailer;

class Exception extends \Exception
{
    public function errorMessage(): string
    {
        return '<strong>' . htmlspecialchars(static::class, ENT_QUOTES, 'UTF-8') . '</strong>'
             . '<br />Error: ' . htmlspecialchars($this->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}
