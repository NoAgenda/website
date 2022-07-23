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

class ChapterController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EpisodeChapterDraftRepository $episodeChapterDraftRepository,
    ) {}

    #[Route('/guidelines/chapters', name: 'chapter_guidelines')]
    public function guidelines(): Response
    {
        return $this->render('chapter/guidelines.html.twig');
    }

    #[Route('/episode/{episode}/chapters/new', name: 'episode_chapter_new')]
    #[ParamConverter('episode', class: Episode::class, options: ['mapping' => ['episode' => 'code']])]
    public function draftNew(Request $request, Episode $episode, ?UserInterface $user): Response
    {
        if ($request->getMethod() === 'POST' && !$user) {
            throw new AccessDeniedException();
        }

        if ($user->isMod()) {
            $entity = new EpisodeChapter();
        } else {
            $entity = new EpisodeChapterDraft();

            $feedbackItem = new FeedbackItem();
            $feedbackItem->setEntityName(EpisodeChapterDraft::class);
            $entity->setFeedbackItem($feedbackItem);
        }

        $entity->setEpisode($episode);

        $form = $this->createForm(EpisodeChapterType::class, $entity);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entity->setCreator($user);

            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            if (isset($feedbackItem)) {
                $feedbackItem->setCreator($user);
                $feedbackItem->setEntityId($entity->getId());

                $this->entityManager->persist($feedbackItem);
                $this->entityManager->flush();
            }

            return $this->redirectToReferral();
        }

        return $this->render('chapter/new.html.twig', [
            'episode' => $episode,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/episode/{episode}/chapters/{chapter}/improve', name: 'episode_chapter_refactor')]
    #[ParamConverter('episode', class: Episode::class, options: ['mapping' => ['episode' => 'code']])]
    public function draftRefactor(Request $request, Episode $episode, EpisodeChapter $chapter, ?UserInterface $user): Response
    {
        if ($request->getMethod() === 'POST' && !$user) {
            throw new AccessDeniedException();
        }

        $feedbackItem = new FeedbackItem();
        $feedbackItem->setEntityName(EpisodeChapterDraft::class);

        $draft = new EpisodeChapterDraft();
        $draft->setFeedbackItem($feedbackItem);
        $draft->setEpisode($episode);
        $draft->setChapter($chapter);
        $draft->setName($chapter->getName());
        $draft->setDescription($chapter->getDescription());
        $draft->setStartsAt($chapter->getStartsAt());
        $draft->setDuration($chapter->getDuration());

        $form = $this->createForm(EpisodeChapterType::class, $draft);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $feedbackItem->setCreator($user);
            $draft->setCreator($user);

            $this->entityManager->persist($draft);
            $this->entityManager->flush();

            $feedbackItem->setEntityId($draft->getId());

            $this->entityManager->persist($feedbackItem);
            $this->entityManager->flush();

            return $this->redirectToReferral();
        }

        return $this->render('chapter/refactor.html.twig', [
            'episode' => $episode,
            'draft' => $draft,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/episode/{episode}/chapter/{chapter}/edit', name: 'episode_chapter_edit')]
    #[ParamConverter('episode', class: Episode::class, options: ['mapping' => ['episode' => 'code']])]
    public function edit(Request $request, Episode $episode, EpisodeChapter $chapter): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MOD');

        $form = $this->createForm(EpisodeChapterType::class, $chapter);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($chapter);
            $this->entityManager->flush();

            return $this->redirectToReferral();
        }

        return $this->render('chapter/edit.html.twig', [
            'episode' => $episode,
            'chapter' => $chapter,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/episode/{episode}/chapter/{chapter}/delete', name: 'episode_chapter_delete')]
    #[ParamConverter('episode', class: Episode::class, options: ['mapping' => ['episode' => 'code']])]
    public function delete(Episode $episode, EpisodeChapter $chapter): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MOD');

        foreach ($chapter->getDrafts() as $draft) {
            $draft->setChapter(null);
        }

        $this->entityManager->remove($chapter);
        $this->entityManager->flush();

        return $this->redirectToReferral();
    }

    #[Route('/episode/{episode}/draft/{draft}/accept', name: 'episode_chapter_accept')]
    #[ParamConverter('episode', class: Episode::class, options: ['mapping' => ['episode' => 'code']])]
    public function accept(Episode $episode, EpisodeChapterDraft $draft): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MOD');

        $this->doAcceptDraft($draft);

        $this->entityManager->flush();

        return $this->redirectToReferral();
    }

    #[Route('/episode/{episode}/draft/{draft}/accept_edit', name: 'episode_chapter_accept_edit')]
    #[ParamConverter('episode', class: Episode::class, options: ['mapping' => ['episode' => 'code']])]
    public function acceptEdit(Episode $episode, EpisodeChapterDraft $draft): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MOD');

        $this->doAcceptDraft($draft);

        $this->entityManager->flush();

        return $this->redirectToRoute('episode_chapter_edit', [
            'episode' => $episode->getCode(),
            'chapter' => $draft->getChapter()->getId(),
        ]);
    }

    #[Route('/episode/{episode}/accept_all', name: 'episode_chapter_accept_all')]
    #[ParamConverter('episode', class: Episode::class, options: ['mapping' => ['episode' => 'code']])]
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

    #[Route('/episode/{episode}/draft/{draft}/reject', name: 'episode_chapter_reject')]
    #[ParamConverter('episode', class: Episode::class, options: ['mapping' => ['episode' => 'code']])]
    public function reject(Episode $episode, EpisodeChapterDraft $draft): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MOD');

        $draft->setRejected(true);

        $this->entityManager->persist($draft);
        $this->entityManager->flush();

        return $this->redirectToReferral();
    }

    #[Route('/episode/{episode}/draft/{draft}/vote/{vote?support|reject}', name: 'episode_chapter_vote')]
    #[ParamConverter('episode', class: Episode::class, options: ['mapping' => ['episode' => 'code']])]
    public function vote(EpisodeChapterDraft $draft, string $vote, ?UserInterface $user): Response
    {
        if (!$user) {
            throw new AccessDeniedException();
        }

        $voteValue = $vote;
        $vote = new FeedbackVote();
        $vote->setItem($draft->getFeedbackItem());
        $vote->setCreator($user);

        if ($voteValue === 'support') {
            $vote->setSupported();
        } else if ($voteValue === 'reject') {
            $vote->setRejected();
        } else {
            throw new \LogicException('Invalid vote value');
        }

        $this->entityManager->persist($vote);
        $this->entityManager->flush();

        return JsonResponse::create();
    }

    private function doAcceptDraft(EpisodeChapterDraft $draft): void
    {
        $draft->setAccepted(true);

        $chapter = $draft->getChapter();

        if (!$chapter) {
            $chapter = new EpisodeChapter();

            $draft->setChapter($chapter);
            $chapter->setCreator($draft->getCreator());
            $chapter->setEpisode($draft->getEpisode());
        }

        $chapter->setEpisode($draft->getEpisode());
        $chapter->setName($draft->getName());
        $chapter->setDescription($draft->getDescription());
        $chapter->setStartsAt($draft->getStartsAt());
        $chapter->setDuration($draft->getDuration());

        $this->entityManager->persist($chapter);
        $this->entityManager->persist($draft);
    }

    private function redirectToReferral(): Response
    {
        $request = $this->get('request_stack')->getCurrentRequest();
        $referral = $request->query->get('referral', 'episode');

        if ($referral === 'mod') {
            return $this->redirectToRoute('feedback_manage');
        } elseif ($referral === 'user') {
            return $this->redirectToRoute('feedback_user', ['user' => $request->attributes->get('draft')->getCreator()->getId()]);
        } else {
            return $this->redirectToRoute('player', ['episode' => $request->attributes->get('episode')->getCode()]);
        }
    }
}
