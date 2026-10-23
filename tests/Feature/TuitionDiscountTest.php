<?php

namespace Tests\Feature;

use App\Models\BillingPolicy;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TuitionDiscountTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_prices_are_discounted_when_a_student_takes_several_courses(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Γιάννης Αλεξίου']);
        $salsa = $this->course('Salsa', ['monthly' => 40, 'drop_in' => 12], ['monthly', 'drop_in']);
        $english = $this->course('English', ['monthly' => 70, 'quarterly' => 190, 'semester' => 150], ['monthly', 'quarterly', 'semester']);

        Enrollment::query()->create([
            'student_id' => $student->id,
            'course_id' => $salsa->id,
            'status' => 'active',
            'billing_method' => 'monthly',
        ]);
        Enrollment::query()->create([
            'student_id' => $student->id,
            'course_id' => $english->id,
            'status' => 'active',
            'billing_method' => 'quarterly',
        ]);

        $this->putDiscounts($admin, [
            ['min_courses' => 3, 'percent' => 15],
            ['min_courses' => 2, 'percent' => 10],
        ])->assertRedirect();

        $this->actingAs($admin)
            ->get(route('admin.enrollments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('quotes.0.student', 'Γιάννης Αλεξίου')
                ->where('quotes.0.course_count', 2)
                ->where('quotes.0.percent', 10)
                ->where('quotes.0.monthly_due', 36)
                ->where('quotes.0.lines.0.billing_method', 'monthly')
                ->where('quotes.0.lines.0.amount', 36)
                ->where('quotes.0.lines.1.billing_method', 'quarterly')
                ->where('quotes.0.lines.1.amount', 190)
                ->etc()
            );

        $this->actingAs($admin)
            ->get(route('admin.payments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('quotes.0.lines.0.amount', 36)
                ->etc()
            );
    }

    public function test_drop_in_does_not_count_toward_the_monthly_discount(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Άννα']);
        $salsa = $this->course('Salsa', ['monthly' => 40, 'drop_in' => 12], ['monthly', 'drop_in']);
        $piano = $this->course('Πιάνο', ['drop_in' => 12], ['drop_in']);

        Enrollment::query()->create([
            'student_id' => $student->id,
            'course_id' => $salsa->id,
            'status' => 'active',
            'billing_method' => 'monthly',
        ]);
        Enrollment::query()->create([
            'student_id' => $student->id,
            'course_id' => $piano->id,
            'status' => 'active',
            'billing_method' => 'drop_in',
        ]);
        $this->putDiscounts($admin, [
            ['min_courses' => 2, 'percent' => 10],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.enrollments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('quotes.0.course_count', 1)
                ->where('quotes.0.percent', 0)
                ->where('quotes.0.monthly_due', 40)
                ->etc()
            );
    }

    public function test_three_monthly_courses_use_the_higher_tier(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Μαθητής']);

        foreach ([['Α', 40], ['Β', 70], ['Γ', 20]] as [$title, $price]) {
            $course = $this->course($title, ['monthly' => $price], ['monthly']);
            Enrollment::query()->create([
                'student_id' => $student->id,
                'course_id' => $course->id,
                'status' => 'active',
                'billing_method' => 'monthly',
            ]);
        }

        $this->putDiscounts($admin, [
            ['min_courses' => 2, 'percent' => 10],
            ['min_courses' => 3, 'percent' => 15],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.enrollments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('quotes.0.percent', 15)
                ->where('quotes.0.monthly_due', 110.5)
                ->etc()
            );
    }

    public function test_an_enrollment_can_only_use_a_method_the_course_offers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('Salsa', ['monthly' => 40], ['monthly']);

        $this->actingAs($admin)
            ->post(route('admin.enrollments.store'), [
                'student_id' => $student->id,
                'course_id' => $course->id,
                'status' => 'active',
                'billing_method' => 'drop_in',
            ])
            ->assertSessionHasErrors('billing_method');

        $this->assertSame(0, Enrollment::query()->count());
    }

    public function test_discount_tiers_are_stored_in_ascending_order_and_can_be_cleared(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->putDiscounts($admin, [
            ['min_courses' => 2, 'percent' => 10],
            ['min_courses' => 2, 'percent' => 20],
        ])->assertSessionHasErrors('discounts.0.min_courses');

        $this->putDiscounts($admin, [
            ['min_courses' => 2, 'percent' => 10],
        ])->assertRedirect();

        $this->putDiscounts($admin, [])->assertRedirect();

        $this->assertSame([], BillingPolicy::current()->fresh()->tiers());
    }

    /**
     * @param  array<string, float>  $prices
     * @param  list<string>  $methods
     */
    private function course(string $title, array $prices, array $methods): Course
    {
        $first = $methods[0];

        return Course::query()->create([
            'title' => $title,
            'price' => $prices[$first],
            'prices' => $prices,
            'type' => 'group',
            'billing_methods' => $methods,
        ]);
    }

    /**
     * @param  list<array{min_courses: int, percent: float}>  $discounts
     */
    private function putDiscounts(User $admin, array $discounts): TestResponse
    {
        return $this->actingAs($admin)->put(route('admin.billing-discounts.update'), [
            'discounts' => $discounts,
        ]);
    }
}
