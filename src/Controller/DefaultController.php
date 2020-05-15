<?php

namespace App\Controller;

use App\Repository\EpisodeRepository;
use App\Repository\FeedbackItemRepository;
use App\Repository\NetworkSiteRepository;
use App\Repository\VideoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends Controller
{
    private $episodeRepository;
    private $feedbackItemRepository;
    private $networkSiteRepository;
    private $videoRepository;

    public function __construct(EpisodeRepository $episodeRepository, FeedbackItemRepository $feedbackItemRepository, NetworkSiteRepository $networkSiteRepository, VideoRepository $videoRepository)
    {
        $this->episodeRepository = $episodeRepository;
        $this->feedbackItemRepository = $feedbackItemRepository;
        $this->networkSiteRepository = $networkSiteRepository;
        $this->videoRepository = $videoRepository;
    }

    /**
     * @Route("/", name="homepage")
     */
    public function index(): Response
    {
        $episodes = $this->episodeRepository->getHomepageEpisodes();
        $feedbackItems = $this->feedbackItemRepository->findOpenFeedbackItems(8);
        $networkSites = $this->networkSiteRepository->getHomepageSites();
        $videos = $this->videoRepository->findLatest();

        return $this->render('default/index.html.twig', [
            'latest_episodes' => $episodes,
            'feedback_items' => $feedbackItems,
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
