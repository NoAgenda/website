<?php

namespace App\Controller;

use App\Repository\EpisodeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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
     * @Route("/archive", name="archive")
     */
    public function index(): Response
    {
        $episodes = $this->episodeRepository->findAll();

        return $this->render('archive/index.html.twig', [
            'episodes' => $episodes,
        ]);
    }
}
