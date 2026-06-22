<?php

/**
 * IMedia Registration — FileStorage service.
 *
 * Phase 5: resume upload for registrations. Reads settings for
 * enabled flag, max bytes, allowed MIME types, and storage path.
 *
 * Per php-pro: strict types, readonly where applicable.
 * Per wordpress-pro:
 *   - Verify with finfo (the *actual* file content), not the
 *     browser-supplied Content-Type.
 *   - Filename comes from a random hex + a whitelist-derived
 *     extension. We never trust the user-supplied filename.
 *   - move_uploaded_file() is the only safe way to move an upload.
 *   - public/.htaccess denies PHP execution in the uploads dir
 *     (defense in depth even if mime detection misses something).
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;

final class FileStorage
{
    /**
     * Handle a single-file upload for a registration resume.
     * Returns the storage-relative path. Throws on any error.
     *
     * @param array{name: string, tmp_name: string, size: int, error: int, type: string} $file
     * @return array{path: string, size: int, mime: string}
     */
    public static function handleResumeUpload(int $registrationId, array $file): array
    {
        $enabled = (int) Setting::get('upload_resume_enabled', 1) === 1;
        if (!$enabled) {
            throw new \RuntimeException('Resume uploads are disabled.');
        }
        if (!isset($file['error']) || (int) $file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(self::uploadErrorMessage((int) ($file['error'] ?? -1)));
        }
        $tmp  = (string) ($file['tmp_name'] ?? '');
        $name = (string) ($file['name']     ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new \RuntimeException('Invalid upload.');
        }

        $maxBytes = (int) Setting::get('upload_resume_max_bytes', 5 * 1024 * 1024);
        $size     = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            throw new \RuntimeException('File is empty.');
        }
        if ($size > $maxBytes) {
            $mb = number_format($maxBytes / 1048576, 1);
            throw new \RuntimeException("File too large. Max {$mb} MB.");
        }

        // Detect MIME from the actual file content.
        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
        if ($finfo === false) {
            throw new \RuntimeException('File-info extension is not available.');
        }
        $detected = (string) finfo_file($finfo, $tmp);
        finfo_close($finfo);

        $allowed = self::allowedMimes();
        if (!in_array($detected, $allowed, true)) {
            throw new \RuntimeException('Unsupported file type. Allowed: PDF, DOC, DOCX.');
        }
        $ext = self::extensionFor($detected);

        // Build the destination path.
        $sub = (string) Setting::get(
            'upload_resume_storage_path',
            'public/uploads/registrations/{YYYY}/{MM}/'
        );
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $relDir = strtr($sub, [
            '{YYYY}' => $now->format('Y'),
            '{MM}'   => $now->format('m'),
        ]);
        // Strip a leading "public/" if present (so callers can store
        // either relative-to-public or relative-to-plugin-root and
        // we end up with the same on-disk location).
        $relDir = preg_replace('#^public/#', '', $relDir) ?? $relDir;
        $absDir = IMREG_PUBLIC_PATH . '/' . $relDir;

        if (!self::ensureDirectory($absDir)) {
            throw new \RuntimeException('Could not create storage directory.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $absPath  = $absDir . '/' . $filename;
        $relPath  = 'public/' . $relDir . '/' . $filename; // stored in DB; served relative to plugin root

        if (!@move_uploaded_file($tmp, $absPath)) {
            throw new \RuntimeException('Failed to move uploaded file.');
        }
        @chmod($absPath, 0644);

        return [
            'path' => $relPath,
            'size' => $size,
            'mime' => $detected,
        ];
    }

    /**
     * Resolve the absolute filesystem path for a stored relative path.
     * Used by the resume download endpoint.
     */
    public static function absolutePath(string $relativePath): ?string
    {
        $rel = ltrim($relativePath, '/');
        // Strip a leading "public/" so we resolve to public/ + the rest.
        $rel = preg_replace('#^public/#', '', $rel) ?? $rel;
        $abs = IMREG_PUBLIC_PATH . '/' . $rel;
        $real = realpath($abs);
        // Defense-in-depth: ensure the resolved path is under public/.
        $publicReal = realpath(IMREG_PUBLIC_PATH);
        if ($real === false || $publicReal === false) {
            return null;
        }
        if (!str_starts_with($real, $publicReal . DIRECTORY_SEPARATOR)) {
            return null;
        }
        return $real;
    }

    /**
     * Map a stored MIME type back to a filename extension.
     *
     * @return array<int, string>
     */
    private static function allowedMimes(): array
    {
        $configured = (string) Setting::get(
            'upload_resume_allowed_mime',
            'application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );
        $parts = array_values(array_filter(array_map('trim', explode(',', $configured))));
        return $parts === []
            ? ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
            : $parts;
    }

    private static function extensionFor(string $mime): string
    {
        return match ($mime) {
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            default => 'bin',
        };
    }

    private static function ensureDirectory(string $absDir): bool
    {
        if (is_dir($absDir)) {
            return is_writable($absDir);
        }
        // Race-safe mkdir: check again after the call in case another
        // request just created the same directory.
        if (!@mkdir($absDir, 0755, true) && !is_dir($absDir)) {
            return false;
        }
        return is_writable($absDir);
    }

    private static function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload limit.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server is missing a temp directory.',
            UPLOAD_ERR_CANT_WRITE => 'Server failed to write the file.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the upload.',
            default               => 'Upload failed.',
        };
    }
}
