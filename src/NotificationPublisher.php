<?php

namespace App;

use App\Entity\Episode;
use Colorfield\Mastodon\MastodonAPI;
use Rudolf\OAuth2\Client\Provider\Reddit;
use Symfony\Component\Routing\RouterInterface;

class NotificationPublisher
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var MastodonAPI|null
     */
    private $mastodonApi;

    /**
     * @var Reddit|null
     */
    private $redditApi;

    public function __construct(RouterInterface $router, ?MastodonAPI $mastodonApi, ?Reddit $redditApi)
    {
        $this->router = $router;
        $this->mastodonApi = $mastodonApi;
        $this->redditApi = $redditApi;
    }

    public function publishEpisode(Episode $episode)
    {
        $path = $this->router->generate('player', ['episode' => $episode->getCode()], RouterInterface::ABSOLUTE_URL);
        $path = str_replace(['localhost:8033', 'localhost'], 'noagendaexperience.com', $path);

        $title = sprintf('No Agenda Episode %s - %s', $episode->getCode(), $episode->getName());

        $contents = sprintf('%s %s', $title, $path);

        if ($this->mastodonApi) {
            // todo add episode art as media file
            // see https://github.com/tootsuite/documentation/blob/master/Using-the-API/API.md#media

            $this->mastodonApi->post('/statuses', [
                'status' => $contents,
            ]);
        }

        if ($this->redditApi) {
            $client = $this->redditApi->getHttpClient();

            $message = [
                'title' => $title,
                'url' => $path,
                'sr' => 'trollroom',
                'api_type' => 'json',
                'kind' => 'link',
                // 'resubmit' => 'true',
            ];

            $response = $client->request('post', 'https://oauth.reddit.com/api/submit', [
                'form_params' => $message,
            ]);
        }
    }
}
