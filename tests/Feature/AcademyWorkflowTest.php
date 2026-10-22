<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Expense;
use App\Models\FlexSuggestion;
use App\Models\LessonLog;
use App\Models\Package;
use App\Models\Payment;
use App\Models\StudentSubscription;
use App\Models\TeacherPayroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AcademyWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_initial_migration_creates_an_admin_who_can_sign_in(): void
    {
        $this->assertSame(1, User::query()->where('email', 'admin@sxoligrid.test')->count());

        $admin = User::query()->where('email', 'admin@sxoligrid.test')->first();
        $this->assertSame(UserRole::Admin, $admin->role);

        $this->post('/login', [
            'email' => 'admin@sxoligrid.test',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->seed();

        $this->assertSame(1, User::query()->where('email', 'admin@sxoligrid.test')->count());
    }

    public function test_public_schedule_hides_teachers(): void
    {
        [$teacher, $course] = $this->classWithTeacher();

        ClassSchedule::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => 2,
            'start_time' => '18:00',
            'end_time' => '19:30',
            'room' => 'Studio 1',
        ]);

        $this->get(route('schedule.public'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Schedule')
                ->has('schedules', 1, fn (Assert $schedule) => $schedule
                    ->where('course.title', 'Salsa')
                    ->where('room', 'Studio 1')
                    ->missing('teacher_id')
                    ->missing('teacher')
                    ->etc()
                )
            );
    }

    public function test_staff_schedule_shows_the_teacher(): void
    {
        [$teacher, $course] = $this->classWithTeacher();
        ClassSchedule::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => 2,
            'start_time' => '18:00',
            'end_time' => '19:30',
            'room' => 'Studio 1',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.schedules.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('schedules.0.teacher.name', 'Μαρία Παπαδοπούλου')
                ->etc()
            );
    }

    public function test_dashboard_sends_each_role_home(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($owner)->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
        $this->actingAs($teacher)->get(route('dashboard'))->assertRedirect(route('teacher.dashboard'));
        $this->actingAs($student)->get(route('dashboard'))->assertRedirect(route('student.dashboard'));
    }

    public function test_login_accepts_the_demo_password(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'email' => 'student@sxoligrid.test',
        ]);

        $this->post('/login', [
            'email' => $student->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($student);
    }

    public function test_teacher_and_student_cannot_open_staff_or_finance_pages(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($teacher)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($teacher)->get(route('owner.finances.index'))->assertForbidden();
        $this->actingAs($student)->get(route('teacher.students.index'))->assertForbidden();
        $this->actingAs($student)->get('/admin/payments')->assertForbidden();
    }

    public function test_admin_cannot_view_finances_until_the_owner_grants_access(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $admin = User::factory()->create(['role' => 'admin', 'can_view_finances' => false]);

        $this->actingAs($admin)->get(route('owner.finances.index'))->assertForbidden();

        $this->actingAs($owner)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'admin',
            'phone' => null,
            'can_view_finances' => true,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertTrue($admin->refresh()->canViewFinances());

        $this->actingAs($admin)->get(route('owner.finances.index'))->assertOk();
    }

    public function test_admin_cannot_promote_themselves_to_owner(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'owner',
            'phone' => null,
        ])->assertSessionHasErrors('role');

        $this->assertTrue($admin->refresh()->isAdmin());
    }

    public function test_owner_finance_summary_uses_paid_income_expenses_and_paid_payroll(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);

        Payment::query()->create([
            'student_id' => $student->id,
            'amount' => 100,
            'type' => 'monthly_subscription',
            'status' => 'paid',
            'due_date' => '2026-10-15',
            'paid_at' => '2026-10-15 12:00:00',
        ]);
        Payment::query()->create([
            'student_id' => $student->id,
            'amount' => 999,
            'type' => 'drop_in_ticket',
            'status' => 'paid',
            'due_date' => '2026-11-01',
            'paid_at' => '2026-11-01 12:00:00',
        ]);
        Expense::query()->create([
            'title' => 'Ενοίκιο',
            'amount' => 30,
            'category' => 'rent',
            'expense_date' => '2026-10-01',
        ]);
        TeacherPayroll::query()->create([
            'teacher_id' => $teacher->id,
            'type' => 'monthly_salary',
            'base_amount' => 40,
            'payment_period' => '2026-10',
            'status' => 'paid',
        ]);
        TeacherPayroll::query()->create([
            'teacher_id' => $teacher->id,
            'type' => 'hourly_rate',
            'base_amount' => 15,
            'payment_period' => '2026-10',
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->get(route('owner.finances.index', ['month' => '2026-10']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Owner/Finances')
                ->where('summary.income', 100)
                ->where('summary.expenses', 30)
                ->where('summary.payroll_paid', 40)
                ->where('summary.payroll_pending', 15)
                ->where('summary.net_profit', 30)
                ->etc()
            );
    }

    public function test_attendance_consumes_a_session_pack_once_per_day_until_it_is_depleted(): void
    {
        [$teacher, $student, $schedule, $subscription] = $this->attendanceFixture(2);

        $this->mark($teacher, $schedule, $student, '2026-10-21', 'Καλή στάση.');
        $this->assertSame(1, $subscription->refresh()->remaining_sessions);
        $this->assertSame(1, LessonLog::query()->count());

        $this->mark($teacher, $schedule, $student, '2026-10-21', null);
        $this->assertSame(1, $subscription->refresh()->remaining_sessions);
        $this->assertSame(1, LessonLog::query()->count());

        $this->mark($teacher, $schedule, $student, '2026-10-22', null);
        $subscription->refresh();

        $this->assertSame(0, $subscription->remaining_sessions);
        $this->assertSame('depleted', $subscription->status->value);
    }

    public function test_attendance_does_not_decrement_an_unlimited_package(): void
    {
        [$teacher, $student, $schedule, $subscription] = $this->attendanceFixture(null);

        $this->mark($teacher, $schedule, $student, '2026-10-22', null);

        $this->assertNull($subscription->refresh()->remaining_sessions);
        $this->assertSame('active', $subscription->status->value);
    }

    public function test_student_sees_only_their_own_progress_notes(): void
    {
        [$teacher, $student, $schedule] = $this->attendanceFixture(4);
        $other = User::factory()->create(['role' => 'student']);

        $this->mark($teacher, $schedule, $student, '2026-10-22', 'Καλή στάση.');
        LessonLog::query()->create([
            'schedule_id' => $schedule->id,
            'student_id' => $other->id,
            'teacher_id' => $teacher->id,
            'performance_notes' => 'Μυστική σημείωση άλλου μαθητή',
        ]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Dashboard')
                ->has('lessonLogs', 1, fn (Assert $log) => $log
                    ->where('performance_notes', 'Καλή στάση.')
                    ->where('test_score', '9/10')
                    ->etc()
                )
            );
    }

    public function test_teacher_sees_payments_only_for_students_in_their_courses(): void
    {
        [$teacher, $student] = $this->attendanceFixture(3);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $otherStudent = User::factory()->create(['role' => 'student', 'name' => 'Κρυφός Μαθητής']);
        $otherCourse = Course::query()->create([
            'title' => 'Πιάνο',
            'price' => 40,
            'type' => 'private',
        ]);
        ClassSchedule::query()->create([
            'course_id' => $otherCourse->id,
            'teacher_id' => $otherTeacher->id,
            'day_of_week' => 3,
            'start_time' => '16:00',
            'end_time' => '16:45',
            'room' => 'Studio 2',
        ]);
        Enrollment::query()->create([
            'student_id' => $otherStudent->id,
            'course_id' => $otherCourse->id,
            'status' => 'active',
        ]);
        Payment::query()->create([
            'student_id' => $student->id,
            'amount' => 25,
            'type' => 'monthly_subscription',
            'status' => 'overdue',
            'due_date' => '2026-10-01',
        ]);
        Payment::query()->create([
            'student_id' => $otherStudent->id,
            'amount' => 999,
            'type' => 'private_session',
            'status' => 'overdue',
            'due_date' => '2026-10-01',
        ]);

        $this->actingAs($teacher)
            ->get(route('teacher.students.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('students', 1, fn (Assert $row) => $row
                    ->where('name', $student->name)
                    ->where('overdue_total', 25)
                    ->etc()
                )
            );
    }

    public function test_gap_scan_suggests_a_hole_of_at_least_two_hours(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [, $course] = $this->classWithTeacher();

        ClassSchedule::query()->create([
            'course_id' => $course->id,
            'teacher_id' => null,
            'day_of_week' => 2,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'room' => 'Α1',
        ]);
        ClassSchedule::query()->create([
            'course_id' => $course->id,
            'teacher_id' => null,
            'day_of_week' => 2,
            'start_time' => '14:00',
            'end_time' => '15:00',
            'room' => 'Α1',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.flex-suggestions.scan'))
            ->assertRedirect();

        $this->assertTrue(
            FlexSuggestion::query()
                ->where('target_user_id', $admin->id)
                ->where('content', 'like', '%Τρίτη (11:00–14:00)%')
                ->exists()
        );
    }

    /**
     * @return array{0: User, 1: Course}
     */
    private function classWithTeacher(): array
    {
        $teacher = User::factory()->create([
            'name' => 'Μαρία Παπαδοπούλου',
            'role' => 'teacher',
        ]);
        $course = Course::query()->create([
            'title' => 'Salsa',
            'price' => 50,
            'type' => 'group',
        ]);

        return [$teacher, $course];
    }

    /**
     * @return array{0: User, 1: User, 2: ClassSchedule, 3: StudentSubscription}
     */
    private function attendanceFixture(?int $sessions): array
    {
        [$teacher, $course] = $this->classWithTeacher();
        $student = User::factory()->create(['role' => 'student', 'name' => 'Άννα Βασιλείου']);
        $schedule = ClassSchedule::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => 2,
            'start_time' => '18:00',
            'end_time' => '19:30',
            'room' => 'Studio 1',
        ]);
        Enrollment::query()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);
        $package = Package::query()->create([
            'title' => $sessions === null ? 'Unlimited' : 'Πακέτο συνεδριών',
            'price' => 80,
            'duration_days' => 30,
            'sessions_count' => $sessions,
        ]);
        $subscription = StudentSubscription::query()->create([
            'student_id' => $student->id,
            'package_id' => $package->id,
            'remaining_sessions' => $sessions,
            'starts_at' => '2026-10-01',
            'expires_at' => '2026-12-31',
            'status' => 'active',
        ]);

        return [$teacher, $student, $schedule, $subscription];
    }

    private function mark(User $teacher, ClassSchedule $schedule, User $student, string $date, ?string $notes): void
    {
        $this->actingAs($teacher)->post(route('teacher.attendance.update', $schedule), [
            'date' => $date,
            'entries' => [[
                'student_id' => $student->id,
                'attended' => true,
                'performance_notes' => $notes,
                'test_title' => $notes ? 'Πρόοδος' : null,
                'test_score' => $notes ? '9/10' : null,
            ]],
        ])->assertRedirect();
    }
}
