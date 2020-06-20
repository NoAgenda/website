<?php

namespace App\Controller;

use App\Criteria\SpecialEpisodes;
use App\Repository\EpisodeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArchiveController extends Controller
{
    private $episodeRepository;

    public function __construct(EpisodeRepository $episodeRepository)
    {
        $this->episodeRepository = $episodeRepository;
    }

    /**
     * @Route("/archive/{page}", name="archive", requirements={"page":"\d+"}, defaults={"page"="1"})
     */
    public function index(Request $request): Response
    {
        $pager = $this->episodeRepository->paginateEpisodes($request->get('page', 1));

        return $this->render('archive/index.html.twig', [
            'pager' => $pager,
        ]);
    }

    /**
     * @Route("/archive/specials/{page}", name="archive_specials", requirements={"page":"\d+"}, defaults={"page"="1"})
     */
    public function special(Request $request): Response
    {
        $pager = $this->episodeRepository->paginateSpecialEpisodes($request->get('page', 1));

        return $this->render('archive/index.html.twig', [
            'pager' => $pager,
        ]);
    }

    /**
     * @Route("/archive/all", name="archive_all")
     */
    public function all(Request $request): Response
    {
        $episodes = $this->episodeRepository->findAll();

        return $this->render('archive/list.html.twig', [
            'episodes' => $episodes,
        ]);
    }
}
