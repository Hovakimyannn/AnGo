<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use App\Entity\ArtistProfile;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\String\Slugger\AsciiSlugger;

(new Dotenv())->bootEnv(__DIR__ . '/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

$artists = $em->getRepository(ArtistProfile::class)->findAll();
$slugger = new AsciiSlugger();

foreach ($artists as $artist) {
    if (!$artist->getSlug()) {
        $base = $artist->getUser() ? $artist->getUser()->getFirstName() . ' ' . $artist->getUser()->getLastName() : 'artist-' . $artist->getId();
        $slug = (string) $slugger->slug($base)->lower();
        $artist->setSlug($slug);
        echo "Updating artist {$artist->getId()} with slug: {$slug}\n";
    }
}

$em->flush();
echo "Done.\n";
