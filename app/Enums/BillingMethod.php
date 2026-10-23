<?php

namespace App\Enums;

enum BillingMethod: string
{
    use Values;

    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Semester = 'semester';
    case OfferPackage = 'offer_package';
    case DropIn = 'drop_in';
}
