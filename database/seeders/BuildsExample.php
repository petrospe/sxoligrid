<?php

namespace Database\Seeders;

use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

trait BuildsExample
{
    protected function person(string $name, string $email, string $role, string $phone, bool $finances = false): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'phone' => $phone,
            'can_view_finances' => $finances,
            'email_verified_at' => now(),
        ]);
    }

    protected function course(string $title, string $description, string $type): Course
    {
        return Course::query()->create([
            'title' => $title,
            'description' => $description,
            'type' => $type,
        ]);
    }

    protected function meet(Course $course, string $section, User $teacher, int $day, string $start, string $end, string $room): ClassSchedule
    {
        $group = $course->sections()->create(['name' => $section]);

        return ClassSchedule::query()->create([
            'section_id' => $group->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => $day,
            'start_time' => $start,
            'end_time' => $end,
            'room' => $room,
        ]);
    }

    protected function enroll(User $student, ClassSchedule $schedule): void
    {
        Enrollment::query()->create([
            'student_id' => $student->id,
            'section_id' => $schedule->section_id,
            'status' => 'active',
        ]);
    }
}
