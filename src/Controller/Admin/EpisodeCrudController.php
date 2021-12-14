<?php

namespace App\Controller\Admin;

use App\Entity\Episode;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

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
            ->showEntityActionsAsDropdown(false)
            ->setDefaultSort(['publishedAt' => 'DESC'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $isIndex = Crud::PAGE_INDEX === $pageName;

        yield TextField::new('code', $isIndex ? 'No.' : 'Episode No.');

        if ($isIndex) {
            yield ImageField::new('cover_uri', 'Cover');
        }

        yield TextField::new('name');

        if (!$isIndex) {
            yield TextField::new('author');
            yield IntegerField::new('duration');
        }

        yield DateField::new('publishedAt');

        if (!$isIndex) {
            yield TextField::new('shownotesUri');
        }

        yield BooleanField::new('special', $isIndex ? 'Special' : 'Special Episode')
            ->renderAsSwitch(!$isIndex)
        ;

        if (!$isIndex) {
            yield BooleanField::new('chatMessages', 'Episode has an archive of the live chat');
            yield BooleanField::new('transcript', 'Episode has a transcript');
            yield TextField::new('chatNotice');

            yield FormField::addPanel('Crawler data')
                ->setIcon('fas fa-bug')
                ->setHelp('Data used for crawling and processing metadata related to the show.')
            ;

            yield TextField::new('coverUri');
            yield TextField::new('recordingUri');
            yield DateTimeField::new('recordedAt')
                ->renderAsText()
                ->setFormTypeOptions([
                    'format' => 'yyyy-MM-dd HH:mm:ss',
                ])
            ;
            yield TextField::new('transcriptUri');
        }
    }
}
