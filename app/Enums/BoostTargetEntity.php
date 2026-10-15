<?php

namespace App\Enums;

enum BoostTargetEntity: string
{
    case ALL = 'all';
    case PRODUCT = 'product';
    case JOB = 'job';
    case PUBLICATION = 'publication';
    case POST = 'post';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
