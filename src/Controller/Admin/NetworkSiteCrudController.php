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
            ->setSearchFields(['id', 'name', 'icon', 'description', 'uri', 'displayUri', 'priority']);
    }

    public function configureFields(string $pageName): iterable
    {
        $name = TextField::new('name');
        $displayUri = TextField::new('displayUri');
        $uri = TextField::new('uri');
        $icon = TextField::new('icon');
        $description = TextareaField::new('description');
        $priority = IntegerField::new('priority');
        $id = IntegerField::new('id', 'ID');
        $createdAt = DateTimeField::new('createdAt');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$name, $uri, $displayUri, $priority];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $name, $icon, $description, $uri, $displayUri, $priority, $createdAt];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$name, $displayUri, $uri, $icon, $description, $priority];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$name, $displayUri, $uri, $icon, $description, $priority];
        }

        throw new \LogicException();
    }
}
