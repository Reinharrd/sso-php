<?php

declare(strict_types=1);

namespace SsoErlangga;

/**
 * PKCE code verifier (RFC 7636).
 */
function generateRandomString(int $length = 64): string
{
    $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~';
    $result = '';
    $bytes = random_bytes($length);
    for ($i = 0; $i < $length; $i++) {
        $result .= $charset[ord($bytes[$i]) % strlen($charset)];
    }

    return $result;
}

/**
 * S256 code challenge from verifier (SHA-256 + base64url).
 */
function generateCodeChallenge2(string $codeVerifier): string
{
    $hash = hash('sha256', $codeVerifier, true);

    return rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
}
