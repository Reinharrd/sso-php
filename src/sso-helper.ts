import { generateRandomString, generateCodeChallenge2 } from './helper';

export interface SSOConfig {
  clientId: string;
  redirectUri: string;
  ssoBaseUrl: string;
}

export async function generateSSOLoginUrl(config: SSOConfig): Promise<string> {
  const codeVerifier = generateRandomString(64);

  // Store in localStorage for verification after callback
  localStorage.setItem('sso_code_verifier', codeVerifier);

  const codeChallenge = await generateCodeChallenge2(codeVerifier);

  const params = new URLSearchParams({
    response_type: 'code',
    client_id: config.clientId,
    redirect_uri: config.redirectUri,
    code_challenge: codeChallenge,
    code_challenge_method: 'S256',
  });

  return `${config.ssoBaseUrl}/callback?${params.toString()}`;
}

/**
 * Returns null if the token cannot be decoded.
 */
export function getSSOTokenPayload(token: string): Record<string, any> | null {
  try {
    const base64Url = token.split('.')[1];
    if (!base64Url) return null;
    const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
    const jsonPayload = decodeURIComponent(
      atob(base64)
        .split('')
        .map((c) => '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2))
        .join('')
    );
    return JSON.parse(jsonPayload);
  } catch (error) {
    console.error('Failed to parse SSO token payload', error);
    return null;
  }
}

export function clearSSOData(): void {
  localStorage.removeItem('sso_state');
  localStorage.removeItem('sso_code_verifier');
  localStorage.removeItem('sso_token'); // Assuming the token might be stored this way, though up to the app
}
