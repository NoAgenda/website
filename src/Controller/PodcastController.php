<?php

namespace App\Controller;

use App\Crawling\Shownotes\ShownotesParserFactory;
use App\Entity\Episode;
use App\Entity\EpisodeChapter;
use App\Entity\EpisodeChapterDraft;
use App\Repository\EpisodeChapterDraftRepository;
use App\Repository\EpisodeChapterRepository;
use App\Repository\EpisodeRepository;
use App\Utilities;
use Benlipp\SrtParser\Parser as SrtParser;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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

    #[Route('/podcast/shownotes', name: 'podcast_shownotes')]
    public function shownotes(): Response
    {
        return $this->render('podcast/shownotes.html.twig');
    }

    #[Route('/podcast/subscribe', name: 'podcast_subscribe')]
    public function subscribe(): Response
    {
        return $this->render('podcast/subscribe.html.twig');
    }

    #[Route('/listen', name: 'podcast_latest_episode')]
    public function latestEpisode(): Response
    {
        $episode = $this->episodeRepository->findLastPublishedEpisode();

        return $this->redirectToRoute('podcast_episode', ['code' => $episode->getCode()]);
    }
}
