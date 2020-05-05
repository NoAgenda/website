<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Entity\EpisodePart;
use App\Entity\EpisodePartCorrection;
use App\Message\CrawlBatSignal;
use App\Message\CrawlEpisodeFiles;
use App\Message\CrawlEpisodeShownotes;
use App\Message\CrawlEpisodeTranscript;
use App\Message\CrawlFeed;
use App\Message\CrawlTranscripts;
use App\Message\CrawlYoutube;
use EasyCorp\Bundle\EasyAdminBundle\Controller\EasyAdminController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends EasyAdminController
{
    public function approveAllAction(): Response
    {
        $id = $this->request->query->get('id');

        $episode = $this->em->getRepository(Episode::class)->find($id);
        $parts = $this->em->getRepository(EpisodePart::class)->findBy([
            'episode' => $episode,
        ]);

        foreach ($parts as $part) {
            foreach ($part->getCorrections() as $correction) {
                $this->approve($correction, $part);
            }
        }

        $this->em->flush();

        $this->addFlash('success', 'Corrections approved.');

        return $this->redirectToRoute('easyadmin', array(
            'action' => 'list',
            'entity' => 'EpisodePartCorrection',
        ));
    }

    public function approveAction(): Response
    {
        $id = $this->request->query->get('id');

        $correction = $this->em->getRepository(EpisodePartCorrection::class)->find($id);
        $correctionPart = $correction->getPart();

        $this->approve($correction, $correctionPart);

        $this->em->flush();

        $this->addFlash('success', 'Correction approved.');

        return $this->redirectToRoute('easyadmin', array(
            'action' => 'show',
            'entity' => 'EpisodePartCorrection',
            'id' => $id,
        ));
    }

    public function dismissAction(): Response
    {
        $id = $this->request->query->get('id');

        $correction = $this->em->getRepository(EpisodePartCorrection::class)->find($id);

        $correction
            ->setResult(null)
            ->setHandled(true)
        ;

        $this->em->persist($correction);

        $this->em->flush();

        $this->addFlash('success', 'Correction dismissed.');

        return $this->redirectToRoute('easyadmin', array(
            'action' => 'show',
            'entity' => 'EpisodePartCorrection',
            'id' => $id,
        ));
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
                'transcripts' => CrawlTranscripts::class,
                'youtube' => CrawlYoutube::class,
            ];

            if (isset($messages[$task])) {
                $message = new $messages[$task]();
            }

            static $episodeMessages = [
                'episode_files' => CrawlEpisodeFiles::class,
                'episode_shownotes' => CrawlEpisodeShownotes::class,
                'episode_transcript' => CrawlEpisodeTranscript::class,
            ];

            if (isset($episodeMessages[$task])) {
                $message = new $episodeMessages[$task]($code);
            }

            if ($message) {
                /** @var object $message */
                $this->dispatchMessage($message);

                $this->addFlash('success', 'Scheduled task: ' . get_class($message));

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

    private function approve(EpisodePartCorrection $correction, EpisodePart $correctionPart)
    {
        if ($correction->getPosition() !== null) {
            $part = (new EpisodePart())
                ->setEpisode($correctionPart->getEpisode())
                ->setCreator($correction->getCreator())
                ->setName($correction->getName())
                ->setStartsAt($correction->getStartsAt())
            ;

            $correction
                ->setResult($part)
                ->setHandled(true)
            ;

            $this->em->persist($correction);
            $this->em->persist($part);
        } elseif ($correction->getAction() !== null) {
            switch ($correction->getAction()) {
                case 'remove';
                    $correctionPart
                        ->setEnabled(false)
                    ;

                    break;

                case 'name';
                    $correctionPart
                        ->setName($correction->getName())
                    ;

                    break;

                case 'startsAt';
                    $correctionPart
                        ->setStartsAt($correction->getStartsAt())
                    ;

                    break;
            }

            $correction
                ->setResult($correctionPart)
                ->setHandled(true)
            ;

            $this->em->persist($correction);
            $this->em->persist($correctionPart);
        } else {
            throw new \Exception('Invalid correction');
        }
    }
}
