<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Entity\EpisodeChapter;
use App\Entity\EpisodeChapterDraft;
use App\Entity\FeedbackItem;
use App\Entity\FeedbackVote;
use App\Form\EpisodeChapterType;
use App\Repository\EpisodeChapterDraftRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/episode/{code}/chapters', name: 'podcast_episode_chapters_')]
#[ParamConverter('episode', class: Episode::class, options: ['mapping' => ['code' => 'code']])]
class PodcastEpisodeChaptersModerationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EpisodeChapterDraftRepository $episodeChapterDraftRepository,
    ) {}

    #[Route('/new', name: 'new')]
    public function new(Request $request, Episode $episode, ?UserInterface $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MOD');

        $chapter = (new EpisodeChapter())
            ->setEpisode($episode);

        $form = $this->createForm(EpisodeChapterType::class, $chapter, [
            'episode' => $episode,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $chapter->setCreator($user);

            $this->entityManager->persist($chapter);
            $this->entityManager->flush();

            return $this->redirectToReferral();
        }

        return $this->render('podcast/episode/chapters/new.html.twig', [
            'episode' => $episode,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/chapter/{chapter}/edit', name: 'edit')]
    #[Route('/draft/{draft}/edit', name: 'edit_draft')]
    public function edit(Request $request, Episode $episode, EpisodeChapter $chapter = null, EpisodeChapterDraft $draft = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MOD');

        $chapter = $chapter ?? $draft;

        $form = $this->createForm(EpisodeChapterType::class, $chapter, [
            'episode' => $episode,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($chapter);
            $this->entityManager->flush();

            return $this->redirectToReferral();
        }

        return $this->render('podcast/episode/chapters/edit.html.twig', [
            'episode' => $episode,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/chapter/{chapter}/delete', name: 'delete')]
    public function delete(EpisodeChapter $chapter): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MOD');

        $chapter->delete();

        $this->entityManager->persist($chapter);
        $this->entityManager->flush();

        return $this->redirectToReferral();
    }

    #[Route('/draft/{draft}/accept', name: 'accept_draft')]
    public function accept( EpisodeChapterDraft $draft): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MOD');

        $this->doAcceptDraft($draft);

        $this->entityManager->flush();

        return $this->redirectToReferral();
    }

    #[Route('/accept_all', name: 'accept_all')]
    public function acceptAll(Episode $episode): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MOD');

        $drafts = $this->episodeChapterDraftRepository->findNewSuggestionsByEpisode($episode, $this->getUser());

        foreach ($drafts as $draft) {
            $this->doAcceptDraft($draft);
        }

        $this->entityManager->flush();

        return $this->redirectToReferral();
    }

    #[Route('/draft/{draft}/reject', name: 'reject_draft')]
    public function reject(EpisodeChapterDraft $draft): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MOD');

        $draft->setRejected(true);

        $this->entityManager->persist($draft);
        $this->entityManager->flush();

        return $this->redirectToReferral();
    }

    private function doAcceptDraft(EpisodeChapterDraft $draft): void
    {
        $draft->setAccepted(true);

        if (!$chapter = $draft->getChapter()) {
            $chapter = new EpisodeChapter();

            $draft->setChapter($chapter);
            $chapter
                ->setCreator($draft->getCreator())
                ->setEpisode($draft->getEpisode());
        }

        $chapter
            ->setName($draft->getName())
            ->setDescription($draft->getDescription())
            ->setStartsAt($draft->getStartsAt())
            ->setDuration($draft->getDuration());

        $this->entityManager->persist($chapter);
        $this->entityManager->persist($draft);
    }

    private function redirectToReferral(): Response
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $referral = $request->query->get('referral');

        if ($referral === 'mod') {
            return $this->redirectToRoute('feedback_manage');
        } elseif ($referral === 'user') {
            return $this->redirectToRoute('feedback_user', ['user' => $request->attributes->get('draft')->getCreator()->getId()]);
        } else {
            return $this->redirectToRoute('podcast_episode_chapters', ['code' => $request->attributes->get('episode')->getCode()]);
        }
    }
}
