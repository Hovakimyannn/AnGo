<?php

namespace App\Controller\Admin;

use App\Entity\ArtistPost;
use App\Entity\ArtistProfile;
use App\Entity\Service;
use App\Entity\User as AppUser;
use App\Repository\ArtistProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
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
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
        if ($entityInstance instanceof ArtistPost) {
            // Priority 1: If artist is already set (e.g. by Admin in form), keep it.
            // Priority 2: If not set, try to auto-fill from currently logged-in user's profile.
            if ($entityInstance->getArtist() === null) {
                $user = $this->getUser();
                if ($user instanceof AppUser) {
                    $profile = $this->artistProfileRepository->findOneBy(['user' => $user]);
                    if ($profile) {
                        $entityInstance->setArtist($profile);
                    }
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

        yield AssociationField::new('services', 'Services')
            ->setFormTypeOption('by_reference', false)
            ->setQueryBuilder(function (QueryBuilder $qb) use ($pageName) {
                $profile = $this->resolveArtistProfileForServiceChoices($pageName);
                if ($profile) {
                    $ids = $profile->getServices()->map(fn(Service $s) => $s->getId())->toArray();
                    $ids = array_values(array_filter($ids));

                    if (empty($ids)) {
                        return $qb->andWhere('entity.id IS NULL'); // Return nothing
                    }

                    return $qb->andWhere('entity.id IN (:ids)')
                        ->setParameter('ids', $ids)
                        ->orderBy('entity.category', 'ASC')
                        ->addOrderBy('entity.name', 'ASC');
                }

                return $qb;
            });

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
     *
     * @param ArtistPost|null $post Set from form PRE_SET_DATA when available (more reliable than admin context).
     */
    private function resolveArtistProfileForServiceChoices(string $pageName, ?ArtistPost $post = null): ?ArtistProfile
    {
        $user = $this->getUser();

        // If we are editing an existing post, show services for that post's artist
        if (Crud::PAGE_EDIT === $pageName) {
            $instance = $post ?? $this->adminContextProvider->getContext()?->getEntity()?->getInstance();
            if ($instance instanceof ArtistPost && $instance->getArtist() instanceof ArtistProfile) {
                $artist = $instance->getArtist();
                return $this->artistProfileRepository->findWithServicesById((int) $artist->getId()) ?? $artist;
            }
        }

        // For NEW posts (or if Edit failed to find artist), default to currently logged-in user's profile
        if ($user instanceof AppUser) {
            return $this->artistProfileRepository->findWithServicesForUser($user)
                ?? $this->artistProfileRepository->findOneBy(['user' => $user]);
        }

        return null;
    }
}


