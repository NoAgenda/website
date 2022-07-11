<?php

namespace App\Controller\Admin;

use App\Entity\UserAccount;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserAccountCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return UserAccount::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('User Account')
            ->setEntityLabelInPlural('User Accounts')
            ->showEntityActionsInlined();
    }

    public function configureFields(string $pageName): iterable
    {
        $isIndex = $pageName === Crud::PAGE_INDEX;

        yield TextField::new('username');
        yield TextField::new('plainPassword', 'New password')
            ->onlyOnForms();
        yield EmailField::new('email');
        yield BooleanField::new('admin', 'Is administrator')
            ->renderAsSwitch(!$isIndex);
        yield BooleanField::new('mod', 'Is moderator')
            ->renderAsSwitch(!$isIndex);
    }
}
