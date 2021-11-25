<?php

namespace App\Http\Services;

use League\OAuth2\Client\Provider\GenericProvider;
use Microsoft\Graph\Graph as MicrosoftGraph;

class MicrosoftGraphService
{
    public function getOAuthClient(): GenericProvider
    {
        return new GenericProvider([
            'clientId' => config('azure.appId'),
            'clientSecret' => config('azure.appSecret'),
            'redirectUri' => config('azure.redirectUri'),
            'urlAuthorize' => config('azure.authority') . config('azure.authorizeEndpoint'),
            'urlAccessToken' => config('azure.authority') . config('azure.tokenEndpoint'),
            'urlResourceOwnerDetails' => '',
            'scopes' => config('azure.scopes')
        ]);
    }

    public function getGraph(): MicrosoftGraph
    {
        // Get the access token from the cache
        $tokenCache = new TokenCacheService();
        $accessToken = $tokenCache->getAccessToken();

        // Create a Graph client
        $graph = new MicrosoftGraph();
        $graph->setAccessToken($accessToken);
        return $graph;
    }

    public function loadViewData()
    {
        $viewData = [];

        // Check for flash errors
        if (session('error')) {
            $viewData['error'] = session('error');
            $viewData['errorDetail'] = session('errorDetail');
        }

        // Check for logged on user
        if (session('userName'))
        {
            $viewData['userName'] = session('userName');
            $viewData['userEmail'] = session('userEmail');
            $viewData['userTimeZone'] = session('userTimeZone');
        }

        return $viewData;
    }
}
