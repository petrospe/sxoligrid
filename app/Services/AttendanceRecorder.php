<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\Attendance;
use App\Models\ClassSchedule;
use App\Models\LessonLog;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceRecorder
{
    /**
     * @param  array{student_id: int, performance_notes?: string|null, test_title?: string|null, test_score?: string|null}  $entry
     */
    public function mark(ClassSchedule $schedule, array $entry, string $date, User $teacher): void
    {
        $student = User::query()->find($entry['student_id']);

        if (! $student instanceof User || ! $student->isStudent()) {
            throw ValidationException::withMessages([
                'entries' => __('ui.errors.invalid_student'),
            ]);
        }

        $enrolled = $schedule->section()->firstOrFail()->enrollments()
            ->where('student_id', $student->id)
            ->where('status', EnrollmentStatus::Active)
            ->exists();

        if (! $enrolled) {
            throw ValidationException::withMessages([
                'entries' => __('ui.errors.not_enrolled'),
            ]);
        }

        DB::transaction(function () use ($schedule, $entry, $date, $teacher, $student): void {
            try {
                Attendance::query()->create([
                    'class_schedule_id' => $schedule->id,
                    'student_id' => $student->id,
                    'attended_on' => $date,
                ]);
            } catch (UniqueConstraintViolationException) {
            }

            $notes = trim((string) ($entry['performance_notes'] ?? ''));
            $testTitle = trim((string) ($entry['test_title'] ?? ''));
            $testScore = trim((string) ($entry['test_score'] ?? ''));

            if ($notes === '' && $testTitle === '' && $testScore === '') {
                return;
            }

            LessonLog::query()->create([
                'schedule_id' => $schedule->id,
                'private_lesson_id' => null,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'happened_on' => $date,
                'performance_notes' => $notes !== '' ? $notes : null,
                'test_title' => $testTitle !== '' ? $testTitle : null,
                'test_score' => $testScore !== '' ? $testScore : null,
            ]);
        });
    }
}
