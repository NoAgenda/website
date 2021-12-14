<?php

namespace App\Controller\Admin;

use App\Entity\BatSignal;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BatSignalCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BatSignal::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Bat Signals')
            ->setEntityLabelInSingular('Bat Signal')
            ->showEntityActionsAsDropdown(false)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('code');
        yield DateTimeField::new('deployedAt')
            ->renderAsText()
            ->setFormTypeOptions([
                'format' => 'yyyy-MM-dd HH:mm:ss',
            ])
        ;
    }
}
