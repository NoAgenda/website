<?php

namespace App\Controller;

use App\Entity\ChatMessage;
use App\Entity\Episode;
use App\Form\ChatMessageType;
use App\Repository\ChatMessageRepository;
use App\Repository\EpisodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class ChatController extends Controller
{
    /** @var Serializer */
    private $serializer;
    private $entityManager;
    private $episodeRepository;
    private $chatMessageRepository;

    public function __construct(SerializerInterface $serializer, EntityManagerInterface $entityManager, EpisodeRepository $episodeRepository, ChatMessageRepository $chatMessageRepository)
    {
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->episodeRepository = $episodeRepository;
        $this->chatMessageRepository = $chatMessageRepository;
    }

    /**
     * @Route("/chat_messages/{episode}/{collection}", name="chat_message_collection", methods="GET")
     * @ParamConverter("episode", class="App\Entity\Episode", options={"mapping": {"episode": "code"}})
     */
    public function messageCollectionAction(Request $request, Episode $episode, int $collection): Response
    {
        $messages = $this->chatMessageRepository->findByEpisodeCollection($episode, $collection);

        return JsonResponse::fromJsonString($this->serializer->serialize($messages, 'json'));
    }

    /**
     * @Route("/chat", name="chat_message_post", methods="POST")
     */
    public function postMessageAction(Request $request, UserInterface $user): Response
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
                    ->setContents(trim($form->get('contents')->getData()))
                    ->setPostedAt($form->get('postedAt')->getData())
                    ->fromWebsite()
                ;

                $this->entityManager->persist($message);
                $this->entityManager->flush();

                return JsonResponse::create([
                    'status' => 'ok',
                    'message' => $this->serializer->normalize($message, 'json'),
                ]);
            }
        }

        return JsonResponse::create([
            'status' => 'error',
            'errors' => $form->getErrors(true),
        ]);
    }
}
