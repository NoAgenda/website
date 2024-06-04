<?php

namespace App\Controller;

use App\Repository\EpisodeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PodcastController extends AbstractController
{
    public function __construct(
        private readonly EpisodeRepository $episodeRepository,
    ) {}

    #[Route('/podcast', name: 'podcast')]
    public function podcast(): Response
    {
        $paginator = $this->episodeRepository->paginateEpisodes(1);

        return $this->render('podcast/podcast.html.twig', [
            'pager' => $paginator,
        ]);
    }

    #[Route('/podcast/archive/{page}', name: 'podcast_archive', requirements: ['page' => '\d+'])]
    public function archive(int $page = 1): Response
    {
        if ($page === 1) {
            return $this->redirectToRoute('podcast', ['_fragment' => 'archive']);
        }

        $paginator = $this->episodeRepository->paginateEpisodes($page ?? 1);

        return $this->render('podcast/archive.html.twig', [
            'intro' => $page === null,
            'pager' => $paginator,
        ]);
    }

    #[Route('/podcast/specials', name: 'podcast_specials')]
    public function specials(): Response
    {
        return $this->render('podcast/specials.html.twig', [
            'episodes' => $this->episodeRepository->findSpecialEpisodes(),
        ]);
    }

    #[Route('/podcast/all', name: 'podcast_all')]
    public function all(): Response
    {
        $episodes = $this->episodeRepository->findPublishedEpisodes();

        return $this->render('podcast/all.html.twig', [
            'episodes' => $episodes,
        ]);
    }

    #[Route('/listen', name: 'podcast_latest_episode')]
    public function latestEpisode(): Response
    {
        $episode = $this->episodeRepository->findLastPublishedEpisode();

        return $this->redirectToRoute('podcast_episode', ['code' => $episode->getCode()]);
    }
}
