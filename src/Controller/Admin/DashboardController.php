<?php

namespace App\Controller\Admin;

use App\Entity\Appointment;
use App\Entity\ArtistProfile;
use App\Entity\Availability;
use App\Entity\Service;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // Aystex hetagayum kavelacnenq statistika
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Beauty Salon Admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Գլխավոր', 'fa fa-home');

        if ($this->isGranted('ROLE_ADMIN')) {
            yield MenuItem::section('Կառավարում');
            yield MenuItem::linkToCrud('Օգտատերեր', 'fas fa-users', User::class);
            yield MenuItem::linkToCrud('Վարպետներ', 'fas fa-paint-brush', ArtistProfile::class);
            yield MenuItem::linkToCrud('Ծառայություններ', 'fas fa-cut', Service::class);
            yield MenuItem::linkToCrud('Աշխատանքային գրաֆիկ', 'fas fa-clock', Availability::class);

            yield MenuItem::section('Գործընթաց');
            yield MenuItem::linkToCrud('Ամրագրումներ', 'fas fa-calendar-check', Appointment::class);
            return;
        }

        // Artist panel (restricted to own data in CRUD controllers)
        if ($this->isGranted('ROLE_ARTIST')) {
            yield MenuItem::section('Իմ բաժին');
            yield MenuItem::linkToCrud('Իմ պրոֆիլը', 'fas fa-user', ArtistProfile::class);
            yield MenuItem::linkToCrud('Իմ գրաֆիկը', 'fas fa-clock', Availability::class);
            yield MenuItem::linkToCrud('Իմ ամրագրումները', 'fas fa-calendar-check', Appointment::class);
        }
    }
}