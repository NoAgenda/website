<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Entity\EpisodeChapter;
use App\Entity\EpisodeChapterDraft;
use App\Entity\FeedbackItem;
use App\Entity\FeedbackVote;
use App\Form\EpisodeChapterType;
use App\Repository\EpisodeChapterDraftRepository;
use App\Security\TokenAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/listen/{code}/chapters', name: 'podcast_episode_chapters_')]
#[ParamConverter('episode', class: Episode::class, options: ['mapping' => ['code' => 'code']])]
class PodcastEpisodeChaptersFeedbackController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TokenAuthenticator $tokenAuthenticator,
    ) {}

    #[Route('/suggest', name: 'suggest')]
    public function suggest(Request $request, Episode $episode, ?UserInterface $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MOD');

        if (!$user) {
            return $this->requestPermission($request, $episode);
        }

        if ($user?->isMod()) {
            return $this->redirectToRoute('podcast_episode_chapters_new', ['code' => $episode->getCode()]);
        }

        $draft = (new EpisodeChapterDraft())
            ->setCreator($user)
            ->setEpisode($episode);

        $form = $this->createForm(EpisodeChapterType::class, $draft, [
            'episode' => $episode,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($draft);
            $this->entityManager->flush();

            $feedbackItem = (new FeedbackItem())
                ->setCreator($user)
                ->setEntityName(EpisodeChapterDraft::class)
                ->setEntityId($draft->getId());

            $draft->setFeedbackItem($feedbackItem);

            $this->entityManager->persist($draft);
            $this->entityManager->persist($feedbackItem);
            $this->entityManager->flush();

            return $this->redirectToReferral();
        }

        return $this->render('podcast/episode/chapters/suggest.html.twig', [
            'episode' => $episode,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/improve/{chapter}', name: 'improve')]
    public function improve(Request $request, ?UserInterface $user, Episode $episode, EpisodeChapter $chapter): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MOD');

        if (!$user) {
            return $this->requestPermission($request, $episode);
        }

        if ($user?->isMod()) {
            return $this->redirectToRoute('podcast_episode_chapters_edit', ['code' => $episode->getCode(), 'chapter' => $chapter->getId()]);
        }

        $draft = (new EpisodeChapterDraft())
            ->setEpisode($episode)
            ->setChapter($chapter)
            ->setName($chapter->getName())
            ->setDescription($chapter->getDescription())
            ->setStartsAt($chapter->getStartsAt())
            ->setDuration($chapter->getDuration());

        $form = $this->createForm(EpisodeChapterType::class, $draft, [
            'episode' => $episode,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($draft);
            $this->entityManager->flush();

            $feedbackItem = (new FeedbackItem())
                ->setCreator($user)
                ->setEntityName(EpisodeChapterDraft::class)
                ->setEntityId($draft->getId());

            $draft->setFeedbackItem($feedbackItem);

            $this->entityManager->persist($draft);
            $this->entityManager->persist($feedbackItem);
            $this->entityManager->flush();

            return $this->redirectToReferral();
        }

        return $this->render('podcast/episode/chapters/improve.html.twig', [
            'episode' => $episode,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/vote/{draft}/{vote?support|reject}', name: 'vote')]
    public function vote(Request $request, ?UserInterface $user, Episode $episode, EpisodeChapterDraft $draft, string $vote): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MOD');

        if (!$user) {
            return $this->requestPermission($request, $episode);
        }

        $voteValue = $vote;
        $vote = (new FeedbackVote())
            ->setCreator($user)
            ->setItem($draft->getFeedbackItem());

        if ($voteValue === 'support') {
            $vote->setSupported();
        } else if ($voteValue === 'reject') {
            $vote->setRejected();
        } else {
            throw new \LogicException('Invalid vote value');
        }

        $this->entityManager->persist($vote);
        $this->entityManager->flush();

        return $this->redirectToReferral();
    }

    private function requestPermission(Request $request, Episode $episode): ?Response
    {
        if ($this->isCsrfTokenValid('generate-token', $request->request->get('_csrf_token'))) {
            $response = new RedirectResponse($request->getUri());

            $this->tokenAuthenticator->generateToken($request, $response);

            return $response;
        }

        return $this->render('feedback/permission.html.twig', [
            'episode' => $episode,
        ]);
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
