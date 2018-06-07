<?php

namespace App\Controller;

use App\Repository\ShowRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ArchiveController extends Controller
{
    private $showRepository;

    public function __construct(ShowRepository $showRepository)
    {
        $this->showRepository = $showRepository;
    }

    /**
     * @Route("/archive", name="archive")
     */
    public function index(): Response
    {
        $shows = $this->showRepository->findAll();

        return $this->render('archive/index.html.twig', [
            'shows' => $shows,
        ]);
    }
}
