# sso-erlangga (PHP)

Library bantuan **SSO OAuth2 dengan PKCE** untuk PHP. `code_verifier` disimpan di **session** (`$_SESSION`), sehingga cocok untuk aplikasi server-side (termasuk CodeIgniter 3) tanpa JavaScript di browser.

Paket Composer: **`reinharrd/sso-erlangga`**.

**Kompatibilitas runtime:** PHP **≥ 5.2.4** (tanpa namespace; fungsi global dengan awalan `sso_`). Untuk keamanan dan entropi PKCE yang lebih baik, disarankan **PHP 5.3+** dengan ekstensi `openssl` (atau **PHP 7+** dengan `random_bytes` jika Anda fork/menambah sendiri).

> **Catatan Composer:** Perintah `composer install` di mesin pengembangan biasanya membutuhkan PHP jauh lebih baru daripada 5.2; itu normal. Yang penting, **server / aplikasi** yang memuat library ini memenuhi `>= 5.2.4` jika Anda menargetkan lingkungan lawas.

---

## Persyaratan

| Persyaratan | Keterangan |
|-------------|------------|
| PHP | ≥ 5.2.4 (`composer.json`) |
| `ext-json` | Wajib |
| `ext-hash` | Wajib untuk `hash('sha256', …)` (PKCE); pada build PHP umum sudah ada |
| `ext-curl` | Sangat disarankan (HTTP ke endpoint token; tanpa ini dipakai fallback `file_get_contents`) |
| `ext-openssl` | Disarankan di PHP 5.3+ untuk `openssl_random_pseudo_bytes` (verifier lebih kuat) |
| Session | Session harus aktif konsisten sebelum redirect ke SSO dan saat kembali ke callback |

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

Setelah `composer install`, autoload Composer mendaftarkan fungsi lewat `files`. Muat vendor sekali di entry point:

```php
<?php

require_once dirname(__FILE__) . '/vendor/autoload.php';
```

Tanpa Composer, salin `autoload.php`, `helper.php`, dan `sso-helper.php` lalu:

```php
require_once dirname(__FILE__) . '/path/ke/autoload.php';
```

---

## Prasyarat di penyedia SSO

1. Daftarkan aplikasi di konsol SSO.
2. Catat **Client ID** dan **base URL** SSO (tanpa slash di akhir).
3. Daftarkan **Redirect URI** sama persis dengan callback aplikasi (skema, host, port, path, `index.php` jika dipakai).

---

## Alur integrasi (ringkas)

1. Panggil `sso_generate_login_url()` → verifier disimpan di `$_SESSION`, dapat URL authorize.
2. Redirect user ke URL tersebut.
3. Setelah login, SSO mengarahkan ke redirect URI dengan `?code=...`.
4. Di callback panggil `sso_exchange_token()` dengan `clientId`, `redirectUri`, `ssoBaseUrl` (dan opsional `code`).
5. Simpan token; panggil `sso_clear_sso_data()` bila ingin membersihkan data SSO di session.

---

## Referensi API

Semua fungsi **global** (bukan namespace). Nama diawali `sso_` untuk mengurangi bentrok.

| Fungsi | Keterangan |
|--------|------------|
| `sso_generate_login_url($config)` | URL authorize + simpan PKCE di session |
| `sso_exchange_token($config)` | POST JSON ke `{ssoBaseUrl}/oauth/token` |
| `sso_get_exchange_body($config)` | Isi body exchange saja (tanpa HTTP) |
| `sso_get_token_payload($token)` | Decode payload JWT (tengah); gagal → `null` |
| `sso_clear_sso_data()` | Hapus `sso_state`, `sso_code_verifier`, `sso_token` dari session |
| `sso_generate_random_string($length)` | Verifier PKCE (biasanya internal) |
| `sso_generate_code_challenge2($verifier)` | Challenge S256 (biasanya internal) |

`$config` adalah array asosiatif dengan key **`clientId`**, **`redirectUri`**, **`ssoBaseUrl`** (untuk login semua wajib; untuk exchange `redirectUri` boleh kosong → default origin + `/callback`).

### Contoh login

```php
$url = sso_generate_login_url(array(
    'clientId'    => 'app-client-id',
    'redirectUri' => 'https://app.example.com/auth/callback',
    'ssoBaseUrl'  => 'https://sso.example.com',
));
header('Location: ' . $url);
exit;
```

### Contoh callback

```php
$tokens = sso_exchange_token(array(
    'clientId'    => 'app-client-id',
    'redirectUri' => 'https://app.example.com/auth/callback',
    'ssoBaseUrl'  => 'https://sso.example.com',
));
sso_clear_sso_data();
```

### Decode JWT (opsional)

```php
$payload = sso_get_token_payload(isset($tokens['access_token']) ? $tokens['access_token'] : '');
```

---

## Contoh: CodeIgniter 3

1. Muat Composer di `index.php` (atau hook), misalnya:

   ```php
   require_once FCPATH . 'vendor/autoload.php';
   ```

2. Konfigurasi di `application/config/sso.php`.

3. Controller (PHP 5.2–kompatibel: tanpa `use function`, tanpa `Throwable`):

   ```php
   <?php
   defined('BASEPATH') OR exit('No direct script access allowed');

   class Auth extends CI_Controller
   {
       public function sso_login()
       {
           $this->config->load('sso');
           $url = sso_generate_login_url(array(
               'clientId'    => $this->config->item('sso_client_id'),
               'redirectUri' => $this->config->item('sso_redirect_uri'),
               'ssoBaseUrl'  => $this->config->item('sso_base_url'),
           ));
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
               $tokens = sso_exchange_token(array(
                   'clientId'    => $this->config->item('sso_client_id'),
                   'redirectUri' => $this->config->item('sso_redirect_uri'),
                   'ssoBaseUrl'  => $this->config->item('sso_base_url'),
               ));
               sso_clear_sso_data();
               redirect('dashboard');
           } catch (Exception $e) {
               log_message('error', $e->getMessage());
               show_error($e->getMessage(), 500);
           }
       }
   }
   ```

---

## Contoh runnable

Folder **`example/`** — lihat **`example/README.txt`**.

---

## Pemecahan masalah

| Gejala | Kemungkinan penyebab |
|--------|----------------------|
| `Code verifier not found` | Session putus setelah redirect (host/cookie/path berbeda) |
| `invalid_redirect_uri` | `redirectUri` tidak identik dengan yang terdaftar |
| Token exchange gagal | URL SSO, `client_id`, atau `code` tidak valid / sudah dipakai |

---

## Lisensi

Lihat `composer.json` (ISC).
