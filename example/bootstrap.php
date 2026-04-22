<?php

declare(strict_types=1);

require_once __DIR__ . '/../autoload.php';

/**
 * @return array{clientId: string, redirectUri: string, ssoBaseUrl: string}
 */
function example_sso_config(): array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $path = __DIR__ . '/config.php';
    if (!is_file($path)) {
        throw new \RuntimeException(
            'Buat config.php dari config.example.php dan isi clientId, redirectUri, ssoBaseUrl.'
        );
    }
    $cached = require $path;

    return $cached;
}
