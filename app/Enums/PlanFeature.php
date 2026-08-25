<?php

namespace App\Enums;

enum PlanFeature: string
{
    case COMPANY_PROFILE = 'company_profile';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return [
            self::COMPANY_PROFILE->value => 'Company Profile',
        ];
    }
}
