<?php

namespace App\Enums;

enum VolunteerStatusEnum : string
{
    case APPROVED = "approved";
    case NEED_REVIEW = "need_review";
    case DECLINED = "declined";
    case COMPLETED = "completed";

    public static function values() : array {
        return array_column(self::cases(), 'value');
    }
}
