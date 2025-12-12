<?php

namespace App\Controller\Admin;

use App\Entity\Availability;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;

class AvailabilityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Availability::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('artist');

        yield ChoiceField::new('dayOfWeek')
            ->setChoices([
                'Monday' => 1,
                'Tuesday' => 2,
                'Wednesday' => 3,
                'Thursday' => 4,
                'Friday' => 5,
                'Saturday' => 6,
                'Sunday' => 7,
            ]);

        yield TimeField::new('startTime')->setFormat('HH:mm');
        yield TimeField::new('endTime')->setFormat('HH:mm');

        yield BooleanField::new('isDayOff');
    }
}