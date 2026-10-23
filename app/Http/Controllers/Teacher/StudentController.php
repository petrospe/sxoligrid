<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $sectionIds = ClassSchedule::query()
            ->forTeacher($request->user()->id)
            ->pluck('section_id')
            ->filter()
            ->unique()
            ->all();

        $students = User::query()
            ->where('role', UserRole::Student)
            ->whereHas('enrollments', fn ($query) => $query->whereIn('section_id', $sectionIds))
            ->with('payments')
            ->orderBy('name')
            ->get()
            ->map(function (User $student): array {
                $sum = fn (PaymentStatus $status): float => round((float) $student->payments
                    ->where('status', $status)
                    ->sum(fn ($payment): float => (float) $payment->amount), 2);

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'phone' => $student->phone,
                    'paid_total' => $sum(PaymentStatus::Paid),
                    'pending_total' => $sum(PaymentStatus::Pending),
                    'overdue_total' => $sum(PaymentStatus::Overdue),
                ];
            })
            ->all();

        return Inertia::render('Teacher/Students', [
            'students' => $students,
        ]);
    }
}
