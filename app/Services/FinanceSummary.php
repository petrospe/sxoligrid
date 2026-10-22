<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\PayrollStatus;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\TeacherPayroll;
use Carbon\CarbonImmutable;

class FinanceSummary
{
    /**
     * @return array{
     *     month: string,
     *     income: float,
     *     expenses: float,
     *     payroll_paid: float,
     *     payroll_pending: float,
     *     net_profit: float
     * }
     */
    public function forMonth(string $month): array
    {
        $start = CarbonImmutable::parse($month.'-01')->startOfMonth();
        $end = $start->endOfMonth();

        $income = (float) Payment::query()
            ->where('status', PaymentStatus::Paid)
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount');

        $expenses = (float) Expense::query()
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        $payrollPaid = (float) TeacherPayroll::query()
            ->where('payment_period', $month)
            ->where('status', PayrollStatus::Paid)
            ->sum('base_amount');

        $payrollPending = (float) TeacherPayroll::query()
            ->where('payment_period', $month)
            ->where('status', PayrollStatus::Pending)
            ->sum('base_amount');

        return [
            'month' => $month,
            'income' => round($income, 2),
            'expenses' => round($expenses, 2),
            'payroll_paid' => round($payrollPaid, 2),
            'payroll_pending' => round($payrollPending, 2),
            'net_profit' => round($income - $expenses - $payrollPaid, 2),
        ];
    }
}
