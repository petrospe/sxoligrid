<?php

namespace App\Support;

final class Week
{
    public static function name(int $day): string
    {
        return (string) __('ui.days.'.$day);
    }

    public static function clock(mixed $value): string
    {
        return substr((string) $value, 0, 5);
    }
}
