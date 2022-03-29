<?php

namespace App\Twig;

use App\Entity\Episode;
use Liip\ImagineBundle\Service\FilterService;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CoverExtension extends AbstractExtension
{
    public function __construct(
        private FilterService $filterService,
    ) {}


    public function getFilters(): array
    {
        return [
            new TwigFilter('episodeCover', [$this, 'episodeCover'], ['needs_environment' => true]),
        ];
    }

    public function episodeCover(Environment $environment, Episode $episode, string $size = 'small'): string
    {
        if ($episode->hasCover()) {
            return $this->filterService->getUrlOfFilteredImage(sprintf('%s.png', $episode->getCode()), sprintf('cover_%s', $size));
        }

        /** @var AssetExtension $assetExtension */
        $assetExtension = $environment->getExtension(AssetExtension::class);

        return $assetExtension->getAssetUrl(sprintf('build/images/placeholder_%s.jpg', $size));
    }
}
