<?php

namespace App\Enums;

enum UserStatusEnum: string
{
    case ACTIVE = 'active';
    case VERIFIED = 'verified';
    case REPORTED = 'reported'; 
    case SUSPENDED = 'suspended';

    
    public static function values() : array {
        return array_column(self::cases(), 'value');
    }
}
