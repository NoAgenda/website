<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Utilities;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class RedirectController extends AbstractController
{
    #[Route('/archive/{page}', name: 'archive_redirect', requirements: ['page' => '\d+'], defaults: ['page' => 1])]
    public function archiveRedirect(int $page): Response
    {
        return $this->redirectToRoute('podcast_archive', ['page' => $page], status: Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/archive/specials/{page}', name: 'archive_specials_redirect', requirements: ['page' => '\d+'], defaults: ['page' => 1])]
    public function archiveSpecialsRedirect(int $page): Response
    {
        return $this->redirectToRoute('podcast_specials', ['page' => $page], status: Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/archive/all', name: 'archive_all')]
    public function archiveAllRedirect(): Response
    {
        return $this->redirectToRoute('podcast_all', status: Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/network', name: 'network_redirect')]
    public function networkRedirect(): Response
    {
        return $this->redirectToRoute('producers', status: Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/social', name: 'social_redirect')]
    public function social(): Response
    {
        return $this->redirectToRoute('livestream', status: Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/mission-statement', name: 'mission_statement_redirect')]
    public function missionStatement(): Response
    {
        return $this->redirectToRoute('about_mission_statement', status: Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/podcast/shownotes', name: 'podcast_shownotes_redirect')]
    public function podcastShownotes(): Response
    {
        return $this->redirectToRoute('about_shownotes', status: Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/podcast/subscribe', name: 'podcast_subscribe_redirect')]
    public function podcastSubscribe(): Response
    {
        return $this->redirectToRoute('subscribe', status: Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/podcasting20', name: 'podcasting20_redirect')]
    public function podcasting20(): Response
    {
        return $this->redirectToRoute('about_podcasting20', status: Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/trollroom', name: 'chat_redirect')]
    public function chat(): Response
    {
        return $this->redirectToRoute('about_trollroom', status: Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/website', name: 'website_redirect')]
    public function website(): Response
    {
        return $this->redirectToRoute('about_website', status: Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/listen/{code}/audio', name: 'podcast_recording_redirect')]
    public function podcastRecordingRedirect(#[MapEntity(mapping: ['code' => 'code'])] Episode $episode): Response
    {
        if (!$recordingUri = $episode->getRecordingUri()) {
            throw new NotFoundHttpException();
        }

        return new RedirectResponse($recordingUri, Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/listen/{code}/chapters', name: 'podcast_episode_chapters_redirect')]
    public function episodeChapters(Request $request, #[MapEntity(mapping: ['code' => 'code'])] Episode $episode): Response
    {
        $redirectParameters = ['code' => $episode->getCode()];

        $timestamp = Utilities::parsePrettyTimestamp($request->query->get('t', 0));
        if ($timestamp) {
            $redirectParameters['t'] = $timestamp;
        }

        return $this->redirectToRoute('podcast_episode', $redirectParameters);
    }
}
