<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

#[AsCommand(
    name: 'assets:build',
    description: 'Build custom assets',
)]
class BuildAssetsCommand extends Command
{
    public function __construct(private readonly Environment $twig)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $timestamp = (new \DateTime())->format('YmdHi');

        $this->buildManifest();
        $this->buildServiceWorker($timestamp);

        return Command::SUCCESS;
    }

    private function buildManifest(): void
    {
        $publicDirectory = dirname(__FILE__, 3) . '/public';

        $assets = json_decode(file_get_contents($publicDirectory . '/build/manifest.json'), true);

        $contents = [
            'name' => 'No Agenda Show',
            'short_name' => 'NoAgenda',
            'description' => 'The official No Agenda player',
            'display' => 'minimal-ui',
            'start_url' => '.',
            'background_color' => '#eeeeee',
            'icons' => [
                [
                    'src' => $assets['build/images/website-icon-192.png'],
                    'size' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $assets['build/images/website-icon-180.png'],
                    'size' => '180x180',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $assets['build/images/website-icon-128.png'],
                    'size' => '128x128',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $assets['build/images/website-icon-32.png'],
                    'size' => '32x32',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
            ],
        ];

        file_put_contents($publicDirectory . '/app-manifest.json', json_encode($contents));
    }

    private function buildServiceWorker(string $timestamp): void
    {
        $publicDirectory = dirname(__FILE__, 3) . '/public';

        $assets = json_decode(file_get_contents($publicDirectory . '/build/manifest.json'), true);
        $logoAsset = array_values(array_filter($assets, fn ($asset) => str_contains($asset, 'website-icon-128')))[0];

        $contents = $this->twig->render('service_worker.js.twig', [
            'timestamp' => $timestamp,
            'assets' => $assets,
            'logo_asset' => $logoAsset,
        ]);

        file_put_contents($publicDirectory . '/service-worker.js', $contents);
    }
}
