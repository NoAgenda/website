<?php

namespace App;

use App\Entity\Episode;
use Colorfield\Mastodon\MastodonAPI;
use Symfony\Component\Routing\RouterInterface;

class NotificationPublisher
{
    private $mastodonApi;
    private $router;

    public function __construct(RouterInterface $router, ?MastodonAPI $mastodonApi)
    {
        $this->router = $router;
        $this->mastodonApi = $mastodonApi;
    }

    public function publishEpisode(Episode $episode)
    {
        if ('prod' !== $_SERVER['APP_ENV']) {
            return;
        }

        $path = $this->router->generate('player', ['episode' => $episode->getCode()], RouterInterface::ABSOLUTE_URL);
        $path = str_replace(['localhost:8033', 'localhost'], 'noagendaexperience.com', $path);

        $title = sprintf('No Agenda Episode %s - %s', $episode->getCode(), $episode->getName());

        if ($this->mastodonApi) {
            // todo add episode art as media file
            // see https://docs.joinmastodon.org/methods/statuses/ and https://docs.joinmastodon.org/methods/statuses/media/

            $this->mastodonApi->post('/statuses', [
                'status' => "$title $path",
            ]);
        }

        /*
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

            $client->request('post', 'https://oauth.reddit.com/api/submit', [
                'form_params' => $message,
            ]);
        }
        */
    }
}
