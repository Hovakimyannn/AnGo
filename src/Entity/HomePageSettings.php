<?php

namespace App\Entity;

use App\Repository\HomePageSettingsRepository;
use Doctrine\DBAL\Types\Types;
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

    // --- HERO content ---
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $heroTitlePre = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $heroTitleHighlight = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $heroSubtitle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $heroPrimaryButtonLabel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $heroSecondaryButtonLabel = null;

    // --- SERVICES content ---
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $servicesTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $servicesSubtitle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $serviceHairTitle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $serviceHairSubtitle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $serviceMakeupTitle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $serviceMakeupSubtitle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $serviceNailsTitle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $serviceNailsSubtitle = null;

    // --- ARTISTS content ---
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $artistsTitle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $artistsSubtitle = null;

    // --- ABOUT content ---
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $aboutTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $aboutText1 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $aboutText2 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $whyUsTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $whyUsItems = null;

    // --- CONTACT content ---
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactTitle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactAddress = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactPhone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactHoursLine1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactHoursLine2 = null;

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

    public function getHeroTitlePre(): ?string
    {
        return $this->heroTitlePre;
    }

    public function setHeroTitlePre(?string $heroTitlePre): static
    {
        $this->heroTitlePre = $heroTitlePre;

        return $this;
    }

    public function getHeroTitleHighlight(): ?string
    {
        return $this->heroTitleHighlight;
    }

    public function setHeroTitleHighlight(?string $heroTitleHighlight): static
    {
        $this->heroTitleHighlight = $heroTitleHighlight;

        return $this;
    }

    public function getHeroSubtitle(): ?string
    {
        return $this->heroSubtitle;
    }

    public function setHeroSubtitle(?string $heroSubtitle): static
    {
        $this->heroSubtitle = $heroSubtitle;

        return $this;
    }

    public function getHeroPrimaryButtonLabel(): ?string
    {
        return $this->heroPrimaryButtonLabel;
    }

    public function setHeroPrimaryButtonLabel(?string $heroPrimaryButtonLabel): static
    {
        $this->heroPrimaryButtonLabel = $heroPrimaryButtonLabel;

        return $this;
    }

    public function getHeroSecondaryButtonLabel(): ?string
    {
        return $this->heroSecondaryButtonLabel;
    }

    public function setHeroSecondaryButtonLabel(?string $heroSecondaryButtonLabel): static
    {
        $this->heroSecondaryButtonLabel = $heroSecondaryButtonLabel;

        return $this;
    }

    public function getServicesTitle(): ?string
    {
        return $this->servicesTitle;
    }

    public function setServicesTitle(?string $servicesTitle): static
    {
        $this->servicesTitle = $servicesTitle;

        return $this;
    }

    public function getServicesSubtitle(): ?string
    {
        return $this->servicesSubtitle;
    }

    public function setServicesSubtitle(?string $servicesSubtitle): static
    {
        $this->servicesSubtitle = $servicesSubtitle;

        return $this;
    }

    public function getServiceHairTitle(): ?string
    {
        return $this->serviceHairTitle;
    }

    public function setServiceHairTitle(?string $serviceHairTitle): static
    {
        $this->serviceHairTitle = $serviceHairTitle;

        return $this;
    }

    public function getServiceHairSubtitle(): ?string
    {
        return $this->serviceHairSubtitle;
    }

    public function setServiceHairSubtitle(?string $serviceHairSubtitle): static
    {
        $this->serviceHairSubtitle = $serviceHairSubtitle;

        return $this;
    }

    public function getServiceMakeupTitle(): ?string
    {
        return $this->serviceMakeupTitle;
    }

    public function setServiceMakeupTitle(?string $serviceMakeupTitle): static
    {
        $this->serviceMakeupTitle = $serviceMakeupTitle;

        return $this;
    }

    public function getServiceMakeupSubtitle(): ?string
    {
        return $this->serviceMakeupSubtitle;
    }

    public function setServiceMakeupSubtitle(?string $serviceMakeupSubtitle): static
    {
        $this->serviceMakeupSubtitle = $serviceMakeupSubtitle;

        return $this;
    }

    public function getServiceNailsTitle(): ?string
    {
        return $this->serviceNailsTitle;
    }

    public function setServiceNailsTitle(?string $serviceNailsTitle): static
    {
        $this->serviceNailsTitle = $serviceNailsTitle;

        return $this;
    }

    public function getServiceNailsSubtitle(): ?string
    {
        return $this->serviceNailsSubtitle;
    }

    public function setServiceNailsSubtitle(?string $serviceNailsSubtitle): static
    {
        $this->serviceNailsSubtitle = $serviceNailsSubtitle;

        return $this;
    }

    public function getArtistsTitle(): ?string
    {
        return $this->artistsTitle;
    }

    public function setArtistsTitle(?string $artistsTitle): static
    {
        $this->artistsTitle = $artistsTitle;

        return $this;
    }

    public function getArtistsSubtitle(): ?string
    {
        return $this->artistsSubtitle;
    }

    public function setArtistsSubtitle(?string $artistsSubtitle): static
    {
        $this->artistsSubtitle = $artistsSubtitle;

        return $this;
    }

    public function getAboutTitle(): ?string
    {
        return $this->aboutTitle;
    }

    public function setAboutTitle(?string $aboutTitle): static
    {
        $this->aboutTitle = $aboutTitle;

        return $this;
    }

    public function getAboutText1(): ?string
    {
        return $this->aboutText1;
    }

    public function setAboutText1(?string $aboutText1): static
    {
        $this->aboutText1 = $aboutText1;

        return $this;
    }

    public function getAboutText2(): ?string
    {
        return $this->aboutText2;
    }

    public function setAboutText2(?string $aboutText2): static
    {
        $this->aboutText2 = $aboutText2;

        return $this;
    }

    public function getWhyUsTitle(): ?string
    {
        return $this->whyUsTitle;
    }

    public function setWhyUsTitle(?string $whyUsTitle): static
    {
        $this->whyUsTitle = $whyUsTitle;

        return $this;
    }

    public function getWhyUsItems(): ?string
    {
        return $this->whyUsItems;
    }

    public function setWhyUsItems(?string $whyUsItems): static
    {
        $this->whyUsItems = $whyUsItems;

        return $this;
    }

    public function getContactTitle(): ?string
    {
        return $this->contactTitle;
    }

    public function setContactTitle(?string $contactTitle): static
    {
        $this->contactTitle = $contactTitle;

        return $this;
    }

    public function getContactAddress(): ?string
    {
        return $this->contactAddress;
    }

    public function setContactAddress(?string $contactAddress): static
    {
        $this->contactAddress = $contactAddress;

        return $this;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(?string $contactPhone): static
    {
        $this->contactPhone = $contactPhone;

        return $this;
    }

    public function getContactHoursLine1(): ?string
    {
        return $this->contactHoursLine1;
    }

    public function setContactHoursLine1(?string $contactHoursLine1): static
    {
        $this->contactHoursLine1 = $contactHoursLine1;

        return $this;
    }

    public function getContactHoursLine2(): ?string
    {
        return $this->contactHoursLine2;
    }

    public function setContactHoursLine2(?string $contactHoursLine2): static
    {
        $this->contactHoursLine2 = $contactHoursLine2;

        return $this;
    }
}


