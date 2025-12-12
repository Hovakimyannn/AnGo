<?php

namespace App\Controller\Admin;

use App\Entity\Availability;
use App\Entity\User as AppUser;
use App\Repository\ArtistProfileRepository;
use App\Repository\AvailabilityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

class AvailabilityCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityManagerInterface $em,
        private AvailabilityRepository $availabilityRepository,
        private ArtistProfileRepository $artistProfileRepository,
        private AdminUrlGenerator $adminUrlGenerator
    ) {}

    public static function getEntityFqcn(): string
    {
        return Availability::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $copyToWeekdays = Action::new('copyToWeekdays', 'Պատճենել՝ Երկ–Շաբ')
            ->linkToCrudAction('copyToWeekdays')
            ->setIcon('fa fa-clone');

        $copyToWeek = Action::new('copyToWeek', 'Պատճենել՝ Երկ–Կիր')
            ->linkToCrudAction('copyToWeek')
            ->setIcon('fa fa-copy');

        return $actions
            ->add(Crud::PAGE_INDEX, $copyToWeekdays)
            ->add(Crud::PAGE_INDEX, $copyToWeek)
            ->add(Crud::PAGE_EDIT, $copyToWeekdays)
            ->add(Crud::PAGE_EDIT, $copyToWeek);
    }

    public function copyToWeekdays(AdminContext $context): Response
    {
        return $this->copyAvailabilityToDays($context, [1, 2, 3, 4, 5, 6], 'Պատճենվեց՝ Երկուշաբթի–Շաբաթ։');
    }

    public function copyToWeek(AdminContext $context): Response
    {
        return $this->copyAvailabilityToDays($context, [1, 2, 3, 4, 5, 6, 7], 'Պատճենվեց՝ Երկուշաբթի–Կիրակի։');
    }

    private function copyAvailabilityToDays(AdminContext $context, array $days, string $flashMessage): Response
    {
        $instance = $context->getEntity()?->getInstance();
        if (!$instance instanceof Availability) {
            $this->addFlash('danger', 'Invalid availability row.');
            return $this->redirectToAvailabilityIndex();
        }

        $artist = $instance->getArtist();
        if (!$artist) {
            $this->addFlash('danger', 'Availability has no artist.');
            return $this->redirectToAvailabilityIndex();
        }

        // Safety: artists can only copy their own schedule
        $user = $this->getUser();
        if ($user && !$this->isGranted('ROLE_ADMIN') && $this->isGranted('ROLE_ARTIST') && $user instanceof AppUser) {
            $artistUser = $artist->getUser();
            if (!$artistUser || $artistUser->getId() !== $user->getId()) {
                $this->addFlash('danger', 'You are not allowed to update this schedule.');
                return $this->redirectToAvailabilityIndex();
            }
        }

        $sourceDay = (int) $instance->getDayOfWeek();
        $sourceIsDayOff = (bool) ($instance->isIsDayOff() ?? false);
        $sourceStart = $instance->getStartTime();
        $sourceEnd = $instance->getEndTime();

        $created = 0;
        foreach ($days as $day) {
            $day = (int) $day;
            if ($day === $sourceDay) {
                continue;
            }

            // Don't create duplicates; only fill missing days.
            $existing = $this->availabilityRepository->findOneBy([
                'artist' => $artist,
                'dayOfWeek' => $day,
            ]);
            if ($existing) {
                continue;
            }

            $copy = new Availability();
            $copy->setArtist($artist);
            $copy->setDayOfWeek($day);
            $copy->setIsDayOff($sourceIsDayOff);

            // Copy times (TIME columns) if present; keep null otherwise
            $copy->setStartTime($sourceStart ? new \DateTime($sourceStart->format('H:i:s')) : null);
            $copy->setEndTime($sourceEnd ? new \DateTime($sourceEnd->format('H:i:s')) : null);

            $this->em->persist($copy);
            $created++;
        }

        $this->em->flush();
        $this->addFlash('success', $flashMessage . " Ստեղծվեց {$created} նոր օր։ Կարող եք ցանկացած օրը առանձին փոփոխել։");

        return $this->redirectToAvailabilityIndex();
    }

    private function redirectToAvailabilityIndex(): Response
    {
        $url = $this->adminUrlGenerator
            ->unsetAll()
            ->setController(self::class)
            ->setAction(Crud::PAGE_INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $user = $this->getUser();

        // Artists should only see their own schedule
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
        // When artist creates availability, auto-attach to their profile
        if ($entityInstance instanceof Availability && !$this->isGranted('ROLE_ADMIN') && $this->isGranted('ROLE_ARTIST')) {
            $user = $this->getUser();
            if ($user instanceof AppUser) {
                $profile = $this->artistProfileRepository->findOneBy(['user' => $user]);
                if ($profile) {
                    $entityInstance->setArtist($profile);
                }
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('artist')->setPermission('ROLE_ADMIN');

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