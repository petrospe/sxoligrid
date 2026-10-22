<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
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
    public function index(): Response
    {
        $enrollments = Enrollment::query()
            ->with(['student:id,name', 'course:id,title'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (Enrollment $enrollment): array => [
                'id' => $enrollment->id,
                'status' => $enrollment->status->value,
                'student' => [
                    'id' => $enrollment->student->id,
                    'name' => $enrollment->student->name,
                ],
                'course' => [
                    'id' => $enrollment->course->id,
                    'title' => $enrollment->course->title,
                ],
            ])
            ->all();

        return Inertia::render('Admin/Enrollments/Index', [
            'enrollments' => $enrollments,
            'students' => Directory::options(UserRole::Student),
            'courses' => Course::query()->orderBy('title')->get(['id', 'title']),
            'statuses' => EnrollmentStatus::values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'status' => ['required', Rule::in(EnrollmentStatus::values())],
        ]);

        $this->ensureStudent((int) $data['student_id']);

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
            'status' => ['required', Rule::in(EnrollmentStatus::values())],
        ]);

        $enrollment->update($data);

        return back()->with('success', __('ui.flash.enrollment_updated'));
    }

    public function destroy(Enrollment $enrollment): RedirectResponse
    {
        $enrollment->delete();

        return back()->with('success', __('ui.flash.enrollment_deleted'));
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
