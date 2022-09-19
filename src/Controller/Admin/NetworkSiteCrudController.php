<?php

namespace App\Controller\Admin;

use App\Entity\NetworkSite;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
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
        yield IntegerField::new('id', 'ID')
            ->onlyOnDetail();
        yield TextField::new('name');
        yield TextField::new('icon')
            ->onlyOnDetail();
        yield TextareaField::new('description')
            ->onlyOnDetail();
        yield TextField::new('displayUri');
        yield TextField::new('uri');
        yield IntegerField::new('priority');
        yield DateTimeField::new('createdAt')
            ->onlyOnDetail();
    }
}
