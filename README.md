# sso-erlangga (PHP)

Library bantuan **SSO OAuth2 dengan PKCE** untuk PHP. `code_verifier` disimpan di **session** (`$_SESSION`), sehingga cocok untuk aplikasi server-side (CodeIgniter 3, Laravel, atau PHP biasa) tanpa JavaScript di browser.

Paket Composer: **`reinharrd/sso-erlangga`**.

---

## Persyaratan

| Persyaratan | Keterangan |
|-------------|------------|
| PHP | ≥ 7.4 |
| `ext-json` | Wajib |
| `ext-curl` | Sangat disarankan (HTTP ke endpoint token; tanpa ini dipakai fallback `streams`) |
| Session | PHP session harus bisa dipakai sebelum redirect ke SSO dan saat kembali ke callback (satu browser, cookie konsisten) |

---

## Instalasi dengan Composer

### Dari Packagist (setelah paket terdaftar)

```bash
composer require reinharrd/sso-erlangga
```

### Dari repositori lokal (pengembangan)

Di `composer.json` project Anda:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../package-sso-edo"
    }
  ],
  "require": {
    "reinharrd/sso-erlangga": "*"
  }
}
```

Lalu:

```bash
composer update reinharrd/sso-erlangga
```

### Dari Git (tanpa Packagist)

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/USERNAME/sso-erlangga.git"
    }
  ],
  "require": {
    "reinharrd/sso-erlangga": "dev-main"
  }
}
```

### Memuat library di kode

Setelah `composer install`, autoload Composer akan mendaftarkan fungsi lewat `files`. Pastikan **autoload vendor** sudah di-require **sekali** di entry point aplikasi:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

// Fungsi namespace SsoErlangga sekarang tersedia.
```

Tidak perlu `require` manual `autoload.php` paket jika Anda sudah memuat `vendor/autoload.php`.

---

## Prasyarat di penyedia SSO

Sebelum mengintegrasikan aplikasi:

1. Daftarkan aplikasi di konsol SSO.
2. Catat **Client ID** dan **base URL** SSO (tanpa slash di akhir), misalnya `https://sso.example.com`.
3. Daftarkan **Redirect URI** — harus **sama persis** dengan URL callback di aplikasi Anda (skema `http`/`https`, host, port, path, dan ada/tidaknya `index.php`).

Contoh redirect URI untuk lokal:

- `http://kelasku.test/index.php/auth/sso_callback`
- `https://app.example.com/auth/sso_callback`

---

## Alur integrasi (ringkas)

1. User membuka halaman “Login SSO” di aplikasi Anda.
2. Aplikasi memanggil `generateSSOLoginUrl()` → library menyimpan PKCE verifier di `$_SESSION` dan mengembalikan URL authorize.
3. Aplikasi mengarahkan user ke URL tersebut (`header('Location: ...')` atau `redirect()`).
4. User login di SSO; SSO mengarahkan kembali ke **redirect URI** Anda dengan query `?code=...`.
5. Di handler callback, panggil `exchangeSSOToken()` dengan `clientId`, `redirectUri` (sama seperti langkah 2), dan `ssoBaseUrl`. Kode otorisasi diambil dari `$_GET['code']` jika Anda tidak mengisi `code` di config.
6. Simpan token yang dikembalikan (session DB, dll.) sesuai kebijakan aplikasi. Panggil `clearSSOData()` setelah sukses jika ingin membersihkan verifier dari session.

---

## Referensi API

Semua fungsi berada di namespace **`SsoErlangga`**. Gunakan:

```php
use function SsoErlangga\generateSSOLoginUrl;
use function SsoErlangga\exchangeSSOToken;
use function SsoErlangga\getSSOExchangeBody;
use function SsoErlangga\getSSOTokenPayload;
use function SsoErlangga\clearSSOData;
```

### `generateSSOLoginUrl(array $config): string`

Membangun URL ke halaman authorize SSO (`{ssoBaseUrl}/callback?...`) dan menyimpan `code_verifier` di `$_SESSION['sso_code_verifier']`.

**`$config` wajib:**

| Key | Tipe | Keterangan |
|-----|------|------------|
| `clientId` | `string` | Client ID dari SSO |
| `redirectUri` | `string` | Callback URL yang terdaftar di SSO |
| `ssoBaseUrl` | `string` | Base URL SSO, tanpa `/` di akhir |

**Contoh:**

```php
$url = generateSSOLoginUrl([
    'clientId'    => getenv('SSO_CLIENT_ID'),
    'redirectUri' => 'https://app.example.com/auth/callback',
    'ssoBaseUrl'  => 'https://sso.example.com',
]);
header('Location: ' . $url);
exit;
```

Library akan memanggil `session_start()` jika session belum aktif.

---

### `exchangeSSOToken(array $config): array`

Mengirim **POST JSON** ke `{ssoBaseUrl}/oauth/token` untuk menukar `code` + PKCE verifier menjadi token.

**`$config`:**

| Key | Wajib | Keterangan |
|-----|--------|------------|
| `clientId` | Ya | Client ID |
| `ssoBaseUrl` | Ya | Base URL SSO |
| `redirectUri` | Tidak | Jika kosong, dipakai default `origin` dari `$_SERVER` + path `/callback` |
| `code` | Tidak | Jika tidak diisi, dipakai `$_GET['code']` |

**Return:** array hasil decode JSON dari server (struktur tergantung SSO), misalnya berisi `access_token`, `refresh_token`, dll.

**Contoh:**

```php
$tokens = exchangeSSOToken([
    'clientId'    => getenv('SSO_CLIENT_ID'),
    'redirectUri' => 'https://app.example.com/auth/callback',
    'ssoBaseUrl'  => 'https://sso.example.com',
]);
```

---

### `getSSOExchangeBody(array $config): array`

Mengembalikan array body JSON untuk exchange **tanpa** mengirim HTTP — berguna jika Anda ingin memanggil endpoint token dengan HTTP client sendiri.

Wajib ada `sso_code_verifier` di session. **`$config`** minimal: `clientId`, `redirectUri`; `code` opsional.

---

### `getSSOTokenPayload(string $token): ?array`

Mendekode bagian payload JWT (segment kedua). Mengembalikan `null` jika token tidak valid.

```php
$payload = getSSOTokenPayload($tokens['access_token'] ?? '');
```

---

### `clearSSOData(): void`

Menghapus dari session key yang dipakai library: `sso_state`, `sso_code_verifier`, `sso_token`.

---

### Fungsi PKCE tingkat rendah

Namespace yang sama:

- `generateRandomString(int $length = 64): string`
- `generateCodeChallenge2(string $codeVerifier): string`

Biasanya tidak perlu dipanggil langsung; `generateSSOLoginUrl` sudah menggunakannya.

---

## Contoh: CodeIgniter 3

1. Pastikan `composer.json` project memuat dependency ini dan `vendor/autoload.php` di-load dari `index.php` atau hook bootstrap CI3, misalnya:

   ```php
   require_once FCPATH . 'vendor/autoload.php';
   ```

2. Simpan konfigurasi di `application/config/sso.php` (atau `.env` yang Anda baca manual).

3. Controller ringkas:

   ```php
   <?php
   defined('BASEPATH') OR exit('No direct script access allowed');

   use function SsoErlangga\generateSSOLoginUrl;
   use function SsoErlangga\exchangeSSOToken;
   use function SsoErlangga\clearSSOData;

   class Auth extends CI_Controller
   {
       public function sso_login()
       {
           $this->config->load('sso');
           $url = generateSSOLoginUrl([
               'clientId'    => $this->config->item('sso_client_id'),
               'redirectUri' => $this->config->item('sso_redirect_uri'),
               'ssoBaseUrl'  => $this->config->item('sso_base_url'),
           ]);
           redirect($url);
       }

       public function sso_callback()
       {
           $this->config->load('sso');
           if ($this->input->get('error')) {
               show_error($this->input->get('error', true), 400);
               return;
           }
           try {
               $tokens = exchangeSSOToken([
                   'clientId'    => $this->config->item('sso_client_id'),
                   'redirectUri' => $this->config->item('sso_redirect_uri'),
                   'ssoBaseUrl'  => $this->config->item('sso_base_url'),
               ]);
               // TODO: simpan token / set session user aplikasi
               clearSSOData();
               redirect('dashboard');
           } catch (Throwable $e) {
               log_message('error', $e->getMessage());
               show_error($e->getMessage(), 500);
           }
       }
   }
   ```

`sso_redirect_uri` harus **identik** dengan yang terdaftar di SSO.

---

## Contoh runnable (tanpa framework)

Folder **`example/`** berisi skrip mini (`public/login.php`, `public/callback.php`). Ikuti **`example/README.txt`** untuk menjalankan dengan `php -S`.

---

## Pemecahan masalah

| Gejala | Kemungkinan penyebab |
|--------|----------------------|
| `Code verifier not found` | Session tidak lanjut setelah kembali dari SSO (host berbeda, cookie, atau session belum dimulai) |
| `invalid_redirect_uri` / ditolak SSO | `redirectUri` di kode tidak sama persis dengan yang terdaftar |
| Token exchange gagal | `ssoBaseUrl` salah, `client_id` salah, atau `code` sudah dipakai / kedaluwarsa |
| `127.0.0.1` vs `localhost` | Dianggap redirect URI berbeda — samakan di SSO dan di config |

---

## Lisensi

Lihat `composer.json` (ISC).
