<?php

namespace App\Controller;

use App\BatSignalReceiver;
use App\FeedParser;
use App\Repository\ChatSourceMessageRepository;
use App\TranscriptParser;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DebugController extends Controller
{
    /**
     * @Route("/debug/bat_signal")
     */
    public function batSignal(): Response
    {
        $output = (new BatSignalReceiver())->receive();

        return $this->render('debug/dump.html.twig', [
            'outputs' => [$output],
        ]);
    }

    /**
     * @Route("/debug/feed")
     */
    public function feed(): Response
    {
        $output = (new FeedParser())->parse();

        return $this->render('debug/dump.html.twig', [
            'outputs' => [$output],
        ]);
    }

    /**
     * @Route("/debug/transcript")
     */
    public function transcript(): Response
    {
        $output = (new TranscriptParser())->parse('https://natranscript.online/tr/wp-content/uploads/2018/05/1035-transcript.opml');

        return $this->render('debug/dump.html.twig', [
            'outputs' => [$output],
        ]);
    }

    /**
     * @Route("/debug/transcripts")
     */
    public function transcripts(): Response
    {
        set_time_limit(0);

        $output = (new TranscriptParser())->crawl();

        return $this->render('debug/dump.html.twig', [
            'outputs' => [$output],
        ]);
    }
}
