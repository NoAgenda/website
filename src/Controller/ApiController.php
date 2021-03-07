<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Message\CrawlEpisodeTranscript;
use App\Message\CrawlFeed;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api", name="account_")
 */
class ApiController extends AbstractController
{
    private $messenger;

    public function __construct(MessageBusInterface $crawlerBus)
    {
        $this->messenger = $crawlerBus;
    }

    /**
     * @Route("/crawl_feed/{token}", name="api_crawl_feed")
     */
    public function crawlFeed(string $token): Response
    {
        if ($token !== $_SERVER['API_SECURITY_TOKEN']) {
            return new Response('Invalid token: ' . $token, 400);
        }

        $this->messenger->dispatch(new CrawlFeed());

        return new Response('OK');
    }

    /**
     * @Route("/crawl_transcripts/{token}/{episode}", name="api_crawl_transcripts", defaults={"episode"=""})
     */
    public function crawlTranscripts(string $episode, string $token): Response
    {
        if ($token !== $_SERVER['API_SECURITY_TOKEN']) {
            return new Response('Invalid token: ' . $token, 400);
        }

        if ('' === $episode) {
            $episode = $this->getDoctrine()->getRepository(Episode::class)->findLatest()->getCode();
        }

        $this->messenger->dispatch(new CrawlEpisodeTranscript($episode));

        return new Response('OK');
    }
}
