<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Payment;
use App\Models\StudentSubscription;
use App\Models\User;
use App\Support\Directory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(): Response
    {
        $payments = Payment::query()
            ->with(['student:id,name', 'course:id,title', 'subscription:id,title'])
            ->orderByDesc('due_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Payment $payment): array => [
                'id' => $payment->id,
                'student' => $payment->student->name,
                'course' => $payment->course?->title,
                'subscription' => $payment->subscription?->title,
                'amount' => (float) $payment->amount,
                'type' => $payment->type->value,
                'status' => $payment->status->value,
                'due_date' => $payment->due_date?->toDateString(),
                'paid_at' => $payment->paid_at?->toDateTimeString(),
            ])
            ->all();

        $subscriptions = StudentSubscription::query()
            ->with('course:id,title')
            ->orderByDesc('id')
            ->get()
            ->map(fn (StudentSubscription $subscription): array => [
                'id' => $subscription->id,
                'student_id' => $subscription->student_id,
                'course_id' => $subscription->course_id,
                'title' => $subscription->title,
                'course' => $subscription->course?->title,
            ])
            ->all();

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'students' => Directory::options(UserRole::Student),
            'courses' => Course::query()->orderBy('title')->get(['id', 'title'])->all(),
            'subscriptions' => $subscriptions,
            'types' => PaymentType::values(),
            'statuses' => PaymentStatus::values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['paid_at'] = $data['status'] === PaymentStatus::Paid->value ? now() : null;

        Payment::query()->create($data);

        return back()->with('success', __('ui.flash.payment_saved'));
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(PaymentStatus::values())],
        ]);

        $payment->status = PaymentStatus::from($data['status']);
        $payment->paid_at = $payment->status === PaymentStatus::Paid ? ($payment->paid_at ?? now()) : null;
        $payment->save();

        return back()->with('success', __('ui.flash.payment_updated'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        foreach (['course_id', 'subscription_id'] as $field) {
            if ($request->input($field) === '') {
                $request->merge([$field => null]);
            }
        }

        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'subscription_id' => ['nullable', 'integer', 'exists:student_subscriptions,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'type' => ['required', Rule::in(PaymentType::values())],
            'status' => ['required', Rule::in(PaymentStatus::values())],
            'due_date' => ['nullable', 'date'],
        ]);

        $student = User::query()->find($data['student_id']);

        if (! $student?->isStudent()) {
            throw ValidationException::withMessages([
                'student_id' => __('ui.errors.choose_student'),
            ]);
        }

        $data['course_id'] = $data['course_id'] ?? null;
        $data['subscription_id'] = $data['subscription_id'] ?? null;

        if ($data['type'] === PaymentType::DropIn->value) {
            $data['subscription_id'] = null;

            return $data;
        }

        if ($data['subscription_id'] === null) {
            throw ValidationException::withMessages([
                'subscription_id' => __('ui.errors.choose_subscription'),
            ]);
        }

        $subscription = StudentSubscription::query()->find($data['subscription_id']);

        if (! $subscription instanceof StudentSubscription || $subscription->student_id !== $student->id) {
            throw ValidationException::withMessages([
                'subscription_id' => __('ui.errors.subscription_mismatch'),
            ]);
        }

        if ($data['course_id'] === null && $subscription->course_id !== null) {
            $data['course_id'] = $subscription->course_id;
        }

        return $data;
    }
}
