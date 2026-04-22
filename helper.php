<?php
/**
 * PKCE helpers — kompatibel PHP >= 5.2.4 (tanpa namespace, tanpa short array).
 */

if (!function_exists('sso_generate_random_string')) {
    /**
     * Code verifier RFC 7636 (charset 43–128).
     *
     * @param int $length
     * @return string
     */
    function sso_generate_random_string($length = 64)
    {
        $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~';
        $result = '';
        $length = (int) $length;
        if ($length < 1) {
            $length = 64;
        }

        $bytes = null;
        if (function_exists('openssl_random_pseudo_bytes')) {
            $strong = false;
            $raw = openssl_random_pseudo_bytes($length, $strong);
            if ($raw !== false && strlen($raw) >= $length) {
                $bytes = $raw;
            }
        }
        if ($bytes === null) {
            $bytes = '';
            for ($i = 0; $i < $length; $i++) {
                $bytes .= chr(mt_rand(0, 255));
            }
        }

        for ($i = 0; $i < $length; $i++) {
            $result .= $charset[ord($bytes[$i]) % strlen($charset)];
        }

        return $result;
    }
}

if (!function_exists('sso_generate_code_challenge2')) {
    /**
     * S256: SHA-256 + base64url.
     *
     * @param string $codeVerifier
     * @return string
     */
    function sso_generate_code_challenge2($codeVerifier)
    {
        $hash = hash('sha256', $codeVerifier, true);

        return rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
    }
}
