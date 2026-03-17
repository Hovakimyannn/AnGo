<?php

namespace App\Controller\Admin;

use App\Entity\DidYouKnowComment;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class DidYouKnowCommentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DidYouKnowComment::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('DYK Comment')
            ->setEntityLabelInPlural('DYK Comments')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['id', 'body']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('post', 'Post')->hideOnForm();
        yield AssociationField::new('user', 'User')->hideOnForm();
        yield TextareaField::new('body', 'Comment')->hideOnForm();
        yield BooleanField::new('isApproved', 'Approved');
        yield DateTimeField::new('createdAt', 'Created')->hideOnForm();
    }
}
