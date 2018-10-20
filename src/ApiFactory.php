<?php

namespace App;

use Colorfield\Mastodon\MastodonAPI;
use Colorfield\Mastodon\MastodonOAuth;
use GuzzleHttp\Client as HttpClient;
use Rudolf\OAuth2\Client\Provider\Reddit;

class ApiFactory
{
    public static $redditAccessToken = null;

    public function mastodon(?string $authorizationCode, ?string $clientKey, ?string $clientSecret, ?string $accessToken): ?MastodonAPI
    {
        if (in_array(null, [$authorizationCode, $clientKey, $clientSecret, $accessToken])) {
            return null;
        }

        $auth = new MastodonOAuth('No Agenda Experience', 'noagendasocial.com');
        $auth->config->setAuthorizationCode($authorizationCode);
        $auth->config->setClientId($clientKey);
        $auth->config->setClientSecret($clientSecret);
        $auth->config->setBearer($accessToken);

        return new MastodonAPI($auth->config);
    }

    public function reddit(?string $clientKey, ?string $clientSecret, ?string $username, ?string $password): ?Reddit
    {
        if (in_array(null, [$clientKey, $clientSecret, $username, $password])) {
            return null;
        }

        $reddit = new Reddit([
            'clientId'      => $clientKey,
            'clientSecret'  => $clientSecret,
            'redirectUri'   => 'http://noagendaexperience.com',
            'userAgent'     => 'php:noagendaexperience:0.1 (by /u/RussianTroll9476)',
            'scopes'        => ['identity', 'read', 'write'],
        ]);

        $token = $reddit->getAccessToken('password', [
            'username' => $username,
            'password' => $password,
        ]);

        self::$redditAccessToken = $token->getToken();

        $client = new HttpClient([
            'headers' => [
                'User-Agent' => 'php:noagendaexperience:0.1 (by /u/RussianTroll9476)',
                'Authorization' => 'bearer ' . ApiFactory::$redditAccessToken,
            ],
        ]);

        $reddit->setHttpClient($client);

        return $reddit;
    }
}
