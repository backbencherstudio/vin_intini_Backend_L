<?php

namespace App\Enums;

enum JobApplicationStatus: string
{
    case PENDING = 'pending';
    case REVIEWING = 'reviewing';
    case SHORTLISTED = 'shortlisted';
    case INTERVIEWED = 'interviewed';
    case OFFERED = 'offered';
    case HIRED = 'hired';
    case REJECTED = 'rejected';

    /**
     * valiues method returns an array of all the enum values.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
