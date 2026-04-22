# sso-erlangga (PHP)

Library bantuan SSO OAuth2 dengan **PKCE** untuk PHP. `code_verifier` disimpan di **session** (`$_SESSION`), cocok untuk CodeIgniter, Laravel, atau PHP biasa.

## Instalasi

```bash
composer require Reinharrd/sso-erlangga
```

Atau salin `autoload.php`, `helper.php`, dan `sso-helper.php` ke project Anda lalu:

```php
require_once __DIR__ . '/path/to/autoload.php';
```

## Fungsi utama

Namespace: `SsoErlangga`

| Fungsi                         | Keterangan                                                                   |
| ------------------------------ | ---------------------------------------------------------------------------- |
| `generateSSOLoginUrl($config)` | Buat URL authorize; simpan PKCE verifier di session                          |
| `exchangeSSOToken($config)`    | POST ke `{ssoBaseUrl}/oauth/token`, ambil `code` dari `$config` atau `$_GET` |
| `getSSOExchangeBody($config)`  | Body JSON untuk exchange (jika Anda POST manual)                             |
| `getSSOTokenPayload($jwt)`     | Decode payload JWT (bagian tengah)                                           |
| `clearSSOData()`               | Hapus `sso_state`, `sso_code_verifier`, `sso_token` dari session             |

**`$config` login:** `clientId`, `redirectUri`, `ssoBaseUrl`  
**`$config` exchange:** `clientId`, `redirectUri` (opsional; default origin + `/callback`), `ssoBaseUrl`, `code` (opsional)

## Contoh lokal

Lihat folder `example/` dan `example/README.txt`.

## Persyaratan

- PHP ≥ 7.4, ekstensi `json`
- Disarankan: `curl` untuk HTTP ke endpoint token
