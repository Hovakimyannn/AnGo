<?php

namespace App\Controller\Admin;

use App\Entity\HomePageSettings;
use App\Repository\HomePageSettingsRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
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
            ->setEntityLabelInSingular('Home էջի կարգավորումներ')
            ->setEntityLabelInPlural('Home էջի կարգավորումներ');
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

    public function createEntity(string $entityFqcn): HomePageSettings
    {
        $settings = new HomePageSettings();

        // Defaults match the current hardcoded homepage copy, so the admin sees meaningful values immediately.
        $settings
            ->setHeroTitlePre('Բացահայտեք Ձեր')
            ->setHeroTitleHighlight('Կատարելությունը')
            ->setHeroSubtitle('Պրոֆեսիոնալ մոտեցում, բարձրակարգ սպասարկում և հարմարավետ միջավայր հենց Աբովյանի սրտում։')
            ->setHeroPrimaryButtonLabel('Ամրագրել Այց')
            ->setHeroSecondaryButtonLabel('Տեսնել Վարպետներին')
            ->setServicesTitle('Մեր Ծառայությունները')
            ->setServicesSubtitle('Աբովյանում (Abovyanum) AnGo-ում՝ Վարսահարդարում, Մատնահարդարում և Դիմահարդարում․ նաև մազերի խնամք ու մանիկյուր՝ Shellac-ով։')
            ->setServiceHairTitle('Վարսահարդարում')
            ->setServiceHairSubtitle('Կտրվածքներ, ներկում և խնամք')
            ->setServiceMakeupTitle('Դիմահարդարում')
            ->setServiceMakeupSubtitle('Երեկոյան և ամենօրյա make-up')
            ->setServiceNailsTitle('Մատնահարդարում')
            ->setServiceNailsSubtitle('Մանիկյուր և Պեդիկյուր')
            ->setArtistsTitle('Թոփ Վարպետներ')
            ->setArtistsSubtitle('Ծանոթացեք մեր պրոֆեսիոնալ թիմի հետ')
            ->setAboutTitle('Մեր մասին')
            ->setAboutText1('AnGo-ը ստեղծվել է՝ մեկ նպատակով․ առաջարկել բարձրակարգ ծառայություններ, պրոֆեսիոնալ մոտեցում և հարմարավետ միջավայր՝ յուրաքանչյուր այցը դարձնելով հաճելի փորձ։')
            ->setAboutText2('Մեր թիմը մշտապես հետևում է նորաձևության թրենդներին և աշխատում է որակյալ նյութերով՝ ապահովելով լավագույն արդյունքը։')
            ->setWhyUsTitle('Ինչու՞ մենք')
            ->setWhyUsItems("Պրոֆեսիոնալ վարպետներ\nԱնհատական մոտեցում\nՈրակյալ նյութեր\nՀարմարավետ միջավայր")
            ->setContactTitle('Կապ')
            ->setContactAddress('Ք.Աբովյան, Սարալանջի 22')
            ->setContactPhone('+374 94 64 99 24')
            ->setContactHoursLine1('Երկ - Շաբ: 10:00 - 20:00')
            ->setContactHoursLine2('Կիր: 11:00 - 18:00');

        return $settings;
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

        if ($pageName === Crud::PAGE_INDEX) {
            yield ImageField::new('heroImage', 'Hero')->setBasePath('uploads/photos');
            yield ImageField::new('serviceHairImage', 'Hair')->setBasePath('uploads/photos');
            yield ImageField::new('serviceMakeupImage', 'Makeup')->setBasePath('uploads/photos');
            yield ImageField::new('serviceNailsImage', 'Nails')->setBasePath('uploads/photos');
            yield TextField::new('servicesTitle', 'Ծառայություններ');
            yield TextField::new('artistsTitle', 'Վարպետներ');
            yield TextField::new('contactPhone', 'Հեռախոս');
            return;
        }

        yield FormField::addTab('Hero');
        yield ImageField::new('heroImage', 'Hero նկար (վերին մեծ նկար)')
            ->setHelp('Խորհուրդ է տրվում լայն նկար (օր. >= 1600px). Չափերը էջում նույնն են մնում (object-cover)։')
            ->setBasePath('uploads/photos')
            ->setUploadDir('public/uploads/photos')
            ->setUploadedFileNamePattern('home-hero-[randomhash].[extension]')
            ->setFileConstraints($constraints)
            ->setFormTypeOption('attr', ['accept' => 'image/jpeg,image/png,image/webp'])
            ->setRequired(false);

        yield TextField::new('heroTitlePre', 'Hero վերնագիր (մաս 1)')
            ->setHelp('Օրինակ՝ «Բացահայտեք Ձեր»')
            ->setRequired(false);
        yield TextField::new('heroTitleHighlight', 'Hero վերնագիր (highlight)')
            ->setHelp('Օրինակ՝ «Կատարելությունը» (կլինի վարդագույն)')
            ->setRequired(false);
        yield TextareaField::new('heroSubtitle', 'Hero նկարագրություն')
            ->setHelp('Կարող եք գրել մի քանի տող․ line break-երը կհայտնվեն էջում։')
            ->setRequired(false);
        yield TextField::new('heroPrimaryButtonLabel', 'Hero կոճակ (առաջին)')
            ->setRequired(false);
        yield TextField::new('heroSecondaryButtonLabel', 'Hero կոճակ (երկրորդ)')
            ->setRequired(false);

        yield FormField::addTab('Ծառայություններ');
        yield TextField::new('servicesTitle', 'Section վերնագիր')
            ->setHelp('Օր. «Մեր Ծառայությունները»')
            ->setRequired(false);
        yield TextareaField::new('servicesSubtitle', 'Section նկարագրություն (պարագրաֆ վերնագրի տակ)')
            ->setHelp('Կարճ տեքստ ծառայությունների ցանկի մասին, օր. Աբովյանում AnGo-ում՝ Վարսահարդարում, Մատնահարդարում...')
            ->setRequired(false);

        yield ImageField::new('serviceHairImage', 'Ծառայություն՝ Վարսահարդարում')
            ->setHelp('Card-ի չափը ֆիքս է (h-80). Նկարը crop կլինի (object-cover)։')
            ->setBasePath('uploads/photos')
            ->setUploadDir('public/uploads/photos')
            ->setUploadedFileNamePattern('home-service-hair-[randomhash].[extension]')
            ->setFileConstraints($constraints)
            ->setFormTypeOption('attr', ['accept' => 'image/jpeg,image/png,image/webp'])
            ->setRequired(false);
        yield TextField::new('serviceHairTitle', 'Վարսահարդարում՝ վերնագիր')->setRequired(false);
        yield TextField::new('serviceHairSubtitle', 'Վարսահարդարում՝ ենթավերնագիր')->setRequired(false);

        yield ImageField::new('serviceMakeupImage', 'Ծառայություն՝ Դիմահարդարում')
            ->setHelp('Card-ի չափը ֆիքս է (h-80). Նկարը crop կլինի (object-cover)։')
            ->setBasePath('uploads/photos')
            ->setUploadDir('public/uploads/photos')
            ->setUploadedFileNamePattern('home-service-makeup-[randomhash].[extension]')
            ->setFileConstraints($constraints)
            ->setFormTypeOption('attr', ['accept' => 'image/jpeg,image/png,image/webp'])
            ->setRequired(false);
        yield TextField::new('serviceMakeupTitle', 'Դիմահարդարում՝ վերնագիր')->setRequired(false);
        yield TextField::new('serviceMakeupSubtitle', 'Դիմահարդարում՝ ենթավերնագիր')->setRequired(false);

        yield ImageField::new('serviceNailsImage', 'Ծառայություն՝ Մատնահարդարում')
            ->setHelp('Card-ի չափը ֆիքս է (h-80). Նկարը crop կլինի (object-cover)։')
            ->setBasePath('uploads/photos')
            ->setUploadDir('public/uploads/photos')
            ->setUploadedFileNamePattern('home-service-nails-[randomhash].[extension]')
            ->setFileConstraints($constraints)
            ->setFormTypeOption('attr', ['accept' => 'image/jpeg,image/png,image/webp'])
            ->setRequired(false);
        yield TextField::new('serviceNailsTitle', 'Մատնահարդարում՝ վերնագիր')->setRequired(false);
        yield TextField::new('serviceNailsSubtitle', 'Մատնահարդարում՝ ենթավերնագիր')->setRequired(false);

        yield FormField::addTab('Վարպետներ');
        yield TextField::new('artistsTitle', 'Section վերնագիր')->setRequired(false);
        yield TextField::new('artistsSubtitle', 'Section նկարագրություն')->setRequired(false);

        yield FormField::addTab('Մեր մասին');
        yield TextField::new('aboutTitle', 'Section վերնագիր')->setRequired(false);
        yield TextareaField::new('aboutText1', 'Տեքստ 1')
            ->setHelp('Կարող եք գրել մի քանի տող․ line break-երը կհայտնվեն էջում։')
            ->setRequired(false);
        yield TextareaField::new('aboutText2', 'Տեքստ 2')
            ->setHelp('Կարող եք գրել մի քանի տող․ line break-երը կհայտնվեն էջում։')
            ->setRequired(false);
        yield TextField::new('whyUsTitle', '«Ինչու՞ մենք» վերնագիր')->setRequired(false);
        yield TextareaField::new('whyUsItems', '«Ինչու՞ մենք» ցանկ')
            ->setHelp('Յուրաքանչյուր տողը կդառնա մեկ կետ (օր՝ Պրոֆեսիոնալ վարպետներ)')
            ->setRequired(false);

        yield FormField::addTab('Կապ');
        yield TextField::new('contactTitle', 'Section վերնագիր')->setRequired(false);
        yield TextField::new('contactAddress', 'Հասցե')->setRequired(false);
        yield TextField::new('contactPhone', 'Հեռախոս')->setRequired(false);
        yield TextField::new('contactHoursLine1', 'Աշխատանքային ժամեր (տող 1)')->setRequired(false);
        yield TextField::new('contactHoursLine2', 'Աշխատանքային ժամեր (տող 2)')->setRequired(false);
    }
}


