<?php

namespace App\Enums;

enum UserBadge: string
{
    case NEWCOMER = 'Newcomer';
    case CONTRIBUTOR = 'Contributor';
    case ADVOCATE = 'Advocate';
    case CHAMPION = 'Champion';
    case LEGEND = 'Legend';

    public function getColor(): string
    {
        return match ($this) {
            self::NEWCOMER => 'text-gray-500 bg-gray-100', // Abu-abu untuk pemula
            self::CONTRIBUTOR => 'text-blue-500 bg-blue-100', // Biru untuk kontributor
            self::ADVOCATE => 'text-green-500 bg-green-100', // Hijau untuk advokat
            self::CHAMPION => 'text-orange-500 bg-orange-100', // Orange untuk champion
            self::LEGEND => 'text-yellow-500 bg-yellow-100', // Emas untuk legend
        };
    }

    public static function getBadge(int $points): self
    {
        return match (true) {
            $points >= 5000 => self::LEGEND,
            $points >= 2000 => self::CHAMPION,
            $points >= 1000 => self::ADVOCATE,
            $points >= 500 => self::CONTRIBUTOR,
            default => self::NEWCOMER,
        };
    }
}

