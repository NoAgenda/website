<?php

namespace App\Command;

use App\Entity\Episode;
use Doctrine\ORM\EntityManagerInterface;
use Http\Client\Common\HttpMethodsClient;
use Laminas\Feed\Reader\Feed\Rss as RssFeed;
use Laminas\Feed\Reader\Reader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ArchiveCommand extends Command
{
    protected static $defaultName = 'app:archive';

    private $httpClient;
    private $entityManager;

    public function __construct(string $name = null, HttpMethodsClient $httpClient, EntityManagerInterface $entityManager)
    {
        parent::__construct($name);

        $this->httpClient = $httpClient;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->crawlFeed();

        return 0;
    }

    private function crawlFeed(): void
    {
        $response = $this->httpClient->get('http://archive.noagendanation.com/rss/all');
        $source = $response->getBody()->getContents();

        Reader::registerExtension('Podcast');

        /** @var RssFeed $rssFeed */
        $rssFeed = Reader::importString($source);

        $existingEpisodes = $this->entityManager->getRepository(Episode::class)->findAll();

        foreach ($rssFeed as $rssItem) {
            preg_match('/Show #(\d+)\.(\d) - (.+)/', $rssItem->getTitle(), $matches);

            if (!isset($matches[1]) || !isset($matches[2]) || !isset($matches[3])) {
                die($rssItem->getTitle());
            }

            list(, $code, $subCode, $title) = $matches;

            if ($subCode !== '0') {
                $code = $code . '.' . $subCode;
            }

            foreach ($existingEpisodes as $episode) {
                if ($episode->getCode() === $code) {
                    continue 2;
                }
            }

            $episode = new Episode();
            $episode->setCode($code);
            $episode->setName($title);
            $episode->setAuthor('Adam Curry & John C. Dvorak');
            $episode->setPublishedAt($rssItem->getDateCreated());
            $episode->setRecordingUri($rssItem->getEnclosure()->url);
            $episode->setDuration($rssItem->getEnclosure()->length);

            $this->entityManager->persist($episode);
        }

        $this->entityManager->flush();
    }
}
