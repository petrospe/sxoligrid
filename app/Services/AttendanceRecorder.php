<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Attendance;
use App\Models\ClassSchedule;
use App\Models\LessonLog;
use App\Models\StudentSubscription;
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

        $enrolled = $schedule->course()->firstOrFail()->enrollments()
            ->where('student_id', $student->id)
            ->where('status', EnrollmentStatus::Active)
            ->exists();

        if (! $enrolled) {
            throw ValidationException::withMessages([
                'entries' => __('ui.errors.not_enrolled'),
            ]);
        }

        DB::transaction(function () use ($schedule, $entry, $date, $teacher, $student): void {
            $created = false;

            try {
                Attendance::query()->create([
                    'class_schedule_id' => $schedule->id,
                    'student_id' => $student->id,
                    'attended_on' => $date,
                ]);
                $created = true;
            } catch (UniqueConstraintViolationException) {
                $created = false;
            }

            if ($created) {
                $this->decrementSession($student, $date);
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
                'performance_notes' => $notes !== '' ? $notes : null,
                'test_title' => $testTitle !== '' ? $testTitle : null,
                'test_score' => $testScore !== '' ? $testScore : null,
            ]);
        });
    }

    private function decrementSession(User $student, string $date): void
    {
        $subscription = StudentSubscription::query()
            ->where('student_id', $student->id)
            ->where('status', SubscriptionStatus::Active)
            ->whereNotNull('remaining_sessions')
            ->where('remaining_sessions', '>', 0)
            ->whereDate('starts_at', '<=', $date)
            ->where(function ($query) use ($date): void {
                $query->whereNull('expires_at')
                    ->orWhereDate('expires_at', '>=', $date);
            })
            ->orderByRaw('case when expires_at is null then 1 else 0 end')
            ->orderBy('expires_at')
            ->lockForUpdate()
            ->first();

        if (! $subscription instanceof StudentSubscription) {
            return;
        }

        $remaining = (int) $subscription->remaining_sessions - 1;
        $subscription->remaining_sessions = max(0, $remaining);

        if ($subscription->remaining_sessions === 0) {
            $subscription->status = SubscriptionStatus::Depleted;
        }

        $subscription->save();
    }
}
