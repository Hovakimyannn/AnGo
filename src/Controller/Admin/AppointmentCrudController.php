<?php

namespace App\Controller\Admin;

use App\Entity\Appointment;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;

class AppointmentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Appointment::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Appointment')
            ->setEntityLabelInPlural('Appointments')
            ->setDefaultSort(['startDatetime' => 'DESC'])
            ->setSearchFields(['clientName', 'clientPhone', 'clientEmail', 'id']);
    }

    // AYS MASUM E GNVUM ANVTANGUTYAN LOGIKAN
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $user = $this->getUser();

        // Ete user-y ADMIN che (aysinqn Artist e), apa cuyc tur miayn IR patvernery
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            $qb->join('entity.artist', 'a')
                ->andWhere('a.user = :user')
                ->setParameter('user', $user);
        }

        return $qb;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield DateTimeField::new('startDatetime', 'Start')->setFormat('yyyy-MM-dd HH:mm');
        yield DateTimeField::new('endDatetime', 'End')->hideOnIndex();

        // Status-y sarqum enq Dropdown (Select)
        yield ChoiceField::new('status')
            ->setChoices([
                'Pending' => Appointment::STATUS_PENDING,
                'Confirmed' => Appointment::STATUS_CONFIRMED,
                'Completed' => Appointment::STATUS_COMPLETED,
                'Canceled' => Appointment::STATUS_CANCELED,
            ])
            ->renderAsBadges([
                Appointment::STATUS_PENDING => 'warning',
                Appointment::STATUS_CONFIRMED => 'success',
                Appointment::STATUS_COMPLETED => 'info',
                Appointment::STATUS_CANCELED => 'danger',
            ]);

        yield AssociationField::new('service', 'Service');
        yield AssociationField::new('artist', 'Master')->setPermission('ROLE_ADMIN'); // Artisty chi karox poxeel artistin, menak Adminy

        yield TextField::new('clientName', 'Client');
        yield TextField::new('clientPhone', 'Phone');
        yield EmailField::new('clientEmail', 'Email')->hideOnIndex();
    }
}