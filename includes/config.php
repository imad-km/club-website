<?php
define('AES_FINAL_KEY', hex2bin('c2f8a1d7e4b93051facd76e2184059bc1a7f3d8e5c2b4a9f6d1e3c7b8a0f2e4d'));
define('DOK_WINDOW', 10);

function aes_decrypt_raw(string $enc): string|false {
    $raw = base64_decode($enc, true);
    if ($raw === false || strlen($raw) < 16) return false;
    $iv   = substr($raw, 0, 16);
    $data = substr($raw, 16);
    return openssl_decrypt($data, 'AES-256-CBC', AES_FINAL_KEY, OPENSSL_RAW_DATA, $iv);
}

function aes_decrypt(string $enc): ?array {
    $dec = aes_decrypt_raw($enc);
    if ($dec === false) return null;
    return json_decode($dec, true);
}

function aes_decrypt_str(string $enc): ?string {
    $dec = aes_decrypt_raw($enc);
    if ($dec === false) return null;
    return $dec;
}

function verify_dok(string $dok): bool {
    $dec = aes_decrypt($dok);
    if (!$dec || !isset($dec['t'])) return false;
    $diff = abs((microtime(true) * 1000) - (float)$dec['t']);
    return $diff <= (DOK_WINDOW * 1000);
}

function check_request(): ?array {
    $dok     = $_POST['_dok']     ?? '';
    $imadenc = $_POST['_imadenc'] ?? '';
    if (!$dok || !$imadenc) return null;
    if (!verify_dok($dok)) return null;
    return aes_decrypt($imadenc);
}
