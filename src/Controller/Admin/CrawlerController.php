<?php

namespace App\Controller\Admin;

use App\Crawling\CrawlingProcessor;
use App\Crawling\Shownotes\ShownotesParserFactory;
use App\Repository\EpisodeRepository;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function Sentry\captureException;

class CrawlerController extends AbstractController
{
    public function __construct(
        private EpisodeRepository $episodeRepository,
        private AdminUrlGenerator $adminUrlGenerator,
        private CrawlingProcessor $crawlingProcessor,
        private ShownotesParserFactory $shownotesParserFactory,
    ) {}

    #[Route('/chat_logs/{date}', name: 'admin_chat_logs', defaults: ['date' => 'today'])]
    public function chatLogs(string $date): Response
    {
        $path = implode('/', [$_SERVER['APP_STORAGE_PATH'], 'chat_logs']);
        $files = $this->getAvailableLogs($path);

        return $this->render('admin/chat_logs.html.twig', [
            'files' => $files,
            'current_file' => $date,
            'logs' => $this->getLogs($files, $date),
        ]);
    }

    #[Route('/crawler/{date}', name: 'admin_crawler', defaults: ['date' => 'today'])]
    public function crawler(Request $request, string $date): Response
    {
        if ('POST' === $request->getMethod()) {
            $url = $this->adminUrlGenerator
                ->setRoute('admin_crawler', ['date' => $date])
                ->generateUrl()
            ;

            $data = $request->request->get('task');
            $episode = null;

            if ($code = $request->request->get('code')) {
                if (!$episode = $this->episodeRepository->findOneByCode($code)) {
                    $this->addFlash('danger', sprintf('Invalid episode: %s', $code));
                }
            }

            try {
                $this->crawlingProcessor->enqueue($data, $episode);
            } catch (\Exception $exception) {
                $this->addFlash('danger', $exception->getMessage());

                captureException($exception);

                return $this->redirect($url);
            }

            if ($code) {
                $this->addFlash('success', sprintf('Scheduled crawling of %s for episode %s.', $data, $code));
            } else {
                $this->addFlash('success', sprintf('Scheduled crawling of %s.', $data));
            }

            return $this->redirect($url);
        }

        $path = implode('/', [$_SERVER['APP_STORAGE_PATH'], 'crawler_logs']);
        $files = $this->getAvailableLogs($path);

        return $this->render('admin/chat_logs.html.twig', [
            'files' => $files,
            'current_file' => $date,
            'logs' => $this->getLogs($files, $date),
        ]);
    }

    #[Route('/livestream_recordings/{date}', name: 'admin_Livestream_recordings', defaults: ['date' => 'today'])]
    public function livestreamRecordings(string $date): Response
    {
        $path = implode('/', [$_SERVER['APP_STORAGE_PATH'], 'livestream_recordings']);

        $finder = Finder::create()
            ->files()
            ->in($path)
            ->name('recording_*')
        ;

        $dates = array_values(array_unique(array_map(function (\SplFileInfo $info) {
            $start = strlen('recording_');
            $date = substr($info->getFilename(), $start, 8);

            return $date;
        }, iterator_to_array($finder->getIterator()))));

        rsort($dates);

        if ('today' === $date) {
            $date = (new \DateTime())->format('Ymd');
        }

        $recordings = [];

        if (in_array($date, $dates)) {
            $prefix = sprintf('recording_%s', $date);

            $finder = Finder::create()
                ->files()
                ->in($path)
                ->name($prefix . '*')
            ;

            $times = array_values(array_unique(array_map(function (\SplFileInfo $info) use ($date, $prefix) {
                $start = strlen($prefix);
                $time = substr($info->getFilename(), $start, 6);

                return $time;
            }, iterator_to_array($finder->getIterator()))));

            $recordings = array_map(function ($time) use ($date, $prefix) {
                $recordingPath = sprintf('%s/livestream_recordings/recording_%s%s.asf', $_SERVER['APP_STORAGE_PATH'], $date, $time);
                $logsPath = sprintf('%s/livestream_recordings/recording_%s%s.log', $_SERVER['APP_STORAGE_PATH'], $date, $time);

                return [
                    'date' => $date,
                    'time' => $time,
                    'logs' => file_exists($logsPath) ? file_get_contents($logsPath) : 'No logs for recording found',
                    'recording' => file_exists($recordingPath),
                ];
            }, $times);

            usort($recordings, function ($a, $b) {
                if ($a['time'] > $b['time']) {
                    return 1;
                }

                if ($a['time'] < $b['time']) {
                    return -1;
                }

                return 0;
            });
        }

        return $this->render('admin/livestream_recordings.html.twig', [
            'dates' => $dates,
            'current_date' => $date,
            'recordings' => $recordings,
        ]);
    }

    #[Route('/livestream_recordings/download/{date}/{time}', name: 'admin_livestream_recordings_download')]
    public function livestreamRecordingsDownload(string $date, string $time): Response
    {
        $recordingPath = sprintf('%s/livestream_recordings/recording_%s%s.asf', $_SERVER['APP_STORAGE_PATH'], $date, $time);

        return $this->file($recordingPath);
    }

    #[Route('/archive/credits/{page}', name: 'admin_archive_credits')]
    public function archiveCredits(int $page): Response
    {
        // todo fix pagination

        $collection = [];

        $start = $page * 100;
        $end = $start + 99;

        for ($i = $start; $i <= $end; $i++) {
            $episode = $this->episodeRepository->findOneByCode($i);

            if ($episode && $shownotes = $this->shownotesParserFactory->get($episode)) {
                $collection[$episode->getCode()] = $shownotes->getCredits();
            }
        }

        return $this->render('admin/archive_credits.html.twig', [
            'shownotes' => $collection,
        ]);
    }

    private function getAvailableLogs(string $path): array
    {
        $finder = Finder::create()
            ->files()
            ->in($path)
            ->name('*.log')
        ;

        $files = array_flip(array_map(function (\SplFileInfo $info) {
            return str_replace('.log', '', $info->getFilename());
        }, iterator_to_array($finder->getIterator())));

        krsort($files);

        return $files;
    }

    private function getLogs(array $files, string $date): string
    {
        if ('today' === $date) {
            $date = (new \DateTime())->format('Ymd');
        }

        if (!isset($files[$date])) {
            return 'No logs found for this date.';
        }

        return file_get_contents($files[$date]);
    }
}
