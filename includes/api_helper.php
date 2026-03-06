<?php
// api_helper.php — shared API calls using the JWT access token from session
require_once __DIR__ . '/config.php';

define('API_BASE', 'http://173.249.28.246:8090/api/v1');

/**
 * Make an authenticated API request.
 * Returns ['code' => int, 'data' => array]
 */
function api_get(string $path): array {
    $token = $_SESSION['access'] ?? '';
    $ch = curl_init(API_BASE . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_TIMEOUT => 15,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'data' => json_decode($body, true) ?? []];
}

function api_post(string $path, array $payload): array {
    $token = $_SESSION['access'] ?? '';
    $ch = curl_init(API_BASE . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT    => 15,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'data' => json_decode($body, true) ?? []];
}

/**
 * Guard: redirect to login if no session token.
 * Also verify the token by calling /me.
 * Returns the current user array.
 */
function require_auth(): array {
    if (!isset($_SESSION['access'])) {
        header('Location: student_login.php');
        exit();
    }
    $r = api_get('/me');
    if ($r['code'] !== 200) {
        session_destroy();
        header('Location: student_login.php');
        exit();
    }
    return $r['data'];
}
