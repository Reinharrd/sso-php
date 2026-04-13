# package-sso

Modul Helper SSO (Single Sign-On) yang menyediakan fungsionalitas untuk konfigurasi login, parsing token, dan pertukaran kode otorisasi berbasis PKCE.

## Instalasi

```bash
npm install package-sso
# atau
pnpm install package-sso
# atau 
yarn add package-sso
```

## Penggunaan

Dokumentasi di bawah ini merupakan referensi fungsi-fungsi dari `sso-helper`.

### 1. `generateSSOLoginUrl(config: SSOConfig)`
Fungsi ini digunakan untuk menghasilkan URL halaman login SSO. Fungsi ini juga secara otomatis meng-generate `code_challenge` untuk standar PKCE dan mengamankan `code_verifier` di dalam `localStorage`.

**Tipe Data (Interface):**
```typescript
interface SSOConfig {
  clientId: string;      // Client ID aplikasi Anda
  redirectUri: string;   // Callback URI
  ssoBaseUrl: string;    // URL dasar / Base URL SSO
}
```

**Contoh Penggunaan:**
```typescript
import { generateSSOLoginUrl } from 'package-sso';

async function loginStart() {
  const loginUrl = await generateSSOLoginUrl({
    clientId: 'app-client-123',
    redirectUri: 'http://localhost:3000/callback',
    ssoBaseUrl: 'https://sso.example.com'
  });

  // Arahkan user ke halaman login SSO:
  window.location.href = loginUrl;
}
```

---

### 2. `getSSOTokenPayload(token: string)`
Fungsi ini bertugas untuk mendekode dan melakukan parsing payload dari sebuah string token berformat JWT.

**Contoh Penggunaan:**
```typescript
import { getSSOTokenPayload } from 'package-sso';

const tokenUrl = "eyJhbGc.eyJzdWIiOiIxMjMifQ.sgn";
const payload = getSSOTokenPayload(tokenUrl);

if (payload) {
  console.log("Data Payload User:", payload);
} else {
  console.log("Token tidak valid / gagal parsing");
}
```

---

### 3. `getSSOExchangeBody(config: SSOExchangeConfig)`
Fungsi ini sangat penting ketika user dikembalikan dari SSO ke aplikasi Anda (Callback). Ia akan menyiapkan Object body untuk ditukar (_exchange_) dengan Access Token. Secara otomatis mengambil `code_verifier` yang dibentuk pada tahap pertama dari `localStorage`.

**Tipe Data (Interface):**
```typescript
interface SSOExchangeConfig {
  code: string;         // Kode otorisasi yg didapat dari query parameter URL Callback
  clientId: string;     // Client ID aplikasi
  redirectUri: string;  // URI Redirect yang dikonfigurasi
}
```

**Contoh Penggunaan:**
```typescript
import { getSSOExchangeBody } from 'package-sso';

// Misalnya didapatkan dari URL query `?code=xyZ123`
const authCode = "xyZ123"; 

try {
  const requestBody = getSSOExchangeBody({
    code: authCode,
    clientId: 'app-client-123',
    redirectUri: 'http://localhost:3000/callback'
  });

  // Anda dapat menggunakan `requestBody` ini untuk Post payload Axios/Fetch 
  // ke Endpoin Token SSO backend Anda.
  /*
    axios.post('https://sso.example.com/token', requestBody)
  */

} catch (error) {
  // Menangkap error jika `code_verifier` tidak ditemukan di browser
  console.error(error.message); 
}
```

---

### 4. `clearSSOData()`
Membersihkan seluruh jejak local state terkait SSO (`sso_state`, `sso_code_verifier`, dan `sso_token`) di dalam `localStorage`.

**Contoh Penggunaan:**
```typescript
import { clearSSOData } from 'package-sso';

function doLogout() {
  clearSSOData();
  // Lanjut redirect ke home, dsb...
}
```
