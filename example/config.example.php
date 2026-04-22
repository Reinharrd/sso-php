<?php
/**
 * Salin file ini menjadi config.php dan isi nilai dari penyedia SSO.
 * redirect_uri harus PERSIS sama dengan yang didaftarkan di konsol SSO
 * (termasuk http/https, host, port, dan path).
 */
return array(
    'clientId' => 'ganti-dengan-client-id',
    'redirectUri' => 'http://127.0.0.1:8080/callback.php',
    'ssoBaseUrl' => 'https://base-url-sso-tanpa-slash-akhir',
);
