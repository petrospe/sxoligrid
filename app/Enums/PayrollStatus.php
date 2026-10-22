<?php

namespace App\Enums;

enum PayrollStatus: string
{
    use Values;

    case Pending = 'pending';
    case Paid = 'paid';
}
