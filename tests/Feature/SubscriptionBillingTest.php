<?php

namespace Tests\Feature;

use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\StudentSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SubscriptionBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_course_only_records_who_teaches_and_whether_it_is_private(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.courses.store'), [
                'title' => 'Αγγλικά D senior',
                'description' => 'Τμήμα',
                'type' => 'group',
            ])
            ->assertRedirect(route('admin.courses.index'));

        $course = Course::query()->first();
        $this->assertNotNull($course);
        $this->assertSame('group', $course->type->value);
        $this->assertArrayNotHasKey('price', $course->getAttributes());
        $this->assertArrayNotHasKey('billing_methods', $course->getAttributes());

        $this->actingAs($admin)
            ->get(route('admin.courses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('courses.0.title', 'Αγγλικά D senior')
                ->where('courses.0.type', 'group')
                ->where('types', ['group', 'private'])
                ->missing('billing_methods')
                ->missing('discounts')
                ->etc()
            );

        $this->actingAs($admin)
            ->post(route('admin.courses.store'), [
                'title' => 'Πιάνο',
                'description' => null,
                'type' => 'drop_in',
            ])
            ->assertSessionHasErrors('type');
    }

    public function test_each_course_lists_its_students_and_teachers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $maria = User::factory()->create(['role' => 'teacher', 'name' => 'Μαρία Παπαδοπούλου']);
        $kostas = User::factory()->create(['role' => 'teacher', 'name' => 'Κώστας Δημητρίου']);
        $giannis = User::factory()->create(['role' => 'student', 'name' => 'Γιάννης Αλεξίου']);
        $paused = User::factory()->create(['role' => 'student', 'name' => 'Νίκος Παύση']);
        $anna = User::factory()->create(['role' => 'student', 'name' => 'Άννα Βασιλείου']);
        $english = Course::query()->create(['title' => 'Αγγλικά D senior', 'type' => 'group']);
        $salsa = Course::query()->create(['title' => 'Salsa', 'type' => 'group']);

        $monday = ClassSchedule::query()->create([
            'course_id' => $english->id,
            'teacher_id' => $maria->id,
            'day_of_week' => 1,
            'start_time' => '17:00',
            'end_time' => '18:30',
            'room' => 'Α1',
        ]);
        $monday->teachers()->attach($kostas->id);
        ClassSchedule::query()->create([
            'course_id' => $english->id,
            'teacher_id' => $maria->id,
            'day_of_week' => 3,
            'start_time' => '17:00',
            'end_time' => '18:30',
            'room' => 'Α1',
        ]);
        Enrollment::query()->create(['student_id' => $giannis->id, 'course_id' => $english->id, 'status' => 'active']);
        Enrollment::query()->create(['student_id' => $paused->id, 'course_id' => $english->id, 'status' => 'paused']);
        Enrollment::query()->create(['student_id' => $anna->id, 'course_id' => $salsa->id, 'status' => 'active']);

        $this->actingAs($admin)
            ->get(route('admin.courses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('courses.0.title', 'Salsa')
                ->where('courses.0.sections.0.users', [[
                    'id' => $anna->id,
                    'name' => 'Άννα Βασιλείου',
                    'role' => 'student',
                    'status' => 'active',
                ]])
                ->where('courses.1.title', 'Αγγλικά D senior')
                ->where('courses.1.sections.0.users', [
                    [
                        'id' => $kostas->id,
                        'name' => 'Κώστας Δημητρίου',
                        'role' => 'teacher',
                        'status' => null,
                    ],
                    [
                        'id' => $maria->id,
                        'name' => 'Μαρία Παπαδοπούλου',
                        'role' => 'teacher',
                        'status' => null,
                    ],
                    [
                        'id' => $giannis->id,
                        'name' => 'Γιάννης Αλεξίου',
                        'role' => 'student',
                        'status' => 'active',
                    ],
                    [
                        'id' => $paused->id,
                        'name' => 'Νίκος Παύση',
                        'role' => 'student',
                        'status' => 'paused',
                    ],
                ])
            );
    }

    public function test_enrollment_connects_a_student_to_a_course_without_a_price(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::query()->create([
            'title' => 'Salsa',
            'type' => 'group',
        ]);
        $section = $course->sections()->create(['name' => 'Τρίτη 18:00']);

        $this->actingAs($admin)
            ->post(route('admin.enrollments.store'), [
                'student_id' => $student->id,
                'section_id' => $section->id,
                'status' => 'active',
            ])
            ->assertRedirect();

        $enrollment = Enrollment::query()->first();
        $this->assertNotNull($enrollment);
        $this->assertSame($student->id, $enrollment->student_id);
        $this->assertSame($section->id, $enrollment->section_id);
        $this->assertSame($course->id, $enrollment->course_id);
        $this->assertArrayNotHasKey('billing_method', $enrollment->getAttributes());
        $this->assertSame(0, StudentSubscription::query()->count());
    }

    public function test_a_subscription_is_a_period_total_and_installments_stay_payments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::query()->create([
            'title' => 'Αγγλικά D senior',
            'type' => 'group',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.subscriptions.store'), [
                'student_id' => $student->id,
                'course_id' => $course->id,
                'title' => 'Αγγλικά D senior 2026-2027',
                'total_amount' => 1800,
                'starts_at' => '2026-09-01',
                'ends_at' => '2027-05-31',
            ])
            ->assertRedirect();

        $subscription = StudentSubscription::query()->first();
        $this->assertNotNull($subscription);
        $this->assertSame($course->id, $subscription->course_id);
        $this->assertEquals(1800, (float) $subscription->total_amount);
        $this->assertSame('2026-09-01', $subscription->starts_at->toDateString());
        $this->assertSame('2027-05-31', $subscription->ends_at?->toDateString());
        $this->assertSame(0, Payment::query()->count());

        $this->actingAs($admin)
            ->post(route('admin.payments.store'), [
                'student_id' => $student->id,
                'subscription_id' => $subscription->id,
                'amount' => 200,
                'type' => 'installment',
                'status' => 'paid',
                'due_date' => '2026-09-05',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.payments.store'), [
                'student_id' => $student->id,
                'subscription_id' => $subscription->id,
                'amount' => 150,
                'type' => 'installment',
                'status' => 'pending',
                'due_date' => '2026-11-05',
            ])
            ->assertRedirect();

        $this->assertSame($course->id, Payment::query()->first()->course_id);

        $this->actingAs($admin)
            ->get(route('admin.subscriptions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('subscriptions.0.title', 'Αγγλικά D senior 2026-2027')
                ->where('subscriptions.0.total_amount', 1800)
                ->where('subscriptions.0.paid_amount', 200)
                ->where('subscriptions.0.remaining_amount', 1600)
                ->etc()
            );
    }

    public function test_a_gym_subscription_does_not_need_a_course(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($admin)
            ->post(route('admin.subscriptions.store'), [
                'student_id' => $student->id,
                'course_id' => '',
                'title' => 'Συνδρομή γυμναστηρίου',
                'total_amount' => 500,
                'starts_at' => '2026-10-01',
                'ends_at' => '2027-09-30',
            ])
            ->assertRedirect();

        $subscription = StudentSubscription::query()->first();
        $this->assertNotNull($subscription);
        $this->assertNull($subscription->course_id);
        $this->assertEquals(500, (float) $subscription->total_amount);
    }

    public function test_a_single_visit_is_a_payment_without_a_subscription(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::query()->create([
            'title' => 'Salsa',
            'type' => 'group',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.payments.store'), [
                'student_id' => $student->id,
                'course_id' => $course->id,
                'subscription_id' => '',
                'amount' => 12,
                'type' => 'drop_in',
                'status' => 'paid',
                'due_date' => '2026-10-23',
            ])
            ->assertRedirect();

        $payment = Payment::query()->first();
        $this->assertNotNull($payment);
        $this->assertNull($payment->subscription_id);
        $this->assertSame($course->id, $payment->course_id);
        $this->assertSame('drop_in', $payment->type->value);
        $this->assertSame(0, StudentSubscription::query()->count());
    }

    public function test_an_installment_must_belong_to_that_students_subscription(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $other = User::factory()->create(['role' => 'student']);
        $subscription = StudentSubscription::query()->create([
            'student_id' => $other->id,
            'course_id' => null,
            'title' => 'Συνδρομή γυμναστηρίου',
            'total_amount' => 500,
            'starts_at' => '2026-10-01',
            'ends_at' => '2027-09-30',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.payments.store'), [
                'student_id' => $student->id,
                'subscription_id' => '',
                'amount' => 100,
                'type' => 'installment',
                'status' => 'pending',
                'due_date' => '2026-10-05',
            ])
            ->assertSessionHasErrors('subscription_id');

        $this->actingAs($admin)
            ->post(route('admin.payments.store'), [
                'student_id' => $student->id,
                'subscription_id' => $subscription->id,
                'amount' => 100,
                'type' => 'installment',
                'status' => 'pending',
                'due_date' => '2026-10-05',
            ])
            ->assertSessionHasErrors('subscription_id');

        $this->assertSame(0, Payment::query()->count());
    }

    public function test_a_subscription_cannot_end_before_it_starts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($admin)
            ->post(route('admin.subscriptions.store'), [
                'student_id' => $student->id,
                'course_id' => '',
                'title' => 'Συνδρομή',
                'total_amount' => 500,
                'starts_at' => '2027-05-31',
                'ends_at' => '2026-09-01',
            ])
            ->assertSessionHasErrors('ends_at');

        $this->assertSame(0, StudentSubscription::query()->count());
    }
}
