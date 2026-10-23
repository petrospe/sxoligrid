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

class CoursePackageSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_course_price_is_shown_on_its_package(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.courses.store'), [
                'title' => 'English B2',
                'description' => null,
                'type' => 'group',
                'billing_methods' => ['monthly', 'semester'],
                'prices' => ['monthly' => 40, 'semester' => 150],
            ])
            ->assertRedirect();

        $course = Course::query()->first();
        $semester = Package::query()->where('billing_method', 'semester')->first();

        $this->assertNotNull($course);
        $this->assertNotNull($semester);
        $this->assertSame($course->id, $semester->course_id);
        $this->assertEquals(150, $semester->price);
        $this->assertSame(180, $semester->duration_days);

        $this->actingAs($admin)
            ->get(route('admin.packages.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('course_packages', function ($rows) {
                    return $rows->contains(fn (array $row): bool => $row['course'] === 'English B2'
                        && $row['billing_method'] === 'semester'
                        && $row['price'] == 150);
                })
                ->etc()
            );

        $this->actingAs($admin)
            ->put(route('admin.courses.update', $course), [
                'title' => 'English B2',
                'description' => null,
                'type' => 'group',
                'billing_methods' => ['semester'],
                'prices' => ['semester' => 180],
            ])
            ->assertRedirect();

        $this->assertEquals(180, $semester->fresh()->price);
        $this->assertNull(Package::query()->where('billing_method', 'monthly')->first());

        $student = User::factory()->create(['role' => 'student']);
        (new SubscriptionAssigner)->assign($student, $semester->fresh(), '2026-10-01', PaymentStatus::Paid);

        $payment = Payment::query()->first();
        $this->assertNotNull($payment);
        $this->assertSame($course->id, $payment->course_id);
        $this->assertSame('semester', $payment->type->value);
        $this->assertEquals(180, $payment->amount);
    }
}
