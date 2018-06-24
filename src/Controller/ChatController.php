<?php

namespace App\Controller;

use App\Entity\ChatMessage;
use App\Form\ChatMessageType;
use App\Repository\EpisodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class ChatController extends Controller
{
    private $entityManager;
    private $episodeRepository;

    public function __construct(EntityManagerInterface $entityManager, EpisodeRepository $episodeRepository)
    {
        $this->entityManager = $entityManager;
        $this->episodeRepository = $episodeRepository;
    }

    /**
     * @Route("/chat", name="chat_post_message", methods="POST")
     */
    public function postAction(Request $request, UserInterface $user)
    {
        $form = $this->createForm(ChatMessageType::class);
        $data = json_decode($request->getContent(), true);

        $form->submit($data);

        if ($form->isSubmitted()) {
            $episode = $this->episodeRepository->findOneBy(['code' => $data['episode']]);

            if ($episode === null) {
                $form->get('episode')->addError(new FormError('Invalid episode.'));
            }

            if ($form->isValid()) {
                $message = (new ChatMessage())
                    ->setEpisode($episode)
                    ->setUsername($user->getUsername())
                    ->setContents($data['contents'])
                    ->setPostedAt($data['postedAt'])
                    ->fromWebsite()
                ;

                $this->entityManager->persist($message);
                $this->entityManager->flush();

                return JsonResponse::create(['status' => 'ok']);
            }
        }

        return JsonResponse::create([
            'status' => 'error',
            'errors' => $form->getErrors(true),
        ]);
    }
}
