<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use function SsoErlangga\exchangeSSOToken;
use function SsoErlangga\clearSSOData;

header('Content-Type: text/html; charset=utf-8');

$c = example_sso_config();

if (isset($_GET['error'])) {
    http_response_code(400);
    echo '<h1>OAuth error</h1><pre>' . htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') . '</pre>';
    exit;
}

try {
    $tokens = exchangeSSOToken([
        'clientId' => $c['clientId'],
        'redirectUri' => $c['redirectUri'],
        'ssoBaseUrl' => $c['ssoBaseUrl'],
    ]);
    echo '<h1>Token exchange OK</h1>';
    echo '<pre>' . htmlspecialchars(json_encode($tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') . '</pre>';
    clearSSOData();
} catch (\Throwable $e) {
    http_response_code(500);
    echo '<h1>Gagal</h1>';
    echo '<pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
}
