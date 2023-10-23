<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/external', name: 'external_')]
class ExternalController extends AbstractController
{
    #[Route('/donations', name: 'donations')]
    #[Cache(maxage: '1 day')]
    public function donations(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('external/donations.html.twig');
    }
}
