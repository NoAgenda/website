<?php

namespace App\Controller;

use App\Repository\EpisodeRepository;
use App\Repository\NetworkSiteRepository;
use App\Repository\VideoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends Controller
{
    private $episodeRepository;
    private $networkSiteRepository;
    private $videoRepository;

    public function __construct(EpisodeRepository $episodeRepository, NetworkSiteRepository $networkSiteRepository, VideoRepository $videoRepository)
    {
        $this->episodeRepository = $episodeRepository;
        $this->networkSiteRepository = $networkSiteRepository;
        $this->videoRepository = $videoRepository;
    }

    /**
     * @Route("/", name="homepage")
     */
    public function index(): Response
    {
        $episodes = $this->episodeRepository->getHomepageEpisodes();
        $networkSites = $this->networkSiteRepository->getHomepageSites();
        $videos = $this->videoRepository->findLatest();

        return $this->render('default/index.html.twig', [
            'latest_episodes' => $episodes,
            'network_sites' => $networkSites,
            'videos' => $videos,
        ]);
    }

    /**
     * @Route("/podcast", name="podcast")
     */
    public function podcast(): Response
    {
        return $this->render('default/podcast.html.twig');
    }
}
