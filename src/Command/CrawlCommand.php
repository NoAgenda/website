<?php

namespace App\Command;

use App\Crawling\BatSignalCrawler;
use App\Crawling\CrawlingLogger;
use App\Crawling\EpisodeChatMessagesMatcher;
use App\Crawling\EpisodeFilesCrawler;
use App\Crawling\EpisodeRecordingTimeMatcher;
use App\Crawling\EpisodeShownotesCrawler;
use App\Crawling\FeedCrawler;
use App\Crawling\TranscriptCrawler;
use App\Entity\Episode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

class CrawlCommand extends Command implements ServiceSubscriberInterface
{
    use ServiceSubscriberTrait;

    protected static $defaultName = 'app:crawl';

    protected function configure(): void
    {
        $this
            ->setDescription('Execute a crawling command')
            ->addArgument('data', InputArgument::REQUIRED, 'The type of data to crawl: feed, bat_signal, transcripts, files, shownotes, transcript, recording_time, chat_messages')
            ->addOption('episode', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'The episode code to crawling')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $data = $input->getArgument('data');

        $this->crawlingLogger()->addLogger(new ConsoleLogger($output));

        $collectionActions = [
            'bat_signal' => function () {
                $crawler = $this->signalCrawler();

                $crawler->crawl();
            },
            'feed' => function () {
                $crawler = $this->feedCrawler();

                $crawler->crawl();
            },
            'transcripts' => function () {
                $crawler = $this->transcriptCrawler();

                $crawler->crawl();
            },
        ];

        $episodeActions = [
            'files' => function (Episode $episode) {
                $crawler = $this->filesCrawler();

                $crawler->crawl($episode);
            },
            'shownotes' => function (Episode $episode) {
                $crawler = $this->shownotesCrawler();

                $crawler->crawl($episode);
            },
            'transcript' => function (Episode $episode) {
                $crawler = $this->transcriptCrawler();

                $crawler->crawlEpisode($episode);
            },

            'chat_messages' => function (Episode $episode) {
                $matcher = $this->chatMessagesMatcher();

                $matcher->match($episode);
            },
            'recording_time' => function (Episode $episode) {
                $matcher = $this->recordingTimeMatcher();

                $matcher->match($episode);
            },
        ];

        if (isset($collectionActions[$data])) {
            $this->entityManager()->beginTransaction();

            $collectionActions[$data]();

            $this->entityManager()->flush();
            $this->entityManager()->commit();

            return 0;
        }

        if (isset($episodeActions[$data])) {
            $this->entityManager()->beginTransaction();

            foreach ($input->getOption('episode') as $code) {
                $episode = $this->findEpisode($code);

                $episodeActions[$data]($episode);
            }

            $this->entityManager()->flush();
            $this->entityManager()->commit();

            return 0;
        }

        $output->writeln("Invalid data type: $data");

        return 1;
    }

    private function chatMessagesMatcher(): EpisodeChatMessagesMatcher
    {
        return $this->container->get(__METHOD__);
    }

    private function feedCrawler(): FeedCrawler
    {
        return $this->container->get(__METHOD__);
    }

    private function filesCrawler(): EpisodeFilesCrawler
    {
        return $this->container->get(__METHOD__);
    }

    private function recordingTimeMatcher(): EpisodeRecordingTimeMatcher
    {
        return $this->container->get(__METHOD__);
    }

    private function shownotesCrawler(): EpisodeShownotesCrawler
    {
        return $this->container->get(__METHOD__);
    }

    private function signalCrawler(): BatSignalCrawler
    {
        return $this->container->get(__METHOD__);
    }

    private function transcriptCrawler(): TranscriptCrawler
    {
        return $this->container->get(__METHOD__);
    }

    private function crawlingLogger(): CrawlingLogger
    {
        return $this->container->get(__METHOD__);
    }

    private function entityManager(): EntityManagerInterface
    {
        return $this->container->get(__METHOD__);
    }

    private function findEpisode(string $code): Episode
    {
        return $this->entityManager()->getRepository(Episode::class)->findOneByCode($code);
    }
}
