<?php

namespace App\Enums;

enum UserRole: string
{
    use Values;

    case Owner = 'owner';
    case Admin = 'admin';
    case Teacher = 'teacher';
    case Student = 'student';
}
