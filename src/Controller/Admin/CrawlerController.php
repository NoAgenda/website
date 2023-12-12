<?php

namespace App\Controller\Admin;

use App\Crawling\CrawlingProcessor;
use App\Repository\EpisodeRepository;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function Sentry\captureException;

#[Route('/admin/crawler')] /* fake route prefix for easyadmin */
class CrawlerController extends AbstractController
{
    public function __construct(
        private readonly EpisodeRepository $episodeRepository,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly CrawlingProcessor $crawlingProcessor,
    ) {}

    #[Route('/', name: 'admin_crawler')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ('POST' === $request->getMethod()) {
            $url = $this->adminUrlGenerator->setRoute('admin_crawler')->generateUrl();

            $data = $request->request->get('task');
            $episode = null;

            if ($code = $request->request->get('code')) {
                if (!$episode = $this->episodeRepository->findOneByCode($code)) {
                    $this->addFlash('danger', sprintf('Invalid episode: %s', $code));

                    return $this->redirect($url);
                }
            }

            try {
                $this->crawlingProcessor->enqueue($data, $episode);
            } catch (\Exception $exception) {
                $this->addFlash('danger', $exception->getMessage());

                captureException($exception);

                return $this->redirect($url);
            }

            if ($code) {
                $this->addFlash('success', sprintf('Scheduled crawling of %s for episode %s.', $data, $code));
            } else {
                $this->addFlash('success', sprintf('Scheduled crawling of %s.', $data));
            }

            return $this->redirect($url);
        }

        return $this->render('admin/crawler/index.html.twig');
    }
}
