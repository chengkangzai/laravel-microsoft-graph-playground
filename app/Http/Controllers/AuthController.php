<?php

namespace App\Http\Controllers;

use App\Http\Services\MicrosoftGraphService;
use App\Http\Services\TokenCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\User;

class AuthController extends Controller
{
    public function signin(MicrosoftGraphService $graphService)
    {
        // Initialize the OAuth client
        $oauthClient = $graphService->getOAuthClient();

        $authUrl = $oauthClient->getAuthorizationUrl();

        // Save client state so we can validate in callback
        Session::put(['oauthState' => $oauthClient->getState()]);

        // Redirect to AAD signin page
        return redirect()->away($authUrl);
    }

    public function callback(Request $request,MicrosoftGraphService $graphService)
    {
        // Validate state
        $expectedState = Session::get('oauthState');
        $request->session()->forget('oauthState');
        $providedState = $request->query('state');

        if (!isset($expectedState)) {
            // If there is no expected state in the session,
            // do nothing and redirect to the home page.
            return redirect('/');
        }

        if (!isset($providedState) || $expectedState != $providedState) {
            return redirect('/')
                ->with('error', 'Invalid auth state')
                ->with('errorDetail', 'The provided auth state did not match the expected value');
        }

        // Authorization code should be in the "code" query param
        $authCode = $request->query('code');
        if (isset($authCode)) {
            // Initialize the OAuth client
            $oauthClient = $graphService->getOAuthClient();

            try {
                // Make the token request
                $accessToken = $oauthClient->getAccessToken('authorization_code', [
                    'code' => $authCode
                ]);

                $graph = new Graph();
                $graph->setAccessToken($accessToken->getToken());

                $user = $graph->createRequest('GET', '/me?$select=displayName,mail,mailboxSettings,userPrincipalName')
                    ->setReturnType(User::class)
                    ->execute();

                $tokenCache = new TokenCacheService();
                $tokenCache->storeTokens($accessToken, $user);
//                // TEMPORARY FOR TESTING!
//                return redirect('/')
//                    ->with('error', 'Access token received')
//                    ->with('errorDetail', 'User:'.$user->getDisplayName().', Token:'.$accessToken->getToken());

            } catch (IdentityProviderException $e) {
                return redirect('/')
                    ->with('error', 'Error requesting access token')
                    ->with('errorDetail', json_encode($e->getResponseBody()));
            }
        }

        return redirect('/')
            ->with('error', $request->query('error'))
            ->with('errorDetail', $request->query('error_description'));
    }

    public function signout()
    {
        $tokenCache = new TokenCacheService();
        $tokenCache->clearTokens();
        return redirect('/');
    }
}
