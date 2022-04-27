<?php

namespace Appwrite\Auth\OAuth2;

use Appwrite\Auth\OAuth2;

// Reference Material
// https://docs.microsoft.com/en-us/azure/active-directory/develop/v2-oauth2-auth-code-flow
// https://docs.microsoft.com/en-us/graph/auth-v2-user

class Microsoft extends OAuth2
{
    /**
     * @var array
     */
    protected $user = [];
    
    /**
     * @var array
     */
    protected $tokens = [];

    /**
     * @var array
     */
    protected $scopes = [
        'offline_access',
        'user.read'
    ];

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'microsoft';
    }
    
    /**
     * @return string
     */
    public function getLoginURL(): string
    {
        return 'https://login.microsoftonline.com/'.$this->getTenantID().'/oauth2/v2.0/authorize?'.\http_build_query([
            'client_id' => $this->appID,
            'redirect_uri' => $this->callback,
            'state'=> \json_encode($this->state),
            'scope'=> \implode(' ', $this->getScopes()),
            'response_type' => 'code',
            'response_mode' => 'query'
        ]);
    }

    /**
     * @param string $code
     *
     * @return array
     */
    protected function getTokens(string $code): array
    {
        if(empty($this->tokens)) {
            $headers = ['Content-Type: application/x-www-form-urlencoded'];
            $this->tokens = \json_decode($this->request(
                'POST',
                'https://login.microsoftonline.com/' . $this->getTenantID() . '/oauth2/v2.0/token',
                $headers,
                \http_build_query([
                    'code' => $code,
                    'client_id' => $this->appID,
                    'client_secret' => $this->getClientSecret(),
                    'redirect_uri' => $this->callback,
                    'scope' => \implode(' ', $this->getScopes()),
                    'grant_type' => 'authorization_code'
                ])
            ), true);
        }

        return $this->tokens;
    }

    /**
     * @param string $refreshToken
     *
     * @return array
     */
    public function refreshTokens(string $refreshToken):array
    {
        $headers = ['Content-Type: application/x-www-form-urlencoded'];
        $this->tokens = \json_decode($this->request(
            'POST',
            'https://login.microsoftonline.com/' . $this->getTenantID() . '/oauth2/v2.0/token',
            $headers,
            \http_build_query([
                'refresh_token' => $refreshToken,
                'client_id' => $this->appID,
                'client_secret' => $this->getClientSecret(),
                'grant_type' => 'refresh_token'
            ])
        ), true);

        if(empty($this->tokens['refresh_token'])) {
            $this->tokens['refresh_token'] = $refreshToken;
        }

        return $this->tokens;
    }

    /**
     * @param string $accessToken
     *
     * @return string
     */
    public function getUserID(string $accessToken): string
    {
        $user = $this->getUser($accessToken);

        if (isset($user['id'])) {
            return $user['id'];
        }

        return '';
    }

    /**
     * @param string $accessToken
     *
     * @return string
     */
    public function getUserEmail(string $accessToken): string
    {
        $user = $this->getUser($accessToken);

        if (isset($user['userPrincipalName'])) {
            return $user['userPrincipalName'];
        }

        return '';
    }

    /**
     * Check if the OAuth email is verified
     * 
     * @param $accessToken
     * 
     * @return bool
     */
    public function isEmailVerified(string $accessToken): bool
    {
        return false;
    }

    /**
     * @param string $accessToken
     *
     * @return string
     */
    public function getUserName(string $accessToken): string
    {
        $user = $this->getUser($accessToken);

        if (isset($user['displayName'])) {
            return $user['displayName'];
        }

        return '';
    }

    /**
     * @param string $accessToken
     *
     * @return array
     */
    protected function getUser(string $accessToken): array
    {
        if (empty($this->user)) {
            $headers = ['Authorization: Bearer '. \urlencode($accessToken)];
            $user = $this->request('GET', 'https://graph.microsoft.com/v1.0/me', $headers);
            $this->user = \json_decode($user, true);
        }

        return $this->user;
    }

    /**
     * Decode the JSON stored in appSecret
     * 
     * @return array
     */
    protected function getAppSecret(): array
    {    
        try {
            $secret = \json_decode($this->appSecret, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $th) {
            throw new \Exception('Invalid secret');
        }
        return $secret;
    }

    /**
     * Extracts the Client Secret from the JSON stored in appSecret
     * 
     * @return string
     */
    protected function getClientSecret(): string
    {
        $secret = $this->getAppSecret();
        return (isset($secret['clientSecret'])) ? $secret['clientSecret'] : ''; 
    }

    /**
     * Extracts the Tenant Id from the JSON stored in appSecret. Defaults to 'common' as a fallback
     * 
     * @return string
     */
    protected function getTenantID(): string
    {
        $secret = $this->getAppSecret();
        return (isset($secret['tenantID'])) ? $secret['tenantID'] : 'common'; 
    }
}
