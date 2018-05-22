<?php

namespace App\Controller;

use App\Entity\Show;
use App\Repository\ShowRepository;
use App\Repository\TranscriptLineRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class PlayerController extends Controller
{
    private $showRepository;
    private $transcriptLineRepository;

    public function __construct(ShowRepository $showRepository, TranscriptLineRepository $transcriptLineRepository)
    {
        $this->showRepository = $showRepository;
        $this->transcriptLineRepository = $transcriptLineRepository;
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
        $lines = $this->transcriptLineRepository->findByShow($show);

        return $this->render('player/show.html.twig', [
            'show' => $show,
            'transcriptLines' => $lines,
        ]);
    }
}
