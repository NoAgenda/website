<?php

namespace App\Controller;

use App\Crawling\Shownotes\ShownotesParserFactory;
use App\Entity\Episode;
use App\Message\CrawlBatSignal;
use App\Message\CrawlEpisodeFiles;
use App\Message\CrawlEpisodeShownotes;
use App\Message\CrawlEpisodeTranscript;
use App\Message\CrawlFeed;
use App\Message\CrawlYoutube;
use App\Message\MatchEpisodeChatMessages;
use App\Message\MatchEpisodeRecordingTime;
use EasyCorp\Bundle\EasyAdminBundle\Controller\EasyAdminController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends EasyAdminController
{
    private $messenger;
    private $shownotesParserFactory;

    public function __construct(ShownotesParserFactory $shownotesParserFactory, MessageBusInterface $crawlerBus)
    {
        $this->shownotesParserFactory = $shownotesParserFactory;
        $this->messenger = $crawlerBus;
    }

    /**
     * @Route("/chat_logs/{date}", name="admin_chat_logs", defaults={"date"="today"})
     */
    public function chatLogsAction(Request $request, string $date): Response
    {
        $path = implode('/', [$_SERVER['APP_STORAGE_PATH'], 'chat_logs']);

        $finder = Finder::create()
            ->files()
            ->in($path)
            ->name('*.log')
        ;

        $files = array_flip(array_map(function (\SplFileInfo $info) {
            return str_replace('.log', '', $info->getFilename());
        }, iterator_to_array($finder->getIterator())));

        krsort($files);

        if ('today' === $date) {
            $date = (new \DateTime())->format('Ymd');
        }

        $logs = 'No logs found for this date.';

        if (isset($files[$date])) {
            $logs = file_get_contents($files[$date]);
        }

        return $this->render('admin/chat_logs.html.twig', [
            'files' => array_keys($files),
            'current_file' => $date,
            'logs' => $logs,
        ]);
    }

    /**
     * @Route("/crawler/{date}", name="admin_crawler", defaults={"date"="today"})
     */
    public function crawlerAction(Request $request, string $date): Response
    {
        if ('POST' === $request->getMethod()) {
            $task = $request->request->get('task');
            $code = $request->request->get('code');

            $message = false;

            static $messages = [
                'bat_signal' => CrawlBatSignal::class,
                'feed' => CrawlFeed::class,
                'youtube' => CrawlYoutube::class,
            ];

            if (isset($messages[$task])) {
                $message = new $messages[$task]();
            }

            static $episodeMessages = [
                'episode_chat' => MatchEpisodeChatMessages::class,
                'episode_time' => MatchEpisodeRecordingTime::class,
                'episode_files' => CrawlEpisodeFiles::class,
                'episode_shownotes' => CrawlEpisodeShownotes::class,
                'episode_transcript' => CrawlEpisodeTranscript::class,
            ];

            if (isset($episodeMessages[$task])) {
                $message = new $episodeMessages[$task]($code);
            }

            if ($message) {
                /** @var object $message */
                $this->messenger->dispatch($message);

                $this->addFlash('success', 'Scheduled job: ' . get_class($message));

                return $this->redirectToRoute('admin_crawler', ['date' => $date]);
            }
        }

        $path = implode('/', [$_SERVER['APP_STORAGE_PATH'], 'crawler_logs']);

        $finder = Finder::create()
            ->files()
            ->in($path)
            ->name('*.log')
        ;

        $files = array_flip(array_map(function (\SplFileInfo $info) {
            return str_replace('.log', '', $info->getFilename());
        }, iterator_to_array($finder->getIterator())));

        krsort($files);

        if ('today' === $date) {
            $date = (new \DateTime())->format('Ymd');
        }

        $logs = 'No logs found for this date.';

        if (isset($files[$date])) {
            $logs = file_get_contents($files[$date]);
        }

        return $this->render('admin/crawler.html.twig', [
            'files' => array_keys($files),
            'current_file' => $date,
            'logs' => $logs,
        ]);
    }

    /**
     * @Route("/livestream_recordings/{date}", name="admin_livestream_recordings", defaults={"date"="today"})
     */
    public function livestreamRecordingsAction(Request $request, string $date): Response
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

        if (false !== array_search($date, $dates)) {
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

    /**
     * @Route("/livestream_recordings/download/{date}/{time}", name="admin_livestream_recordings_download")
     */
    public function livestreamRecordingsDownloadAction(Request $request, string $date, string $time): Response
    {
        $recordingPath = sprintf('%s/livestream_recordings/recording_%s%s.asf', $_SERVER['APP_STORAGE_PATH'], $date, $time);

        return $this->file($recordingPath);
    }

    /**
     * @Route("/archive/credits/{page}", name="admin_archive_credits")
     */
    public function archiveCreditsAction(Request $request, int $page): Response
    {
        $shownotes = [];

        $repository = $this->getDoctrine()->getRepository(Episode::class);

        $start = $page * 100;
        $end = $start + 99;

        for ($i = $start; $i <= $end; $i++) {
            $episode = $repository->findOneByCode($i);

            if ($episode) {
                $shownote = $this->shownotesParserFactory->get($episode);

                if ($shownote) {
                    $shownotes[$episode->getCode()] = $shownote->getCredits();
                }
            }
        }

        return $this->render('admin/archive_credits.html.twig', [
            'shownotes' => $shownotes,
        ]);
    }
}
