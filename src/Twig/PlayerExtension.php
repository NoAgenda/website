<?php

namespace App\Twig;

use App\Entity\Episode;
use App\Utilities;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

class PlayerExtension extends AbstractExtension
{
    public function __construct(private readonly RouterInterface $router) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('player_metadata', [$this, 'episodeMetadata'], ['needs_environment' => true]),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('player_livestream_metadata', [$this, 'livestreamMetadata'], ['needs_environment' => true])
        ];
    }

    public function episodeMetadata(Environment $environment, Episode $episode): array
    {
        return [
            'type' => 'episode',
            'code' => $episode->getCode(),
            'title' => (string) $episode,
            'src' => $episode->getRecordingUri(),
            'duration' => $episode->getDuration(),
            'publishedAt' => $episode->getPublishedAt()->format('Y-m-d'),
            'url' => $this->router->generate('podcast_episode', ['code' => $episode->getCode()]),
            'cover' => $environment->getExtension(CoverExtension::class)->episodeCover($episode, 'large'),
            'transcript' => $episode->getTranscriptUri(),
            'chapters' => $episode->getChaptersUri(),
        ];
    }

    public function livestreamMetadata(Environment $environment): array
    {
        return [
            'type' => 'livestream',
            'title' => 'No Agenda Stream',
            'src' => 'http://listen.noagendastream.com/noagenda',
            'cover' => $environment->getExtension(AssetExtension::class)->getAssetUrl('build/images/placeholder_large.jpg', 'app'),
        ];
    }
}
