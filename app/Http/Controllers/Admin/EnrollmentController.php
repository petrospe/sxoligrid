<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\CourseSection;
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
            ->with(['student:id,name', 'course:id,title,type', 'section:id,name'])
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
                    'type' => $enrollment->course->type->value,
                ],
                'section' => [
                    'id' => $enrollment->section->id,
                    'name' => $enrollment->section->name,
                ],
            ])
            ->all();

        return Inertia::render('Admin/Enrollments/Index', [
            'enrollments' => $enrollments,
            'students' => Directory::options(UserRole::Student),
            'sections' => $this->sections(),
            'statuses' => EnrollmentStatus::values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'section_id' => ['required', 'integer', 'exists:course_sections,id'],
            'status' => ['required', Rule::in(EnrollmentStatus::values())],
        ]);

        $this->ensureStudent((int) $data['student_id']);
        $data['course_id'] = CourseSection::query()->findOrFail($data['section_id'])->course_id;

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

    /**
     * @return list<array{id: int, name: string, course: string, type: string}>
     */
    private function sections(): array
    {
        return CourseSection::query()
            ->with('course:id,title,type')
            ->get()
            ->sortBy(fn (CourseSection $section): string => $section->course->title.' '.$section->name, SORT_NATURAL | SORT_FLAG_CASE)
            ->map(fn (CourseSection $section): array => [
                'id' => $section->id,
                'name' => $section->name,
                'course' => $section->course->title,
                'type' => $section->course->type->value,
            ])
            ->values()
            ->all();
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
