<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PrivateLessonStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\PrivateLesson;
use App\Models\User;
use App\Support\Directory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PrivateLessonController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/PrivateLessons/Index', [
            'lessons' => $this->lessons(),
            'students' => Directory::options(UserRole::Student),
            'teachers' => Directory::options(UserRole::Teacher),
            'statuses' => PrivateLessonStatus::values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        PrivateLesson::query()->create($this->validated($request));

        return back()->with('success', __('ui.flash.private_saved'));
    }

    public function update(Request $request, PrivateLesson $privateLesson): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(PrivateLessonStatus::values())],
        ]);

        $privateLesson->update($data);

        return back()->with('success', __('ui.flash.private_updated'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function lessons(): array
    {
        return PrivateLesson::query()
            ->with(['student:id,name', 'teacher:id,name'])
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn (PrivateLesson $lesson): array => [
                'id' => $lesson->id,
                'scheduled_at' => $lesson->scheduled_at->format('Y-m-d H:i'),
                'duration_minutes' => $lesson->duration_minutes,
                'status' => $lesson->status->value,
                'student' => $lesson->student->name,
                'teacher' => $lesson->teacher->name,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'teacher_id' => ['required', 'integer', 'exists:users,id'],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'status' => ['required', Rule::in(PrivateLessonStatus::values())],
        ]);

        $student = User::query()->find($data['student_id']);
        $teacher = User::query()->find($data['teacher_id']);

        if (! $student?->isStudent()) {
            throw ValidationException::withMessages(['student_id' => __('ui.errors.choose_student')]);
        }

        if (! $teacher?->isTeacher()) {
            throw ValidationException::withMessages(['teacher_id' => __('ui.errors.choose_teacher')]);
        }

        return $data;
    }
}
