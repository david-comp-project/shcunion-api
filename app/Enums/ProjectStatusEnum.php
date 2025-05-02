<?php

namespace App\Enums;

enum ProjectStatusEnum: string
{
    case PROPOSED = "proposed";
    case IN_REVIEW = "in_review"; // Perbaikan dari "in review"
    case IN_PROGRESS = "in_progress"; // Konsisten dengan gaya snake_case
    case COMPLETED = "completed";
    case INACTIVE = "inactive"; // Perbaikan dari "in active"

    public static function values(): array {
        return array_column(self::cases(), 'value');
    }

}
