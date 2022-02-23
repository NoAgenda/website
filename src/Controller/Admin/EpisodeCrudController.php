<?php

namespace App\Controller\Admin;

use App\Entity\Episode;
use App\Message\CrawlEpisodeTranscript;
use App\Message\MatchEpisodeChatMessages;
use App\Message\MatchEpisodeRecordingTime;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;

class EpisodeCrudController extends AbstractCrudController
{
    public function __construct(
        private MessageBusInterface $messenger,
        private AdminUrlGenerator $adminUrlGenerator
    ) { }

    public static function getEntityFqcn(): string
    {
        return Episode::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Episodes')
            ->setDefaultSort(['publishedAt' => 'DESC'])
            ->showEntityActionsInlined()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $episodeUrl = Action::new('player', 'Go To Episode', 'fas fa-external-link-alt')
            ->linkToRoute('player', function (Episode $episode): array {
                return ['episode' => $episode->getCode()];
            })
            ->setHtmlAttributes(['target' => '_blank'])
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $episodeUrl)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $isIndex = Crud::PAGE_INDEX === $pageName;
        $isDetail = Crud::PAGE_DETAIL === $pageName;

        yield TextField::new('code', $isIndex ? 'No.' : 'Episode No.');

        if ($isIndex) {
            yield ImageField::new('cover_uri', 'Cover');
        }

        yield TextField::new('name');

        if (!$isIndex) {
            yield TextField::new('author');
            yield IntegerField::new('duration');
        }

        yield DateField::new('publishedAt')
            ->renderAsText()
        ;
        yield BooleanField::new('special', $isIndex ? 'Special' : 'Special Episode')
            ->renderAsSwitch(!$isIndex)
        ;

        if (!$isIndex) {
            yield FormField::addPanel('Crawler')
                ->setIcon('fas fa-bug')
                ->setHelp('Data used for crawling and processing metadata related to the show.')
            ;

            yield UrlField::new('recordingUri');
            yield DateTimeField::new('recordedAt')
                ->renderAsText()
                ->setFormTypeOptions([
                    'format' => 'yyyy-MM-dd HH:mm:ss',
                ])
                ->setTemplatePath('admin/field/recorded_at.html.twig')
            ;
            yield UrlField::new('coverUri');
            yield UrlField::new('shownotesUri');

            yield FormField::addPanel('Transcript')
                ->setIcon('fas fa-bars')
                ->setHelp('Data used for crawling and processing metadata related to the transcript.')
            ;

            if ($isDetail) {
                yield BooleanField::new('transcriptExists')
                    ->setHelp('Episode has a transcript')
                    ->setTemplatePath('admin/field/transcript_exists.html.twig')
                ;
            }

            yield BooleanField::new('transcript')
                ->setHelp('Transcript is visible on the website')
            ;
            yield UrlField::new('transcriptUri');
            yield ChoiceField::new('transcriptType')
                ->setChoices([
                    'SRT' => 'srt',
                    'JSON' => 'json',
                ])
            ;

            yield FormField::addPanel('Live Chat')
                ->setIcon('fas fa-comments')
                ->setHelp('Data used for crawling and processing metadata related to live chat logs.')
            ;

            if ($isDetail) {
                yield BooleanField::new('chatMessagesExist')
                    ->setHelp('Episode has an archive of the live chat')
                    ->setTemplatePath('admin/field/chat_messages_exist.html.twig')
                ;
            }

            yield BooleanField::new('chatMessages')
                ->setHelp('Chat archive is visible on the website')
            ;
            yield TextField::new('chatNotice')
                ->setHelp('Message displayed above the chat archive in case of a problem, like being out of sync for a few minutes.')
            ;
        }
    }

    public function chatMessages(AdminContext $context): Response
    {
        $episode = $context->getEntity()->getInstance();

        if (!$episode->getChatMessagesExist()) {
            return $this->render('admin/error.html.twig', [
                'error' => sprintf('The chat archive for episode "%s" could not be found.', $episode),
            ]);
        }

        $chatMessages = json_decode(file_get_contents($episode->getChatMessagesPath()));

        return $this->render('admin/episode/chat_messages.html.twig', [
            'episode' => $episode,
            'chat_messages' => $chatMessages,
        ]);
    }

    public function transcript(AdminContext $context): Response
    {
        $episode = $context->getEntity()->getInstance();

        if (!$episode->hasTranscript()) {
            return $this->render('admin/error.html.twig', [
                'error' => sprintf('The transcript for episode "%s" could not be found.', $episode),
            ]);
        }

        $contents = file_get_contents($episode->getTranscriptPath());

        if ('json' === $episode->getTranscriptType()) {
            $contents = json_encode(json_decode($contents), JSON_PRETTY_PRINT);
        }

        return $this->render('admin/episode/transcript.html.twig', [
            'episode' => $episode,
            'contents' => $contents,
        ]);
    }

    public function crawlTranscript(AdminContext $context): Response
    {
        $episode = $context->getEntity()->getInstance();

        $message = new CrawlEpisodeTranscript($episode->getCode());

        $this->messenger->dispatch($message);

        $this->addFlash('success', 'Job queued');

        return $this->redirect($this->adminUrlGenerator
            ->setController(self::class)
            ->setAction('detail')
            ->setEntityId($episode->getId())
            ->generateUrl()
        );
    }

    public function matchChatMessages(AdminContext $context): Response
    {
        $episode = $context->getEntity()->getInstance();

        $message = new MatchEpisodeChatMessages($episode->getCode());

        $this->messenger->dispatch($message);

        $this->addFlash('success', 'Job queued');

        return $this->redirect($this->adminUrlGenerator
            ->setController(self::class)
            ->setAction('detail')
            ->setEntityId($episode->getId())
            ->generateUrl()
        );
    }

    public function matchRecordingTime(AdminContext $context): Response
    {
        $episode = $context->getEntity()->getInstance();

        $message = new MatchEpisodeRecordingTime($episode->getCode());

        $this->messenger->dispatch($message);

        $this->addFlash('success', 'Job queued');

        return $this->redirect($this->adminUrlGenerator
            ->setController(self::class)
            ->setAction('detail')
            ->setEntityId($episode->getId())
            ->generateUrl()
        );
    }
}
