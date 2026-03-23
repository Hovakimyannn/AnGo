<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\HomePageSettings;

/**
 * Builds Schema.org OpeningHoursSpecification arrays from HomePageSettings contact hour lines
 * (same strings as the footer / contact section).
 */
final class OpeningHoursSpecificationFromContactLines
{
    private const DEFAULT_LINE_WEEKDAY = 'Երկ - Շաբ: 10:00 - 20:00';

    private const DEFAULT_LINE_SUNDAY = 'Կիր: 11:00 - 18:00';

    /**
     * @return list<array<string, mixed>>
     */
    public function build(?HomePageSettings $settings): array
    {
        $raw1 = $settings?->getContactHoursLine1();
        $raw2 = $settings?->getContactHoursLine2();

        $line1 = (null !== $raw1 && '' !== trim($raw1)) ? trim($raw1) : self::DEFAULT_LINE_WEEKDAY;
        $line2 = (null !== $raw2 && '' !== trim($raw2)) ? trim($raw2) : self::DEFAULT_LINE_SUNDAY;

        $weekday = null;
        $sunday = null;

        foreach ([$line1, $line2] as $line) {
            $pair = $this->extractOpenClose($line);
            if (null === $pair) {
                continue;
            }
            if ($this->isSundayLine($line)) {
                $sunday = $pair;
            } else {
                $weekday = $pair;
            }
        }

        if (null === $weekday) {
            $weekday = $this->extractOpenClose(self::DEFAULT_LINE_WEEKDAY) ?? ['10:00', '20:00'];
        }
        if (null === $sunday) {
            $sunday = $this->extractOpenClose(self::DEFAULT_LINE_SUNDAY) ?? ['11:00', '18:00'];
        }

        return [
            [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                'opens' => $weekday[0],
                'closes' => $weekday[1],
            ],
            [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Sunday'],
                'opens' => $sunday[0],
                'closes' => $sunday[1],
            ],
        ];
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private function extractOpenClose(string $line): ?array
    {
        if (!preg_match_all('/(?<![0-9])(\d{1,2}):(\d{2})(?![0-9])/', $line, $m, PREG_SET_ORDER)) {
            return null;
        }
        if (\count($m) < 2) {
            return null;
        }

        $open = $this->normalizeTime($m[0][1], $m[0][2]);
        $close = $this->normalizeTime($m[1][1], $m[1][2]);

        return [$open, $close];
    }

    private function normalizeTime(string $hours, string $minutes): string
    {
        return sprintf('%02d:%s', (int) $hours, $minutes);
    }

    private function isSundayLine(string $line): bool
    {
        if (preg_match('/կիրակի/u', $line)) {
            return true;
        }
        if (preg_match('/^\s*[Կկ]իր\s*[:\-–—]/u', $line)) {
            return true;
        }
        if (preg_match('/\b(sun|sunday)\b/i', $line)) {
            return true;
        }

        return false;
    }
}
