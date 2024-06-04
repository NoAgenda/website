<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/about', name: 'about_')]
#[Cache(maxage: '1 day')]
class AboutController extends AbstractController
{
    #[Route('', name: 'root')]
    public function root(): Response
    {
        return $this->render('about/root.html.twig');
    }

    #[Route('/community', name: 'community')]
    public function community(): Response
    {
        return $this->render('about/community.html.twig');
    }

    #[Route('/donations', name: 'donations')]
    public function donations(): Response
    {
        return $this->render('about/donations.html.twig');
    }

    #[Route('/dudes-named-ben', name: 'dudesnamedben')]
    public function dudesnamedben(): Response
    {
        return $this->render('about/dudesnamedben.html.twig');
    }

    #[Route('/stream', name: 'livestream')]
    public function livestream(): Response
    {
        return $this->render('about/livestream.html.twig');
    }

    #[Route('/mission-statement', name: 'mission_statement')]
    public function missionStatement(): Response
    {
        return $this->render('about/mission_statement.html.twig');
    }

    #[Route('/newsletter', name: 'newsletter')]
    public function newsletter(): Response
    {
        return $this->render('about/newsletter.html.twig');
    }

    #[Route('/donation-notes', name: 'notes')]
    public function notes(): Response
    {
        return $this->render('about/notes.html.twig');
    }

    #[Route('/peerage', name: 'peerage')]
    public function peerage(): Response
    {
        return $this->render('about/peerage.html.twig');
    }

    #[Route('/podcast', name: 'podcast')]
    public function podcast(): Response
    {
        return $this->render('about/podcast.html.twig');
    }

    #[Route('/podcasting20', name: 'podcasting20')]
    public function podcasting20(): Response
    {
        return $this->render('about/podcasting20.html.twig');
    }

    #[Route('/shownotes', name: 'shownotes')]
    public function shownotes(): Response
    {
        return $this->render('about/shownotes.html.twig');
    }

    #[Route('/troll-room', name: 'trollroom')]
    public function trollroom(): Response
    {
        return $this->render('about/trollroom.html.twig');
    }

    #[Route('/troll-room-registration', name: 'trollroom_registration')]
    public function trollroomRegistration(): Response
    {
        return $this->render('about/trollroom_registration.html.twig');
    }

    #[Route('/value4value', name: 'value4value')]
    public function value4value(): Response
    {
        return $this->render('about/value4value.html.twig');
    }

    #[Route('/website', name: 'website')]
    public function website(): Response
    {
        return $this->render('about/website.html.twig');
    }
}
