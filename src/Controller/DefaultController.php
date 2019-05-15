<?php

namespace App\Controller;

use App\Repository\EpisodeRepository;
use App\Repository\NetworkSiteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends Controller
{
    private $episodeRepository;
    private $networkSiteRepository;

    public function __construct(EpisodeRepository $episodeRepository, NetworkSiteRepository $networkSiteRepository)
    {
        $this->episodeRepository = $episodeRepository;
        $this->networkSiteRepository = $networkSiteRepository;
    }

    /**
     * @Route("/", name="homepage")
     */
    public function index(): Response
    {
        $episodes = $this->episodeRepository->getHomepageEpisodes();
        $networkSites = $this->networkSiteRepository->getHomepageSites();

        return $this->render('default/index.html.twig', [
            'latest_episodes' => $episodes,
            'network_sites' => $networkSites,
        ]);
    }
}
