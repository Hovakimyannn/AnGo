<?php

namespace App\Controller\Admin;

use App\Entity\Service;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ServiceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Service::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityPermission('ROLE_ADMIN');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Անվանում');

        // Aystex avelacnum enq Category-n vorpes Dropdown
        yield ChoiceField::new('category', 'Կատեգորիա')
            ->setChoices([
                'Վարսահարդարում' => 'hair',
                'Մատնահարդարում' => 'nails',
                'Դիմահարդարում' => 'makeup',
            ]);

        yield IntegerField::new('durationMinutes', 'Տևողություն (րոպե)');

        yield MoneyField::new('price', 'Գին')
            ->setCurrency('AMD')
            ->setStoredAsCents(false); // Ete uzum eq pahi tchisht tivy (orinak 5000), voch te lumanerov
    }
}