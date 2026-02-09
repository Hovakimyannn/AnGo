<?php

namespace App\Twig;

use App\Repository\HomePageSettingsRepository;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class HomeSettingsTwigExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly HomePageSettingsRepository $homePageSettingsRepository,
    ) {}

    public function getGlobals(): array
    {
        $settings = $this->homePageSettingsRepository->findOneBy([], ['id' => 'ASC']);

        return [
            'home_settings' => $settings,
        ];
    }
}
