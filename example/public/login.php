<?php

require_once dirname(__FILE__) . '/../bootstrap.php';

$c = example_sso_config();

$url = sso_generate_login_url(array(
    'clientId' => $c['clientId'],
    'redirectUri' => $c['redirectUri'],
    'ssoBaseUrl' => $c['ssoBaseUrl'],
));

header('Location: ' . $url, true, 302);
exit;
