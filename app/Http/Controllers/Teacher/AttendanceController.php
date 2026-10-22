<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\EnrollmentStatus;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ClassSchedule;
use App\Models\StudentSubscription;
use App\Services\AttendanceRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function edit(Request $request, ClassSchedule $schedule): Response
    {
        abort_unless($schedule->taughtBy($request->user()), 403);

        $date = $this->date($request);
        $schedule->load('course:id,title');

        $enrollments = $schedule->course->enrollments()
            ->where('status', EnrollmentStatus::Active)
            ->with('student:id,name')
            ->get();

        $attended = Attendance::query()
            ->where('class_schedule_id', $schedule->id)
            ->whereDate('attended_on', $date)
            ->pluck('student_id');

        $packs = StudentSubscription::query()
            ->with('package:id,title')
            ->whereIn('student_id', $enrollments->pluck('student_id'))
            ->where('status', SubscriptionStatus::Active)
            ->whereNotNull('remaining_sessions')
            ->get()
            ->groupBy('student_id');

        $students = $enrollments->map(function ($enrollment) use ($attended, $packs): array {
            $pack = $packs->get($enrollment->student_id)?->sortBy('expires_at')->first();

            return [
                'id' => $enrollment->student->id,
                'name' => $enrollment->student->name,
                'attended' => $attended->contains($enrollment->student_id),
                'remaining_sessions' => $pack?->remaining_sessions,
                'package_title' => $pack?->package?->title,
            ];
        })->values()->all();

        return Inertia::render('Teacher/Attendance', [
            'date' => $date,
            'schedule' => [
                'id' => $schedule->id,
                'course' => $schedule->course->title,
                'start_time' => substr((string) $schedule->start_time, 0, 5),
                'end_time' => substr((string) $schedule->end_time, 0, 5),
                'room' => $schedule->room,
            ],
            'students' => $students,
        ]);
    }

    public function update(Request $request, ClassSchedule $schedule, AttendanceRecorder $recorder): RedirectResponse
    {
        abort_unless($schedule->taughtBy($request->user()), 403);

        $data = $request->validate([
            'date' => ['required', 'date'],
            'entries' => ['required', 'array'],
            'entries.*.student_id' => ['required', 'integer'],
            'entries.*.attended' => ['required', 'boolean'],
            'entries.*.performance_notes' => ['nullable', 'string'],
            'entries.*.test_title' => ['nullable', 'string', 'max:255'],
            'entries.*.test_score' => ['nullable', 'string', 'max:50'],
        ]);

        foreach ($data['entries'] as $entry) {
            if (! $entry['attended']) {
                continue;
            }

            $recorder->mark($schedule, $entry, $data['date'], $request->user());
        }

        return back()->with('success', __('ui.flash.attendance_saved'));
    }

    private function date(Request $request): string
    {
        $date = (string) $request->query('date', now()->toDateString());

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? $date : now()->toDateString();
    }
}
