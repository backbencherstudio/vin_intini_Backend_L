<?php

namespace App\Enums;

enum OtpType: string
{
    case REGISTRATION = 'registration';
    case PASSWORD_RESET = 'password_reset';
    case SUBSCRIPTION = 'subscription';
    case RECOVERY = 'recovery';
    case RECOVERY_EMAIL = 'recovery_email';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
