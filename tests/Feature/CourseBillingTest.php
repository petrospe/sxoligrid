<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use App\Services\SubscriptionAssigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CourseBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_course_can_be_paid_monthly_quarterly_by_package_or_drop_in(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.courses.store'), [
                'title' => 'Salsa',
                'description' => 'Ομαδικό',
                'type' => 'group',
                'billing_methods' => ['drop_in', 'monthly', 'semester'],
                'prices' => [
                    'drop_in' => 12,
                    'monthly' => 40,
                    'semester' => 150,
                    'quarterly' => 999,
                ],
            ])
            ->assertRedirect(route('admin.courses.index'));

        $course = Course::query()->first();
        $this->assertNotNull($course);
        $this->assertSame('group', $course->type->value);
        $this->assertSame(['monthly', 'semester', 'drop_in'], $course->billing_methods);
        $this->assertEquals(40, $course->priceFor('monthly'));
        $this->assertEquals(150, $course->priceFor('semester'));
        $this->assertEquals(12, $course->priceFor('drop_in'));
        $this->assertNull($course->priceFor('quarterly'));
        $this->assertEquals(40, $course->price);

        $this->actingAs($admin)
            ->get(route('admin.courses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('courses.0.billing_methods', ['monthly', 'semester', 'drop_in'])
                ->where('courses.0.prices.monthly', 40)
                ->where('courses.0.prices.drop_in', 12)
                ->where('courses.0.prices.semester', 150)
                ->where('billing_methods', ['monthly', 'quarterly', 'semester', 'offer_package', 'drop_in'])
                ->where('types', ['group', 'private'])
                ->etc()
            );
    }

    public function test_drop_in_is_rejected_as_a_course_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.courses.store'), [
                'title' => 'Salsa',
                'description' => null,
                'type' => 'drop_in',
                'billing_methods' => ['drop_in'],
                'prices' => ['drop_in' => 15],
            ])
            ->assertSessionHasErrors('type');

        $this->assertSame(0, Course::query()->count());
    }

    public function test_a_course_needs_at_least_one_billing_method(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.courses.store'), [
                'title' => 'Salsa',
                'description' => null,
                'type' => 'group',
                'billing_methods' => [],
                'prices' => [],
            ])
            ->assertSessionHasErrors('billing_methods');
    }

    public function test_session_packs_and_long_durations_are_not_recorded_as_drop_in(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $assigner = new SubscriptionAssigner;

        $sessions = Package::query()->create([
            'title' => 'Πακέτο 10 μαθημάτων',
            'price' => 150,
            'duration_days' => 90,
            'sessions_count' => 10,
        ]);
        $quarter = Package::query()->create([
            'title' => 'Τρίμηνο',
            'price' => 180,
            'duration_days' => 90,
            'sessions_count' => null,
        ]);

        $semester = Package::query()->create([
            'title' => 'Εξάμηνο',
            'price' => 300,
            'duration_days' => 180,
            'sessions_count' => null,
        ]);

        $assigner->assign($student, $sessions, '2026-10-01', PaymentStatus::Paid);
        $assigner->assign($student, $quarter, '2026-10-01', PaymentStatus::Paid);
        $assigner->assign($student, $semester, '2026-10-01', PaymentStatus::Paid);

        $this->assertSame(
            ['offer_package', 'quarterly', 'semester'],
            Payment::query()->orderBy('id')->pluck('type')->map(fn ($type) => $type->value)->all(),
        );
    }

    public function test_a_selected_billing_method_needs_its_own_price(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.courses.store'), [
                'title' => 'Salsa',
                'description' => null,
                'type' => 'group',
                'billing_methods' => ['monthly', 'drop_in'],
                'prices' => ['monthly' => 40],
            ])
            ->assertSessionHasErrors('prices.drop_in');

        $this->assertSame(0, Course::query()->count());
    }
}
