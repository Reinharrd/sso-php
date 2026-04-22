Contoh tes SSO (alur PKCE sama dengan library ini)

1) Daftarkan aplikasi di konsol SSO:
   - redirect_uri harus sama persis dengan nilai redirectUri di config.php
   - Contoh: http://127.0.0.1:8080/callback.php

2) Salin config:
   copy config.example.php config.php
   Edit clientId, redirectUri, ssoBaseUrl.

3) Jalankan server PHP dari folder example (bukan dari public):
   php -S 127.0.0.1:8080 -t public

4) Buka browser:
   http://127.0.0.1:8080/
   Klik "Mulai login SSO" atau buka langsung http://127.0.0.1:8080/login.php

5) Setelah login di SSO, Anda akan diarahkan ke callback.php.
   Jika sukses, respons token ditampilkan (hanya untuk debug).

Catatan:
- Gunakan host yang sama di URL dan di redirect_uri (127.0.0.1 vs localhost beda).
- Produksi: HTTPS wajib; sesuaikan redirect_uri.
- Jangan commit config.php (berisi identitas klien).
