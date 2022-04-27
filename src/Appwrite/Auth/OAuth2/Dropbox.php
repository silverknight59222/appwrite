<?php

namespace Appwrite\Auth\OAuth2;

use Appwrite\Auth\OAuth2;

// Reference Material
// https://www.dropbox.com/developers/reference/oauth-guide
// https://www.dropbox.com/developers/documentation/http/documentation#users-get_current_account

class Dropbox extends OAuth2
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
    protected $scopes = [];

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'dropbox';
    }
    
    /**
     * @return string
     */
    public function getLoginURL(): string
    {
        return 'https://www.dropbox.com/oauth2/authorize?'.\http_build_query([
                'client_id' => $this->appID,
                'redirect_uri' => $this->callback,
                'state' => \json_encode($this->state),
                'response_type' => 'code'
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
                'https://api.dropboxapi.com/oauth2/token',
                $headers,
                \http_build_query([
                    'code' => $code,
                    'client_id' => $this->appID,
                    'client_secret' => $this->appSecret,
                    'redirect_uri' => $this->callback,
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
            'https://api.dropboxapi.com/oauth2/token',
            $headers,
            \http_build_query([
                'refresh_token' => $refreshToken,
                'client_id' => $this->appID,
                'client_secret' => $this->appSecret,
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

        if (isset($user['account_id'])) {
            return $user['account_id'];
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

        if (isset($user['email'])) {
            return $user['email'];
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
    public function isEmailVerififed(string $accessToken): bool
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

        if (isset($user['name'])) {
            return $user['name']['display_name'];
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
            $user = $this->request('POST', 'https://api.dropboxapi.com/2/users/get_current_account', $headers);
            $this->user = \json_decode($user, true);
        }

        return $this->user;
    }
}
