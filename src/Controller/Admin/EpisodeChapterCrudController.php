<?php

namespace App\Controller\Admin;

use App\Entity\EpisodeChapter;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class EpisodeChapterCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EpisodeChapter::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Chapters')
            ->setEntityLabelInSingular('Chapter')
            ->showEntityActionsInlined()
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('episode');
        yield TextField::new('name');
        yield IntegerField::new('startsAt');
        yield DateTimeField::new('createdAt')
            ->renderAsText()
            ->setFormTypeOptions([
                'format' => 'yyyy-MM-dd HH:mm:ss',
                'disabled' => true,
            ])
        ;
        yield AssociationField::new('creator');
    }
}
