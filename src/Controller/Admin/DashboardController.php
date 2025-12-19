<?php

namespace App\Controller\Admin;

use App\Entity\Appointment;
use App\Entity\ArtistPost;
use App\Entity\ArtistProfile;
use App\Entity\Availability;
use App\Entity\HomePageSettings;
use App\Entity\PostComment;
use App\Entity\PostRating;
use App\Entity\Service;
use App\Entity\User;
use App\Repository\HomePageSettingsRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
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

    #[Route('/admin/home-images', name: 'admin_home_images')]
    public function homeImages(AdminUrlGenerator $adminUrlGenerator, HomePageSettingsRepository $homePageSettingsRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $settings = $homePageSettingsRepository->findOneBy([]);

        $adminUrlGenerator->unsetAll()
            ->setController(HomePageSettingsCrudController::class);

        if ($settings instanceof HomePageSettings && $settings->getId()) {
            $url = $adminUrlGenerator
                ->setAction(Action::EDIT)
                ->setEntityId($settings->getId())
                ->generateUrl();

            return $this->redirect($url);
        }

        $url = $adminUrlGenerator
            ->setAction(Action::NEW)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Beauty Salon Admin')
            // Avoid "Path must not be empty" in EasyAdmin layout when dashboard favicon path is empty.
            ->setFaviconPath('favicon.svg');
    }

    public function configureAssets(): Assets
    {
        return parent::configureAssets()
            ->addCssFile('admin.css')
            ->addJsFile('admin.js');
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

            yield MenuItem::section('Բլոգ');
            yield MenuItem::linkToCrud('Posts', 'fas fa-pen', ArtistPost::class);
            yield MenuItem::linkToCrud('Comments', 'fas fa-comments', PostComment::class);
            yield MenuItem::linkToCrud('Ratings', 'fas fa-star', PostRating::class);

            yield MenuItem::section('Կայք');
            yield MenuItem::linkToRoute('Home նկարներ', 'fas fa-image', 'admin_home_images');
            return;
        }

        // Artist panel (restricted to own data in CRUD controllers)
        if ($this->isGranted('ROLE_ARTIST')) {
            yield MenuItem::section('Իմ բաժին');
            yield MenuItem::linkToCrud('Իմ պրոֆիլը', 'fas fa-user', ArtistProfile::class);
            yield MenuItem::linkToCrud('Իմ գրաֆիկը', 'fas fa-clock', Availability::class);
            yield MenuItem::linkToCrud('Իմ ամրագրումները', 'fas fa-calendar-check', Appointment::class);

            yield MenuItem::section('Իմ բլոգ');
            yield MenuItem::linkToCrud('Իմ Posts', 'fas fa-pen', ArtistPost::class);
            yield MenuItem::linkToCrud('Comments', 'fas fa-comments', PostComment::class);
            yield MenuItem::linkToCrud('Ratings', 'fas fa-star', PostRating::class);
        }
    }
}