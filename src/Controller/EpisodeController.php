<?php

namespace App\Controller;

use App\Entity\EpisodePartCorrection;
use App\Entity\User;
use App\Form\EpisodePartSuggestionType;
use App\Repository\EpisodePartRepository;
use App\Repository\EpisodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
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
     * @param User $user
     *
     * @Route("/part_suggestion", name="part_suggestion")
     */
    public function partSuggestionAction(Request $request, UserInterface $user): Response
    {
        $correction = new EpisodePartCorrection;
        $correction->setCreator($user);

        $form = $this->createForm(EpisodePartSuggestionType::class, $correction);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->get('part')->getData() == null) {
                throw new \RuntimeException('Invalid episode part.');
            }

            if ($form->isValid()) {
                $this->entityManager->persist($correction);

                $this->entityManager->flush();

                return JsonResponse::create();
            }
        }

        $violations = [];

        foreach ($form->getErrors(true) as $violation) {
            $field = $violation->getOrigin()->getName();

            $violations[$field] = $violations[$field] ?? [];

            $violations[$field][] = $violation->getMessage();
        }

        return JsonResponse::create($violations, Response::HTTP_BAD_REQUEST);
    }
}
