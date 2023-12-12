<?php

namespace App\Controller\Admin;

use App\Entity\Episode;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Symfony\Component\HttpFoundation\Response;

class EpisodeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Episode::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Episodes')
            ->setDefaultSort(['publishedAt' => 'DESC'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $episodeUrl = Action::new('view', 'Go To Episode', 'fas fa-external-link-alt')
            ->linkToRoute('podcast_episode', fn (Episode $episode) => ['code' => $episode->getCode()])
            ->setHtmlAttributes(['target' => '_blank']);

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $episodeUrl);
    }

    public function configureFields(string $pageName): iterable
    {
        $isIndex = $pageName === Crud::PAGE_INDEX;

        yield FormField::addPanel('Info')
            ->setIcon('fas fa-info-circle');

        yield TextField::new('code', $isIndex ? 'No.' : 'Episode No.');

        if ($isIndex) {
            yield ImageField::new('cover_uri', 'Cover');
        }

        yield TextField::new('name');

        if (!$isIndex) {
            yield TextField::new('author');
            yield IntegerField::new('duration')
                ->setTemplatePath('admin/field/duration.html.twig');
        }

        yield DateField::new('publishedAt')
            ->renderAsText();
        yield BooleanField::new('special', $isIndex ? 'Special' : 'Special Episode')
            ->renderAsSwitch(!$isIndex);
        yield BooleanField::new('published')
            ->setHelp('Whether the episode is published on the website. Data for the episode is crawled before the episode is published.')
            ->renderAsSwitch(!$isIndex);

        if (!$isIndex) {
            yield FormField::addPanel('Crawler')
                ->setIcon('fas fa-bug')
                ->setHelp('Data used for crawling and processing metadata related to the show.');

            yield UrlField::new('recordingUri');
            yield UrlField::new('coverUri')
                ->setTemplatePath('admin/field/cover_uri.html.twig');
            yield TextField::new('coverPath');
            yield UrlField::new('publicShownotesUri');
            yield UrlField::new('shownotesUri')
                ->setTemplatePath('admin/field/shownotes_uri.html.twig');
            yield TextField::new('shownotesPath');

            yield FormField::addPanel('Chapters')
                ->setIcon('fas fa-book')
                ->setHelp('Data used for crawling and processing metadata related to the chapters.');

            yield UrlField::new('chaptersUri')
                ->setTemplatePath('admin/field/chapters_uri.html.twig');
            yield TextField::new('chaptersPath');

            yield FormField::addPanel('Transcript')
                ->setIcon('fas fa-bars')
                ->setHelp('Data used for crawling and processing metadata related to the transcript.');

            yield UrlField::new('transcriptUri')
                ->setTemplatePath('admin/field/transcript_uri.html.twig');
            yield TextField::new('transcriptPath')
                ->setTemplatePath('admin/field/transcript_path.html.twig');
        }
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

        return $this->render('admin/episode/transcript.html.twig', [
            'episode' => $episode,
            'contents' => $contents,
        ]);
    }
}
