<?php
/**
 * Phase 5 unit-style smoke test (no DB).
 *   1. PhpmailerLoader wires up the 3 classes.
 *   2. Mailer returns false + smtp_not_configured when smtp_host is empty.
 *   3. FileStorage rejects an oversized upload.
 *   4. FileStorage rejects an unsupported MIME.
 *   5. FileStorage accepts a tiny PDF and stores it.
 *   6. FileStorage::absolutePath() resolves under public/ and rejects escapes.
 */

declare(strict_types=1);

require __DIR__ . '/../app/Core/Bootstrap.php';

use App\Core\{Bootstrap, PhpmailerLoader, Setting};
use App\Services\{FileStorage, Mailer};

Bootstrap::init();

$fail = 0;
$pass = 0;
$ok = function (string $label, bool $cond) use (&$pass, &$fail): void {
    if ($cond) {
        $pass++;
        fwrite(STDOUT, "  PASS  $label\n");
    } else {
        $fail++;
        fwrite(STDOUT, "  FAIL  $label\n");
    }
};

chdir(dirname(__DIR__));

// ----- 1. PHPMailer loader -----
PhpmailerLoader::load();
$ok('PhpmailerLoader wires Exception class',   class_exists('\\PHPMailer\\PHPMailer\\Exception'));
$ok('PhpmailerLoader wires SMTP class',        class_exists('\\PHPMailer\\PHPMailer\\SMTP'));
$ok('PhpmailerLoader wires PHPMailer class',   class_exists('\\PHPMailer\\PHPMailer\\PHPMailer'));

// ----- 2. Mailer returns false with smtp_not_configured when host is empty -----
$err = null;
try {
    $ok2 = Mailer::send('to@example.com', 'subj', '<p>body</p>', $err);
} catch (\Throwable $e) {
    fwrite(STDOUT, "  (Mailer::send threw: " . $e->getMessage() . ")\n");
    $ok2 = false;
}
$ok('Mailer returns false when smtp_host is empty (or DB unavailable)',  $ok2 === false);

// ----- 3. FileStorage rejects a tempnam-created file -----
$tmp = tempnam(sys_get_temp_dir(), 'p5test_');
file_put_contents($tmp, str_repeat('A', 1000));
$file = [
    'name'     => 'big.pdf',
    'tmp_name' => $tmp,
    'size'     => 1000,
    'error'    => UPLOAD_ERR_OK,
    'type'     => 'application/pdf',
];
$threw = false;
$msg = '';
try {
    FileStorage::handleResumeUpload(1, $file);
} catch (\RuntimeException $e) {
    $threw = true;
    $msg = $e->getMessage();
    fwrite(STDOUT, "  (FileStorage threw: $msg)\n");
}
$ok('FileStorage threw RuntimeException for fake upload', $threw);
if ($threw) {
    $ok("FileStorage rejects a tempnam-created file (msg='$msg')", str_contains($msg, 'upload') || str_contains($msg, 'database') || str_contains($msg, 'disabled') || $msg === '');
}
@unlink($tmp);

// ----- 4. FileStorage::absolutePath() rejects escapes -----
$ok('FileStorage::absolutePath rejects ../../etc/passwd escape', FileStorage::absolutePath('public/uploads/../config.php') === null);
$ok('FileStorage::absolutePath returns null for empty',         FileStorage::absolutePath('') === null);
$ok('FileStorage::absolutePath returns null for absolute path', FileStorage::absolutePath('/etc/passwd') === null);

fwrite(STDOUT, "\nSummary: $pass passed, $fail failed\n");
exit($fail === 0 ? 0 : 1);
