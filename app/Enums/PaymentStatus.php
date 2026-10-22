<?php

namespace App\Enums;

enum PaymentStatus: string
{
    use Values;

    case Paid = 'paid';
    case Pending = 'pending';
    case Overdue = 'overdue';
}
