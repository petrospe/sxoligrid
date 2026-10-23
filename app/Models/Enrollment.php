<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'course_id', 'section_id', 'status'])]
class Enrollment extends Model
{
    protected static function booted(): void
    {
        static::saving(function (Enrollment $enrollment): void {
            if ($enrollment->section_id !== null) {
                $section = CourseSection::query()->find($enrollment->section_id);

                if ($section instanceof CourseSection) {
                    $enrollment->course_id = $section->course_id;
                }

                return;
            }

            $course = $enrollment->course_id === null ? null : Course::query()->find($enrollment->course_id);

            if ($course instanceof Course) {
                $enrollment->section_id = $course->defaultSection()->id;
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'section_id');
    }
}
