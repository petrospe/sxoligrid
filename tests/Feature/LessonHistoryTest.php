<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonLog;
use App\Models\PrivateLesson;
use App\Models\StudentSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LessonHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_a_subscribed_student_sees_past_notes_and_future_lessons_until_the_subscription_ends(): void
    {
        Carbon::setTestNow('2026-10-23 09:00:00');
        [$teacher, $student, $salsa] = $this->classOnTuesday();
        $piano = $this->enrollOn($student, $teacher, 'Πιάνο', 3, '16:00', '16:45');
        $this->note($teacher, $student, $salsa, '2026-09-15', 'Καλή στάση.', '9/10');
        StudentSubscription::query()->create([
            'student_id' => $student->id,
            'course_id' => $salsa->course_id,
            'title' => 'Salsa 2026',
            'total_amount' => 1800,
            'starts_at' => '2026-09-01',
            'ends_at' => '2026-11-30',
            'status' => 'active',
        ]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('pastLessons.0.date', '2026-09-15')
                ->where('pastLessons.0.course', 'Salsa')
                ->where('pastLessons.0.performance_notes', 'Καλή στάση.')
                ->where('pastLessons.0.test_score', '9/10')
                ->where('pastLessons.0.teacher', 'Μαρία Παπαδοπούλου')
                ->has('enrollments', 2)
                ->has('upcomingLessons', 5)
                ->where('upcomingLessons.0.date', '2026-10-27')
                ->where('upcomingLessons.0.course', 'Salsa')
                ->where('upcomingLessons.4.date', '2026-11-24')
                ->where('upcomingLessons.4.course', 'Salsa')
            );

        $this->assertNotSame($salsa->course_id, $piano->course_id);
    }

    public function test_a_student_without_a_subscription_sees_past_work_and_only_this_week(): void
    {
        Carbon::setTestNow('2026-10-23 09:00:00');
        [$teacher, $student, $salsa] = $this->classOnTuesday();
        $this->note($teacher, $student, $salsa, '2026-09-15', 'Καλή στάση.', '9/10');
        PrivateLesson::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'scheduled_at' => '2026-10-24 11:00:00',
            'duration_minutes' => 45,
            'status' => 'confirmed',
        ]);
        PrivateLesson::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'scheduled_at' => '2026-11-02 11:00:00',
            'duration_minutes' => 45,
            'status' => 'confirmed',
        ]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('pastLessons', 1)
                ->where('pastLessons.0.performance_notes', 'Καλή στάση.')
                ->has('enrollments', 1)
                ->where('upcomingLessons', [])
                ->has('privateLessons', 1)
                ->where('privateLessons.0.scheduled_at', '2026-10-24 11:00')
            );
    }

    public function test_a_teacher_sees_past_notes_and_future_classes_only_for_subscribed_students(): void
    {
        Carbon::setTestNow('2026-10-23 09:00:00');
        [$teacher, $anna, $salsa] = $this->classOnTuesday();
        $nikos = User::factory()->create(['role' => 'student', 'name' => 'Νίκος Παύση']);
        Enrollment::query()->create([
            'student_id' => $nikos->id,
            'section_id' => $salsa->section_id,
            'status' => 'active',
        ]);
        $this->note($teacher, $anna, $salsa, '2026-09-15', 'Καλή στάση.', '9/10');
        StudentSubscription::query()->create([
            'student_id' => $anna->id,
            'course_id' => $salsa->course_id,
            'title' => 'Salsa 2026',
            'total_amount' => 500,
            'starts_at' => '2026-09-01',
            'ends_at' => '2026-11-30',
            'status' => 'active',
        ]);

        $this->actingAs($teacher)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('schedules', 1)
                ->where('pastLessons.0.date', '2026-09-15')
                ->where('pastLessons.0.student', 'Άννα Βασιλείου')
                ->where('pastLessons.0.performance_notes', 'Καλή στάση.')
                ->where('pastLessons.0.test_score', '9/10')
                ->has('upcomingLessons', 5)
                ->where('upcomingLessons.0.date', '2026-10-27')
                ->has('upcomingLessons.0.students', 1)
                ->where('upcomingLessons.0.students.0.name', 'Άννα Βασιλείου')
            );
    }

    /**
     * @return array{0: User, 1: User, 2: ClassSchedule}
     */
    private function classOnTuesday(): array
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Μαρία Παπαδοπούλου']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Άννα Βασιλείου']);
        $schedule = $this->enrollOn($student, $teacher, 'Salsa', 2, '18:00', '19:30');

        return [$teacher, $student, $schedule];
    }

    private function enrollOn(User $student, User $teacher, string $title, int $day, string $start, string $end): ClassSchedule
    {
        $course = Course::query()->create(['title' => $title, 'type' => 'group']);
        $section = $course->sections()->create(['name' => $title]);
        $schedule = ClassSchedule::query()->create([
            'section_id' => $section->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => $day,
            'start_time' => $start,
            'end_time' => $end,
            'room' => 'Α1',
        ]);
        Enrollment::query()->create([
            'student_id' => $student->id,
            'section_id' => $section->id,
            'status' => 'active',
        ]);

        return $schedule;
    }

    private function note(User $teacher, User $student, ClassSchedule $schedule, string $date, string $notes, string $score): void
    {
        Attendance::query()->create([
            'class_schedule_id' => $schedule->id,
            'student_id' => $student->id,
            'attended_on' => $date,
        ]);
        LessonLog::query()->create([
            'schedule_id' => $schedule->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'happened_on' => $date,
            'performance_notes' => $notes,
            'test_title' => 'Πρόοδος',
            'test_score' => $score,
        ]);
    }
}
