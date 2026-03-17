<?php

namespace App\Controller\Admin;

use App\Entity\DidYouKnowRating;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

class DidYouKnowRatingCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DidYouKnowRating::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('DYK Rating')
            ->setEntityLabelInPlural('DYK Ratings')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('post', 'Post')->hideOnForm();
        yield AssociationField::new('user', 'User')->hideOnForm();
        yield IntegerField::new('value', 'Rating')->hideOnForm();
        yield DateTimeField::new('createdAt', 'Created')->hideOnForm();
    }
}
