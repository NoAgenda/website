<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Entity\EpisodeChapter;
use App\Entity\EpisodeChapterDraft;
use App\Entity\FeedbackItem;
use App\Entity\FeedbackVote;
use App\Form\EpisodeChapterType;
use App\Repository\EpisodeChapterDraftRepository;
use App\UserTokenManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ChapterController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EpisodeChapterDraftRepository $episodeChapterDraftRepository,
        private UserTokenManager $userTokenManager,
    ) {}

    #[Route('/guidelines/chapters', name: 'chapter_guidelines')]
    public function guidelines(): Response
    {
        return $this->render('chapter/guidelines.html.twig');
    }

    /**
     * @ParamConverter("episode", class="App\Entity\Episode", options={"mapping": {"episode": "code"}})
     */
    #[Route('/episode/{episode}/chapters/new', name: 'episode_chapter_new')]
    public function draftNew(Request $request, Episode $episode): Response
    {
        if ($request->getMethod() === 'POST' && !$this->userTokenManager->isAuthenticated()) {
            throw new AccessDeniedException();
        }

        $feedbackItem = new FeedbackItem();
        $feedbackItem->setEntityName(EpisodeChapterDraft::class);

        $draft = new EpisodeChapterDraft();
        $draft->setFeedbackItem($feedbackItem);
        $draft->setEpisode($episode);

        $this->userTokenManager->fill($draft);

        $form = $this->createForm(EpisodeChapterType::class, $draft);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($draft);
            $this->entityManager->flush();

            $feedbackItem->setEntityId($draft->getId());

            $this->entityManager->persist($feedbackItem);
            $this->entityManager->flush();

            return $this->redirectToReferral();
        }

        return $this->render('chapter/new.html.twig', [
            'episode' => $episode,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @ParamConverter("episode", class="App\Entity\Episode", options={"mapping": {"episode": "code"}})
     */
    #[Route('/episode/{episode}/chapters/{chapter}/improve', name: 'episode_chapter_refactor')]
    public function draftRefactor(Request $request, Episode $episode, EpisodeChapter $chapter): Response
    {
        if ($request->getMethod() === 'POST' && !$this->userTokenManager->isAuthenticated()) {
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

        $this->userTokenManager->fill($draft);

        $form = $this->createForm(EpisodeChapterType::class, $draft);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

    /**
     * @ParamConverter("episode", class="App\Entity\Episode", options={"mapping": {"episode": "code"}})
     */
    #[Route('/episode/{episode}/chapter/{chapter}/edit', name: 'episode_chapter_edit')]
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

    /**
     * @ParamConverter("episode", class="App\Entity\Episode", options={"mapping": {"episode": "code"}})
     */
    #[Route('/episode/{episode}/chapter/{chapter}/delete', name: 'episode_chapter_delete')]
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

    /**
     * @ParamConverter("episode", class="App\Entity\Episode", options={"mapping": {"episode": "code"}})
     */
    #[Route('/episode/{episode}/draft/{draft}/accept', name: 'episode_chapter_accept')]
    public function accept(Episode $episode, EpisodeChapterDraft $draft): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MOD');

        $this->doAcceptDraft($draft);

        $this->entityManager->flush();

        return $this->redirectToReferral();
    }

    /**
     * @ParamConverter("episode", class="App\Entity\Episode", options={"mapping": {"episode": "code"}})
     */
    #[Route('/episode/{episode}/draft/{draft}/accept_edit', name: 'episode_chapter_accept_edit')]
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

    /**
     * @ParamConverter("episode", class="App\Entity\Episode", options={"mapping": {"episode": "code"}})
     */
    #[Route('/episode/{episode}/accept_all', name: 'episode_chapter_accept_all')]
    public function acceptAll(Episode $episode): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MOD');

        $drafts = $this->episodeChapterDraftRepository->findNewSuggestionsByEpisode($episode);

        foreach ($drafts as $draft) {
            $this->doAcceptDraft($draft);
        }

        $this->entityManager->flush();

        return $this->redirectToReferral();
    }

    /**
     * @ParamConverter("episode", class="App\Entity\Episode", options={"mapping": {"episode": "code"}})
     */
    #[Route('/episode/{episode}/draft/{draft}/reject', name: 'episode_chapter_reject')]
    public function reject(Episode $episode, EpisodeChapterDraft $draft): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MOD');

        $draft->setRejected(true);

        $this->entityManager->persist($draft);
        $this->entityManager->flush();

        return $this->redirectToReferral();
    }

    /**
     * @ParamConverter("episode", class="App\Entity\Episode", options={"mapping": {"episode": "code"}})
     */
    #[Route('/episode/{episode}/draft/{draft}/vote/{vote?support|reject}', name: 'episode_chapter_vote')]
    public function vote(EpisodeChapterDraft $draft, string $vote): Response
    {
        if (!$this->userTokenManager->isAuthenticated()) {
            throw new AccessDeniedException();
        }

        $voteValue = $vote;
        $vote = new FeedbackVote();
        $vote->setItem($draft->getFeedbackItem());

        $this->userTokenManager->fill($vote);

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
            return $this->redirectToRoute('contributions_manage');
        } else {
            return $this->redirectToRoute('player', ['episode' => $request->attributes->get('episode')->getCode()]);
        }
    }
}
