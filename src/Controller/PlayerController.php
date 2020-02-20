<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Form\EpisodePartCorrectionType;
use App\Form\EpisodePartSuggestionType;
use App\Repository\ChatMessageRepository;
use App\Repository\EpisodePartRepository;
use App\Repository\EpisodeRepository;
use App\Repository\TranscriptLineRepository;
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
    public function playerAction(Request $request, Episode $episode): Response
    {
        $timestamp = static::parsePrettyTimestamp($request->query->get('t', 0));
        $transcriptTimestamp = static::parsePrettyTimestamp($request->query->get('transcript', 0));

        if ($transcriptTimestamp > 0) {
            $timestamp = $transcriptTimestamp;
        }

        $lines = $this->transcriptLineRepository->findByEpisode($episode);

        // $messageForm = $this->createForm(ChatMessageType::class, [
        //     'episode' => $episode->getCode(),
        //     'postedAt' => 0,
        // ]);

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
            'transcriptLines' => $lines,

            // 'chatMessageForm' => $messageForm->createView(),
            'partCorrectionForm' => $partCorrectionForm->createView(),
            'partSuggestionForm' => $partSuggestionForm->createView(),
        ]);
    }

    /**
     * @Route("/listen/{episode}/audio", name="player_audio")
     * @ParamConverter("episode", class="App\Entity\Episode", options={"mapping": {"episode": "code"}})
     */
    public function audioAction(Request $request, Episode $episode): Response
    {
        $path = sprintf('%s/episode_recordings/%s.mp3', $_SERVER['APP_STORAGE_PATH'], $episode->getCode());

        return new BinaryFileResponse($path);
    }

    public static function parsePrettyTimestamp(string $prettyTimestamp): int
    {
        if (strpos($prettyTimestamp, ':')) {
            $components = explode(':', $prettyTimestamp);

            if (count($components) >= 3) {
                list($hours, $minutes, $seconds) = $components;
            } else {
                $hours = 0;
                list($minutes, $seconds) = $components;
            }

            $timestamp = (int) $seconds;
            $timestamp += (int) $minutes * 60;
            $timestamp += (int) $hours * 60 * 60;

            return $timestamp;
        }

        return (int) $prettyTimestamp;
    }
}
