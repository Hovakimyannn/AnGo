<?php

namespace App\Controller;

use App\Repository\DidYouKnowPostRepository;
use App\Repository\HomePageSettingsRepository;
use App\Repository\ArtistProfileRepository;
use App\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        ArtistProfileRepository $artistRepository,
        ServiceRepository $serviceRepository,
        HomePageSettingsRepository $homePageSettingsRepository,
        DidYouKnowPostRepository $didYouKnowPostRepository,
    ): Response {
        // Vercnum enq 3 patahakan kam verjin artistnerin Home page-i hamar
        // Irական project-um kareli e nshel "Featured" dasht ev yst dra filter anel
        $artists = $artistRepository->findAll();

        // Vercnum enq carayutyunnery (Category-neri hamar)
        $services = $serviceRepository->findAll();

        $homeSettings = $homePageSettingsRepository->findOneBy([]);

        $didYouKnowPosts = $didYouKnowPostRepository->findLatestPublished(3);

        return $this->render('home/index.html.twig', [
            'artists' => $artists,
            'services' => $services,
            'homeSettings' => $homeSettings,
            'didYouKnowPosts' => $didYouKnowPosts,
        ]);
    }
}