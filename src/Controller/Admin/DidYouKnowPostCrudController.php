<?php

namespace App\Controller\Admin;

use App\Entity\DidYouKnowPost;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use Symfony\Component\Validator\Constraints\File;

class DidYouKnowPostCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DidYouKnowPost::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Իսկ դուք գիտեի՞ք, որ...')
            ->setEntityLabelInPlural('Իսկ դուք գիտեի՞ք, որ...')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['id', 'title', 'slug', 'seoTitle']);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof DidYouKnowPost) {
            $entityInstance->setUpdatedAt(new \DateTime());
            if ($entityInstance->isPublished() && !$entityInstance->getPublishedAt()) {
                $entityInstance->setPublishedAt(new \DateTime());
            }
            if (!$entityInstance->isPublished()) {
                $entityInstance->setPublishedAt(null);
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof DidYouKnowPost) {
            $entityInstance->setUpdatedAt(new \DateTime());
            if ($entityInstance->isPublished() && !$entityInstance->getPublishedAt()) {
                $entityInstance->setPublishedAt(new \DateTime());
            }
            if (!$entityInstance->isPublished()) {
                $entityInstance->setPublishedAt(null);
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield TextField::new('title', 'Title');
        yield SlugField::new('slug')->setTargetFieldName('title')->hideOnIndex();
        yield TextEditorField::new('content', 'Content');

        yield BooleanField::new('isPublished', 'Published');
        yield DateTimeField::new('publishedAt', 'Published at')->hideOnForm();

        yield ImageField::new('imageUrl', 'Image or Video')
            ->setBasePath('uploads/posts')
            ->setUploadDir('public/uploads/posts')
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->setFileConstraints([
                new File([
                    'maxSize' => '50M',
                    'mimeTypes' => [
                        'image/jpeg', 'image/png', 'image/webp',
                        'video/mp4', 'video/webm', 'video/ogg'
                    ],
                ]),
            ])
            ->setFormTypeOption('attr', ['accept' => 'image/jpeg,image/png,image/webp,video/mp4,video/webm,video/ogg'])
            ->setRequired(false);

        yield TextField::new('seoTitle', 'SEO title')->hideOnIndex()->setRequired(false);
        yield TextareaField::new('metaDescription', 'Meta description')->hideOnIndex()->setRequired(false);

        yield DateTimeField::new('createdAt', 'Created')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Updated')->hideOnForm();
    }
}
