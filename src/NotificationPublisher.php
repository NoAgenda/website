<?php

namespace App;

use App\Entity\Episode;
use Http\Client\Common\HttpMethodsClient;
//use Http\Discovery\Psr17FactoryDiscovery;
//use Http\Message\MultipartStream\MultipartStreamBuilder;
use Liip\ImagineBundle\Service\FilterService;
use Symfony\Component\Routing\RouterInterface;

class NotificationPublisher
{
    private $filterService;
    private $httpClient;
    private $router;

    public function __construct(RouterInterface $router, HttpMethodsClient $mastodonClient, FilterService $filterService)
    {
        $this->router = $router;
        $this->httpClient = $mastodonClient;
        $this->filterService = $filterService;
    }

    public function publishEpisode(Episode $episode)
    {
        if ('prod' !== $_SERVER['APP_ENV']) {
            return;
        }

        $code = $episode->getCode();

        $path = $this->router->generate('player', ['episode' => $code], RouterInterface::ABSOLUTE_URL);

        $title = sprintf('No Agenda Episode %s - %s', $code, $episode->getName());

        if ($_SERVER['MASTODON_ACCESS_TOKEN']) {
            /*
            The following code is a failed attempt at adding the episode cover to the Mastodon toot.
            I think it's because it collides with metadata for the link to the episode player.
            It's still here for reference purposes because it should work when not including a link in the toot.

            $filename = "${code}.png";
            $coverPath = sprintf('%s/episode_covers/%s.png', $_SERVER['APP_STORAGE_PATH'], $code);

            $streamFactory = Psr17FactoryDiscovery::findStreamFactory();
            $builder = new MultipartStreamBuilder($streamFactory);
            $builder
                ->addResource('file', file_get_contents($coverPath), ['filename' => $filename])
                ->addResource('description', sprintf('Artwork for No Agenda episode %s', $code))
            ;

            $multipartStream = $builder->build();

            $mediaResponse = $this->httpClient->post('/media', [
                'Content-Type' => sprintf('multipart/form-data; boundary="%s"', $builder->getBoundary()),
            ], $multipartStream);

            if ($mediaResponse->getStatusCode() > 200) {
                throw new \Exception('Failed to upload media to Mastodon.');
            }

            $mediaData = json_decode($mediaResponse->getBody()->getContents(), true);
            */

            $messageParameters = [
                'status' => "$title $path",
                // 'media_ids' => [$mediaData['id']],
            ];

            $messageResponse = $this->httpClient->post('/statuses', [], http_build_query($messageParameters));

            if ($messageResponse->getStatusCode() > 200) {
                throw new \Exception('Failed to upload episode notification to Mastodon.');
            }
        }
    }
}
