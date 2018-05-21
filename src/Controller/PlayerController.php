<?php

namespace App\Controller;

use App\Entity\Show;
use App\Repository\ShowRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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

    /**
     * @Route("/listen/{show}", name="player")
     * @ParamConverter("show", class="App\Entity\Show", options={"mapping": {"show": "code"}})
     */
    public function playerAction(Show $show)
    {
        return $this->render('player/show.html.twig', [
            'show' => $show,
        ]);
    }
}
