<?php

namespace App\Controller\Admin;

use App\Entity\Appointment;
use App\Entity\User as AppUser;
use App\Service\AppointmentMailer;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AppointmentCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityManagerInterface $em,
        private AdminUrlGenerator $adminUrlGenerator,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private AppointmentMailer $appointmentMailer,
    ) {}

    public static function getEntityFqcn(): string
    {
        return Appointment::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $confirm = Action::new('markConfirmed', 'Հաստատել')
            ->setIcon('fa fa-check')
            ->linkToCrudAction('markConfirmed')
            ->displayIf(fn(Appointment $a) => $a->getStatus() !== Appointment::STATUS_CONFIRMED && $a->getStatus() !== Appointment::STATUS_COMPLETED && $a->getStatus() !== Appointment::STATUS_CANCELED);

        $complete = Action::new('markCompleted', 'Ավարտել')
            ->setIcon('fa fa-flag-checkered')
            ->linkToCrudAction('markCompleted')
            ->displayIf(fn(Appointment $a) => $a->getStatus() === Appointment::STATUS_CONFIRMED);

        $cancel = Action::new('markCanceled', 'Չեղարկել')
            ->setIcon('fa fa-ban')
            ->linkToCrudAction('markCanceled')
            ->displayIf(fn(Appointment $a) => $a->getStatus() !== Appointment::STATUS_CANCELED && $a->getStatus() !== Appointment::STATUS_COMPLETED);

        return $actions
            ->add(Crud::PAGE_INDEX, $confirm)
            ->add(Crud::PAGE_INDEX, $complete)
            ->add(Crud::PAGE_INDEX, $cancel);
    }

    public function markConfirmed(AdminContext $context): Response
    {
        return $this->updateStatus($context, Appointment::STATUS_CONFIRMED, 'Ամրագրումը հաստատվեց։');
    }

    public function markCompleted(AdminContext $context): Response
    {
        return $this->updateStatus($context, Appointment::STATUS_COMPLETED, 'Ամրագրումը նշվեց որպես ավարտված։');
    }

    public function markCanceled(AdminContext $context): Response
    {
        return $this->updateStatus($context, Appointment::STATUS_CANCELED, 'Ամրագրումը չեղարկվեց։');
    }

    public function ajaxSetStatus(AdminContext $context, Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent() ?: '[]', true) ?: [];
        $token = (string)($payload['_token'] ?? '');
        $status = (string)($payload['status'] ?? '');

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('appointment_status', $token))) {
            return $this->json(['success' => false, 'error' => 'Invalid CSRF token.'], 400);
        }

        $allowed = [
            Appointment::STATUS_PENDING,
            Appointment::STATUS_CONFIRMED,
            Appointment::STATUS_COMPLETED,
            Appointment::STATUS_CANCELED,
        ];
        if (!in_array($status, $allowed, true)) {
            return $this->json(['success' => false, 'error' => 'Invalid status.'], 400);
        }

        $instance = $context->getEntity()?->getInstance();
        if (!$instance instanceof Appointment) {
            return $this->json(['success' => false, 'error' => 'Invalid appointment.'], 400);
        }

        // Same safety as actions: artists can only modify their own appointments
        $user = $this->getUser();
        if ($user && !in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            $artistUser = $instance->getArtist()?->getUser();
            if (!$artistUser || !($user instanceof AppUser) || $artistUser->getId() !== $user->getId()) {
                return $this->json(['success' => false, 'error' => 'Not allowed.'], 403);
            }
        }

        $oldStatus = (string) $instance->getStatus();
        $instance->setStatus($status);
        $this->em->flush();

        if ($oldStatus !== $status) {
            $this->appointmentMailer->sendStatusChanged($instance, $oldStatus);
        }

        return $this->json(['success' => true, 'status' => $instance->getStatus()]);
    }

    private function updateStatus(AdminContext $context, string $status, string $message): Response
    {
        $instance = $context->getEntity()?->getInstance();
        if (!$instance instanceof Appointment) {
            $this->addFlash('danger', 'Invalid appointment row.');
            return $this->redirectToIndex();
        }

        // Basic safety: artists can only modify their own appointments (index is already filtered, but enforce anyway)
        $user = $this->getUser();
        if ($user && !in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            $artistUser = $instance->getArtist()?->getUser();
            if (!$artistUser || !($user instanceof AppUser) || $artistUser->getId() !== $user->getId()) {
                $this->addFlash('danger', 'You are not allowed to update this appointment.');
                return $this->redirectToIndex();
            }
        }

        $oldStatus = (string) $instance->getStatus();
        $instance->setStatus($status);
        $this->em->flush();

        if ($oldStatus !== $status) {
            $this->appointmentMailer->sendStatusChanged($instance, $oldStatus);
        }
        $this->addFlash('success', $message);

        return $this->redirectToIndex();
    }

    private function redirectToIndex(): Response
    {
        $url = $this->adminUrlGenerator
            ->unsetAll()
            ->setController(self::class)
            ->setAction(Crud::PAGE_INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Ամրագրում')
            ->setEntityLabelInPlural('Ամրագրումներ')
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

        yield DateTimeField::new('startDatetime', 'Սկիզբ')->setFormat('yyyy-MM-dd HH:mm');
        yield TextField::new('durationHuman', 'Տևողություն')->onlyOnIndex();
        yield DateTimeField::new('endDatetime', 'Ավարտ')->hideOnIndex();

        // Index: inline clickable status badge (AJAX)
        yield ChoiceField::new('status')
            ->setChoices([
                'Սպասման մեջ' => Appointment::STATUS_PENDING,
                'Հաստատված' => Appointment::STATUS_CONFIRMED,
                'Ավարտված' => Appointment::STATUS_COMPLETED,
                'Չեղարկված' => Appointment::STATUS_CANCELED,
            ])
            ->setTemplatePath('admin/fields/appointment_status_inline.html.twig')
            ->onlyOnIndex();

        // Forms: normal select + badges on detail
        yield ChoiceField::new('status')
            ->setChoices([
                'Սպասման մեջ' => Appointment::STATUS_PENDING,
                'Հաստատված' => Appointment::STATUS_CONFIRMED,
                'Ավարտված' => Appointment::STATUS_COMPLETED,
                'Չեղարկված' => Appointment::STATUS_CANCELED,
            ])
            ->renderAsBadges([
                Appointment::STATUS_PENDING => 'warning',
                Appointment::STATUS_CONFIRMED => 'success',
                Appointment::STATUS_COMPLETED => 'info',
                Appointment::STATUS_CANCELED => 'danger',
            ])
            ->hideOnIndex();

        yield MoneyField::new('servicePriceAtBooking', 'Գին')
            ->setCurrency('AMD')
            ->setStoredAsCents(false)
            ->setHelp('Պահվում է ամրագրելու պահին, որպեսզի հաշվետվությունը չփոխվի, եթե հետո Service-ի գինը փոխվի։')
            ->setPermission('ROLE_ADMIN')
            ->hideOnForm();

        yield AssociationField::new('service', 'Ծառայություն');
        yield AssociationField::new('artist', 'Master')->setPermission('ROLE_ADMIN'); // Artisty chi karox poxeel artistin, menak Adminy

        yield TextField::new('clientName', 'Հաճախորդ');
        yield TextField::new('clientPhone', 'Հեռախոս');
        yield EmailField::new('clientEmail', 'Էլ․ հասցե')->hideOnIndex();
    }
}