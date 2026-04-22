<?php

require_once dirname(__FILE__) . '/../autoload.php';

/**
 * @return array
 */
function example_sso_config()
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $path = dirname(__FILE__) . '/config.php';
    if (!is_file($path)) {
        throw new RuntimeException(
            'Buat config.php dari config.example.php dan isi clientId, redirectUri, ssoBaseUrl.'
        );
    }
    $cached = require $path;

    return $cached;
}
