<?php

namespace App\Enums;

enum DonationTransactionStatusEnum : string
{
    case PENDING = "pending";
    case CAPTURE = "capture";
    case SETTLEMENT = "settlement";
    case DECLINE = "decline";

    public static function values() : array {
        return array_column(self::cases(), 'value');
    }
}
