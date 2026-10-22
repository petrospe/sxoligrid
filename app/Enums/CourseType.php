<?php

namespace App\Enums;

enum CourseType: string
{
    use Values;

    case Group = 'group';
    case Private = 'private';
}
