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
                'price' => 15,
                'type' => 'group',
                'billing_methods' => ['drop_in', 'monthly'],
            ])
            ->assertRedirect(route('admin.courses.index'));

        $course = Course::query()->first();
        $this->assertNotNull($course);
        $this->assertSame('group', $course->type->value);
        $this->assertSame(['monthly', 'drop_in'], $course->billing_methods);

        $this->actingAs($admin)
            ->get(route('admin.courses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('courses.0.billing_methods', ['monthly', 'drop_in'])
                ->where('billing_methods', ['monthly', 'quarterly', 'offer_package', 'drop_in'])
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
                'price' => 15,
                'type' => 'drop_in',
                'billing_methods' => ['drop_in'],
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
                'price' => 15,
                'type' => 'group',
                'billing_methods' => [],
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

        $assigner->assign($student, $sessions, '2026-10-01', PaymentStatus::Paid);
        $assigner->assign($student, $quarter, '2026-10-01', PaymentStatus::Paid);

        $this->assertSame(
            ['offer_package', 'quarterly'],
            Payment::query()->orderBy('id')->pluck('type')->map(fn ($type) => $type->value)->all(),
        );
    }
}
