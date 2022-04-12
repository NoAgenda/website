<?php

namespace App\Controller\Admin;

use App\Entity\Episode;
use App\Message\Crawl;
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
        private AdminUrlGenerator $adminUrlGenerator,
    ) {}

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
        $isIndex = $pageName === Crud::PAGE_INDEX;

        yield TextField::new('code', $isIndex ? 'No.' : 'Episode No.');

        if ($isIndex) {
            yield ImageField::new('cover_uri', 'Cover');
        }

        yield TextField::new('name');

        if (!$isIndex) {
            yield TextField::new('author');
            yield IntegerField::new('duration')
                ->setTemplatePath('admin/field/duration.html.twig')
            ;
        }

        yield DateField::new('publishedAt')
            ->renderAsText()
        ;
        yield BooleanField::new('special', $isIndex ? 'Special' : 'Special Episode')
            ->renderAsSwitch(!$isIndex)
        ;
        yield BooleanField::new('published')
            ->setHelp('Whether the episode is published on the website. Data for the episode is crawled before the episode is published.')
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
            yield UrlField::new('coverUri')
                ->setTemplatePath('admin/field/cover_uri.html.twig')
            ;
            yield TextField::new('coverPath');
            yield UrlField::new('publicShownotesUri');
            yield UrlField::new('shownotesUri')
                ->setTemplatePath('admin/field/shownotes_uri.html.twig')
            ;
            yield TextField::new('shownotesPath');

            yield FormField::addPanel('Transcript')
                ->setIcon('fas fa-bars')
                ->setHelp('Data used for crawling and processing metadata related to the transcript.')
            ;

            yield UrlField::new('transcriptUri')
                ->setTemplatePath('admin/field/transcript_uri.html.twig')
            ;
            yield TextField::new('transcriptPath')
                ->setTemplatePath('admin/field/transcript_path.html.twig')
            ;
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

            yield TextField::new('chatArchivePath')
                ->setTemplatePath('admin/field/chat_archive_path.html.twig')
            ;
            yield TextField::new('chatNotice')
                ->setHelp('Message displayed above the chat archive in case of a problem, like being out of sync for a few minutes.')
            ;
        }
    }

    public function chatArchive(AdminContext $context): Response
    {
        $episode = $context->getEntity()->getInstance();

        if (!$episode->getChatMessagesExist()) {
            return $this->render('admin/error.html.twig', [
                'error' => sprintf('The chat archive for episode "%s" could not be found.', $episode),
            ]);
        }

        $chatArchive = json_decode(file_get_contents($episode->getChatMessagesPath()));

        return $this->render('admin/episode/chat_archive.html.twig', [
            'episode' => $episode,
            'chat_archive' => $chatArchive,
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

        $message = new Crawl('transcript', $episode->getCode());
        $this->messenger->dispatch($message);

        $this->addFlash('success', sprintf('Scheduled crawling of transcript for episode %s.', $episode->getCode()));

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

        $message = new Crawl('chat_archive', $episode->getCode());
        $this->messenger->dispatch($message);

        $this->addFlash('success', sprintf('Scheduled crawling of chat_messages for episode %s.', $episode->getCode()));

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

        $message = new Crawl('recording_time', $episode->getCode());
        $this->messenger->dispatch($message);

        $this->addFlash('success', sprintf('Scheduled crawling of recording_time for episode %s.', $episode->getCode()));

        return $this->redirect($this->adminUrlGenerator
            ->setController(self::class)
            ->setAction('detail')
            ->setEntityId($episode->getId())
            ->generateUrl()
        );
    }
}
