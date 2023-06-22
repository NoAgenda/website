<?php

namespace App\Command;

use App\Repository\EpisodeRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'assets:header',
    description: 'Generate header for donation page',
)]
class GenerateDonationsHeaderCommand extends Command
{
    public function __construct(private readonly EpisodeRepository $episodeRepository)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->generateHeader();

        return Command::SUCCESS;
    }

    private function generateHeader(): void
    {
        $publicDirectory = dirname(__FILE__, 3) . '/public';
        $outputPath = $publicDirectory . '/media/donations-header.png';

        $episodes = $this->episodeRepository->findLatestEpisodes(8);

        $imageSize = 400;
        $imageQuantity = 7;
        $imagePaths = [];

        foreach ($episodes as $episode) {
            if ($episode->hasCover()) {
                $imagePaths[] = $episode->getCoverPath();
            }
        }

        $canvas = new \Imagick();
        $canvas->newImage($imageSize * $imageQuantity, $imageSize, 'none');

        $j = 0;
        for ($i = 0; $i < $imageQuantity; $i++) {
            $image = new \Imagick($imagePaths[$j]);
            $image->resizeImage($imageSize, $imageSize, \Imagick::FILTER_LANCZOS, 1);

            $canvas->compositeImage($image, \Imagick::COMPOSITE_DEFAULT, $i * $imageSize, 0);

            $image->clear();
            $image->destroy();

            $j = ($j >= count($imagePaths) - 1) ? 0 : ($j + 1);
        }

        $canvas->writeImage($outputPath);

        $canvas->clear();
        $canvas->destroy();
    }
}
