<?php

namespace App\Twig;

use App\Entity\Episode;
use Liip\ImagineBundle\Templating\FilterExtension;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CoverExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('episodeCover', [$this, 'episodeCover'], ['needs_environment' => true]),
        ];
    }

    public function episodeCover(Environment $environment, Episode $episode, string $size = 'small')
    {
        if ($episode->hasCover()) {
            /** @var FilterExtension $filterExtension */
            $filterExtension = $environment->getExtension(FilterExtension::class);

            $episodeCode = $episode->getCode();

            return $filterExtension->filter("$episodeCode.png", "cover_$size");
        }

        /** @var AssetExtension $assetExtension */
        $assetExtension = $environment->getExtension(AssetExtension::class);

        return $assetExtension->getAssetUrl("build/placeholder_$size.jpg");
    }
}
