<?php

namespace App\Entity;

use App\Repository\HomePageSettingsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HomePageSettingsRepository::class)]
class HomePageSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $heroImage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $serviceHairImage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $serviceMakeupImage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $serviceNailsImage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHeroImage(): ?string
    {
        return $this->heroImage;
    }

    public function setHeroImage(?string $heroImage): static
    {
        $this->heroImage = $heroImage;

        return $this;
    }

    public function getServiceHairImage(): ?string
    {
        return $this->serviceHairImage;
    }

    public function setServiceHairImage(?string $serviceHairImage): static
    {
        $this->serviceHairImage = $serviceHairImage;

        return $this;
    }

    public function getServiceMakeupImage(): ?string
    {
        return $this->serviceMakeupImage;
    }

    public function setServiceMakeupImage(?string $serviceMakeupImage): static
    {
        $this->serviceMakeupImage = $serviceMakeupImage;

        return $this;
    }

    public function getServiceNailsImage(): ?string
    {
        return $this->serviceNailsImage;
    }

    public function setServiceNailsImage(?string $serviceNailsImage): static
    {
        $this->serviceNailsImage = $serviceNailsImage;

        return $this;
    }
}


