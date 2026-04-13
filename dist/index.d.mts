interface SSOConfig {
    clientId: string;
    redirectUri: string;
    ssoBaseUrl: string;
}
declare function generateSSOLoginUrl(config: SSOConfig): Promise<string>;
/**
 * Returns null if the token cannot be decoded.
 */
declare function getSSOTokenPayload(token: string): Record<string, any> | null;
declare function clearSSOData(): void;

declare function generateRandomString(length: number): string;
declare function generateCodeChallenge2(codeVerifier: string): Promise<string>;

export { type SSOConfig, clearSSOData, generateCodeChallenge2, generateRandomString, generateSSOLoginUrl, getSSOTokenPayload };
