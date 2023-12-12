<?php

namespace App\Twig;

use App\Entity\Episode;
use Liip\ImagineBundle\Service\FilterService;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CoverExtension extends AbstractExtension
{
    public function __construct(
        private readonly AssetExtension $assetExtension,
        private readonly FilterService $filterService,
    ) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('episode_cover', [$this, 'episodeCover']),
        ];
    }

    public function episodeCover(Episode $episode, string $size = 'small'): string
    {
        if ($episode->hasCover()) {
            return $this->filterService->getUrlOfFilteredImage(sprintf('%s.png', $episode->getCode()), sprintf('cover_%s', $size));
        }

        return $this->assetExtension->getAssetUrl(sprintf('build/images/placeholder_%s.jpg', $size), 'app');
    }
}
