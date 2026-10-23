<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BillingMethod;
use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\TuitionQuote;
use App\Support\Directory;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EnrollmentController extends Controller
{
    public function index(TuitionQuote $quotes): Response
    {
        $enrollments = Enrollment::query()
            ->with(['student:id,name', 'course'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (Enrollment $enrollment): array => [
                'id' => $enrollment->id,
                'status' => $enrollment->status->value,
                'billing_method' => $enrollment->billing_method->value,
                'list_price' => $enrollment->course->priceFor($enrollment->billing_method->value) ?? 0.0,
                'student' => [
                    'id' => $enrollment->student->id,
                    'name' => $enrollment->student->name,
                ],
                'course' => [
                    'id' => $enrollment->course->id,
                    'title' => $enrollment->course->title,
                    'billing_methods' => array_values($enrollment->course->billing_methods ?? []),
                    'prices' => $enrollment->course->pricesForMethods(),
                ],
            ])
            ->all();

        return Inertia::render('Admin/Enrollments/Index', [
            'enrollments' => $enrollments,
            'students' => Directory::options(UserRole::Student),
            'courses' => Course::query()->orderBy('title')->get()->map(fn (Course $course): array => [
                'id' => $course->id,
                'title' => $course->title,
                'billing_methods' => array_values($course->billing_methods ?? []),
                'prices' => $course->pricesForMethods(),
            ])->all(),
            'statuses' => EnrollmentStatus::values(),
            'quotes' => $quotes->summaries(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'status' => ['required', Rule::in(EnrollmentStatus::values())],
            'billing_method' => ['required', Rule::in(BillingMethod::values())],
        ]);

        $this->ensureStudent((int) $data['student_id']);
        $this->ensureMethod((int) $data['course_id'], (string) $data['billing_method']);

        try {
            Enrollment::query()->create($data);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'student_id' => __('ui.errors.already_enrolled'),
            ]);
        }

        return back()->with('success', __('ui.flash.enrollment_saved'));
    }

    public function update(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['sometimes', 'required', Rule::in(EnrollmentStatus::values())],
            'billing_method' => ['sometimes', 'required', Rule::in(BillingMethod::values())],
        ]);

        if (isset($data['billing_method'])) {
            $this->ensureMethod($enrollment->course_id, (string) $data['billing_method']);
        }

        if ($data === []) {
            return back();
        }

        $enrollment->update($data);

        return back()->with('success', __('ui.flash.enrollment_updated'));
    }

    public function destroy(Enrollment $enrollment): RedirectResponse
    {
        $enrollment->delete();

        return back()->with('success', __('ui.flash.enrollment_deleted'));
    }

    private function ensureMethod(int $courseId, string $method): void
    {
        $course = Course::query()->find($courseId);
        $offered = $course?->billing_methods ?? [];

        if (! in_array($method, $offered, true)) {
            throw ValidationException::withMessages([
                'billing_method' => __('ui.errors.billing_method_unavailable'),
            ]);
        }
    }

    private function ensureStudent(int $id): void
    {
        $student = User::query()->find($id);

        if (! $student?->isStudent()) {
            throw ValidationException::withMessages([
                'student_id' => __('ui.errors.choose_student'),
            ]);
        }
    }
}
