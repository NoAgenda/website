<?php

namespace App\Controller\Admin;

use App\Entity\BatSignal;
use App\Entity\Episode;
use App\Entity\FeedbackItem;
use App\Entity\NetworkSite;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('No Agenda Show Administration Panel')
        ;
    }

    public function configureCrud(): Crud
    {
        return Crud::new();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fas fa-home');
        //yield MenuItem::linkToRoute('Contributions', 'fas fa-comment-alt', 'admin_feedback');
        yield MenuItem::linkToCrud('Episodes', 'fas fa-podcast', Episode::class);
        //yield MenuItem::linkToCrud('Chapters', 'fas fa-bookmark', EpisodeChapter::class);
        yield MenuItem::linkToCrud('Network Sites', 'fas fa-globe', NetworkSite::class);
        yield MenuItem::linkToCrud('Users', 'fas fa-user', User::class);

        yield MenuItem::section('Processing');
        yield MenuItem::linkToRoute('Crawler', 'fas fa-bug', 'admin_crawler', ['date' => 'today']);
        yield MenuItem::linkToCrud('Bat Signals', 'fas fa-signal', BatSignal::class);
        yield MenuItem::linkToRoute('Livestream Recordings', 'fas fa-microphone', 'admin_livestream_recordings');
        yield MenuItem::linkToRoute('Chat Logs', 'fas fa-comments', 'admin_chat_logs');

        yield MenuItem::section('Site');
        yield MenuItem::linkToRoute('Back to Site', 'fas fa-door-open', 'homepage');
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'latest_episodes' => $this->entityManager->getRepository(Episode::class)->findLatestEpisodes(8),
            'unresolved_feedback_count' => $this->entityManager->getRepository(FeedbackItem::class)->countUnresolvedItems(),
        ]);
    }

    /* Concept of feedback management in the admin panel */
    #[Route('/admin/feedback', name: 'admin_feedback')]
    public function feedback(): Response
    {
        return $this->render('admin/feedback.html.twig', [
            'latest_episodes' => $this->entityManager->getRepository(Episode::class)->findLatestEpisodes(8),
            'latest_feedback_items' => $this->entityManager->getRepository(FeedbackItem::class)->findOpenFeedbackItems(8),
        ]);
    }
}
