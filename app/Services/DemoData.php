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
use App\Models\Package;
use App\Models\Payment;
use App\Models\PrivateLesson;
use App\Models\StudentSubscription;
use App\Models\TeacherPayroll;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DemoData
{
    /** @var list<string> */
    private const USER_EMAILS = [
        'owner@sxoligrid.test',
        'teacher@sxoligrid.test',
        'teacher2@sxoligrid.test',
        'student@sxoligrid.test',
        'student2@sxoligrid.test',
    ];

    /** @var list<string> */
    private const COURSE_TITLES = [
        'Μαθηματικά Λυκείου',
        'English B2',
        'Salsa',
        'Πιάνο',
    ];

    /** @var list<string> */
    private const PACKAGE_TITLES = [
        'Μηνιαίο Unlimited Χορού',
        'Πακέτο 10 Μαθημάτων Μουσικής',
        'Drop-in εισιτήριο',
        'Μηνιαία φροντιστηρίου',
    ];

    /** @var list<string> */
    private const EXPENSE_TITLES = [
        'Ενοίκιο Οκτωβρίου',
        'Αναλώσιμα στούντιο',
    ];

    private const EVENT_TITLE = 'Χριστουγεννιάτικο Showcase';

    private const FLEX_CONTENT = 'Κενό 1 ώρας την Τρίτη (17:00–18:00). Προτείνεται μετακίνηση τμήματος Salsa.';

    public function installed(): bool
    {
        return User::query()->whereIn('email', self::USER_EMAILS)->exists()
            || Course::query()->whereIn('title', self::COURSE_TITLES)->exists()
            || Package::query()->whereIn('title', self::PACKAGE_TITLES)->exists()
            || Event::query()->where('title', self::EVENT_TITLE)->exists()
            || Expense::query()->whereIn('title', self::EXPENSE_TITLES)->exists();
    }

    public function install(): void
    {
        if ($this->installed()) {
            throw ValidationException::withMessages([
                'demo' => __('ui.errors.demo_exists'),
            ]);
        }

        (new DatabaseSeeder)->run();
    }

    public function remove(): void
    {
        DB::transaction(function (): void {
            $userIds = User::query()->whereIn('email', self::USER_EMAILS)->pluck('id')->all();
            $courseIds = Course::query()->whereIn('title', self::COURSE_TITLES)->pluck('id')->all();
            $packageIds = Package::query()->whereIn('title', self::PACKAGE_TITLES)->pluck('id')->all();
            $eventIds = Event::query()->where('title', self::EVENT_TITLE)->pluck('id')->all();
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
            StudentSubscription::query()
                ->where(function ($query) use ($userIds, $packageIds): void {
                    $query->whereIn('student_id', $userIds)->orWhereIn('package_id', $packageIds);
                })
                ->delete();
            Payment::query()->whereIn('student_id', $userIds)->delete();
            PrivateLesson::query()->whereIn('id', $lessonIds)->delete();
            Enrollment::query()
                ->where(function ($query) use ($userIds, $courseIds): void {
                    $query->whereIn('student_id', $userIds)->orWhereIn('course_id', $courseIds);
                })
                ->delete();
            FlexSuggestion::query()
                ->where(function ($query) use ($userIds): void {
                    $query->whereIn('target_user_id', $userIds)->orWhere('content', self::FLEX_CONTENT);
                })
                ->delete();
            ClassSchedule::query()->whereIn('id', $scheduleIds)->delete();
            Event::query()->whereIn('id', $eventIds)->delete();
            Course::query()->whereIn('id', $courseIds)->delete();
            Package::query()->whereIn('id', $packageIds)->delete();
            Expense::query()->whereIn('title', self::EXPENSE_TITLES)->delete();
            User::query()->whereIn('email', self::USER_EMAILS)->delete();
        });
    }
}
