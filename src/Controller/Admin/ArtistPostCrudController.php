<?php

namespace App\Controller\Admin;

use App\Entity\ArtistPost;
use App\Entity\ArtistProfile;
use App\Entity\User as AppUser;
use App\Repository\ArtistProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use Symfony\Component\Validator\Constraints\Image;

class ArtistPostCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly ArtistProfileRepository $artistProfileRepository,
        private readonly AdminContextProviderInterface $adminContextProvider,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return ArtistPost::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Post')
            ->setEntityLabelInPlural('Posts')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['id', 'title', 'slug', 'seoTitle']);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $user = $this->getUser();

        // Artists only see their own posts
        if ($user && !$this->isGranted('ROLE_ADMIN') && $this->isGranted('ROLE_ARTIST') && $user instanceof AppUser) {
            $qb->join('entity.artist', 'a')
                ->join('a.user', 'u')
                ->andWhere('u = :user')
                ->setParameter('user', $user);
        }

        return $qb;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof ArtistPost && !$this->isGranted('ROLE_ADMIN') && $this->isGranted('ROLE_ARTIST')) {
            $user = $this->getUser();
            if ($user instanceof AppUser) {
                $profile = $this->artistProfileRepository->findOneBy(['user' => $user]);
                if ($profile) {
                    $entityInstance->setArtist($profile);
                }
            }
        }

        // Keep publishedAt coherent
        if ($entityInstance instanceof ArtistPost) {
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
        if ($entityInstance instanceof ArtistPost) {
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

        // Admin can choose artist; artists auto-attach to themselves
        yield AssociationField::new('artist', 'Artist')->setPermission('ROLE_ADMIN');

        yield TextField::new('title', 'Title');
        yield SlugField::new('slug')->setTargetFieldName('title')->hideOnIndex();
        yield TextField::new('seoTitle', 'SEO title')
            ->hideOnIndex()
            ->setRequired(false)
            ->setPermission('ROLE_ADMIN');
        yield TextareaField::new('metaDescription', 'Meta description')
            ->hideOnIndex()
            ->setRequired(false)
            ->setHelp('Recommended: up to ~160 characters.')
            ->setPermission('ROLE_ADMIN');
        yield TextField::new('canonicalUrl', 'Canonical URL')
            ->hideOnIndex()
            ->setRequired(false)
            ->setHelp('Optional absolute URL override for canonical tag.')
            ->setPermission('ROLE_ADMIN');
        yield TextField::new('robotsDirective', 'Robots directive')
            ->hideOnIndex()
            ->setRequired(false)
            ->setHelp('Example: index,follow or noindex,nofollow.')
            ->setPermission('ROLE_ADMIN');
        yield TextField::new('ogTitle', 'OG title')
            ->hideOnIndex()
            ->setRequired(false)
            ->setPermission('ROLE_ADMIN');
        yield TextareaField::new('ogDescription', 'OG description')
            ->hideOnIndex()
            ->setRequired(false)
            ->setPermission('ROLE_ADMIN');
        yield TextField::new('ogImageUrl', 'OG image URL/path')
            ->hideOnIndex()
            ->setRequired(false)
            ->setHelp('Absolute URL or filename from uploads/posts.')
            ->setPermission('ROLE_ADMIN');
        yield TextField::new('ogImageAlt', 'OG image alt')
            ->hideOnIndex()
            ->setRequired(false)
            ->setPermission('ROLE_ADMIN');

        yield ImageField::new('imageUrl', 'Image')
            ->setBasePath('uploads/posts')
            ->setUploadDir('public/uploads/posts')
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

        $servicesField = AssociationField::new('services', 'Services')
            ->setFormTypeOption('by_reference', false);

        $profile = $this->resolveArtistProfileForServiceChoices($pageName);

        if ($profile instanceof ArtistProfile) {
            // EasyAdmin 4.27: if we set form query_builder ourselves, AssociationConfigurator's
            // merge never runs and setQueryBuilder is skipped. Only setQueryBuilder here; the
            // configurator wraps it with createQueryBuilder('entity') and invokes the callable
            // (return value is ignored — mutate the passed QueryBuilder).
            $servicesField = $servicesField
                ->setQueryBuilder(static function (QueryBuilder $qb) use ($profile): void {
                    $qb
                        ->join('entity.artistProfiles', 'ap')
                        ->andWhere('ap = :artist')
                        ->setParameter('artist', $profile)
                        ->orderBy('entity.category', 'ASC')
                        ->addOrderBy('entity.name', 'ASC');
                })
                // TomSelect "autocomplete" widget can still show unfiltered remote results; native
                // multi-select uses the EntityType query only (fine when the list is small).
                ->renderAsNativeWidget();
        }

        yield $servicesField;

        yield BooleanField::new('isPublished', 'Published');
        yield DateTimeField::new('publishedAt', 'Published at')->hideOnForm();
        yield DateTimeField::new('createdAt', 'Created')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Updated')->hideOnForm();

        yield TextEditorField::new('content', 'Content');
    }

    /**
     * Services on a post must be a subset of the artist's profile services.
     *
     * - Artist panel (no ROLE_ADMIN): logged-in artist's profile.
     * - Admin editing a post: the post's artist (new post still shows all services until saved).
     */
    private function resolveArtistProfileForServiceChoices(string $pageName): ?ArtistProfile
    {
        $user = $this->getUser();

        if (!$this->isGranted('ROLE_ADMIN') && $this->isGranted('ROLE_ARTIST') && $user instanceof AppUser) {
            return $this->artistProfileRepository->findOneBy(['user' => $user]);
        }

        if ($this->isGranted('ROLE_ADMIN') && Crud::PAGE_EDIT === $pageName) {
            $instance = $this->adminContextProvider->getContext()?->getEntity()?->getInstance();
            if ($instance instanceof ArtistPost) {
                $artist = $instance->getArtist();

                return $artist instanceof ArtistProfile ? $artist : null;
            }
        }

        return null;
    }
}


