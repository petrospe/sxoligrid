<?php

namespace Tests\Feature;

use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TimetableTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_in_visitors_do_not_get_the_landing_introduction(): void
    {
        $this->get(route('schedule.public'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('show_landing', true)
                ->etc()
            );

        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->get(route('schedule.public'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('show_landing', false)
                ->etc()
            );
    }

    public function test_the_public_schedule_lists_only_slots_chosen_for_the_landing_page(): void
    {
        $course = Course::query()->create([
            'title' => 'Salsa',
            'price' => 50,
            'type' => 'group',
        ]);
        ClassSchedule::query()->create([
            'course_id' => $course->id,
            'day_of_week' => 2,
            'start_time' => '16:00',
            'end_time' => '17:00',
            'room' => 'Studio 1',
            'visible_on_landing' => true,
        ]);
        ClassSchedule::query()->create([
            'course_id' => $course->id,
            'day_of_week' => 2,
            'start_time' => '18:00',
            'end_time' => '19:00',
            'room' => 'Studio 2',
            'visible_on_landing' => false,
        ]);

        $this->get(route('schedule.public'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('schedules', 1, fn (Assert $schedule) => $schedule
                    ->where('room', 'Studio 1')
                    ->missing('visible_on_landing')
                    ->etc()
                )
            );
    }

    public function test_a_class_can_have_several_teachers_and_stay_off_the_landing_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $maria = User::factory()->create(['role' => 'teacher', 'name' => 'Μαρία Παπαδοπούλου']);
        $kostas = User::factory()->create(['role' => 'teacher', 'name' => 'Κώστας Δημητρίου']);
        $other = User::factory()->create(['role' => 'teacher', 'name' => 'Άλλος']);
        $course = Course::query()->create([
            'title' => 'Salsa',
            'price' => 50,
            'type' => 'group',
        ]);
        $section = $course->sections()->create(['name' => 'Τρίτη 18:00']);

        $this->actingAs($admin)
            ->post(route('admin.schedules.store'), [
                'section_id' => $section->id,
                'teacher_ids' => [$maria->id, $kostas->id],
                'day_of_week' => 2,
                'start_time' => '18:00',
                'end_time' => '19:30',
                'room' => 'Studio 1',
                'visible_on_landing' => false,
            ])
            ->assertRedirect(route('admin.schedules.index'));

        $schedule = ClassSchedule::query()->first();
        $this->assertNotNull($schedule);
        $this->assertFalse($schedule->visible_on_landing);
        $this->assertSame($maria->id, $schedule->teacher_id);
        $this->assertEqualsCanonicalizing(
            [$maria->id, $kostas->id],
            $schedule->teachers()->pluck('users.id')->all(),
        );

        $this->get(route('schedule.public'))
            ->assertInertia(fn (Assert $page) => $page->has('schedules', 0)->etc());

        $this->actingAs($admin)
            ->get(route('admin.schedules.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('schedules.0.visible_on_landing', false)
                ->where('schedules.0.teachers', [
                    ['id' => $maria->id, 'name' => 'Μαρία Παπαδοπούλου'],
                    ['id' => $kostas->id, 'name' => 'Κώστας Δημητρίου'],
                ])
                ->etc()
            );

        $this->actingAs($kostas)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('schedules.0.course.title', 'Salsa')
                ->etc()
            );

        $this->actingAs($kostas)
            ->get(route('teacher.attendance.edit', $schedule))
            ->assertOk();

        $this->actingAs($other)
            ->get(route('teacher.attendance.edit', $schedule))
            ->assertForbidden();
    }
}
