<?php

namespace App\Controller;

use App\Repository\FeedbackItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FeedbackController extends AbstractController
{
    public function __construct(
        private FeedbackItemRepository $feedbackItemRepository,
    ) {}

    #[Route('/contributions', name: 'contributions_open')]
    public function open(): Response
    {
        $openFeedbackItems = $this->feedbackItemRepository->findOpenFeedbackItems(50);

        return $this->render('feedback/open.html.twig', [
            'items' => $openFeedbackItems,
        ]);
    }

    #[Route('/contributions/manage', name: 'contributions_manage')]
    public function manage(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MOD');

        $openFeedbackItems = $this->feedbackItemRepository->findOpenFeedbackItems(null);

        return $this->render('feedback/manage.html.twig', [
            'items' => $openFeedbackItems,
        ]);
    }
}
