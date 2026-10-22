<?php

namespace App\Enums;

enum PrivateLessonStatus: string
{
    use Values;

    case Requested = 'requested';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
