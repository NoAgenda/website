<?php

namespace App\Controller;

use App\Entity\EpisodePartCorrection;
use App\Form\EpisodePartSuggestionType;
use App\Repository\EpisodePartRepository;
use App\Repository\EpisodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/episode", name="episode_")
 */
class EpisodeController extends AbstractController
{
    /** @var Serializer */
    private $serializer;
    private $entityManager;
    private $episodeRepository;
    private $episodePartRepository;

    public function __construct(SerializerInterface $serializer, EntityManagerInterface $entityManager, EpisodeRepository $episodeRepository, EpisodePartRepository $episodePartRepository)
    {
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->episodeRepository = $episodeRepository;
        $this->episodePartRepository = $episodePartRepository;
    }

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
        dump($request);

        if ($form->isSubmitted() && $form->isValid()) {
            dump($correction);
            die('valid');
            $this->entityManager->persist($correction);

            $this->entityManager->flush();
        }

        $violations = $form->getErrors(true);

        dump($form->get('part')->getErrors());
        dump($correction);

        dump($form->isSubmitted() && $form->isValid());
        die;
    }
}
