<?php

namespace App\Twig;

use App\Entity\EpisodePartCorrection;
use App\Entity\EpisodePartCorrectionVote;
use App\Repository\EpisodePartCorrectionVoteRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EpisodePartCorrectionExtension extends AbstractExtension
{
    private $episodePartCorrectionVoteRepository;
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage, EpisodePartCorrectionVoteRepository $episodePartCorrectionVoteRepository)
    {
        $this->tokenStorage = $tokenStorage;
        $this->episodePartCorrectionVoteRepository = $episodePartCorrectionVoteRepository;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('correctionVotes', [$this, 'getCorrectionVotes']),
            new TwigFunction('canVoteCorrection', [$this, 'canVote']),
        ];
    }

    public function getCorrectionVotes(EpisodePartCorrection $correction): array
    {
        $votes = $this->episodePartCorrectionVoteRepository->findBy(['correction' => $correction]);
        $results = [];

        array_map(function ($key) use (&$results) {
            $results[$key] = 0;
        }, EpisodePartCorrectionVote::VOTES);

        foreach ($votes as $vote) {
            if ($vote->getSupported()) {
                ++$results[EpisodePartCorrectionVote::VOTE_SUPPORT];
            }
            if ($vote->getRejected()) {
                ++$results[EpisodePartCorrectionVote::VOTE_REJECT];
            }
            if ($vote->getQuestioned()) {
                ++$results[EpisodePartCorrectionVote::VOTE_QUESTION];
            }
        }

        return $results;
    }

    public function canVote(EpisodePartCorrection $correction): bool
    {
        if (null === $token = $this->tokenStorage->getToken()) {
            return false;
        }

        $user = $token->getUser();

        if ($user === $correction->getCreator()) {
            return false;
        }

        $vote = $this->episodePartCorrectionVoteRepository->findOneBy([
            'correction' => $correction,
            'creator' => $user,
        ]);

        if ($vote) {
            return false;
        }

        return true;
    }
}
