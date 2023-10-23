<?php

namespace App\Controller;

use App\Repository\EpisodeRepository;
use App\Repository\NetworkSiteRepository;
use App\Repository\VideoRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RootController extends AbstractController
{
    public function __construct(
        private readonly EpisodeRepository $episodeRepository,
        private readonly NetworkSiteRepository $networkSiteRepository,
        private readonly VideoRepository $videoRepository,
    ) {}

    #[Route('/', name: 'root')]
    public function root(): Response
    {
        $episodes = $this->episodeRepository->findLatestEpisodes();
        $videos = $this->videoRepository->findLatest();

        return $this->render('root/root.html.twig', [
            'latest_episodes' => $episodes,
            'videos' => $videos,
        ]);
    }

    #[Route('/adam-curry', name: 'adam')]
    #[Cache(maxage: '1 day')]
    public function adam(): Response
    {
        return $this->render('root/adam.html.twig');
    }

    #[Route('/john-c-dvorak', name: 'john')]
    #[Cache(maxage: '1 day')]
    public function john(): Response
    {
        return $this->render('root/john.html.twig');
    }

    #[Route('/live', name: 'livestream')]
    #[Cache(maxage: '1 day')]
    public function livestream(): Response
    {
        return $this->render('root/livestream.html.twig');
    }

    #[Route('/trollroom', name: 'chat')]
    #[Cache(maxage: '1 day')]
    public function chat(): Response
    {
        return $this->render('root/chat.html.twig');
    }

    #[Route('/mission-statement', name: 'mission_statement')]
    #[Cache(maxage: '1 day')]
    public function missionStatement(): Response
    {
        return $this->render('root/mission_statement.html.twig');
    }

    #[Route('/website', name: 'website')]
    #[Cache(maxage: '1 day')]
    public function website(): Response
    {
        return $this->render('root/website.html.twig');
    }

    #[Route('/social', name: 'social')]
    #[Cache(maxage: '1 day')]
    public function social(): Response
    {
        return $this->render('root/social.html.twig');
    }

    #[Route('/podcasting20', name: 'podcasting20')]
    #[Cache(maxage: '1 day')]
    public function podcasting20(): Response
    {
        return $this->render('root/podcasting20.html.twig');
    }

    #[Route('/producers', name: 'producers')]
    #[Cache(maxage: '1 day')]
    public function producers(): Response
    {
        return $this->render('root/producers.html.twig', [
            'network_sites' => $this->networkSiteRepository->findAll(),
        ]);
    }

    #[Route('/privacy', name: 'privacy_policy')]
    #[Cache(maxage: '1 day')]
    public function privacyPolicy(): Response
    {
        return $this->render('root/privacy_policy.html.twig');
    }

    #[Route('/error', name: 'error')]
    #[Cache(maxage: '1 day')]
    public function error(): Response
    {
        return $this->render('@Twig/Exception/error.html.twig');
    }

    #[Route('/offline', name: 'offline')]
    #[Cache(maxage: '1 day')]
    public function offline(): Response
    {
        return $this->render('root/offline.html.twig');
    }
}
