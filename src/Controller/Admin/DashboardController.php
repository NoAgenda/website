<?php

namespace App\Controller\Admin;

use App\Entity\BatSignal;
use App\Entity\Episode;
use App\Entity\NetworkSite;
use App\Entity\User;
use App\Repository\EpisodeRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly EpisodeRepository $episodeRepository,
    ) {}

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('No Agenda Website Console');
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addWebpackEncoreEntry(Asset::new('console')
                ->webpackEntrypointName('console'));
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fas fa-home');
        yield MenuItem::linkToCrud('Episodes', 'fas fa-podcast', Episode::class);
        yield MenuItem::linkToCrud('Network Sites', 'fas fa-globe', NetworkSite::class);
        yield MenuItem::linkToCrud('Users', 'fas fa-user', User::class);

        yield MenuItem::section('Processing');
        yield MenuItem::linkToRoute('Crawler', 'fas fa-bug', 'admin_crawler');
        yield MenuItem::linkToCrud('Bat Signals', 'fas fa-signal', BatSignal::class);

        yield MenuItem::section('Site');
        yield MenuItem::linkToRoute('Back to Site', 'fas fa-door-open', 'root');
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        return parent::configureUserMenu($user)
            ->setName($user->getUserIdentifier());
    }

    #[Route('/console', name: 'admin')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/dashboard.html.twig', [
            'latest_episodes' => $this->episodeRepository->findLatestEpisodes(8, false),
        ]);
    }
}
