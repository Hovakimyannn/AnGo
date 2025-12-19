<?php

namespace App\Controller\Admin;

use App\Entity\ServiceCategory;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class ServiceCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ServiceCategory::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityPermission('ROLE_ADMIN')
            ->setEntityLabelInSingular('Կատեգորիա')
            ->setEntityLabelInPlural('Կատեգորիաներ')
            ->setDefaultSort(['sortOrder' => 'ASC', 'label' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield TextField::new('key', 'Key')
            ->setHelp('Օր. hair, makeup, nails. Պետք է լինի եզակի (unique) և ցանկալի է՝ միայն փոքրատառ (a-z, 0-9, _)։');

        yield TextField::new('label', 'Անվանում');

        yield IntegerField::new('sortOrder', 'Դասակարգում')
            ->setHelp('Փոքր թիվ = ավելի վերև ցուցակում։');

        yield BooleanField::new('isActive', 'Ակտիվ');
    }
}


