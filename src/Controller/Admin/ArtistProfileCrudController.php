<?php

namespace App\Controller\Admin;

use App\Entity\ArtistProfile;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ArtistProfileCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ArtistProfile::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('user', 'Linked User')
            ->setHelp('Yntreq User-in, ov handisanum e ays Artisty');

        yield TextField::new('specialization', 'Specialization')
            ->setHelp('Orinak: Hair Stylist, Nail Master');

        // Nkarneri Upload
        yield ImageField::new('photoUrl', 'Profile Photo')
            ->setBasePath('uploads/photos') // Vortexic karda browser-y
            ->setUploadDir('public/uploads/photos') // Vortex qci server-y
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->setRequired(false);

        yield AssociationField::new('services', 'Services')
            ->setFormTypeOption('by_reference', false); // Many-to-Many fix

        yield TextEditorField::new('bio', 'Biography');
    }
}