<?php

// MakerBundle is require-dev; skip registration when vendor is missing (e.g. composer install --no-dev).
// Otherwise cache:clear during post-install fails with ClassNotFoundError if APP_ENV=dev.
return array_merge([
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Twig\Extra\TwigExtraBundle\TwigExtraBundle::class => ['all' => true],
    Symfony\UX\TwigComponent\TwigComponentBundle::class => ['all' => true],
    EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle::class => ['all' => true],
], class_exists(Symfony\Bundle\MakerBundle\MakerBundle::class) ? [
    Symfony\Bundle\MakerBundle\MakerBundle::class => ['dev' => true],
] : []);
