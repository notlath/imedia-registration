<?php

/**
 * IMedia Registration — Template renderer.
 *
 * Conventions:
 *   - Templates are PHP files under resources/views/.
 *   - Names use dot-notation: "admin.dashboard" => resources/views/admin/dashboard.php
 *   - Layouts are PHP files under resources/views/layouts/ (e.g. "public", "admin").
 *   - The View exposes $__content, $__title, $__data, and any data keys.
 *
 * Per php-pro: static methods, no globals (everything is passed explicitly),
 * strict types, output buffering for capture.
 */

declare(strict_types=1);

namespace App\Core;

final class View
{
    /**
     * Read a previously-flashed "old" form value (per WordPress-style
     * form re-population after a validation failure). Returns the
     * default if no value was flashed for that key.
     *
     * The convention: a controller that fails validation calls
     *   Session::flash('_old', $input);
     *   Session::flash('errors', $errors);
     * then re-renders the form. The view calls View::old('email') to
     * retrieve the user's last input.
     */
    public static function old(string $key, mixed $default = ''): mixed
    {
        $bag = Session::getFlash('_old');
        if (is_array($bag) && array_key_exists($key, $bag)) {
            return $bag[$key];
        }
        return $default;
    }

    /**
     * Read a previously-flashed errors bag.
     *
     * @return array<string, string>
     */
    public static function errors(): array
    {
        $bag = Session::getFlash('errors');
        return is_array($bag) ? $bag : [];
    }

    /**
     * Read a previously-flashed single error message.
     */
    public static function errorMessage(): ?string
    {
        $msg = Session::getFlash('error');
        return is_string($msg) ? $msg : null;
    }

    /**
     * Render a view, optionally wrapped in a layout, and return the HTML.
     *
     * @param array<string, mixed> $data
     */
    public static function render(string $name, array $data = [], ?string $layout = null): string
    {
        $template = self::resolve($name);
        if (!is_file($template)) {
            throw new \RuntimeException("View not found: {$name} ({$template})");
        }

        // Pull out layout-only variables (reserved names) so they don't
        // appear in the inner view's scope. They will be re-injected below
        // for the layout's scope.
        $title = (string) ($data['__title'] ?? '');
        unset($data['__title'], $data['__content']);

        $content = self::capture($template, $data);

        if ($layout === null || $layout === '') {
            return $content;
        }
        $layoutFile = self::resolveLayout($layout);
        if (!is_file($layoutFile)) {
            throw new \RuntimeException("Layout not found: {$layout} ({$layoutFile})");
        }
        return self::capture($layoutFile, array_merge($data, [
            '__content' => $content,
            '__title'   => $title,
        ]));
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function capture(string $__file, array $__data): string
    {
        // $__file and $__data are reserved because they're our locals.
        // __content / __title are NOT reserved here: render() injects them
        // intentionally for the layout, and that injection must succeed.
        if (array_key_exists('__file', $__data) || array_key_exists('__data', $__data)) {
            throw new \RuntimeException("Reserved view variable name: __file or __data");
        }
        extract($__data, EXTR_SKIP);

        ob_start();
        try {
            require $__file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }

    private static function resolve(string $name): string
    {
        $relative = str_replace('.', DIRECTORY_SEPARATOR, $name);
        return IMREG_VIEWS_PATH . DIRECTORY_SEPARATOR . $relative . '.php';
    }

    private static function resolveLayout(string $name): string
    {
        return IMREG_VIEWS_PATH . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . $name . '.php';
    }
}
