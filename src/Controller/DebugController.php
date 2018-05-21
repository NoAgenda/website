<?php

namespace App\Controller;

use App\FeedParser;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DebugController extends Controller
{
    /**
     * @Route("/debug/parser")
     */
    public function test()
    {
        $output = (new FeedParser())->parse();

        return $this->render('debug/dump.html.twig', [
            'outputs' => [$output],
        ]);
    }
}
