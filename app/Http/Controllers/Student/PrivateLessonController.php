<?php

namespace App\Http\Controllers\Student;

use App\Enums\PrivateLessonStatus;
use App\Http\Controllers\Controller;
use App\Models\PrivateLesson;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PrivateLessonController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'teacher_id' => ['required', 'integer', 'exists:users,id'],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
        ]);

        $teacher = User::query()->find($data['teacher_id']);

        if (! $teacher?->isTeacher()) {
            throw ValidationException::withMessages([
                'teacher_id' => __('ui.errors.choose_teacher'),
            ]);
        }

        PrivateLesson::query()->create([
            'student_id' => $request->user()->id,
            'teacher_id' => $teacher->id,
            'scheduled_at' => $data['scheduled_at'],
            'duration_minutes' => $data['duration_minutes'],
            'status' => PrivateLessonStatus::Requested,
        ]);

        return back()->with('success', __('ui.flash.private_requested'));
    }
}
