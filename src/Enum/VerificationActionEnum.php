<?php

namespace App\Enum;

enum VerificationActionEnum: string
{
    case REGISTRATION = 'registration';
    case LOGIN = 'login';
}
