<?php

namespace App\Twig;

use App\Entity\HomePageSettings;
use App\Repository\HomePageSettingsRepository;
use App\Service\OpeningHoursSpecificationFromContactLines;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

final class HomeSettingsTwigExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly HomePageSettingsRepository $homePageSettingsRepository,
        private readonly OpeningHoursSpecificationFromContactLines $openingHoursSpecificationFromContactLines,
    ) {}

    public function getGlobals(): array
    {
        $settings = $this->homePageSettingsRepository->findOneBy([], ['id' => 'ASC']);

        return [
            'home_settings' => $settings,
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('ango_opening_hours_specification', $this->openingHoursForJsonLd(...)),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function openingHoursForJsonLd(?HomePageSettings $settings): array
    {
        return $this->openingHoursSpecificationFromContactLines->build($settings);
    }
}
