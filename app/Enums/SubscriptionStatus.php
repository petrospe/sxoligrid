<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    use Values;

    case Active = 'active';
    case Expired = 'expired';
    case Depleted = 'depleted';
}
