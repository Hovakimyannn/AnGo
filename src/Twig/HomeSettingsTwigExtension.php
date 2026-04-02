<?php

namespace App\Twig;

use App\Entity\HomePageSettings;
use App\Repository\HomePageSettingsRepository;
use App\Service\OpeningHoursSpecificationFromContactLines;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
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

    public function getFilters(): array
    {
        return [
            new TwigFilter('tel_uri', $this->telUri(...)),
        ];
    }

    /**
     * Human-readable phone for text; tel: href with digits (and leading + when present).
     */
    public function telUri(?string $phone): string
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return '#';
        }

        $digitsOnly = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digitsOnly === '') {
            return '#';
        }

        $prefix = str_starts_with($phone, '+') ? '+' : '';

        return 'tel:'.$prefix.$digitsOnly;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function openingHoursForJsonLd(?HomePageSettings $settings): array
    {
        return $this->openingHoursSpecificationFromContactLines->build($settings);
    }
}
