<?php

namespace App\Enums;

enum PayrollType: string
{
    use Values;

    case MonthlySalary = 'monthly_salary';
    case HourlyRate = 'hourly_rate';
    case PerStudentCut = 'per_student_cut';
}
