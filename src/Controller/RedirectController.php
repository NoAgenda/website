<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Utilities;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
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

    #[Route('/subscribe', name: 'subscribe_redirect')]
    public function subscribeRedirect(): Response
    {
        return $this->redirectToRoute('podcast_subscribe', status: Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/social', name: 'social_redirect')]
    public function social(): Response
    {
        return $this->redirectToRoute('livestream', status: Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/listen/{code}/audio', name: 'podcast_recording_redirect')]
    #[ParamConverter('episode', class: Episode::class, options: ['mapping' => ['code' => 'code']])]
    public function podcastRecordingRedirect(Episode $episode): Response
    {
        if (!$recordingUri = $episode->getRecordingUri()) {
            throw new NotFoundHttpException();
        }

        return new RedirectResponse($recordingUri, Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/listen/{code}/chapters', name: 'podcast_episode_chapters_redirect')]
    #[ParamConverter('episode', class: Episode::class, options: ['mapping' => ['code' => 'code']])]
    public function episodeChapters(Request $request, Episode $episode): Response
    {
        $redirectParameters = ['code' => $episode->getCode()];

        $timestamp = Utilities::parsePrettyTimestamp($request->query->get('t', 0));
        if ($timestamp) {
            $redirectParameters['t'] = $timestamp;
        }

        return $this->redirectToRoute('podcast_episode', $redirectParameters);
    }
}
