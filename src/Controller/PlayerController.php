<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;

class PlayerController extends Controller
{
    private $showRepository;

    public function __construct(ShowRepository $showRepository)
    {
        $this->showRepository = $showRepository;
    }

    /**
     * @Route("/listen", name="player_latest")
     */
    public function latestAction()
    {
        $show = $this->showRepository->findLatest();

        return $this->playerAction($show);
    }
}
