<?php

namespace App;

use Colorfield\Mastodon\MastodonAPI;
use Colorfield\Mastodon\MastodonOAuth;

class MastodonFactory
{
    public function build(?string $authorizationCode, ?string $clientKey, ?string $clientSecret, ?string $accessToken): ?MastodonAPI
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
}
