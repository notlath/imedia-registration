<?php
declare(strict_types=1);

/**
 * HMAC signer for /api/submit.
 *
 * Usage:
 *   php tools/sign_submit.php <secret>
 *
 * Output: curl command that sends a signed registration submission.
 */

if ($argc < 2) {
    fwrite(STDERR, "Usage: php tools/sign_submit.php <hmac_secret>\n");
    exit(1);
}

$secret = $argv[1];

$payload = [
    'form_id' => 9999,
    '_imf_timestamp' => time(),
    'fields' => [
        'name'    => 'Jane Doe',
        'mobile'  => '+1234567890',
        'email'   => 'jane.doe@example.com',
        'address' => '123 Main Street, Springfield',
        'course'  => 'Web Development',
        'start_date' => '2026-07-15',
        'end_date'   => '2026-12-15',
    ],
];

$body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$sig = 'sha256=' . hash_hmac('sha256', $body, $secret);

echo json_encode([
    'url'         => 'http://localhost:8080/registration/api/submit',
    'method'      => 'POST',
    'headers'     => [
        'Content-Type: application/json',
        'X-IMF-Signature: ' . $sig,
    ],
    'body'        => $body,
    'curl_command' => sprintf(
        "curl -v -X POST http://localhost:8080/registration/api/submit -H 'Content-Type: application/json' -H 'X-IMF-Signature: %s' -d '%s'",
        $sig,
        $body
    ),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
