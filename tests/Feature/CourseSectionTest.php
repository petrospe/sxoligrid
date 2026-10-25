<?php

namespace Tests\Feature;

use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CourseSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_course_can_have_many_sections_with_different_people_and_times(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $maria = User::factory()->create(['role' => 'teacher', 'name' => 'Μαρία Παπαδοπούλου']);
        $kostas = User::factory()->create(['role' => 'teacher', 'name' => 'Κώστας Δημητρίου']);
        $anna = User::factory()->create(['role' => 'student', 'name' => 'Άννα Βασιλείου']);
        $giannis = User::factory()->create(['role' => 'student', 'name' => 'Γιάννης Αλεξίου']);
        $course = Course::query()->create([
            'title' => 'Salsa advanced',
            'type' => 'group',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.courses.sections.store', $course), ['name' => 'Δευτέρα 19:00'])
            ->assertRedirect();
        $this->actingAs($admin)
            ->post(route('admin.courses.sections.store', $course), ['name' => 'Τετάρτη 21:00'])
            ->assertRedirect();

        $monday = CourseSection::query()->where('name', 'Δευτέρα 19:00')->first();
        $wednesday = CourseSection::query()->where('name', 'Τετάρτη 21:00')->first();
        $this->assertNotNull($monday);
        $this->assertNotNull($wednesday);
        $this->assertSame($course->id, $monday->course_id);
        $this->assertSame($course->id, $wednesday->course_id);

        $this->actingAs($admin)
            ->post(route('admin.enrollments.store'), [
                'student_id' => $anna->id,
                'section_id' => $monday->id,
                'status' => 'active',
            ])
            ->assertRedirect();
        $this->actingAs($admin)
            ->post(route('admin.enrollments.store'), [
                'student_id' => $giannis->id,
                'section_id' => $wednesday->id,
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.schedules.store'), [
                'section_id' => $monday->id,
                'teacher_ids' => [$maria->id],
                'day_of_week' => 1,
                'start_time' => '19:00',
                'end_time' => '20:30',
                'room' => 'Studio 1',
                'visible_on_landing' => false,
            ])
            ->assertRedirect(route('admin.schedules.index'));
        $this->actingAs($admin)
            ->post(route('admin.schedules.store'), [
                'section_id' => $wednesday->id,
                'teacher_ids' => [$kostas->id],
                'day_of_week' => 3,
                'start_time' => '21:00',
                'end_time' => '22:30',
                'room' => 'Studio 2',
                'visible_on_landing' => false,
            ])
            ->assertRedirect(route('admin.schedules.index'));

        $this->actingAs($admin)
            ->get(route('admin.courses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('courses.0.title', 'Salsa advanced')
                ->where('courses.0.sections.0.name', 'Δευτέρα 19:00')
                ->where('courses.0.sections.0.schedules.0.start_time', '19:00')
                ->where('courses.0.sections.0.schedules.0.end_time', '20:30')
                ->where('courses.0.sections.0.users', [
                    [
                        'id' => $maria->id,
                        'name' => 'Μαρία Παπαδοπούλου',
                        'role' => 'teacher',
                        'status' => null,
                    ],
                    [
                        'id' => $anna->id,
                        'name' => 'Άννα Βασιλείου',
                        'role' => 'student',
                        'status' => 'active',
                    ],
                ])
                ->where('courses.0.sections.1.name', 'Τετάρτη 21:00')
                ->where('courses.0.sections.1.schedules.0.day_of_week', 3)
                ->where('courses.0.sections.1.users', [
                    [
                        'id' => $kostas->id,
                        'name' => 'Κώστας Δημητρίου',
                        'role' => 'teacher',
                        'status' => null,
                    ],
                    [
                        'id' => $giannis->id,
                        'name' => 'Γιάννης Αλεξίου',
                        'role' => 'student',
                        'status' => 'active',
                    ],
                ])
            );

        $mondaySchedule = ClassSchedule::query()->where('section_id', $monday->id)->first();
        $this->assertNotNull($mondaySchedule);

        $this->actingAs($maria)
            ->get(route('teacher.attendance.edit', $mondaySchedule))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('students', 1)
                ->where('students.0.name', 'Άννα Βασιλείου')
            );

        $this->actingAs($kostas)
            ->get(route('teacher.students.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('students', 1)
                ->where('students.0.name', 'Γιάννης Αλεξίου')
            );
    }

    public function test_a_section_name_can_be_changed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = Course::query()->create([
            'title' => 'Salsa advanced',
            'type' => 'group',
        ]);
        $section = $course->sections()->create(['name' => 'Δευτέρα 19:00']);
        $other = Course::query()->create([
            'title' => 'Bachata',
            'type' => 'group',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.courses.sections.update', [$course, $section]), ['name' => 'Δευτέρα 20:00'])
            ->assertRedirect();

        $this->assertSame('Δευτέρα 20:00', $section->fresh()->name);

        $this->actingAs($admin)
            ->put(route('admin.courses.sections.update', [$other, $section]), ['name' => 'Λάθος μάθημα'])
            ->assertNotFound();

        $this->assertSame('Δευτέρα 20:00', $section->fresh()->name);
    }
}
