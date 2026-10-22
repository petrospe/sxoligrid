<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'phone', 'can_view_finances'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'can_view_finances' => 'boolean',
        ];
    }

    public function isOwner(): bool
    {
        return $this->role === UserRole::Owner;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isTeacher(): bool
    {
        return $this->role === UserRole::Teacher;
    }

    public function isStudent(): bool
    {
        return $this->role === UserRole::Student;
    }

    public function managesSchool(): bool
    {
        return $this->isOwner() || $this->isAdmin();
    }

    public function canViewFinances(): bool
    {
        return $this->isOwner() || $this->can_view_finances;
    }

    public function taughtSchedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'teacher_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'student_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'student_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(StudentSubscription::class, 'student_id');
    }

    public function privateLessonsAsStudent(): HasMany
    {
        return $this->hasMany(PrivateLesson::class, 'student_id');
    }

    public function privateLessonsAsTeacher(): HasMany
    {
        return $this->hasMany(PrivateLesson::class, 'teacher_id');
    }

    public function lessonLogs(): HasMany
    {
        return $this->hasMany(LessonLog::class, 'student_id');
    }

    public function flexSuggestions(): HasMany
    {
        return $this->hasMany(FlexSuggestion::class, 'target_user_id');
    }

    public function eventParticipants(): HasMany
    {
        return $this->hasMany(EventParticipant::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(TeacherPayroll::class, 'teacher_id');
    }
}
