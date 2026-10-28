<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnrollmentStatus;
use App\Enums\FlexSuggestionStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\FlexSuggestion;
use App\Models\Payment;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $overdue = Payment::query()
            ->where(function ($query): void {
                $query->where('status', PaymentStatus::Overdue)
                    ->orWhere(function ($query): void {
                        $query->where('status', PaymentStatus::Pending)
                            ->whereDate('due_date', '<', now()->toDateString());
                    });
            })
            ->count();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'students' => User::query()->withRole(UserRole::Student)->count(),
                'teachers' => User::query()->withRole(UserRole::Teacher)->count(),
                'courses' => Course::query()->count(),
                'active_enrollments' => Enrollment::query()->where('status', EnrollmentStatus::Active)->count(),
                'overdue_payments' => $overdue,
                'unread_suggestions' => FlexSuggestion::query()->where('status', FlexSuggestionStatus::Unread)->count(),
            ],
        ]);
    }
}
