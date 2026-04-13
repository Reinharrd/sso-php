interface SSOConfig {
    clientId: string;
    redirectUri: string;
    ssoBaseUrl: string;
}
declare function generateSSOLoginUrl(config: SSOConfig): Promise<string>;
declare function getSSOTokenPayload(token: string): Record<string, any> | null;
interface SSOExchangeConfig {
    code: string;
    clientId: string;
    redirectUri: string;
}
declare function getSSOExchangeBody(config: SSOExchangeConfig): {
    grant_type: string;
    code: string;
    redirect_uri: string;
    client_id: string;
    code_verifier: string;
};
declare function clearSSOData(): void;

declare function generateRandomString(length: number): string;
declare function generateCodeChallenge2(codeVerifier: string): Promise<string>;

export { type SSOConfig, type SSOExchangeConfig, clearSSOData, generateCodeChallenge2, generateRandomString, generateSSOLoginUrl, getSSOExchangeBody, getSSOTokenPayload };
