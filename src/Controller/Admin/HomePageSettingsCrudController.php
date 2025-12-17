<?php

namespace App\Controller\Admin;

use App\Entity\HomePageSettings;
use App\Repository\HomePageSettingsRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use Symfony\Component\Validator\Constraints\Image;

final class HomePageSettingsCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly HomePageSettingsRepository $homePageSettingsRepository,
    ) {}

    public static function getEntityFqcn(): string
    {
        return HomePageSettings::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityPermission('ROLE_ADMIN')
            ->setEntityLabelInSingular('Home էջի նկարներ')
            ->setEntityLabelInPlural('Home էջի նկարներ');
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = $actions->disable(Action::DELETE);

        // Keep a single settings row (create only once).
        if ($this->homePageSettingsRepository->count([]) > 0) {
            $actions = $actions->disable(Action::NEW);
        }

        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        $constraints = [
            new Image([
                'maxSize' => '10M',
                'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                'mimeTypesMessage' => 'Խնդրում ենք վերբեռնել ճիշտ նկար (JPG/PNG/WebP)։',
            ]),
        ];

        yield ImageField::new('heroImage', 'Hero նկար (վերին մեծ նկար)')
            ->setHelp('Խորհուրդ է տրվում լայն նկար (օր. >= 1600px). Չափերը էջում նույնն են մնում (object-cover)։')
            ->setBasePath('uploads/photos')
            ->setUploadDir('public/uploads/photos')
            ->setUploadedFileNamePattern('home-hero-[randomhash].[extension]')
            ->setFileConstraints($constraints)
            ->setFormTypeOption('attr', ['accept' => 'image/jpeg,image/png,image/webp'])
            ->setRequired(false);

        yield ImageField::new('serviceHairImage', 'Ծառայություն՝ Վարսահարդարում')
            ->setHelp('Card-ի չափը ֆիքս է (h-80). Նկարը crop կլինի (object-cover)։')
            ->setBasePath('uploads/photos')
            ->setUploadDir('public/uploads/photos')
            ->setUploadedFileNamePattern('home-service-hair-[randomhash].[extension]')
            ->setFileConstraints($constraints)
            ->setFormTypeOption('attr', ['accept' => 'image/jpeg,image/png,image/webp'])
            ->setRequired(false);

        yield ImageField::new('serviceMakeupImage', 'Ծառայություն՝ Դիմահարդարում')
            ->setHelp('Card-ի չափը ֆիքս է (h-80). Նկարը crop կլինի (object-cover)։')
            ->setBasePath('uploads/photos')
            ->setUploadDir('public/uploads/photos')
            ->setUploadedFileNamePattern('home-service-makeup-[randomhash].[extension]')
            ->setFileConstraints($constraints)
            ->setFormTypeOption('attr', ['accept' => 'image/jpeg,image/png,image/webp'])
            ->setRequired(false);

        yield ImageField::new('serviceNailsImage', 'Ծառայություն՝ Մատնահարդարում')
            ->setHelp('Card-ի չափը ֆիքս է (h-80). Նկարը crop կլինի (object-cover)։')
            ->setBasePath('uploads/photos')
            ->setUploadDir('public/uploads/photos')
            ->setUploadedFileNamePattern('home-service-nails-[randomhash].[extension]')
            ->setFileConstraints($constraints)
            ->setFormTypeOption('attr', ['accept' => 'image/jpeg,image/png,image/webp'])
            ->setRequired(false);
    }
}


