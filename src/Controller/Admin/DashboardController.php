<?php

namespace App\Controller\Admin;

use App\Entity\Appointment;
use App\Entity\ArtistProfile;
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
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        // Bazhanum enq menyun bajinneri
        yield MenuItem::section('Management');
        yield MenuItem::linkToCrud('Users', 'fas fa-users', User::class);
        yield MenuItem::linkToCrud('Artists', 'fas fa-paint-brush', ArtistProfile::class);
        yield MenuItem::linkToCrud('Services', 'fas fa-cut', Service::class);
        yield MenuItem::linkToCrud('Work Schedule', 'fas fa-clock', \App\Entity\Availability::class);

        yield MenuItem::section('Business');
        yield MenuItem::linkToCrud('Appointments', 'fas fa-calendar-check', Appointment::class);
    }
}