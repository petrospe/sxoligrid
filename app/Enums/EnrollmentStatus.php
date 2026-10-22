<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    use Values;

    case Active = 'active';
    case Paused = 'paused';
    case Completed = 'completed';
}
