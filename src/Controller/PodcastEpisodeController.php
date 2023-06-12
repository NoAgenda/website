<?php

namespace App\Controller;

use App\Crawling\Shownotes\ShownotesParserFactory;
use App\Entity\Episode;
use App\Repository\EpisodeChapterDraftRepository;
use App\Repository\EpisodeChapterRepository;
use App\Utilities;
use Benlipp\SrtParser\Parser as SrtParser;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class PodcastEpisodeController extends AbstractController
{
    public function __construct(
        private readonly EpisodeChapterRepository $episodeChapterRepository,
        private readonly EpisodeChapterDraftRepository $episodeChapterDraftRepository,
        private readonly ShownotesParserFactory $shownotesParserFactory,
    ) {}

    #[Route('/listen/{code}', name: 'podcast_episode')]
    #[ParamConverter('episode', class: Episode::class, options: ['mapping' => ['code' => 'code']])]
    public function episode(Request $request, Episode $episode): Response
    {
        $timestamp = Utilities::parsePrettyTimestamp($request->query->get('t', 0));
        $transcriptTimestamp = Utilities::parsePrettyTimestamp($request->query->get('transcript', 0));

        if ($transcriptTimestamp > 0) {
            return $this->redirectToRoute('podcast_episode_transcript', ['code' => $episode->getCode(), 't' => $transcriptTimestamp]);
        }

        if ($episode->hasShownotes()) {
            $shownotes = $this->shownotesParserFactory->create($episode);
        }

        if ($episode->hasChapters()) {
            $chapters = json_decode(file_get_contents($episode->getChaptersPath()), true);
            $chapters = $chapters['chapters'] ?? null;
        }

        return $this->render('podcast/episode/episode.html.twig', [
            'autoplay_timestamp' => $timestamp,

            'episode' => $episode,
            'chapters' => $chapters ?? null,
            'shownotes' => $shownotes ?? null,
        ]);
    }

    #[Route('/listen/{code}/chapters', name: 'podcast_episode_chapters')]
    #[ParamConverter('episode', class: Episode::class, options: ['mapping' => ['code' => 'code']])]
    public function episodeChapters(Request $request, ?UserInterface $user, Episode $episode): Response
    {
        $timestamp = Utilities::parsePrettyTimestamp($request->query->get('t', 0));

        if (!$user?->isMod()) {
            return $this->redirectToRoute('podcast_episode', ['t' => $timestamp]);
        }

        $chapters = array_merge(
            $this->episodeChapterRepository->findByEpisode($episode),
            $this->episodeChapterDraftRepository->findNewSuggestionsByEpisode($episode, $this->getUser() ?? null)
        );

        usort($chapters, function ($a, $b) {
            return $a->getStartsAt() <=> $b->getStartsAt();
        });

        return $this->render('podcast/episode/chapters.html.twig', [
            'autoplay_timestamp' => $timestamp,

            'episode' => $episode,
            'chapters' => $chapters,
        ]);
    }

    #[Route('/listen/{code}/shownotes', name: 'podcast_episode_shownotes')]
    #[ParamConverter('episode', class: Episode::class, options: ['mapping' => ['code' => 'code']])]
    public function episodeShownotes(Episode $episode): Response
    {
        if (!$episode->hasShownotes()) {
            throw new NotFoundHttpException();
        }

        $shownotes = $this->shownotesParserFactory->create($episode);

        return $this->render('podcast/episode/shownotes.html.twig', [
            'episode' => $episode,
            'shownotes' => $shownotes,
        ]);
    }

    #[Route('/listen/{code}/transcript', name: 'podcast_episode_transcript')]
    #[ParamConverter('episode', class: Episode::class, options: ['mapping' => ['code' => 'code']])]
    public function episodeTranscript(Request $request, Episode $episode): Response
    {
        if (!$episode->hasTranscript()) {
            throw new NotFoundHttpException();
        }

        $timestamp = Utilities::parsePrettyTimestamp($request->query->get('t', 0));

        $contents = file_get_contents($episode->getTranscriptPath());
        $transcript = (new SrtParser())->loadString($contents)->parse();

        return $this->render('podcast/episode/transcript.html.twig', [
            'autoplay_timestamp' => $timestamp,

            'episode' => $episode,
            'transcript_lines' => $transcript,
        ]);
    }

    #[Route('/listen/{code}/chat', name: 'podcast_episode_chat_archive')]
    #[ParamConverter('episode', class: Episode::class, options: ['mapping' => ['code' => 'code']])]
    public function episodeChatArchive(Episode $episode): Response
    {
        if (!$episode->hasChatArchive()) {
            throw new NotFoundHttpException();
        }

        return new BinaryFileResponse($episode->getChatArchivePath());
    }
}
