<?php

namespace App\Controller;

use App\Crawling\Shownotes\ShownotesParserFactory;
use App\Entity\Episode;
use App\Entity\EpisodeChapter;
use App\Entity\EpisodeChapterDraft;
use App\Repository\EpisodeChapterDraftRepository;
use App\Repository\EpisodeChapterRepository;
use App\Repository\EpisodeRepository;
use App\Utilities;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PlayerController extends Controller
{
    private $episodeChapterDraftRepository;
    private $episodeChapterRepository;
    private $episodeRepository;
    private $shownotesParserFactory;

    public function __construct(
        ShownotesParserFactory $shownotesParserFactory,
        EpisodeRepository $episodeRepository,
        EpisodeChapterRepository $episodeChapterRepository,
        EpisodeChapterDraftRepository $episodeChapterDraftRepository
    ) {
        $this->shownotesParserFactory = $shownotesParserFactory;
        $this->episodeRepository = $episodeRepository;
        $this->episodeChapterRepository = $episodeChapterRepository;
        $this->episodeChapterDraftRepository = $episodeChapterDraftRepository;
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

        $transcriptPath = sprintf('%s/transcripts/%s.json', $_SERVER['APP_STORAGE_PATH'], $episode->getCode());
        if ($episode->hasTranscript() && file_exists($transcriptPath)) {
            $lines = json_decode(file_get_contents($transcriptPath));
        }

        $chapters = array_merge(
            $this->episodeChapterRepository->findByEpisode($episode),
            $this->episodeChapterDraftRepository->findNewSuggestionsByEpisode($episode)
        );

        usort($chapters, function ($a, $b) {
            /** @var EpisodeChapter|EpisodeChapterDraft $a */
            /** @var EpisodeChapter|EpisodeChapterDraft $b */
            return $a->getStartsAt() - $b->getStartsAt();
        });

        $shownotes = $this->shownotesParserFactory->get($episode);

        return $this->render('player/episode.html.twig', [
            'timestamp' => $timestamp,
            'transcriptTimestamp' => $transcriptTimestamp,

            'episode' => $episode,
            'chapters' => $chapters,
            'shownotes' => $shownotes,
            'transcriptLines' => $lines ?? [],
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
