<?php

namespace App\Controller\Admin;

use App\Entity\NetworkSite;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class NetworkSiteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return NetworkSite::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Network Sites')
            ->setDefaultSort(['priority' => 'ASC'])
            ->setSearchFields(['id', 'name', 'icon', 'description', 'uri', 'displayUri', 'priority'])
            ->showEntityActionsInlined();
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name');
        yield TextField::new('icon')
            ->hideOnIndex();
        yield TextareaField::new('description')
            ->hideOnIndex();
        yield TextField::new('uri');
        yield TextField::new('displayUri');
        yield TextField::new('author');
        yield TextField::new('authorUri');
        yield IntegerField::new('priority');
    }
}
