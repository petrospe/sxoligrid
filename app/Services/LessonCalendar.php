<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\PrivateLessonStatus;
use App\Models\Attendance;
use App\Models\ClassSchedule;
use App\Models\Enrollment;
use App\Models\LessonLog;
use App\Models\PrivateLesson;
use App\Models\StudentSubscription;
use App\Models\User;
use App\Support\Week;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class LessonCalendar
{
    private const OPEN_WEEKS = 16;

    /** @var array<int, Collection<int, StudentSubscription>> */
    private array $subscriptions = [];

    /**
     * @return array{past: list<array<string, mixed>>, upcoming: list<array<string, mixed>>}
     */
    public function forStudent(User $student): array
    {
        $subscriptions = $this->subscriptionsFor($student->id);
        $schedules = Enrollment::query()
            ->where('student_id', $student->id)
            ->where('status', EnrollmentStatus::Active)
            ->with([
                'section.schedules.course',
                'section.schedules.section',
                'section.schedules.teacher',
                'section.schedules.teachers',
            ])
            ->get()
            ->flatMap(fn (Enrollment $enrollment) => $enrollment->section->schedules)
            ->unique('id')
            ->values();

        $rows = $this->pastStudentRows($student->id);

        foreach ($this->privateLessonsFor($student->id, 'student_id') as $lesson) {
            $date = $lesson->scheduled_at->toDateString();

            if ($date <= $this->today()) {
                $rows['private-'.$lesson->id] = $this->privateRow($lesson, $lesson->teacher->name, null);
            }
        }

        $upcoming = [];

        foreach ($schedules as $schedule) {
            foreach ($this->futureDates($schedule, $subscriptions) as $date) {
                $upcoming[] = $this->studentSlot($schedule, $date, false);
            }
        }

        foreach ($this->privateLessonsFor($student->id, 'student_id') as $lesson) {
            $date = $lesson->scheduled_at->toDateString();

            if ($date > $this->weekEnd() && $this->covers($subscriptions, null, $date)) {
                $upcoming[] = $this->privateRow($lesson, $lesson->teacher->name, null);
            }
        }

        return [
            'past' => $this->sorted($rows, true),
            'upcoming' => $this->sorted($upcoming, false),
        ];
    }

    /**
     * @return array{past: list<array<string, mixed>>, upcoming: list<array<string, mixed>>}
     */
    public function forTeacher(User $teacher): array
    {
        $schedules = ClassSchedule::query()
            ->forTeacher($teacher->id)
            ->with([
                'course',
                'section.enrollments.student',
                'teacher',
                'teachers',
            ])
            ->get();

        $studentIds = $schedules
            ->flatMap(fn (ClassSchedule $schedule) => $schedule->section?->enrollments->pluck('student_id') ?? [])
            ->unique()
            ->all();

        if ($studentIds !== []) {
            $grouped = StudentSubscription::query()->whereIn('student_id', $studentIds)->get()->groupBy('student_id');

            foreach ($studentIds as $studentId) {
                $this->subscriptions[(int) $studentId] = $grouped->get($studentId, collect());
            }
        }

        $rows = $this->pastTeacherRows($teacher->id, $schedules->pluck('id')->all());
        $upcoming = [];

        foreach ($schedules as $schedule) {
            $subscriptions = $this->subscriptionsForStudents($schedule);
            $dates = $this->dates((int) $schedule->day_of_week, $this->upcomingStart(), $this->horizon($subscriptions, $schedule->course_id));

            foreach ($dates as $date) {
                $students = $this->coveredStudents($schedule, $date);

                if ($students === []) {
                    continue;
                }

                $upcoming[] = [
                    ...$this->slot($schedule, $date),
                    'students' => $students,
                ];
            }
        }

        return [
            'past' => $this->sorted($rows, true),
            'upcoming' => $this->sorted($upcoming, false),
        ];
    }

    public function showsPrivateLesson(PrivateLesson $lesson): bool
    {
        $date = $lesson->scheduled_at->toDateString();

        if ($date <= $this->weekEnd()) {
            return true;
        }

        return $this->covers($this->subscriptionsFor($lesson->student_id), null, $date);
    }

    /**
     * @return Collection<int, PrivateLesson>
     */
    private function privateLessonsFor(int $userId, string $column): Collection
    {
        return PrivateLesson::query()
            ->where($column, $userId)
            ->where('status', '!=', PrivateLessonStatus::Cancelled)
            ->with(['teacher:id,name', 'student:id,name', 'lessonLogs'])
            ->get();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function pastStudentRows(int $studentId): array
    {
        $rows = [];
        $attendances = Attendance::query()
            ->where('student_id', $studentId)
            ->whereDate('attended_on', '<=', $this->today())
            ->with(['schedule.course', 'schedule.section', 'schedule.teacher', 'schedule.teachers'])
            ->get();

        foreach ($attendances as $attendance) {
            $date = $attendance->attended_on->toDateString();
            $rows[$this->key($attendance->class_schedule_id, $date)] = $this->studentSlot($attendance->schedule, $date, true);
        }

        $logs = LessonLog::query()
            ->where('student_id', $studentId)
            ->whereNotNull('schedule_id')
            ->with(['teacher:id,name', 'schedule.course', 'schedule.section', 'schedule.teacher', 'schedule.teachers'])
            ->orderBy('id')
            ->get();

        foreach ($logs as $log) {
            $date = $this->lessonDate($log);

            if ($date === null || $date > $this->today() || $log->schedule === null) {
                continue;
            }

            $key = $this->key($log->schedule_id, $date);

            if (! isset($rows[$key])) {
                $rows[$key] = $this->studentSlot($log->schedule, $date, false);
            }

            $rows[$key]['performance_notes'] = $log->performance_notes;
            $rows[$key]['test_title'] = $log->test_title;
            $rows[$key]['test_score'] = $log->test_score;
            $rows[$key]['teacher'] = $log->teacher->name;
        }

        return $rows;
    }

    /**
     * @param  list<int>  $scheduleIds
     * @return array<string, array<string, mixed>>
     */
    private function pastTeacherRows(int $teacherId, array $scheduleIds): array
    {
        $rows = [];
        $attendances = Attendance::query()
            ->whereIn('class_schedule_id', $scheduleIds)
            ->whereDate('attended_on', '<=', $this->today())
            ->with(['student:id,name', 'schedule.course', 'schedule.section'])
            ->get();

        foreach ($attendances as $attendance) {
            $date = $attendance->attended_on->toDateString();
            $rows[$this->key($attendance->class_schedule_id, $date, $attendance->student_id)] = [
                ...$this->slot($attendance->schedule, $date),
                'student' => $attendance->student->name,
                'attended' => true,
                'performance_notes' => null,
                'test_title' => null,
                'test_score' => null,
            ];
        }

        $logs = LessonLog::query()
            ->where('teacher_id', $teacherId)
            ->whereNotNull('schedule_id')
            ->with(['student:id,name', 'schedule.course', 'schedule.section'])
            ->orderBy('id')
            ->get();

        foreach ($logs as $log) {
            $date = $this->lessonDate($log);

            if ($date === null || $date > $this->today() || $log->schedule === null) {
                continue;
            }

            $key = $this->key($log->schedule_id, $date, $log->student_id);

            if (! isset($rows[$key])) {
                $rows[$key] = [
                    ...$this->slot($log->schedule, $date),
                    'student' => $log->student->name,
                    'attended' => false,
                    'performance_notes' => null,
                    'test_title' => null,
                    'test_score' => null,
                ];
            }

            $rows[$key]['performance_notes'] = $log->performance_notes;
            $rows[$key]['test_title'] = $log->test_title;
            $rows[$key]['test_score'] = $log->test_score;
        }

        return $rows;
    }

    /**
     * @param  Collection<int, StudentSubscription>  $subscriptions
     * @return list<string>
     */
    private function futureDates(ClassSchedule $schedule, Collection $subscriptions): array
    {
        $dates = $this->dates(
            (int) $schedule->day_of_week,
            $this->upcomingStart(),
            $this->horizon($subscriptions, $schedule->course_id),
        );

        return array_values(array_filter(
            $dates,
            fn (string $date): bool => $this->covers($subscriptions, $schedule->course_id, $date),
        ));
    }

    /**
     * @return list<array{name: string, attended: bool, performance_notes: null, test_title: null, test_score: null}>
     */
    private function coveredStudents(ClassSchedule $schedule, string $date): array
    {
        if ($schedule->section === null) {
            return [];
        }

        $students = [];

        foreach ($schedule->section->enrollments as $enrollment) {
            if ($enrollment->status !== EnrollmentStatus::Active) {
                continue;
            }

            if (! $this->covers($this->subscriptionsFor($enrollment->student_id), $schedule->course_id, $date)) {
                continue;
            }

            $students[] = [
                'name' => $enrollment->student->name,
                'attended' => false,
                'performance_notes' => null,
                'test_title' => null,
                'test_score' => null,
            ];
        }

        usort($students, fn (array $left, array $right): int => strnatcasecmp($left['name'], $right['name']));

        return $students;
    }

    /**
     * @return Collection<int, StudentSubscription>
     */
    private function subscriptionsForStudents(ClassSchedule $schedule): Collection
    {
        $all = collect();

        foreach ($schedule->section?->enrollments ?? [] as $enrollment) {
            $all = $all->concat($this->subscriptionsFor($enrollment->student_id));
        }

        return $all;
    }

    /**
     * @param  Collection<int, StudentSubscription>  $subscriptions
     */
    private function covers(Collection $subscriptions, ?int $courseId, string $date): bool
    {
        return $subscriptions->contains(function (StudentSubscription $subscription) use ($courseId, $date): bool {
            if (! $subscription->isUsableOn($date)) {
                return false;
            }

            if ($courseId === null || $subscription->course_id === null) {
                return true;
            }

            return $subscription->course_id === $courseId;
        });
    }

    /**
     * @param  Collection<int, StudentSubscription>  $subscriptions
     */
    private function horizon(Collection $subscriptions, ?int $courseId): Carbon
    {
        $matching = $subscriptions->filter(function (StudentSubscription $subscription) use ($courseId): bool {
            if ($subscription->status->value !== 'active') {
                return false;
            }

            return $courseId === null || $subscription->course_id === null || $subscription->course_id === $courseId;
        });

        if ($matching->isEmpty()) {
            return $this->weekEnd();
        }

        if ($matching->contains(fn (StudentSubscription $subscription): bool => $subscription->ends_at === null)) {
            return $this->weekEnd()->addWeeks(self::OPEN_WEEKS);
        }

        $end = $matching->max(fn (StudentSubscription $subscription): string => $subscription->ends_at->toDateString());

        return Carbon::parse($end)->startOfDay();
    }

    /**
     * @return list<string>
     */
    private function dates(int $dayOfWeek, Carbon $from, Carbon $until): array
    {
        $dates = [];
        $cursor = $from->copy();

        while ($cursor->lte($until)) {
            if ($cursor->dayOfWeekIso === $dayOfWeek) {
                $dates[] = $cursor->toDateString();
            }

            $cursor->addDay();
        }

        return $dates;
    }

    /**
     * @return array<string, mixed>
     */
    private function studentSlot(ClassSchedule $schedule, string $date, bool $attended): array
    {
        return [
            ...$this->slot($schedule, $date),
            'teacher' => $this->teacherNames($schedule),
            'attended' => $attended,
            'performance_notes' => null,
            'test_title' => null,
            'test_score' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function slot(ClassSchedule $schedule, string $date): array
    {
        return [
            'date' => $date,
            'start_time' => Week::clock($schedule->start_time),
            'end_time' => Week::clock($schedule->end_time),
            'course' => $schedule->course->title,
            'section' => $schedule->section?->name,
            'room' => $schedule->room,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function privateRow(PrivateLesson $lesson, ?string $teacher, ?string $student): array
    {
        $log = $lesson->lessonLogs->sortBy('id')->last();

        return [
            'date' => $lesson->scheduled_at->toDateString(),
            'start_time' => $lesson->scheduled_at->format('H:i'),
            'end_time' => $lesson->scheduled_at->copy()->addMinutes($lesson->duration_minutes)->format('H:i'),
            'course' => __('ui.common.private_lesson'),
            'section' => null,
            'room' => null,
            'teacher' => $teacher,
            'student' => $student ?? $lesson->student->name,
            'attended' => $lesson->status === PrivateLessonStatus::Completed,
            'performance_notes' => $log?->performance_notes,
            'test_title' => $log?->test_title,
            'test_score' => $log?->test_score,
        ];
    }

    private function teacherNames(ClassSchedule $schedule): string
    {
        $names = [];

        if ($schedule->teacher !== null) {
            $names[] = $schedule->teacher->name;
        }

        foreach ($schedule->teachers as $teacher) {
            $names[] = $teacher->name;
        }

        return implode(', ', array_values(array_unique($names)));
    }

    /**
     * @return Collection<int, StudentSubscription>
     */
    private function subscriptionsFor(int $studentId): Collection
    {
        if (! isset($this->subscriptions[$studentId])) {
            $this->subscriptions[$studentId] = StudentSubscription::query()
                ->where('student_id', $studentId)
                ->get();
        }

        return $this->subscriptions[$studentId];
    }

    /**
     * @param  array<string, array<string, mixed>>|list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function sorted(array $rows, bool $newestFirst): array
    {
        $list = array_values($rows);
        usort($list, function (array $left, array $right) use ($newestFirst): int {
            $byDate = strcmp((string) $left['date'], (string) $right['date']);

            if ($byDate !== 0) {
                return $newestFirst ? -$byDate : $byDate;
            }

            return strcmp((string) $left['start_time'], (string) $right['start_time']);
        });

        return $list;
    }

    private function key(int $scheduleId, string $date, ?int $studentId = null): string
    {
        return $scheduleId.'|'.$date.'|'.($studentId ?? '0');
    }

    private function lessonDate(LessonLog $log): ?string
    {
        return $log->happened_on?->toDateString() ?? $log->created_at?->toDateString();
    }

    private function today(): string
    {
        return Carbon::now()->toDateString();
    }

    private function weekEnd(): Carbon
    {
        return Carbon::now()->startOfDay()->endOfWeek(Carbon::SUNDAY)->startOfDay();
    }

    private function upcomingStart(): Carbon
    {
        return $this->weekEnd()->addDay();
    }
}
