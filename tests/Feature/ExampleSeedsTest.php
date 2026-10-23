<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Payment;
use App\Models\StudentSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExampleSeedsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_offers_the_general_example_and_four_school_examples(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.settings.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('examples', [
                    ['key' => 'general', 'installed' => false],
                    ['key' => 'dance', 'installed' => false],
                    ['key' => 'fitness', 'installed' => false],
                    ['key' => 'conservatory', 'installed' => false],
                    ['key' => 'tutoring', 'installed' => false],
                ])
            );
    }

    public function test_the_dance_example_has_two_salsa_sections_and_a_single_visit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.settings.demo.install'), ['example' => 'dance'])
            ->assertRedirect();

        $course = Course::query()->where('title', 'Salsa advanced')->first();
        $this->assertNotNull($course);
        $sections = CourseSection::query()->where('course_id', $course->id)->orderBy('name')->get();
        $this->assertSame(['Δευτέρα 19:00', 'Τετάρτη 21:00'], $sections->pluck('name')->all());
        $this->assertNotSame(
            $sections[0]->schedules()->value('teacher_id'),
            $sections[1]->schedules()->value('teacher_id'),
        );
        $this->assertNotSame(
            $sections[0]->enrollments()->value('student_id'),
            $sections[1]->enrollments()->value('student_id'),
        );
        $this->assertTrue(Payment::query()->where('type', 'drop_in')->where('course_id', $course->id)->exists());
        $this->assertFalse(Course::query()->where('title', 'Salsa')->exists());
    }

    public function test_the_fitness_example_is_a_yearly_membership_without_a_course(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.settings.demo.install'), ['example' => 'fitness'])
            ->assertRedirect();

        $membership = StudentSubscription::query()->where('title', '12 μήνες fitness')->first();
        $this->assertNotNull($membership);
        $this->assertNull($membership->course_id);
        $this->assertSame('500.00', $membership->total_amount);
        $this->assertSame('2026-10-01', $membership->starts_at->toDateString());
        $this->assertSame('2027-09-30', $membership->ends_at->toDateString());
        $this->assertTrue(Course::query()->where('title', 'Yoga')->exists());
        $this->assertSame(2, CourseSection::query()->whereIn('course_id', Course::query()->where('title', 'Yoga')->pluck('id'))->count());
    }

    public function test_the_conservatory_example_mixes_a_private_lesson_and_group_theory(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.settings.demo.install'), ['example' => 'conservatory'])
            ->assertRedirect();

        $this->assertSame('private', Course::query()->where('title', 'Πιάνο ωδείου')->firstOrFail()->type->value);
        $this->assertSame('group', Course::query()->where('title', 'Θεωρία της μουσικής')->firstOrFail()->type->value);
        $this->assertSame(2, CourseSection::query()->whereIn(
            'course_id',
            Course::query()->where('title', 'Θεωρία της μουσικής')->pluck('id'),
        )->count());
        $piano = StudentSubscription::query()->where('title', 'Πιάνο 2026-2027')->first();
        $this->assertNotNull($piano);
        $this->assertSame('1200.00', $piano->total_amount);
    }

    public function test_the_tutoring_example_is_a_school_year_with_two_english_sections(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.settings.demo.install'), ['example' => 'tutoring'])
            ->assertRedirect();

        $year = StudentSubscription::query()->where('title', 'Αγγλικά D senior 2026-2027')->first();
        $this->assertNotNull($year);
        $this->assertSame('1800.00', $year->total_amount);
        $this->assertSame('2026-09-01', $year->starts_at->toDateString());
        $this->assertSame('2027-05-31', $year->ends_at->toDateString());
        $this->assertSame(2, CourseSection::query()->where('course_id', $year->course_id)->count());
        $this->assertTrue(Payment::query()->where('subscription_id', $year->id)->where('status', 'paid')->where('amount', 200)->exists());
        $this->assertTrue(Payment::query()->where('subscription_id', $year->id)->where('status', 'pending')->where('amount', 150)->exists());
    }

    public function test_one_example_can_be_removed_while_another_and_real_records_stay(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Course::query()->create([
            'title' => 'Ιστορία',
            'description' => 'Καταχώριση της γραμματείας.',
            'type' => 'group',
        ]);

        $this->actingAs($admin)->post(route('admin.settings.demo.install'), ['example' => 'dance'])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.settings.demo.install'), ['example' => 'tutoring'])->assertRedirect();
        $this->actingAs($admin)
            ->post(route('admin.settings.demo.install'), ['example' => 'dance'])
            ->assertSessionHasErrors('demo');

        $this->actingAs($admin)
            ->delete(route('admin.settings.demo.remove'), ['example' => 'dance'])
            ->assertRedirect();

        $this->assertFalse(Course::query()->where('title', 'Salsa advanced')->exists());
        $this->assertFalse(User::query()->where('email', 'dance-student@sxoligrid.test')->exists());
        $this->assertTrue(Course::query()->where('title', 'Αγγλικά D senior')->exists());
        $this->assertTrue(User::query()->where('email', 'tutor-student@sxoligrid.test')->exists());
        $this->assertTrue(Course::query()->where('title', 'Ιστορία')->exists());
        $this->assertTrue(User::query()->whereKey($admin->id)->exists());
    }
}
