<?php

namespace App\Controller;

use App\Crawling\CrawlerInterface;
use App\Crawling\Crawlers;
use App\Crawling\EpisodeCrawlerInterface;
use App\Crawling\EpisodeFileCrawlerInterface;
use App\Crawling\FileDownloader;
use App\Message\Crawl;
use App\Message\CrawlFile;
use App\Repository\EpisodeRepository;
use Monolog\Handler\StreamHandler;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use function Sentry\captureException;
use function Symfony\Component\String\u;

#[Route('/api', name: 'api_')]
class ApiController extends AbstractController
{
    public function __construct(
        private EpisodeRepository $episodeRepository,
        private ContainerInterface $crawlers,
        private MessageBusInterface $messenger,
        private FileDownloader $fileDownloader,
        private LoggerInterface $crawlerLogger,
        private string $securityToken,
    ) {}

    #[Route('/crawl/{data}', name: 'crawl')]
    public function crawl(Request $request, string $data): Response
    {
        $this->prepare();

        if (!$crawlerName = Crawlers::$crawlers[$data] ?? false) {
            throw new BadRequestHttpException();
        }

        $crawler = $this->crawlers->get($crawlerName);
        $episode = null;

        if ($crawler instanceof EpisodeCrawlerInterface || $crawler instanceof EpisodeFileCrawlerInterface) {
            if (!$episodeCode = $request->query->get('episode')) {
                throw new BadRequestHttpException();
            }

            if (!$episode = $this->episodeRepository->findOneByCode($episodeCode)) {
                throw new BadRequestHttpException();
            }
        }

        $this->crawlerLogger->pushHandler(new StreamHandler('php://output'));

        $response = new StreamedResponse();
        $response->setCallback(function () use ($crawler, $crawlerName, $episode) {
            $title = u('Executing ')->append($crawlerName);
            $separator = u('=')->repeat(16);

            if ($episode) {
                $title = $title->append(' for episode ')->append($episode);
            }

            echo implode(PHP_EOL, [
                '<pre>',
                'Crawler',
                $separator,
                $title,
                $separator,
                '',
                '',
            ]);

            try {
                if ($crawler instanceof CrawlerInterface) {
                    $crawler->crawl();
                } else if ($crawler instanceof EpisodeCrawlerInterface) {
                    $crawler->crawl($episode);
                } else if ($crawler instanceof EpisodeFileCrawlerInterface) {
                    $lastModifiedAt = $crawler->crawl($episode);

                    $this->fileDownloader->updateSchedule($crawlerName, $episode, $lastModifiedAt, new \DateTime());
                }
            } catch (\Throwable $exception) {
                $this->crawlerLogger->error(sprintf('An error occurred: %s', $exception->getMessage()));

                captureException($exception);
            }

            echo implode(PHP_EOL, [
                '',
                $separator,
                'Finished',
            ]);
        });

        return $response;
    }

    #[Route('/queue/{data}', name: 'queue')]
    public function queue(Request $request, string $data): Response
    {
        $this->prepare();

        if (!$crawlerName = Crawlers::$crawlers[$data] ?? false) {
            throw new BadRequestHttpException();
        }

        $crawler = $this->crawlers->get($crawlerName);
        $episodeCode = null;
        $episode = null;

        if ($crawler instanceof EpisodeCrawlerInterface || $crawler instanceof EpisodeFileCrawlerInterface) {
            if (!$episodeCode = $request->query->get('episode')) {
                throw new BadRequestHttpException();
            }

            if (!$episode = $this->episodeRepository->findOneByCode($episodeCode)) {
                throw new BadRequestHttpException();
            }
        }

        if ($crawler instanceof EpisodeFileCrawlerInterface) {
            $message = new CrawlFile($crawlerName, $episodeCode);
        } else {
            $message = new Crawl($crawlerName, $episodeCode);
        }

        $this->messenger->dispatch($message);

        $title = u('Queueing ')->append($crawlerName);
        $separator = u('=')->repeat(16);

        if ($episode) {
            $title = $title->append(' for episode ')->append($episode);
        }

        return new Response(implode(PHP_EOL, [
            '<pre>',
            'Crawler',
            $separator,
            $title,
            $separator,
            'Done',
        ]));
    }

    private function prepare(): void
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        if ($this->securityToken !== $request->query->get('token')) {
            throw new AccessDeniedHttpException();
        }
    }
}
