// src/helper.ts
function base64UrlEncode(arrayBuffer) {
  const bytes = new Uint8Array(arrayBuffer);
  let binary = "";
  for (let i = 0; i < bytes.byteLength; i++) {
    binary += String.fromCharCode(bytes[i]);
  }
  const base64 = btoa(binary);
  return base64.replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/, "");
}
function generateRandomString(length) {
  const charset = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~";
  let result = "";
  const values = new Uint32Array(length);
  window.crypto.getRandomValues(values);
  for (let i = 0; i < length; i++) {
    result += charset[values[i] % charset.length];
  }
  return result;
}
async function generateCodeChallenge2(codeVerifier) {
  const encoder = new TextEncoder();
  const data = encoder.encode(codeVerifier);
  const digest = await window.crypto.subtle.digest("SHA-256", data);
  return base64UrlEncode(digest);
}

// src/sso-helper.ts
async function generateSSOLoginUrl(config) {
  const state = generateRandomString(16);
  const codeVerifier = generateRandomString(64);
  localStorage.setItem("sso_state", state);
  localStorage.setItem("sso_code_verifier", codeVerifier);
  const codeChallenge = await generateCodeChallenge2(codeVerifier);
  const params = new URLSearchParams({
    response_type: "code",
    client_id: config.clientId,
    redirect_uri: config.redirectUri,
    state,
    code_challenge: codeChallenge,
    code_challenge_method: "S256"
  });
  return `${config.ssoBaseUrl}/callback?${params.toString()}`;
}
function getSSOTokenPayload(token) {
  try {
    const base64Url = token.split(".")[1];
    if (!base64Url) return null;
    const base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
    const jsonPayload = decodeURIComponent(
      atob(base64).split("").map((c) => "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2)).join("")
    );
    return JSON.parse(jsonPayload);
  } catch (error) {
    console.error("Failed to parse SSO token payload", error);
    return null;
  }
}
function clearSSOData() {
  localStorage.removeItem("sso_state");
  localStorage.removeItem("sso_code_verifier");
  localStorage.removeItem("sso_token");
}
export {
  clearSSOData,
  generateCodeChallenge2,
  generateRandomString,
  generateSSOLoginUrl,
  getSSOTokenPayload
};
//# sourceMappingURL=index.mjs.map