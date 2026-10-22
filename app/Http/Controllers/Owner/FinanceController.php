<?php

namespace App\Http\Controllers\Owner;

use App\Enums\ExpenseCategory;
use App\Enums\PayrollStatus;
use App\Enums\PayrollType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\TeacherPayroll;
use App\Models\User;
use App\Services\FinanceSummary;
use App\Support\Directory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FinanceController extends Controller
{
    public function index(Request $request, FinanceSummary $summary): Response
    {
        $month = (string) $request->query('month', now()->format('Y-m'));

        if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            $month = now()->format('Y-m');
        }

        $expenses = Expense::query()
            ->whereBetween('expense_date', [$month.'-01', $month.'-31'])
            ->orderBy('expense_date')
            ->get()
            ->map(fn (Expense $expense): array => [
                'id' => $expense->id,
                'title' => $expense->title,
                'amount' => (float) $expense->amount,
                'category' => $expense->category->value,
                'expense_date' => $expense->expense_date->toDateString(),
            ])
            ->all();

        $payrolls = TeacherPayroll::query()
            ->with('teacher:id,name')
            ->where('payment_period', $month)
            ->orderBy('id')
            ->get()
            ->map(fn (TeacherPayroll $payroll): array => [
                'id' => $payroll->id,
                'teacher' => $payroll->teacher->name,
                'type' => $payroll->type->value,
                'base_amount' => (float) $payroll->base_amount,
                'payment_period' => $payroll->payment_period,
                'status' => $payroll->status->value,
            ])
            ->all();

        return Inertia::render('Owner/Finances', [
            'summary' => $summary->forMonth($month),
            'expenses' => $expenses,
            'payrolls' => $payrolls,
            'teachers' => Directory::options(UserRole::Teacher),
            'categories' => ExpenseCategory::values(),
            'payrollTypes' => PayrollType::values(),
            'payrollStatuses' => PayrollStatus::values(),
        ]);
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        Expense::query()->create($request->validate([
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'category' => ['required', Rule::in(ExpenseCategory::values())],
            'expense_date' => ['required', 'date'],
        ]));

        return back()->with('success', __('ui.flash.expense_saved'));
    }

    public function destroyExpense(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return back()->with('success', __('ui.flash.expense_deleted'));
    }

    public function storePayroll(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'teacher_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', Rule::in(PayrollType::values())],
            'base_amount' => ['required', 'numeric', 'min:0'],
            'payment_period' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'status' => ['required', Rule::in(PayrollStatus::values())],
        ]);

        $teacher = User::query()->find($data['teacher_id']);

        if (! $teacher?->isTeacher()) {
            throw ValidationException::withMessages([
                'teacher_id' => __('ui.errors.choose_teacher'),
            ]);
        }

        TeacherPayroll::query()->create($data);

        return back()->with('success', __('ui.flash.payroll_saved'));
    }

    public function updatePayroll(Request $request, TeacherPayroll $payroll): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(PayrollStatus::values())],
        ]);

        $payroll->update($data);

        return back()->with('success', __('ui.flash.payroll_updated'));
    }

    public function destroyPayroll(TeacherPayroll $payroll): RedirectResponse
    {
        $payroll->delete();

        return back()->with('success', __('ui.flash.payroll_deleted'));
    }
}
