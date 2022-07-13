<?php

namespace App\Twig;

use App\Entity\EpisodeChapter;
use App\Entity\EpisodeChapterDraft;
use App\Entity\FeedbackVote;
use App\Entity\User;
use App\Repository\EpisodeChapterDraftRepository;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FeedbackExtension extends AbstractExtension
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly EpisodeChapterDraftRepository $episodeChapterDraftRepository,
        private readonly TokenStorageInterface $tokenStorage,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('feedback_vote_count', [$this, 'getVoteCount']),
            new TwigFunction('feedback_can_vote', [$this, 'canVote']),
            new TwigFunction('feedback_creators', [$this, 'getCreators'], ['is_safe' => ['html']]),
        ];
    }

    public function getCreators(EpisodeChapter|EpisodeChapterDraft $entity): string
    {
        if ($entity->isDraft()) {
            return $this->renderUsername($entity->getCreator());
        }

        $creators = [$entity->getCreator()];

        foreach ($this->episodeChapterDraftRepository->findAcceptedDraftsByChapter($entity) as $draft) {
            if ($draft->getCreator()) {
                $creators[] = $draft->getCreator();
            }
        }

        $creators = array_unique($creators, SORT_REGULAR);

        $output = '';
        $anonymous = 0;

        if (count($creators) > 0) {
            foreach ($creators as $creator) {
                if ($creator->isPublic() || $this->getUser()?->isMod()) {
                    if ($output !== '') {
                        $output .= ', ';
                    }


                    $output .= $this->renderUsername($creator);
                } else {
                    $anonymous++;
                }
            }

        }

        if ($anonymous > 0 && !$this->getUser()?->isMod()) {
            if ($output !== '') {
                $output .= ' & ';

                if ($anonymous === 1) {
                    $output .= 'an Anonymous Producer';
                } else {
                    $output .= $anonymous . ' Anonymous Producers';
                }
            } else {
                if ($anonymous === 1) {
                    $output .= 'Anonymous Producer';
                } else {
                    $output .= $anonymous . ' Anonymous Producers';
                }
            }
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
            'all' => 0,
        ];

        foreach ($votes as $vote) {
            $results[$vote->getSupported() ? 'supported' : 'rejected']++;
            $results['all']++;
        }

        return $results;
    }

    public function canVote(EpisodeChapterDraft $draft): bool
    {
        if ($draft->getCreator() === $this->getUser()) {
            return false;
        }

        foreach ($draft->getFeedbackItem()->getVotes() as $vote) {
            if ($this->getUser() === $vote->getCreator()) {
                return false;
            }
        }

        return true;
    }

    private function getUser(): ?User
    {
        return $this->tokenStorage->getToken()?->getUser();
    }

    private function renderUsername(User $creator): string
    {
        if ($this->getUser()?->isMod()) {
            return sprintf(
                '<a href="%s">%s</a>',
                $this->router->generate('feedback_user', ['user'=> $creator->getId()]),
                $creator->getUsername(),
            );
        }

        return $creator->getUsername();
    }
}
