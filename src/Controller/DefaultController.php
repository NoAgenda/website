<?php

namespace App\Controller;

use App\Repository\EpisodeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends Controller
{
    private $episodeRepository;

    public function __construct(EpisodeRepository $episodeRepository)
    {
        $this->episodeRepository = $episodeRepository;
    }

    /**
     * @Route("/", name="homepage")
     */
    public function index(): Response
    {
        $episodes = $this->episodeRepository->findBy(null, null, 4);

        return $this->render('default/index.html.twig', [
            'latest_episodes' => $episodes,
        ]);
    }
}
