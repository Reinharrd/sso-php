<?php

declare(strict_types=1);

namespace SsoErlangga;

require_once __DIR__ . '/helper.php';

function ssoEnsureSession(): void
{
    if (session_status() === \PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Default redirect_uri when omitted: current origin + /callback.
 */
function ssoDefaultRedirectUri(): string
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . '://' . $host . '/callback';
}

/**
 * HTTP POST JSON to token endpoint (curl if available, else streams).
 *
 * @return array{status:int, body:string}
 */
function ssoHttpPostJson(string $url, string $jsonBody): array
{
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];

    if ($curl = extension_loaded('curl')) {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('Failed to initialize cURL');
        }
        curl_setopt_array($ch, [
            \CURLOPT_POST => true,
            \CURLOPT_POSTFIELDS => $jsonBody,
            \CURLOPT_HTTPHEADER => $headers,
            \CURLOPT_RETURNTRANSFER => true,
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false) {
            throw new \RuntimeException('cURL request failed');
        }

        return ['status' => $status, 'body' => $body];
    }

    $header = implode("\r\n", $headers);
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => $header,
            'content' => $jsonBody,
            'ignore_errors' => true,
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) {
        throw new \RuntimeException('HTTP request failed');
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

    return ['status' => $status, 'body' => $body];
}

/**
 * @param array{clientId: string, redirectUri: string, ssoBaseUrl: string} $config
 */
function generateSSOLoginUrl(array $config): string
{
    $clientId = $config['clientId'] ?? '';
    $redirectUri = $config['redirectUri'] ?? '';
    $ssoBaseUrl = $config['ssoBaseUrl'] ?? '';
    if ($clientId === '' || $redirectUri === '' || $ssoBaseUrl === '') {
        throw new \InvalidArgumentException('clientId, redirectUri, and ssoBaseUrl are required');
    }

    ssoEnsureSession();

    $codeVerifier = generateRandomString(64);
    $_SESSION['sso_code_verifier'] = $codeVerifier;

    $codeChallenge = generateCodeChallenge2($codeVerifier);

    $params = [
        'response_type' => 'code',
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'code_challenge' => $codeChallenge,
        'code_challenge_method' => 'S256',
    ];

    $query = http_build_query($params, '', '&', \PHP_QUERY_RFC3986);

    return rtrim($ssoBaseUrl, '/') . '/callback?' . $query;
}

function getSSOTokenPayload(string $token): ?array
{
    try {
        $parts = explode('.', $token);
        $base64Url = $parts[1] ?? '';
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
        if (json_last_error() !== \JSON_ERROR_NONE || !is_array($decoded)) {
            return null;
        }

        return $decoded;
    } catch (\Throwable $e) {
        error_log('Failed to parse SSO token payload: ' . $e->getMessage());

        return null;
    }
}

/**
 * @param array{code?: string|null, clientId: string, redirectUri: string, ssoBaseUrl?: string} $config
 *
 * @return array{grant_type: string, code: mixed, redirect_uri: string, client_id: string, code_verifier: string}
 */
function getSSOExchangeBody(array $config): array
{
    ssoEnsureSession();

    $codeVerifier = $_SESSION['sso_code_verifier'] ?? '';
    if ($codeVerifier === '') {
        throw new \RuntimeException('Code verifier not found');
    }

    return [
        'grant_type' => 'authorization_code',
        'code' => $config['code'] ?? null,
        'redirect_uri' => $config['redirectUri'],
        'client_id' => $config['clientId'],
        'code_verifier' => $codeVerifier,
    ];
}

/**
 * @param array{code?: string|null, clientId: string, redirectUri?: string, ssoBaseUrl?: string} $config
 *
 * @return array<mixed>
 */
function exchangeSSOToken(array $config): array
{
    ssoEnsureSession();

    $code = $config['code'] ?? ($_GET['code'] ?? null);
    $codeVerifier = $_SESSION['sso_code_verifier'] ?? '';
    if ($codeVerifier === '') {
        throw new \RuntimeException('Code verifier not found');
    }

    $redirectUri = $config['redirectUri'] ?? '';
    if ($redirectUri === '') {
        $redirectUri = ssoDefaultRedirectUri();
    }

    $ssoBaseUrl = $config['ssoBaseUrl'] ?? '';
    if ($ssoBaseUrl === '') {
        throw new \InvalidArgumentException('ssoBaseUrl is required');
    }

    $body = [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirectUri,
        'client_id' => $config['clientId'],
        'code_verifier' => $codeVerifier,
    ];

    $url = rtrim($ssoBaseUrl, '/') . '/oauth/token';
    $jsonBody = json_encode($body);
    if ($jsonBody === false) {
        throw new \RuntimeException('Failed to encode request body');
    }

    $response = ssoHttpPostJson($url, $jsonBody);

    $status = $response['status'];
    $responseBody = $response['body'];

    if ($status < 200 || $status >= 300) {
        $errorData = json_decode($responseBody, true);
        if ($errorData === null && $responseBody !== '') {
            error_log('Gagal login: invalid JSON');
        }
        $message = is_array($errorData) && isset($errorData['message'])
            ? (string) $errorData['message']
            : 'Failed to exchange SSO token';
        throw new \RuntimeException($message);
    }

    $decoded = json_decode($responseBody, true);
    if (!is_array($decoded)) {
        throw new \RuntimeException('Invalid token response');
    }

    return $decoded;
}

function clearSSOData(): void
{
    ssoEnsureSession();
    unset($_SESSION['sso_state'], $_SESSION['sso_code_verifier'], $_SESSION['sso_token']);
}
