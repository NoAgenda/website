<?php

namespace App\Controller;

use App\Entity\FeedbackItem;
use App\Entity\User;
use App\Repository\FeedbackItemRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/contributions', name: 'feedback_')]
class FeedbackController extends AbstractController
{
    public function __construct(
        private readonly FeedbackItemRepository $feedbackItemRepository,
        private readonly UserRepository $userRepository,
    ) {}

    #[Route('', name: 'open')]
    public function open(): Response
    {
        $unresolvedItems = $this->feedbackItemRepository->findPublicUnresolvedItems(50);

        return $this->render('feedback/open.html.twig', [
            'items' => $unresolvedItems,
        ]);
    }

    #[Route('/manage', name: 'manage')]
    public function manage(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MOD');

        $unresolvedItems = $this->feedbackItemRepository->findUnresolvedItems(null, true);
        $unreviewedUsers = $this->userRepository->findUnreviewedUsers();

        return $this->render('feedback/manage.html.twig', [
            'unresolved_items' => $unresolvedItems,
            'unreviewed_users' => $unreviewedUsers,
        ]);
    }

    #[Route('/user/{user}', name: 'user')]
    public function user(User $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MOD');

        $items = $this->feedbackItemRepository->findByCreator($user);

        $accepted = count(array_filter($items, fn (FeedbackItem $item) => $item->isAccepted()));
        $rejected = count(array_filter($items, fn (FeedbackItem $item) => $item->isRejected()));
        $resolved = max($accepted + $rejected, 1);
        $ratio = round(($accepted / $resolved) * 100, 1);

        return $this->render('feedback/user.html.twig', [
            'creator' => $user,
            'items' => $items,
            'acceptance_ratio' => $ratio,
        ]);
    }

    #[Route('/user/{user}/review', name: 'review')]
    public function review(User $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MOD');

        $user->setReviewed(true);

        $this->userRepository->persist($user, true);

        $this->addFlash('success', 'User authorized');

        return $this->redirectToRoute('feedback_user', ['user' => $user->getId()]);
    }

    #[Route('/user/{user}/ban', name: 'ban')]
    public function ban(User $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MOD');

        $user->setBanned(true);

        $this->userRepository->persist($user, true);

        $this->addFlash('success', 'User banned');

        return $this->redirectToRoute('feedback_user', ['user' => $user->getId()]);
    }

    #[Route('/user/{user}/unban', name: 'unban')]
    public function unban(User $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MOD');

        $user->setBanned(false);

        $this->userRepository->persist($user, true);

        $this->addFlash('success', 'User unbanned');

        return $this->redirectToRoute('feedback_user', ['user' => $user->getId()]);
    }
}
