<?php

require_once dirname(__FILE__) . '/../bootstrap.php';

header('Content-Type: text/html; charset=utf-8');

$c = example_sso_config();

if (isset($_GET['error'])) {
    if (function_exists('http_response_code')) {
        http_response_code(400);
    } else {
        header('HTTP/1.1 400 Bad Request');
    }
    echo '<h1>OAuth error</h1><pre>' . htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') . '</pre>';
    exit;
}

try {
    $tokens = sso_exchange_token(array(
        'clientId' => $c['clientId'],
        'redirectUri' => $c['redirectUri'],
        'ssoBaseUrl' => $c['ssoBaseUrl'],
    ));
    echo '<h1>Token exchange OK</h1>';
    $jsonFlags = 0;
    if (defined('JSON_PRETTY_PRINT')) {
        $jsonFlags |= JSON_PRETTY_PRINT;
    }
    if (defined('JSON_UNESCAPED_SLASHES')) {
        $jsonFlags |= JSON_UNESCAPED_SLASHES;
    }
    echo '<pre>' . htmlspecialchars(json_encode($tokens, $jsonFlags), ENT_QUOTES, 'UTF-8') . '</pre>';
    sso_clear_sso_data();
} catch (Exception $e) {
    if (function_exists('http_response_code')) {
        http_response_code(500);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
    }
    echo '<h1>Gagal</h1>';
    echo '<pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
}
