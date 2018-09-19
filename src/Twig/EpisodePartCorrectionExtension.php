<?php

namespace App\Twig;

use App\Entity\EpisodePartCorrection;
use App\Entity\EpisodePartCorrectionVote;
use App\Repository\EpisodePartCorrectionVoteRepository;
use App\Repository\UserTokenRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EpisodePartCorrectionExtension extends AbstractExtension
{
    private $episodePartCorrectionVoteRepository;
    private $requestStack;
    private $tokenStorage;
    private $userTokenRepository;

    public function __construct(RequestStack $requestStack, TokenStorageInterface $tokenStorage, EpisodePartCorrectionVoteRepository $episodePartCorrectionVoteRepository, UserTokenRepository $userTokenRepository)
    {
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
        $this->episodePartCorrectionVoteRepository = $episodePartCorrectionVoteRepository;
        $this->userTokenRepository = $userTokenRepository;
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
        $token = $this->tokenStorage->getToken();
        $user = $token->getUser();

        if ($token && $user instanceof UserInterface) {
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

        if ($request = $this->requestStack->getMasterRequest()) {
            $string = $request->cookies->get('guest_token');

            $token = $this->userTokenRepository->findOneBy(['token' => $string]);

            if (!$token) {
                return true;
            }

            if ($token === $correction->getCreatorToken()) {
                return false;
            }

            $vote = $this->episodePartCorrectionVoteRepository->findOneBy([
                'correction' => $correction,
                'creatorToken' => $token,
            ]);

            if ($vote) {
                return false;
            }

            return true;
        }

        return false;
    }
}
