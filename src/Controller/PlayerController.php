<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Form\EpisodePartCorrectionType;
use App\Form\EpisodePartSuggestionType;
use App\Repository\EpisodePartRepository;
use App\Repository\EpisodeRepository;
use App\Utilities;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class PlayerController extends Controller
{
    private $serializer;

    private $episodeRepository;
    private $episodePartRepository;

    public function __construct(
        SerializerInterface $serializer,
        EpisodeRepository $episodeRepository,
        EpisodePartRepository $episodePartRepository
    ) {
        $this->serializer = $serializer;

        $this->episodeRepository = $episodeRepository;
        $this->episodePartRepository = $episodePartRepository;
    }

    /**
     * @Route("/listen", name="player_latest")
     */
    public function latest(): Response
    {
        $episode = $this->episodeRepository->findLatest();

        return $this->redirectToRoute('player', ['episode' => $episode->getCode()], Response::HTTP_TEMPORARY_REDIRECT);
    }

    /**
     * @Route("/listen/{episode}", name="player")
     * @ParamConverter("episode", class="App\Entity\Episode", options={"mapping": {"episode": "code"}})
     */
    public function player(Request $request, Episode $episode): Response
    {
        $timestamp = Utilities::parsePrettyTimestamp($request->query->get('t', 0));
        $transcriptTimestamp = Utilities::parsePrettyTimestamp($request->query->get('transcript', 0));

        if ($transcriptTimestamp > 0) {
            $timestamp = $transcriptTimestamp;
        }

        if ($episode->hasTranscript()) {
            $lines = json_decode(file_get_contents(sprintf('%s/transcripts/%s.json', $_SERVER['APP_STORAGE_PATH'], $episode->getCode())));
        }

        $parts = $this->episodePartRepository->findBy(['episode' => $episode, 'enabled' => true], ['startsAt' => 'ASC']);

        $partCorrectionForm = $this->createForm(EpisodePartCorrectionType::class, null, [
            'action' => $this->generateUrl('episode_part_correction'),
        ]);

        $partSuggestionForm = $this->createForm(EpisodePartSuggestionType::class, null, [
            'action' => $this->generateUrl('episode_part_suggestion'),
        ]);

        return $this->render('player/episode.html.twig', [
            'timestamp' => $timestamp,
            'transcriptTimestamp' => $transcriptTimestamp,

            'episode' => $episode,
            'parts' => $parts,
            'transcriptLines' => $lines ?? [],

            'partCorrectionForm' => $partCorrectionForm->createView(),
            'partSuggestionForm' => $partSuggestionForm->createView(),
        ]);
    }

    /**
     * @Route("/listen/{episode}/audio", name="player_audio")
     * @ParamConverter("episode", class="App\Entity\Episode", options={"mapping": {"episode": "code"}})
     */
    public function audio(Episode $episode): Response
    {
        $path = sprintf('%s/episode_recordings/%s.mp3', $_SERVER['APP_STORAGE_PATH'], $episode->getCode());

        return new BinaryFileResponse($path);
    }

    /**
     * @Route("/listen/{episode}/chat", name="player_chat_messages")
     * @ParamConverter("episode", class="App\Entity\Episode", options={"mapping": {"episode": "code"}})
     */
    public function chatMessages(Episode $episode): Response
    {
        $path = sprintf('%s/chat_messages/%s.json', $_SERVER['APP_STORAGE_PATH'], $episode->getCode());

        return new BinaryFileResponse($path);
    }
}
