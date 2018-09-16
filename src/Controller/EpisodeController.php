<?php

namespace App\Controller;

use App\Entity\EpisodePartCorrection;
use App\Entity\EpisodePartCorrectionVote;
use App\Entity\User;
use App\Form\EpisodePartCorrectionType;
use App\Form\EpisodePartSuggestionType;
use App\Repository\EpisodePartCorrectionRepository;
use App\Repository\EpisodePartCorrectionVoteRepository;
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
    private $episodePartCorrectionRepository;
    private $episodePartCorrectionVoteRepository;

    public function __construct(SerializerInterface $serializer, EntityManagerInterface $entityManager, EpisodeRepository $episodeRepository, EpisodePartRepository $episodePartRepository, EpisodePartCorrectionRepository $episodePartCorrectionRepository, EpisodePartCorrectionVoteRepository $episodePartCorrectionVoteRepository)
    {
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->episodeRepository = $episodeRepository;
        $this->episodePartRepository = $episodePartRepository;
        $this->episodePartCorrectionRepository = $episodePartCorrectionRepository;
        $this->episodePartCorrectionVoteRepository = $episodePartCorrectionVoteRepository;
    }

    /**
     * @param User $user
     *
     * @Route("/part_correction", name="part_correction", methods="POST")
     */
    public function partCorrectionAction(Request $request, UserInterface $user): Response
    {
        $correction = new EpisodePartCorrection;
        $correction->setCreator($user);

        $form = $this->createForm(EpisodePartCorrectionType::class, $correction);

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

    /**
     * @param User $user
     *
     * @Route("/part_suggestion", name="part_suggestion", methods="POST")
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

    /**
     * @param User $user
     *
     * @Route("/vote", name="vote", methods="POST")
     */
    public function voteAction(Request $request, UserInterface $user): Response
    {
        $correction = null;
        $correctionId = $request->request->get('correction');
        $voteValue = $request->request->get('vote');

        if (!in_array($voteValue, EpisodePartCorrectionVote::VOTES)) {
            throw new \RuntimeException(sprintf('Invalid vote "%s".', $voteValue));
        }

        if ($correctionId) {
            $correction = $this->episodePartCorrectionRepository->find($correctionId);
        }

        if (!$correction) {
            throw new \RuntimeException(sprintf('Invalid correction "%s".', $correctionId));
        }

        $vote = $this->episodePartCorrectionVoteRepository->findOneBy([
            'correction' => $correction,
            'creator' => $user,
        ]);

        if ($vote) {
            throw new \RuntimeException('You already voted on this correction.');
        }

        $vote = EpisodePartCorrectionVote::create($correction, $user, $voteValue);

        $this->entityManager->persist($vote);

        $this->entityManager->flush();

        return JsonResponse::create();
    }
}
