<?php

namespace App\Twig;

use App\Entity\FeedbackVote;
use App\Entity\User;
use App\Entity\UserToken;
use App\Repository\EpisodeChapterDraftRepository;
use App\UserTokenManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FeedbackExtension extends AbstractExtension
{
    private $episodeChapterDraftRepository;
    private $userTokenManager;

    public function __construct(UserTokenManager $userTokenManager, EpisodeChapterDraftRepository $episodeChapterDraftRepository)
    {
        $this->userTokenManager = $userTokenManager;
        $this->episodeChapterDraftRepository = $episodeChapterDraftRepository;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('feedback_vote_count', [$this, 'getVoteCount']),
            new TwigFunction('feedback_can_vote', [$this, 'canVote']),
            new TwigFunction('feedback_creators', [$this, 'getCreators']),
        ];
    }

    public function getCreators($entity): string
    {
        if ($entity->isDraft()) {
            if ($entity->getCreator()) {
                return $entity->getCreator()->getUsername();
            } else if ($entity->getCreatorToken()) {
                return 'Guest producer';
            } else {
                return 'Woodstock';
            }
        }

        $creators = [];
        $anonymousCreators = [];

        if ($entity->getCreator()) {
            $creators[] = $entity->getCreator();
        } else if ($entity->getCreatorToken()) {
            $anonymousCreators[] = $entity->getCreatorToken();
        }

        foreach ($this->episodeChapterDraftRepository->findAcceptedDraftsByChapter($entity) as $draft) {
            if ($draft->getCreator()) {
                $creators[] = $draft->getCreator();
            } else if ($draft->getCreatorToken()) {
                $anonymousCreators[] = $draft->getCreatorToken();
            }
        }

        $creators = array_unique($creators);
        $anonymousCreators = array_unique($anonymousCreators);

        $output = '';

        if (count($creators) > 0) {
            foreach ($creators as $creator) {
                if ($output !== '') {
                    $output .= ', ';
                }

                $output .= $creator->getUsername();
            }
        }

        if (count($anonymousCreators) > 0) {
            if ($output !== '') {
                $output .= ' & ';

                if (count($anonymousCreators) === 1) {
                    $output .= 'a guest producer';
                } else {
                    $output .= count($anonymousCreators) . ' guest producers';
                }
            } else {
                if (count($anonymousCreators) === 1) {
                    $output .= 'Guest producer';
                } else {
                    $output .= count($anonymousCreators) . ' guest producers';
                }
            }
        }

        if ($output === '') {
            $output = 'Woodstock';
        }

        return $output;
    }

    /**
     * @param FeedbackVote[] $votes
     */
    public function getVoteCount(iterable $votes): array
    {
        $results = [
            'supported' => 0,
            'rejected' => 0,
        ];

        foreach ($votes as $vote) {
            if ($vote->getSupported()) {
                $results['supported']++;
            } else if ($vote->getRejected()) {
                $results['rejected']++;
            }
        }

        return $results;
    }

    /**
     * @param User|UserToken $creator
     * @param FeedbackVote[] $votes
     */
    public function canVote($creator, iterable $votes): bool
    {
        $token = $this->userTokenManager->getCurrent();

        if ($token === $creator) {
            return false;
        }

        foreach ($votes as $vote) {
            if ($vote->getCreator() instanceof User) {
                if ($token === $vote->getCreator()) {
                    return false;
                }
            } else if ($vote->getCreatorToken() instanceof UserToken) {
                if ($token === $vote->getCreatorToken()) {
                    return false;
                }
            }
        }

        return true;
    }
}
