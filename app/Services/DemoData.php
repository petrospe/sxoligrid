<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Expense;
use App\Models\FlexSuggestion;
use App\Models\LessonLog;
use App\Models\Payment;
use App\Models\PrivateLesson;
use App\Models\StudentSubscription;
use App\Models\TeacherPayroll;
use App\Models\User;
use Database\Seeders\ConservatorySeeder;
use Database\Seeders\DanceSchoolSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\FitnessStudioSeeder;
use Database\Seeders\TutoringSchoolSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DemoData
{
    /** @var array<string, class-string<Seeder>> */
    private const EXAMPLES = [
        'general' => DatabaseSeeder::class,
        'dance' => DanceSchoolSeeder::class,
        'fitness' => FitnessStudioSeeder::class,
        'conservatory' => ConservatorySeeder::class,
        'tutoring' => TutoringSchoolSeeder::class,
    ];

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys(self::EXAMPLES);
    }

    /**
     * @return list<array{key: string, installed: bool}>
     */
    public function examples(): array
    {
        $examples = [];

        foreach ($this->keys() as $key) {
            $examples[] = [
                'key' => $key,
                'installed' => $this->installed($key),
            ];
        }

        return $examples;
    }

    public function installed(string $key = 'general'): bool
    {
        $markers = $this->markers($key);

        return User::query()->whereIn('email', $markers['emails'])->exists()
            || Course::query()->whereIn('title', $markers['courses'])->exists()
            || ($markers['subscriptions'] !== [] && StudentSubscription::query()->whereIn('title', $markers['subscriptions'])->exists())
            || ($markers['events'] !== [] && Event::query()->whereIn('title', $markers['events'])->exists())
            || ($markers['expenses'] !== [] && Expense::query()->whereIn('title', $markers['expenses'])->exists());
    }

    public function install(string $key = 'general'): void
    {
        if ($this->installed($key)) {
            throw ValidationException::withMessages([
                'demo' => __('ui.errors.demo_exists'),
            ]);
        }

        $class = self::EXAMPLES[$key];
        (new $class)->run();
    }

    public function remove(string $key = 'general'): void
    {
        $markers = $this->markers($key);

        DB::transaction(function () use ($markers): void {
            $userIds = User::query()->whereIn('email', $markers['emails'])->pluck('id')->all();
            $courseIds = Course::query()->whereIn('title', $markers['courses'])->pluck('id')->all();
            $subscriptionIds = $markers['subscriptions'] === []
                ? []
                : StudentSubscription::query()->whereIn('title', $markers['subscriptions'])->pluck('id')->all();
            $eventIds = $markers['events'] === []
                ? []
                : Event::query()->whereIn('title', $markers['events'])->pluck('id')->all();
            $scheduleIds = ClassSchedule::query()->whereIn('course_id', $courseIds)->pluck('id')->all();
            $lessonIds = PrivateLesson::query()
                ->where(function ($query) use ($userIds): void {
                    $query->whereIn('student_id', $userIds)->orWhereIn('teacher_id', $userIds);
                })
                ->pluck('id')
                ->all();

            Attendance::query()
                ->where(function ($query) use ($userIds, $scheduleIds): void {
                    $query->whereIn('student_id', $userIds)->orWhereIn('class_schedule_id', $scheduleIds);
                })
                ->delete();
            LessonLog::query()
                ->where(function ($query) use ($userIds, $scheduleIds, $lessonIds): void {
                    $query->whereIn('student_id', $userIds)
                        ->orWhereIn('teacher_id', $userIds)
                        ->orWhereIn('schedule_id', $scheduleIds)
                        ->orWhereIn('private_lesson_id', $lessonIds);
                })
                ->delete();
            EventParticipant::query()
                ->where(function ($query) use ($userIds, $eventIds): void {
                    $query->whereIn('user_id', $userIds)->orWhereIn('event_id', $eventIds);
                })
                ->delete();
            TeacherPayroll::query()->whereIn('teacher_id', $userIds)->delete();
            Payment::query()
                ->where(function ($query) use ($userIds, $subscriptionIds): void {
                    $query->whereIn('student_id', $userIds)->orWhereIn('subscription_id', $subscriptionIds);
                })
                ->delete();
            StudentSubscription::query()
                ->where(function ($query) use ($userIds, $subscriptionIds): void {
                    $query->whereIn('student_id', $userIds)->orWhereIn('id', $subscriptionIds);
                })
                ->delete();
            PrivateLesson::query()->whereIn('id', $lessonIds)->delete();
            Enrollment::query()
                ->where(function ($query) use ($userIds, $courseIds): void {
                    $query->whereIn('student_id', $userIds)->orWhereIn('course_id', $courseIds);
                })
                ->delete();
            FlexSuggestion::query()
                ->where(function ($query) use ($userIds, $markers): void {
                    $query->whereIn('target_user_id', $userIds);

                    if ($markers['flex'] !== []) {
                        $query->orWhereIn('content', $markers['flex']);
                    }
                })
                ->delete();
            ClassSchedule::query()->whereIn('id', $scheduleIds)->delete();
            if ($eventIds !== []) {
                Event::query()->whereIn('id', $eventIds)->delete();
            }
            Course::query()->whereIn('id', $courseIds)->delete();
            if ($markers['expenses'] !== []) {
                Expense::query()->whereIn('title', $markers['expenses'])->delete();
            }
            User::query()->whereIn('email', $markers['emails'])->delete();
        });
    }

    /**
     * @return array{emails: list<string>, courses: list<string>, subscriptions: list<string>, expenses: list<string>, events: list<string>, flex: list<string>}
     */
    private function markers(string $key): array
    {
        $class = self::EXAMPLES[$key] ?? null;

        if ($class === null || ! method_exists($class, 'markers')) {
            throw ValidationException::withMessages([
                'example' => __('ui.errors.demo_example'),
            ]);
        }

        /** @var array{emails: list<string>, courses: list<string>, subscriptions: list<string>, expenses: list<string>, events: list<string>, flex: list<string>} $markers */
        $markers = $class::markers();

        return $markers;
    }
}
