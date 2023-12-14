<?php

namespace App\Controller\Admin;

use App\Crawling\CrawlingProcessor;
use App\Crawling\EpisodeCrawlerInterface;
use App\Crawling\EpisodeFileCrawlerInterface;
use App\Entity\Episode;
use App\Repository\EpisodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use function Symfony\Component\String\u;

#[Route('/console/api', name: 'admin_api_')]
class ApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EpisodeRepository $episodeRepository,
        private CrawlingProcessor $crawlingProcessor,
        private LoggerInterface $crawlerLogger,
    ) {}

    #[Route('/crawl/{data}', name: 'crawl')]
    public function crawl(Request $request, string $data): Response
    {
        $this->prepare();

        $episode = $this->validateCrawlRequest($request, $data);

        $this->crawlerLogger->pushHandler(new StreamHandler('php://output'));

        $response = new StreamedResponse();
        $response->setCallback(function () use ($data, $episode) {
            $title = u('Executing ')->append(CrawlingProcessor::$crawlerClasses[$data]);
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

            $this->entityManager->beginTransaction();

            try {
                $this->crawlingProcessor->crawl($data, $episode);
                $this->entityManager->flush();

                $this->entityManager->commit();
            } catch (\Throwable $exception) {
                $this->crawlerLogger->error(sprintf('An exception occurred: %s', $exception->getMessage()));

                $this->entityManager->rollback();
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

        $episode = $this->validateCrawlRequest($request, $data);

        $this->crawlingProcessor->enqueue($data, $episode);

        $title = u('Queueing ')->append(CrawlingProcessor::$crawlerClasses[$data]);
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

        if ($_SERVER['APP_SECURITY_TOKEN'] !== $request->query->get('token')) {
            throw new AccessDeniedHttpException();
        }
    }

    private function validateCrawlRequest(Request $request, string $data): ?Episode
    {
        if (!$crawlerName = CrawlingProcessor::$crawlerClasses[$data] ?? false) {
            throw new BadRequestHttpException();
        }

        $episode = null;

        if (is_subclass_of($crawlerName, EpisodeCrawlerInterface::class) || is_subclass_of($crawlerName, EpisodeFileCrawlerInterface::class)) {
            if (!$episodeCode = $request->query->get('episode')) {
                throw new BadRequestHttpException();
            }

            if (!$episode = $this->episodeRepository->findOneByCode($episodeCode)) {
                throw new BadRequestHttpException();
            }
        }

        return $episode;
    }
}
