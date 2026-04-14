import { generateRandomString, generateCodeChallenge2 } from './helper';

export interface SSOConfig {
  clientId: string;
  redirectUri: string;
  ssoBaseUrl: string;
}

export async function generateSSOLoginUrl(config: SSOConfig): Promise<string> {
  const codeVerifier = generateRandomString(64);

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

export function getSSOExchangeBody(config: SSOExchangeConfig) {
  const codeVerifier = localStorage.getItem('sso_code_verifier') || '';
  if (!codeVerifier) {
    throw new Error('Code verifier not found');
  }

  return {
    grant_type: 'authorization_code',
    code: config.code,
    redirect_uri: config.redirectUri,
    client_id: config.clientId,
    code_verifier: codeVerifier,
  };
}

export interface SSOExchangeConfig {
  code: string;
  clientId: string;
  redirectUri: string;
  ssoBaseUrl: string;
}

export async function exchangeSSOToken(config: SSOExchangeConfig) {

  const codeVerifier = localStorage.getItem('sso_code_verifier') || '';
  if (!codeVerifier) {
    throw new Error('Code verifier not found');
  }

  const body = {
    grant_type: 'authorization_code',
    code: config.code,
    redirect_uri: config.redirectUri ?? window.location.origin + '/callback',
    client_id: config.clientId,
    code_verifier: codeVerifier,
  };

  const response = await fetch(`${config.ssoBaseUrl}/oauth/token`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify(body),
  });

  if (!response.ok) {
    const errorData = await response.json().catch(() => ({}));
    throw new Error(errorData.message || 'Failed to exchange SSO token');
  }

  return response.json();
}

export function clearSSOData(): void {
  localStorage.removeItem('sso_state');
  localStorage.removeItem('sso_code_verifier');
  localStorage.removeItem('sso_token'); // Assuming the token might be stored this way, though up to the app
}
