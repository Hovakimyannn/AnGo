<?php

namespace App\Controller\Admin;

use App\Entity\ArtistProfile;
use App\Entity\User as AppUser;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use Symfony\Component\Validator\Constraints\Image;

class ArtistProfileCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ArtistProfile::class;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $user = $this->getUser();
        if ($user && !$this->isGranted('ROLE_ADMIN') && $this->isGranted('ROLE_ARTIST') && $user instanceof AppUser) {
            $qb->join('entity.user', 'u')
                ->andWhere('u = :user')
                ->setParameter('user', $user);
        }

        return $qb;
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('user', 'Կապված օգտատեր')
            ->setHelp('Ընտրեք այն օգտատիրոջը, ով հանդիսանում է տվյալ վարպետը։')
            ->setPermission('ROLE_ADMIN');

        yield AssociationField::new('category', 'Կատեգորիա')
            ->setHelp('Ընտրեք կատեգորիա (կառավարվում է Admin → Կատեգորիաներ)։');

        // Nkarneri Upload
        yield ImageField::new('photoUrl', 'Պրոֆիլի նկար')
            ->setBasePath('uploads/photos') // Vortexic karda browser-y
            ->setUploadDir('public/uploads/photos') // Vortex qci server-y
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            // Server-side validation: only allow real images (prevents uploading HTML/JS into /uploads)
            ->setFileConstraints([
                new Image([
                    'maxSize' => '5M',
                    'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                    'mimeTypesMessage' => 'Խնդրում ենք վերբեռնել ճիշտ նկար (JPG/PNG/WebP)։',
                ]),
            ])
            ->setFormTypeOption('attr', ['accept' => 'image/jpeg,image/png,image/webp'])
            ->setRequired(false);

        yield AssociationField::new('services', 'Ծառայություններ')
            ->setFormTypeOption('by_reference', false) // Many-to-Many fix
            ->setPermission('ROLE_ADMIN');

        yield TextEditorField::new('bio', 'Կենսագրություն');
    }
}