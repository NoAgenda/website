<?php

namespace App\Controller;

use App\FeedParser;
use App\TranscriptParser;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class DebugController extends Controller
{
    /**
     * @Route("/debug/parser")
     */
    public function parser()
    {
        $output = (new FeedParser())->parse();

        return $this->render('debug/dump.html.twig', [
            'outputs' => [$output],
        ]);
    }

    /**
     * @Route("/debug/transcripts")
     */
    public function transcripts()
    {
        $output = (new TranscriptParser())->parse('https://natranscript.online/tr/wp-content/uploads/2018/05/1035-transcript.opml');

        return $this->render('debug/dump.html.twig', [
            'outputs' => [$output],
        ]);
    }
}
