<?php

namespace App\Controller;

use App\Repository\FeedbackItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FeedbackController extends AbstractController
{
    private $entityManager;
    private $feedbackItemRepository;

    public function __construct(EntityManagerInterface $entityManager, FeedbackItemRepository $feedbackItemRepository)
    {
        $this->entityManager = $entityManager;
        $this->feedbackItemRepository = $feedbackItemRepository;
    }

    /**
     * @Route("/contributions", name="contributions_open")
     */
    public function open(): Response
    {
        $openFeedbackItems = $this->feedbackItemRepository->findOpenFeedbackItems(50);

        return $this->render('feedback/open.html.twig', [
            'items' => $openFeedbackItems,
        ]);
    }
}
