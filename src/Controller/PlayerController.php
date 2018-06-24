<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Form\ChatMessageType;
use App\Repository\ChatMessageRepository;
use App\Repository\EpisodeRepository;
use App\Repository\TranscriptLineRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PlayerController extends Controller
{
    private $episodeRepository;
    private $chatMessageRepository;
    private $transcriptLineRepository;

    public function __construct(EpisodeRepository $episodeRepository, ChatMessageRepository $chatMessageRepository, TranscriptLineRepository $transcriptLineRepository)
    {
        $this->episodeRepository = $episodeRepository;
        $this->chatMessageRepository = $chatMessageRepository;
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
     * @ParamConverter("episode", class="App\Entity\Episode", options={"mapping": {"episode": "code"}})
     */
    public function playerAction(Episode $episode): Response
    {
        $messages = $this->chatMessageRepository->findByEpisode($episode);
        $lines = $this->transcriptLineRepository->findByEpisode($episode);

        $messageForm = $this->createForm(ChatMessageType::class, [
            'episode' => $episode->getCode(),
            'postedAt' => 0,
        ]);

        return $this->render('player/episode.html.twig', [
            'episode' => $episode,
            'chatMessages' => $messages,
            'transcriptLines' => $lines,

            'chatMessageForm' => $messageForm->createView(),
        ]);
    }
}
