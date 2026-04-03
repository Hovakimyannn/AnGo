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

    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $builder = parent::createNewFormBuilder($entityDto, $formOptions, $context);
        $this->addServicesFilterListener($builder);

        return $builder;
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $builder = parent::createEditFormBuilder($entityDto, $formOptions, $context);
        $this->addServicesFilterListener($builder);

        return $builder;
    }

    /**
     * Replace the "services" field at runtime so only the artist's own services appear.
     * EasyAdmin's setQueryBuilder doesn't work with Autocomplete, so we use PRE_SET_DATA.
     */
    private function addServicesFilterListener(FormBuilderInterface $builder): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $form = $event->getForm();
            if (!$form->has('services')) {
                return;
            }

            // Determine which artist profile to filter by
            $artistProfile = $this->resolveArtistProfile($event->getData());
            if (!$artistProfile) {
                return;
            }

            $profileId = $artistProfile->getId();
            if (!$profileId) {
                return;
            }

            $existing = $form->get('services');
            $label = $existing->getOption('label');
            $required = $existing->getOption('required');

            $form->remove('services');

            $form->add('services', EntityType::class, [
                'class' => Service::class,
                'label' => $label,
                'required' => $required,
                'multiple' => true,
                'expanded' => false,
                'by_reference' => false,
                'query_builder' => static function (EntityRepository $er) use ($profileId): QueryBuilder {
                    return $er->createQueryBuilder('s')
                        ->innerJoin('s.artistProfiles', 'ap')
                        ->andWhere('ap.id = :profileId')
                        ->setParameter('profileId', $profileId)
                        ->orderBy('s.category', 'ASC')
                        ->addOrderBy('s.name', 'ASC');
                },
                'attr' => [
                    'class' => 'form-select',
                ],
            ]);
        });
    }

    /**
     * Find the artist profile to use for filtering services.
     */
    private function resolveArtistProfile(mixed $data): ?ArtistProfile
    {
        // If editing an existing post that has an artist, use that artist
        if ($data instanceof ArtistPost && $data->getArtist() instanceof ArtistProfile) {
            return $data->getArtist();
        }

        // Otherwise use the currently logged-in user's artist profile
        $user = $this->getUser();
        if ($user instanceof AppUser) {
            return $this->artistProfileRepository->findOneBy(['user' => $user]);
        }

        return null;
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

        // Services field - filtering is done in addServicesFilterListener()
        yield AssociationField::new('services', 'Services')
            ->setFormTypeOption('by_reference', false);

        yield BooleanField::new('isPublished', 'Published');
        yield DateTimeField::new('publishedAt', 'Published at')->hideOnForm();
        yield DateTimeField::new('createdAt', 'Created')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Updated')->hideOnForm();

        yield TextEditorField::new('content', 'Content');
    }

}
