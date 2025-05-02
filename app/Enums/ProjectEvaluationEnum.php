<?php

namespace App\Enums;

enum ProjectEvaluationEnum : string
{
    case IN_REVIEW = "in_review";
    case APPROVED = "approved";
    case REJECTED = "rejected";

    
    public static function values() : array {
        return array_column(self::cases(), 'value');
    }
}
