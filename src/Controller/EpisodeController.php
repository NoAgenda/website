<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/episode", name="episode_")
 */
class EpisodeController
{
    /**
     * @Route("/part_correction", name="part_correction")
     */
    public function partCorrectionAction(): Response
    {
        return new Response('{yoo:"Boo"}');
    }

    /**
     * @Route("/part_suggestion", name="part_suggestion")
     */
    public function partSuggestionAction(): Response
    {
        return new Response('{yoo:"Boo"}');
    }
}
