<?php

declare(strict_types=1);

namespace App\Support;

class GoogleCalendarEventColor
{
    /** @var array<int, string> */
    private const PALETTE = [
        1 => '#7986CB',
        2 => '#33B679',
        3 => '#8E24AA',
        4 => '#E67C73',
        5 => '#F6BF26',
        6 => '#F4511E',
        7 => '#039BE5',
        8 => '#616161',
        9 => '#3F51B5',
        10 => '#0B8043',
        11 => '#D50000',
    ];

    /**
     * @return array{id: int, hex: string}
     */
    public static function for(string $seed): array
    {
        $hash = crc32($seed);
        $keys = array_keys(self::PALETTE);
        $id = $keys[$hash % count($keys)];

        return ['id' => $id, 'hex' => self::PALETTE[$id]];
    }

    public static function hex(int $colorId): ?string
    {
        return self::PALETTE[$colorId] ?? null;
    }
}
