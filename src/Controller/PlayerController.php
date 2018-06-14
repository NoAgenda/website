<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Repository\EpisodeRepository;
use App\Repository\TranscriptLineRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PlayerController extends Controller
{
    private $episodeRepository;
    private $transcriptLineRepository;

    public function __construct(EpisodeRepository $episodeRepository, TranscriptLineRepository $transcriptLineRepository)
    {
        $this->episodeRepository = $episodeRepository;
        $this->transcriptLineRepository = $transcriptLineRepository;
    }

    /**
     * @Route("/listen", name="player_latest")
     */
    public function latestAction(): Response
    {
        $episode = $this->episodeRepository->findLatest();

        return $this->redirectToRoute('player', ['episode' => $episode->getCode()], Response::HTTP_TEMPORARY_REDIRECT);
    }

    /**
     * @Route("/listen/{episode}", name="player")
     * @ParamConverter("episode", class="Episode", options={"mapping": {"episode": "code"}})
     */
    public function playerAction(Episode $episode): Response
    {
        $lines = $this->transcriptLineRepository->findByEpisode($episode);

        return $this->render('player/episode.html.twig', [
            'episode' => $episode,
            'transcriptLines' => $lines,
        ]);
    }
}
