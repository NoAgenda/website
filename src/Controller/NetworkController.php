<?php

namespace App\Controller;

use App\Repository\NetworkSiteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NetworkController extends AbstractController
{
    private $networkSiteRepository;

    public function __construct(NetworkSiteRepository $networkSiteRepository)
    {
        $this->networkSiteRepository = $networkSiteRepository;
    }

    /**
     * @Route("/network", name="network")
     */
    public function index(): Response
    {
        $networkSites = $this->networkSiteRepository->findAll();

        return $this->render('network/index.html.twig', [
            'network_sites' => $networkSites,
        ]);
    }
}
