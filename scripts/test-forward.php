<?php
/**
 * Smoke test for the WP-to-standalone submission contract.
 *
 * Builds a canonical payload, signs it with HMAC-SHA256, POSTs to the
 * standalone app's /api/submit endpoint, and asserts a 201 response.
 *
 * Usage:
 *   php scripts/test-forward.php <base_url> <secret> [form_id]
 *
 * Examples:
 *   php scripts/test-forward.php http://localhost:8080 "my-hmac-secret" 42
 *   php scripts/test-forward.php http://localhost:8080 "$(mysql ...)" 1
 *
 * Returns exit code 0 on success, 1 on failure.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script is CLI-only.\n");
    exit(1);
}

// ---- Parse args ----
$args    = array_slice($argv, 1);
$baseUrl = $args[0] ?? '';
$secret  = $args[1] ?? '';
$formId  = (int) ($args[2] ?? 1);

if ($baseUrl === '' || $secret === '') {
    fwrite(STDERR, "Usage: php scripts/test-forward.php <base_url> <secret> [form_id]\n");
    exit(1);
}

$submitUrl = rtrim($baseUrl, '/') . '/api/submit';

// ---- Build payload ----
$payload = array(
    'form_id'        => $formId,
    'fields'         => array(
        'name'       => 'Test User',
        'email'      => 'test-forward@example.com',
        'mobile'     => '09170000000',
        'course'     => 'Smoke Test Course',
        'start_date' => date('Y-m-d'),
        'end_date'   => date('Y-m-d', strtotime('+3 months')),
        'address'    => '123 Smoke Test St',
        'subject'    => 'Smoke Test',
        'message'    => 'This is a smoke test submission.',
        'extra_field' => 'should-land-in-dynamic-data',
    ),
    '_imf_timestamp' => time(),
);

$bodyJson = json_encode($payload);
if ($bodyJson === false) {
    fwrite(STDERR, "Failed to encode payload.\n");
    exit(1);
}

// ---- Sign ----
$signature = 'sha256=' . hash_hmac('sha256', $bodyJson, $secret);

fwrite(STDOUT, "[test-forward] POST {$submitUrl}\n");
fwrite(STDOUT, "[test-forward] form_id={$formId}, payload_size=" . strlen($bodyJson) . "\n");

// ---- POST (non-blocking, but we want the response here) ----
$ch = curl_init($submitUrl);
curl_setopt_array($ch, array(
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $bodyJson,
    CURLOPT_HTTPHEADER     => array(
        'Content-Type: application/json; charset=utf-8',
        'Accept: application/json',
        'X-IMF-Signature: ' . $signature,
        'User-Agent: IMF-SmokeTest/1.0',
    ),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
));

$response   = curl_exec($ch);
$httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr    = curl_error($ch);
curl_close($ch);

// ---- Assert ----
if ($curlErr !== '') {
    fwrite(STDERR, "[test-forward] FAIL: curl error: {$curlErr}\n");
    exit(1);
}

$decoded = json_decode($response, true);
$success = ($httpCode === 201) && isset($decoded['success']) && $decoded['success'] === true;

if ($success) {
    $id = $decoded['id'] ?? 'unknown';
    fwrite(STDOUT, "[test-forward] PASS: 201 Created, id={$id}\n");
    exit(0);
}

fwrite(STDERR, "[test-forward] FAIL: HTTP {$httpCode}, body=" . ($response ?: '(empty)') . "\n");
exit(1);
