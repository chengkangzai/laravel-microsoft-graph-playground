<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Session;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class TokenCacheService
{
    public function storeTokens($accessToken, $user)
    {
        Session::put([
            'accessToken' => $accessToken->getToken(),
            'refreshToken' => $accessToken->getRefreshToken(),
            'tokenExpires' => $accessToken->getExpires(),
            'userName' => $user->getDisplayName(),
            'userEmail' => null !== $user->getMail() ? $user->getMail() : $user->getUserPrincipalName(),
            'userTimeZone' => $user->getMailboxSettings()->getTimeZone()
        ]);
    }

    public function clearTokens()
    {
        Session::forget([
            'accessToken',
            'refreshToken',
            'tokenExpires',
            'userName',
            'userEmail',
            'userTimeZone',
        ]);
    }

    public function getAccessToken()
    {
        // Check if tokens exist
        if (!Session::exists(['accessToken', 'refreshToken', 'tokenExpires',])) {
            return '';
        }

        // Check if token is expired
        $now = time() + 300;
        if (!Session::get('tokenExpires') >= $now) {
            return Session::get('accessToken');
        }


        // Token is expired (or very close to it) so let's refresh
        $oauthClient = app(MicrosoftGraphService::class)->getOAuthClient();

        try {
            $newToken = $oauthClient->getAccessToken('refresh_token', [
                'refresh_token' => Session::get('refreshToken')
            ]);

            // Store the new values
            $this->updateTokens($newToken);

            return $newToken->getToken();
        } catch (IdentityProviderException $e) {
            return '';
        }

    }

    public function updateTokens($accessToken)
    {
        Session::put([
            'accessToken' => $accessToken->getToken(),
            'refreshToken' => $accessToken->getRefreshToken(),
            'tokenExpires' => $accessToken->getExpires()
        ]);
    }
}
