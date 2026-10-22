<?php

namespace App\Enums;

enum PaymentType: string
{
    use Values;

    case MonthlySubscription = 'monthly_subscription';
    case Quarterly = 'quarterly';
    case OfferPackage = 'offer_package';
    case DropInTicket = 'drop_in_ticket';
    case PrivateSession = 'private_session';
}
