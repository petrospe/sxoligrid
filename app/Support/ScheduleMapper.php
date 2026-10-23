<?php

namespace App\Support;

use App\Models\ClassSchedule;
use App\Models\User;
use Illuminate\Support\Collection;

final class ScheduleMapper
{
    /**
     * Public weekly grid. Teacher identity is never included.
     *
     * @param  Collection<int, ClassSchedule>  $schedules
     * @return list<array<string, mixed>>
     */
    public static function publicList(Collection $schedules): array
    {
        return $schedules->map(fn (ClassSchedule $schedule): array => [
            'id' => $schedule->id,
            'day_of_week' => $schedule->day_of_week,
            'start_time' => Week::clock($schedule->start_time),
            'end_time' => Week::clock($schedule->end_time),
            'room' => $schedule->room,
            'course' => [
                'title' => $schedule->course->title,
                'type' => $schedule->course->type->value,
            ],
            'section' => [
                'name' => $schedule->section?->name,
            ],
        ])->values()->all();
    }

    /**
     * @param  Collection<int, ClassSchedule>  $schedules
     * @return list<array<string, mixed>>
     */
    public static function withTeacher(Collection $schedules): array
    {
        return $schedules->map(function (ClassSchedule $schedule): array {
            $teachers = self::teachers($schedule);
            $row = self::publicList(collect([$schedule]))[0];
            $row['course_id'] = $schedule->course_id;
            $row['section_id'] = $schedule->section_id;
            $row['teacher_id'] = $teachers[0]['id'] ?? $schedule->teacher_id;
            $row['teacher_ids'] = array_column($teachers, 'id');
            $row['teacher'] = $teachers[0] ?? null;
            $row['teachers'] = $teachers;
            $row['visible_on_landing'] = (bool) $schedule->visible_on_landing;

            return $row;
        })->values()->all();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private static function teachers(ClassSchedule $schedule): array
    {
        $assigned = $schedule->relationLoaded('teachers')
            ? $schedule->teachers
            : $schedule->teachers()->get();

        if ($assigned->isEmpty() && $schedule->teacher !== null) {
            $assigned = collect([$schedule->teacher]);
        }

        return $assigned->map(fn (User $teacher): array => [
            'id' => $teacher->id,
            'name' => $teacher->name,
        ])->values()->all();
    }
}
