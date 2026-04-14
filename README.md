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

### 3. `exchangeSSOToken(config: SSOExchangeConfig)`
Fungsi ini sangat penting ketika user dikembalikan dari SSO ke aplikasi Anda (Callback). Ia akan melakukan **HTTP Post (fetch) secara langsung** ke endpoint `/oauth/token` untuk menukarkan (_exchange_) authorization code dengan Access Token. Secara otomatis menyesuaikan URL dinamis dari props dan mengambil `code_verifier` yang dibentuk pada tahap pertama dari `localStorage`.

**Tipe Data (Interface):**
```typescript
interface SSOExchangeConfig {
  code: string;         // Kode otorisasi yg didapat dari query parameter URL Callback
  clientId: string;     // Client ID aplikasi
  redirectUri: string;  // URI Redirect yang dikonfigurasi
  ssoBaseUrl: string;   // URL dasar / Base URL SSO (dinamis untuk staging/production)
}
```

**Contoh Penggunaan:**
```typescript
import { exchangeSSOToken } from 'package-sso';

// Misalnya didapatkan dari URL query `?code=xyZ123`
const authCode = "xyZ123"; 

async function handleSSOCallback() {
  try {
    const responseData = await exchangeSSOToken({
      code: authCode,
      clientId: 'app-client-123',
      redirectUri: 'http://localhost:3000/callback',
      ssoBaseUrl: 'https://api-staging.erlangga.co.id' // via props / env variables
    });

    console.log("Token Payload:", responseData);
    // Simpan token/akses Anda atau update global store/session

  } catch (error) {
    // Menangkap error jika verifier hilang atau API gagal
    console.error("Gagal melakukan exchange code:", error.message); 
  }
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
