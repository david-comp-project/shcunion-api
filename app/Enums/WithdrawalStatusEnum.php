<?php

namespace App\Enums;

enum WithdrawalStatusEnum : string
{
    case PROPOSED = "diajukan";
    case PROCESSED = "diproses";
    case DONE = "selesai";

    public static function values() : array {
        return array_column(self::cases(), 'value');
    }
}
