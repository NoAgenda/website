<?php

namespace App\Controller;

use App\Repository\EpisodeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArchiveController extends AbstractController
{
    public function __construct(
        private EpisodeRepository $episodeRepository,
    ) {}

    #[Route('/archive/{page}', name: 'archive', requirements: ['page' => '\d+'], defaults: ['page' => 1])]
    public function index(int $page): Response
    {
        $paginator = $this->episodeRepository->paginateEpisodes($page);

        return $this->render('archive/index.html.twig', [
            'pager' => $paginator,
        ]);
    }

    #[Route('/archive/specials/{page}', name: 'archive_specials', requirements: ['page' => '\d+'], defaults: ['page' => 1])]
    public function special(int $page): Response
    {
        $paginator = $this->episodeRepository->paginateSpecialEpisodes($page);

        return $this->render('archive/index.html.twig', [
            'pager' => $paginator,
        ]);
    }

    #[Route('/archive/all', name: 'archive_all')]
    public function all(): Response
    {
        $episodes = $this->episodeRepository->findPublishedEpisodes();

        return $this->render('archive/list.html.twig', [
            'episodes' => $episodes,
        ]);
    }
}
