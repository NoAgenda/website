<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Users')
            ->showEntityActionsInlined()
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $isIndex = Crud::PAGE_INDEX === $pageName;

        yield TextField::new('username');
        yield TextField::new('plainPassword', 'New password')
            ->onlyOnForms()
        ;
        yield EmailField::new('email');
        yield BooleanField::new('hidden', 'Account is disabled by the user')
            ->onlyOnForms()
        ;
        yield BooleanField::new('admin', 'Is administrator')
            ->renderAsSwitch(!$isIndex)
        ;
        yield BooleanField::new('mod', 'Is moderator')
            ->renderAsSwitch(!$isIndex)
        ;
        yield BooleanField::new('hidden', 'Disabled')
            ->renderAsSwitch(false)
            ->onlyOnIndex()
        ;
    }
}
