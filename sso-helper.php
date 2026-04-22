<?php
/**
 * SSO OAuth2 + PKCE — kompatibel PHP >= 5.2.4.
 * Session keys: sso_code_verifier, sso_state, sso_token
 */

require_once dirname(__FILE__) . '/helper.php';

if (!function_exists('sso__ensure_session')) {
    function sso__ensure_session()
    {
        if (function_exists('session_status')) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
        } else {
            if (session_id() == '') {
                session_start();
            }
        }
    }
}

if (!function_exists('sso__default_redirect_uri')) {
    function sso__default_redirect_uri()
    {
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $https ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

        return $scheme . '://' . $host . '/callback';
    }
}

if (!function_exists('sso__http_post_json')) {
    /**
     * @param string $url
     * @param string $jsonBody
     * @return array Array with keys 'status' (int) and 'body' (string)
     */
    function sso__http_post_json($url, $jsonBody)
    {
        $headers = array(
            'Content-Type: application/json',
            'Accept: application/json',
        );

        if (extension_loaded('curl')) {
            $ch = curl_init($url);
            if ($ch === false) {
                throw new RuntimeException('Failed to initialize cURL');
            }
            curl_setopt_array($ch, array(
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $jsonBody,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
            ));
            $body = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($body === false) {
                throw new RuntimeException('cURL request failed');
            }

            return array('status' => $status, 'body' => $body);
        }

        $header = implode("\r\n", $headers);
        $ctx = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => $header,
                'content' => $jsonBody,
                'ignore_errors' => true,
            ),
        ));
        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) {
            throw new RuntimeException('HTTP request failed');
        }
        $status = 0;
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $line) {
                if (preg_match('#^HTTP/\S+\s+(\d+)#', $line, $m)) {
                    $status = (int) $m[1];
                    break;
                }
            }
        }

        return array('status' => $status, 'body' => $body);
    }
}

if (!function_exists('sso_generate_login_url')) {
    /**
     * Bangun URL authorize; simpan code_verifier di session.
     *
     * @param array $config Keys: clientId, redirectUri, ssoBaseUrl
     * @return string
     */
    function sso_generate_login_url($config)
    {
        $clientId = isset($config['clientId']) ? $config['clientId'] : '';
        $redirectUri = isset($config['redirectUri']) ? $config['redirectUri'] : '';
        $ssoBaseUrl = isset($config['ssoBaseUrl']) ? $config['ssoBaseUrl'] : '';
        if ($clientId === '' || $redirectUri === '' || $ssoBaseUrl === '') {
            throw new InvalidArgumentException('clientId, redirectUri, and ssoBaseUrl are required');
        }

        sso__ensure_session();

        $codeVerifier = sso_generate_random_string(64);
        $_SESSION['sso_code_verifier'] = $codeVerifier;

        $codeChallenge = sso_generate_code_challenge2($codeVerifier);

        $params = array(
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        );

        $query = http_build_query($params, '', '&');

        return rtrim($ssoBaseUrl, '/') . '/callback?' . $query;
    }
}

if (!function_exists('sso_get_token_payload')) {
    /**
     * Decode payload JWT (segment kedua).
     *
     * @param string $token
     * @return array|null
     */
    function sso_get_token_payload($token)
    {
        try {
            $parts = explode('.', $token);
            $base64Url = isset($parts[1]) ? $parts[1] : '';
            if ($base64Url === '') {
                return null;
            }
            $base64 = strtr($base64Url, '-_', '+/');
            $pad = strlen($base64) % 4;
            if ($pad > 0) {
                $base64 .= str_repeat('=', 4 - $pad);
            }
            $jsonPayload = base64_decode($base64, true);
            if ($jsonPayload === false) {
                return null;
            }
            $decoded = json_decode($jsonPayload, true);
            if (function_exists('json_last_error')) {
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                    return null;
                }
            } elseif (!is_array($decoded)) {
                return null;
            }

            return $decoded;
        } catch (Exception $e) {
            error_log('Failed to parse SSO token payload: ' . $e->getMessage());

            return null;
        }
    }
}

if (!function_exists('sso_get_exchange_body')) {
    /**
     * Body untuk POST /oauth/token (tanpa mengirim HTTP).
     *
     * @param array $config
     * @return array
     */
    function sso_get_exchange_body($config)
    {
        sso__ensure_session();

        $codeVerifier = isset($_SESSION['sso_code_verifier']) ? $_SESSION['sso_code_verifier'] : '';
        if ($codeVerifier === '') {
            throw new RuntimeException('Code verifier not found');
        }

        return array(
            'grant_type' => 'authorization_code',
            'code' => isset($config['code']) ? $config['code'] : null,
            'redirect_uri' => $config['redirectUri'],
            'client_id' => $config['clientId'],
            'code_verifier' => $codeVerifier,
        );
    }
}

if (!function_exists('sso_exchange_token')) {
    /**
     * Tukar authorization code ke token.
     *
     * @param array $config clientId, ssoBaseUrl wajib; redirectUri opsional; code opsional (pakai $_GET['code'])
     * @return array
     */
    function sso_exchange_token($config)
    {
        sso__ensure_session();

        $code = isset($config['code']) ? $config['code'] : null;
        if ($code === null && isset($_GET['code'])) {
            $code = $_GET['code'];
        }

        $codeVerifier = isset($_SESSION['sso_code_verifier']) ? $_SESSION['sso_code_verifier'] : '';
        if ($codeVerifier === '') {
            throw new RuntimeException('Code verifier not found');
        }

        $redirectUri = isset($config['redirectUri']) ? $config['redirectUri'] : '';
        if ($redirectUri === '') {
            $redirectUri = sso__default_redirect_uri();
        }

        $ssoBaseUrl = isset($config['ssoBaseUrl']) ? $config['ssoBaseUrl'] : '';
        if ($ssoBaseUrl === '') {
            throw new InvalidArgumentException('ssoBaseUrl is required');
        }

        $body = array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'client_id' => $config['clientId'],
            'code_verifier' => $codeVerifier,
        );

        $url = rtrim($ssoBaseUrl, '/') . '/oauth/token';
        $jsonBody = json_encode($body);
        if ($jsonBody === false) {
            throw new RuntimeException('Failed to encode request body');
        }

        $response = sso__http_post_json($url, $jsonBody);

        $status = $response['status'];
        $responseBody = $response['body'];

        if ($status < 200 || $status >= 300) {
            $errorData = json_decode($responseBody, true);
            if ($errorData === null && $responseBody !== '') {
                error_log('Gagal login: invalid JSON');
            }
            $message = 'Failed to exchange SSO token';
            if (is_array($errorData) && isset($errorData['message'])) {
                $message = (string) $errorData['message'];
            }
            throw new RuntimeException($message);
        }

        $decoded = json_decode($responseBody, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Invalid token response');
        }

        return $decoded;
    }
}

if (!function_exists('sso_clear_sso_data')) {
    function sso_clear_sso_data()
    {
        sso__ensure_session();
        unset($_SESSION['sso_state'], $_SESSION['sso_code_verifier'], $_SESSION['sso_token']);
    }
}
