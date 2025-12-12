<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
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
            ->setEntityPermission('ROLE_ADMIN')
            ->setEntityLabelInSingular('Օգտատեր')
            ->setEntityLabelInPlural('Օգտատերեր')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['email', 'firstName', 'lastName', 'phone']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield EmailField::new('email', 'Էլ․ հասցե');
        yield TextField::new('firstName', 'Անուն');
        yield TextField::new('lastName', 'Ազգանուն');
        yield TextField::new('phone', 'Հեռախոս')->hideOnIndex();

        yield ChoiceField::new('roles', 'Դերեր')
            ->allowMultipleChoices()
            ->renderExpanded(false)
            ->setHelp('Նշեք աշխատակցի դերը։ ROLE_USER-ը միշտ ավտոմատ առկա է։')
            ->setChoices([
                'Ադմին' => 'ROLE_ADMIN',
                'Վարպետ' => 'ROLE_ARTIST',
            ]);
    }
}
