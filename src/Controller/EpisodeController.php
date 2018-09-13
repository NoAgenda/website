<?php

namespace App\Controller;

use App\Entity\EpisodePartCorrection;
use App\Form\EpisodePartSuggestionType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/episode", name="episode_")
 */
class EpisodeController extends AbstractController
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
    public function partSuggestionAction(Request $request): Response
    {
        $correction = new EpisodePartCorrection;

        $form = $this->createForm(EpisodePartSuggestionType::class, $correction);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            dump($correction);
        }

        dump($form->isSubmitted());
        dump($form->isValid());
        die;
    }
}
