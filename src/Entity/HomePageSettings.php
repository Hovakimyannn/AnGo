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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $servicePedicureImage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $servicePedicureTitle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $servicePedicureSubtitle = null;

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

    // --- FOOTER (same as contact + extra) ---
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $footerTagline = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactInstagramUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactFacebookUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactCopyrightText = null;

    // --- FAQ content ---
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $faqTitle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $faqItem1Question = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $faqItem1Answer = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $faqItem2Question = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $faqItem2Answer = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $faqItem3Question = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $faqItem3Answer = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHeroImage(): ?string
    {
        return $this->heroImage;
    }

    public function setHeroImage(?string $heroImage): self
    {
        $this->heroImage = $heroImage;

        return $this;
    }

    public function getServiceHairImage(): ?string
    {
        return $this->serviceHairImage;
    }

    public function setServiceHairImage(?string $serviceHairImage): self
    {
        $this->serviceHairImage = $serviceHairImage;

        return $this;
    }

    public function getServiceMakeupImage(): ?string
    {
        return $this->serviceMakeupImage;
    }

    public function setServiceMakeupImage(?string $serviceMakeupImage): self
    {
        $this->serviceMakeupImage = $serviceMakeupImage;

        return $this;
    }

    public function getServiceNailsImage(): ?string
    {
        return $this->serviceNailsImage;
    }

    public function setServiceNailsImage(?string $serviceNailsImage): self
    {
        $this->serviceNailsImage = $serviceNailsImage;

        return $this;
    }

    public function getHeroTitlePre(): ?string
    {
        return $this->heroTitlePre;
    }

    public function setHeroTitlePre(?string $heroTitlePre): self
    {
        $this->heroTitlePre = $heroTitlePre;

        return $this;
    }

    public function getHeroTitleHighlight(): ?string
    {
        return $this->heroTitleHighlight;
    }

    public function setHeroTitleHighlight(?string $heroTitleHighlight): self
    {
        $this->heroTitleHighlight = $heroTitleHighlight;

        return $this;
    }

    public function getHeroSubtitle(): ?string
    {
        return $this->heroSubtitle;
    }

    public function setHeroSubtitle(?string $heroSubtitle): self
    {
        $this->heroSubtitle = $heroSubtitle;

        return $this;
    }

    public function getHeroPrimaryButtonLabel(): ?string
    {
        return $this->heroPrimaryButtonLabel;
    }

    public function setHeroPrimaryButtonLabel(?string $heroPrimaryButtonLabel): self
    {
        $this->heroPrimaryButtonLabel = $heroPrimaryButtonLabel;

        return $this;
    }

    public function getHeroSecondaryButtonLabel(): ?string
    {
        return $this->heroSecondaryButtonLabel;
    }

    public function setHeroSecondaryButtonLabel(?string $heroSecondaryButtonLabel): self
    {
        $this->heroSecondaryButtonLabel = $heroSecondaryButtonLabel;

        return $this;
    }

    public function getServicesTitle(): ?string
    {
        return $this->servicesTitle;
    }

    public function setServicesTitle(?string $servicesTitle): self
    {
        $this->servicesTitle = $servicesTitle;

        return $this;
    }

    public function getServicesSubtitle(): ?string
    {
        return $this->servicesSubtitle;
    }

    public function setServicesSubtitle(?string $servicesSubtitle): self
    {
        $this->servicesSubtitle = $servicesSubtitle;

        return $this;
    }

    public function getServiceHairTitle(): ?string
    {
        return $this->serviceHairTitle;
    }

    public function setServiceHairTitle(?string $serviceHairTitle): self
    {
        $this->serviceHairTitle = $serviceHairTitle;

        return $this;
    }

    public function getServiceHairSubtitle(): ?string
    {
        return $this->serviceHairSubtitle;
    }

    public function setServiceHairSubtitle(?string $serviceHairSubtitle): self
    {
        $this->serviceHairSubtitle = $serviceHairSubtitle;

        return $this;
    }

    public function getServiceMakeupTitle(): ?string
    {
        return $this->serviceMakeupTitle;
    }

    public function setServiceMakeupTitle(?string $serviceMakeupTitle): self
    {
        $this->serviceMakeupTitle = $serviceMakeupTitle;

        return $this;
    }

    public function getServiceMakeupSubtitle(): ?string
    {
        return $this->serviceMakeupSubtitle;
    }

    public function setServiceMakeupSubtitle(?string $serviceMakeupSubtitle): self
    {
        $this->serviceMakeupSubtitle = $serviceMakeupSubtitle;

        return $this;
    }

    public function getServiceNailsTitle(): ?string
    {
        return $this->serviceNailsTitle;
    }

    public function setServiceNailsTitle(?string $serviceNailsTitle): self
    {
        $this->serviceNailsTitle = $serviceNailsTitle;

        return $this;
    }

    public function getServiceNailsSubtitle(): ?string
    {
        return $this->serviceNailsSubtitle;
    }

    public function setServiceNailsSubtitle(?string $serviceNailsSubtitle): self
    {
        $this->serviceNailsSubtitle = $serviceNailsSubtitle;

        return $this;
    }

    public function getServicePedicureImage(): ?string
    {
        return $this->servicePedicureImage;
    }

    public function setServicePedicureImage(?string $servicePedicureImage): self
    {
        $this->servicePedicureImage = $servicePedicureImage;

        return $this;
    }

    public function getServicePedicureTitle(): ?string
    {
        return $this->servicePedicureTitle;
    }

    public function setServicePedicureTitle(?string $servicePedicureTitle): self
    {
        $this->servicePedicureTitle = $servicePedicureTitle;

        return $this;
    }

    public function getServicePedicureSubtitle(): ?string
    {
        return $this->servicePedicureSubtitle;
    }

    public function setServicePedicureSubtitle(?string $servicePedicureSubtitle): self
    {
        $this->servicePedicureSubtitle = $servicePedicureSubtitle;

        return $this;
    }

    public function getArtistsTitle(): ?string
    {
        return $this->artistsTitle;
    }

    public function setArtistsTitle(?string $artistsTitle): self
    {
        $this->artistsTitle = $artistsTitle;

        return $this;
    }

    public function getArtistsSubtitle(): ?string
    {
        return $this->artistsSubtitle;
    }

    public function setArtistsSubtitle(?string $artistsSubtitle): self
    {
        $this->artistsSubtitle = $artistsSubtitle;

        return $this;
    }

    public function getAboutTitle(): ?string
    {
        return $this->aboutTitle;
    }

    public function setAboutTitle(?string $aboutTitle): self
    {
        $this->aboutTitle = $aboutTitle;

        return $this;
    }

    public function getAboutText1(): ?string
    {
        return $this->aboutText1;
    }

    public function setAboutText1(?string $aboutText1): self
    {
        $this->aboutText1 = $aboutText1;

        return $this;
    }

    public function getAboutText2(): ?string
    {
        return $this->aboutText2;
    }

    public function setAboutText2(?string $aboutText2): self
    {
        $this->aboutText2 = $aboutText2;

        return $this;
    }

    public function getWhyUsTitle(): ?string
    {
        return $this->whyUsTitle;
    }

    public function setWhyUsTitle(?string $whyUsTitle): self
    {
        $this->whyUsTitle = $whyUsTitle;

        return $this;
    }

    public function getWhyUsItems(): ?string
    {
        return $this->whyUsItems;
    }

    public function setWhyUsItems(?string $whyUsItems): self
    {
        $this->whyUsItems = $whyUsItems;

        return $this;
    }

    public function getContactTitle(): ?string
    {
        return $this->contactTitle;
    }

    public function setContactTitle(?string $contactTitle): self
    {
        $this->contactTitle = $contactTitle;

        return $this;
    }

    public function getContactAddress(): ?string
    {
        return $this->contactAddress;
    }

    public function setContactAddress(?string $contactAddress): self
    {
        $this->contactAddress = $contactAddress;

        return $this;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(?string $contactPhone): self
    {
        $this->contactPhone = $contactPhone;

        return $this;
    }

    public function getContactHoursLine1(): ?string
    {
        return $this->contactHoursLine1;
    }

    public function setContactHoursLine1(?string $contactHoursLine1): self
    {
        $this->contactHoursLine1 = $contactHoursLine1;

        return $this;
    }

    public function getContactHoursLine2(): ?string
    {
        return $this->contactHoursLine2;
    }

    public function setContactHoursLine2(?string $contactHoursLine2): self
    {
        $this->contactHoursLine2 = $contactHoursLine2;

        return $this;
    }

    public function getFooterTagline(): ?string
    {
        return $this->footerTagline;
    }

    public function setFooterTagline(?string $footerTagline): self
    {
        $this->footerTagline = $footerTagline;

        return $this;
    }

    public function getContactInstagramUrl(): ?string
    {
        return $this->contactInstagramUrl;
    }

    public function setContactInstagramUrl(?string $contactInstagramUrl): self
    {
        $this->contactInstagramUrl = $contactInstagramUrl;

        return $this;
    }

    public function getContactFacebookUrl(): ?string
    {
        return $this->contactFacebookUrl;
    }

    public function setContactFacebookUrl(?string $contactFacebookUrl): self
    {
        $this->contactFacebookUrl = $contactFacebookUrl;

        return $this;
    }

    public function getContactCopyrightText(): ?string
    {
        return $this->contactCopyrightText;
    }

    public function setContactCopyrightText(?string $contactCopyrightText): self
    {
        $this->contactCopyrightText = $contactCopyrightText;

        return $this;
    }

    public function getFaqTitle(): ?string
    {
        return $this->faqTitle;
    }

    public function setFaqTitle(?string $faqTitle): self
    {
        $this->faqTitle = $faqTitle;
        return $this;
    }

    public function getFaqItem1Question(): ?string
    {
        return $this->faqItem1Question;
    }

    public function setFaqItem1Question(?string $faqItem1Question): self
    {
        $this->faqItem1Question = $faqItem1Question;
        return $this;
    }

    public function getFaqItem1Answer(): ?string
    {
        return $this->faqItem1Answer;
    }

    public function setFaqItem1Answer(?string $faqItem1Answer): self
    {
        $this->faqItem1Answer = $faqItem1Answer;
        return $this;
    }

    public function getFaqItem2Question(): ?string
    {
        return $this->faqItem2Question;
    }

    public function setFaqItem2Question(?string $faqItem2Question): self
    {
        $this->faqItem2Question = $faqItem2Question;
        return $this;
    }

    public function getFaqItem2Answer(): ?string
    {
        return $this->faqItem2Answer;
    }

    public function setFaqItem2Answer(?string $faqItem2Answer): self
    {
        $this->faqItem2Answer = $faqItem2Answer;
        return $this;
    }

    public function getFaqItem3Question(): ?string
    {
        return $this->faqItem3Question;
    }

    public function setFaqItem3Question(?string $faqItem3Question): self
    {
        $this->faqItem3Question = $faqItem3Question;
        return $this;
    }

    public function getFaqItem3Answer(): ?string
    {
        return $this->faqItem3Answer;
    }

    public function setFaqItem3Answer(?string $faqItem3Answer): self
    {
        $this->faqItem3Answer = $faqItem3Answer;
        return $this;
    }
}


