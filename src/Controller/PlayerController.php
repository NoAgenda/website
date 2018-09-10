<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Form\EpisodePartSuggestionType;
use App\Repository\ChatMessageRepository;
use App\Repository\EpisodePartRepository;
use App\Repository\EpisodeRepository;
use App\Repository\TranscriptLineRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class PlayerController extends Controller
{
    private $serializer;

    private $episodeRepository;
    private $episodePartRepository;
    private $chatMessageRepository;
    private $transcriptLineRepository;

    public function __construct(
        SerializerInterface $serializer,
        EpisodeRepository $episodeRepository,
        EpisodePartRepository $episodePartRepository,
        ChatMessageRepository $chatMessageRepository,
        TranscriptLineRepository $transcriptLineRepository
    )
    {
        $this->serializer = $serializer;

        $this->episodeRepository = $episodeRepository;
        $this->episodePartRepository = $episodePartRepository;
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
        $lines = $this->transcriptLineRepository->findByEpisode($episode);

        // $messageForm = $this->createForm(ChatMessageType::class, [
        //     'episode' => $episode->getCode(),
        //     'postedAt' => 0,
        // ]);

        $parts = $this->episodePartRepository->findBy(['episode' => $episode]);

        $partSuggestionForm = $this->createForm(EpisodePartSuggestionType::class, null, [
            'action' => $this->generateUrl('episode_part_suggestion'),
        ]);

        return $this->render('player/episode.html.twig', [
            'episode' => $episode,
            'parts' => $parts,
            'transcriptLines' => $lines,

            // 'chatMessageForm' => $messageForm->createView(),
            'partSuggestionForm' => $partSuggestionForm->createView(),
        ]);
    }
}
