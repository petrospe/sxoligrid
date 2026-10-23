<?php

namespace App\Enums;

enum PaymentType: string
{
    use Values;

    case Installment = 'installment';
    case DropIn = 'drop_in';
}
