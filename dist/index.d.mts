interface SSOConfig {
    clientId: string;
    redirectUri: string;
    ssoBaseUrl: string;
}
declare function generateSSOLoginUrl(config: SSOConfig): Promise<string>;
declare function getSSOTokenPayload(token: string): Record<string, any> | null;
interface SSOExchangeConfig {
    code?: string;
    clientId: string;
    redirectUri: string;
    ssoBaseUrl?: string;
}
declare function getSSOExchangeBody(config: SSOExchangeConfig): {
    grant_type: string;
    code: string | undefined;
    redirect_uri: string;
    client_id: string;
    code_verifier: string;
};
declare function exchangeSSOToken(config: SSOExchangeConfig): Promise<any>;
declare function clearSSOData(): void;

declare function generateRandomString(length: number): string;
declare function generateCodeChallenge2(codeVerifier: string): Promise<string>;

export { type SSOConfig, type SSOExchangeConfig, clearSSOData, exchangeSSOToken, generateCodeChallenge2, generateRandomString, generateSSOLoginUrl, getSSOExchangeBody, getSSOTokenPayload };
