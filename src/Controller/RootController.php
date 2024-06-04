<?php

namespace App\Controller;

use App\Repository\EpisodeRepository;
use App\Repository\NetworkSiteRepository;
use App\Repository\VideoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Annotation\Route;

class RootController extends AbstractController
{
    #[Route('/', name: 'root')]
    public function root(EpisodeRepository $episodeRepository): Response
    {
        return $this->render('root/root.html.twig', [
            'latest_episodes' => $episodeRepository->findLatestEpisodes(),
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

    #[Route('/subscribe', name: 'subscribe')]
    #[Cache(maxage: '1 day')]
    public function subscribe(): Response
    {
        return $this->render('root/subscribe.html.twig');
    }

    #[Route('/producers', name: 'producers')]
    #[Cache(maxage: '1 day')]
    public function producers(NetworkSiteRepository $networkSiteRepository, VideoRepository $videoRepository): Response
    {
        return $this->render('root/producers.html.twig', [
            'network_sites' => $networkSiteRepository->findAll(),
            'videos' => $videoRepository->findLatest(),
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
