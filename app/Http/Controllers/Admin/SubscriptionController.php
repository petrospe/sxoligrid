<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\StudentSubscription;
use App\Models\User;
use App\Support\Directory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function index(): Response
    {
        $subscriptions = StudentSubscription::query()
            ->with(['student:id,name', 'course:id,title'])
            ->withSum(['payments as paid_amount' => function ($query): void {
                $query->where('status', PaymentStatus::Paid);
            }], 'amount')
            ->orderByDesc('id')
            ->get()
            ->map(fn (StudentSubscription $subscription): array => $this->present($subscription))
            ->all();

        return Inertia::render('Admin/Subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'students' => Directory::options(UserRole::Student),
            'courses' => Course::query()->orderBy('title')->get(['id', 'title'])->all(),
            'titles' => $this->titles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->input('course_id') === '') {
            $request->merge(['course_id' => null]);
        }

        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
        ], [
            'ends_at.after_or_equal' => __('ui.errors.period_order'),
        ]);

        $student = User::query()->find($data['student_id']);

        if (! $student?->isStudent()) {
            throw ValidationException::withMessages([
                'student_id' => __('ui.errors.choose_student'),
            ]);
        }

        $data['status'] = SubscriptionStatus::Active;

        StudentSubscription::query()->create($data);

        return back()->with('success', __('ui.flash.subscription_saved'));
    }

    /**
     * @return list<string>
     */
    private function titles(): array
    {
        $titles = [];

        foreach (StudentSubscription::query()->orderBy('title')->pluck('title') as $title) {
            $label = trim((string) $title);

            if ($label === '') {
                continue;
            }

            $titles[mb_strtolower($label)] ??= $label;
        }

        $labels = array_values($titles);
        usort($labels, static fn (string $left, string $right): int => strnatcasecmp($left, $right));

        return $labels;
    }

    /**
     * @return array<string, mixed>
     */
    private function present(StudentSubscription $subscription): array
    {
        $paid = $subscription->paidAmount();

        return [
            'id' => $subscription->id,
            'student' => $subscription->student->name,
            'student_id' => $subscription->student_id,
            'course' => $subscription->course?->title,
            'course_id' => $subscription->course_id,
            'title' => $subscription->title,
            'total_amount' => round((float) $subscription->total_amount, 2),
            'paid_amount' => $paid,
            'remaining_amount' => $subscription->remainingAmount(),
            'starts_at' => $subscription->starts_at->toDateString(),
            'ends_at' => $subscription->ends_at?->toDateString(),
            'status' => $this->visibleStatus($subscription),
        ];
    }

    private function visibleStatus(StudentSubscription $subscription): string
    {
        $status = $subscription->status->value;

        if (
            $status === SubscriptionStatus::Active->value
            && $subscription->ends_at !== null
            && $subscription->ends_at->toDateString() < now()->toDateString()
        ) {
            return SubscriptionStatus::Expired->value;
        }

        return $status;
    }
}
