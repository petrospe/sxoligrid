<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\PrivateLesson;
use App\Support\ScheduleMapper;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $teacher = $request->user();

        $schedules = ClassSchedule::query()
            ->with(['course:id,title,type', 'teacher:id,name', 'teachers:id,name'])
            ->forTeacher($teacher->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $lessons = PrivateLesson::query()
            ->with('student:id,name')
            ->where('teacher_id', $teacher->id)
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn (PrivateLesson $lesson): array => [
                'id' => $lesson->id,
                'scheduled_at' => $lesson->scheduled_at->format('Y-m-d H:i'),
                'duration_minutes' => $lesson->duration_minutes,
                'status' => $lesson->status->value,
                'student' => $lesson->student->name,
            ])
            ->all();

        return Inertia::render('Teacher/Dashboard', [
            'schedules' => ScheduleMapper::withTeacher($schedules),
            'privateLessons' => $lessons,
        ]);
    }
}
