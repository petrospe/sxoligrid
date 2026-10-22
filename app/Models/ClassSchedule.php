<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course_id', 'teacher_id', 'day_of_week', 'start_time', 'end_time', 'room', 'visible_on_landing'])]
class ClassSchedule extends Model
{
    protected function casts(): array
    {
        return [
            'visible_on_landing' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_schedule_teacher', 'class_schedule_id', 'teacher_id')
            ->orderBy('class_schedule_teacher.id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function lessonLogs(): HasMany
    {
        return $this->hasMany(LessonLog::class, 'schedule_id');
    }

    public function taughtBy(?Authenticatable $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($this->teacher_id === $user->id) {
            return true;
        }

        return $this->teachers()->whereKey($user->id)->exists();
    }

    /**
     * @param  Builder<ClassSchedule>  $query
     * @return Builder<ClassSchedule>
     */
    public function scopeForTeacher(Builder $query, int $teacherId): Builder
    {
        return $query->where(function (Builder $query) use ($teacherId): void {
            $query->where('teacher_id', $teacherId)
                ->orWhereHas('teachers', function (Builder $query) use ($teacherId): void {
                    $query->whereKey($teacherId);
                });
        });
    }
}
