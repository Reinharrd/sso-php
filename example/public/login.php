<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use function SsoErlangga\generateSSOLoginUrl;

$c = example_sso_config();

$url = generateSSOLoginUrl([
    'clientId' => $c['clientId'],
    'redirectUri' => $c['redirectUri'],
    'ssoBaseUrl' => $c['ssoBaseUrl'],
]);

header('Location: ' . $url, true, 302);
exit;
