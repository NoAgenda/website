<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;

#[AsCommand(
    name: 'assets:build',
    description: 'Build custom assets',
)]
class BuildAssetsCommand extends Command
{
    private readonly string $publicDirectory;
    private readonly string $webpackManifest;

    public function __construct(private readonly Environment $twig)
    {
        parent::__construct();

        $this->publicDirectory = dirname(__FILE__, 3) . '/public';
        $this->webpackManifest = $this->publicDirectory . '/build/manifest.json';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!file_exists($this->webpackManifest)) {
            return Command::SUCCESS;
        }

        $timestamp = (new \DateTime())->format('YmdHi');

        $this->buildManifest();
        $this->buildServiceWorker($timestamp);

        return Command::SUCCESS;
    }

    private function buildManifest(): void
    {
        $assets = json_decode(file_get_contents($this->webpackManifest), true);

        $contents = [
            'name' => 'No Agenda Show',
            'short_name' => 'No Agenda',
            'description' => 'The official No Agenda player',
            'display' => 'minimal-ui',
            'start_url' => '.',
            'icons' => [
                [
                    'src' => $assets['build/images/website-icon-512.png'],
                    'size' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $assets['build/images/website-icon-192.png'],
                    'size' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $assets['build/images/website-icon-128.png'],
                    'size' => '128x128',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
            ],
        ];

        file_put_contents($this->publicDirectory . '/site.webmanifest', json_encode($contents, JSON_UNESCAPED_SLASHES));
    }

    private function buildServiceWorker(string $timestamp): void
    {
        $assets = json_decode(file_get_contents($this->webpackManifest), true);
        $logoAsset = array_values(array_filter($assets, fn ($asset) => str_contains($asset, 'website-icon-192')))[0];

        $contents = $this->twig->render('service_worker.js.twig', [
            'timestamp' => $timestamp,
            'assets' => $assets,
            'logo_asset' => $logoAsset,
        ]);

        file_put_contents($this->publicDirectory . '/service-worker.js', $contents);
    }
}
