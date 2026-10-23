<?php

namespace App\Models;

use App\Enums\CourseType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'type'])]
class Course extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CourseType::class,
        ];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(CourseSection::class);
    }

    public function defaultSection(): CourseSection
    {
        $existing = $this->sections()->orderBy('id')->first();

        if ($existing instanceof CourseSection) {
            return $existing;
        }

        return $this->sections()->create(['name' => $this->title]);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(StudentSubscription::class);
    }
}
