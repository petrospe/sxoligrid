<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    use Values;

    case Rent = 'rent';
    case Utilities = 'utilities';
    case Payroll = 'payroll';
    case Supplies = 'supplies';
    case Other = 'other';
}
