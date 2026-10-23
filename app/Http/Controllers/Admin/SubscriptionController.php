<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\StudentSubscription;
use App\Models\User;
use App\Services\SubscriptionAssigner;
use App\Support\Directory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function index(): Response
    {
        $subscriptions = StudentSubscription::query()
            ->with(['student:id,name', 'package:id,title,sessions_count'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (StudentSubscription $subscription): array => [
                'id' => $subscription->id,
                'student' => $subscription->student->name,
                'package' => $subscription->package->title,
                'remaining_sessions' => $subscription->remaining_sessions,
                'starts_at' => $subscription->starts_at->toDateString(),
                'expires_at' => $subscription->expires_at?->toDateString(),
                'status' => $subscription->status->value,
            ])
            ->all();

        return Inertia::render('Admin/Subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'students' => Directory::options(UserRole::Student),
            'packages' => Package::query()->with('course:id,title')->orderBy('title')->get()->map(fn (Package $package): array => [
                'id' => $package->id,
                'title' => $package->title,
                'price' => (float) $package->price,
                'billing_method' => $package->billing_method?->value,
                'course' => $package->course?->title,
            ])->all(),
            'paymentStatuses' => [PaymentStatus::Paid->value, PaymentStatus::Pending->value],
        ]);
    }

    public function store(Request $request, SubscriptionAssigner $assigner): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'package_id' => ['required', 'integer', 'exists:packages,id'],
            'starts_at' => ['required', 'date'],
            'payment_status' => ['required', Rule::in([PaymentStatus::Paid->value, PaymentStatus::Pending->value])],
        ]);

        $student = User::query()->find($data['student_id']);

        if (! $student?->isStudent()) {
            throw ValidationException::withMessages([
                'student_id' => __('ui.errors.choose_student'),
            ]);
        }

        $assigner->assign(
            $student,
            Package::query()->findOrFail($data['package_id']),
            $data['starts_at'],
            PaymentStatus::from($data['payment_status']),
        );

        return back()->with('success', __('ui.flash.subscription_saved'));
    }
}
