<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'roles', 'phone', 'can_view_finances'])]
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
            'roles' => 'array',
            'can_view_finances' => 'boolean',
        ];
    }

    /**
     * Keeps `role` working for a single assignment. Stored users keep every selected role.
     *
     * @return Attribute<?UserRole, UserRole|string|null>
     */
    protected function role(): Attribute
    {
        return Attribute::make(
            get: fn (): ?UserRole => $this->roleList()[0] ?? null,
            set: fn (UserRole|string|null $value): array => [
                'roles' => json_encode(self::normalizeRoles($value === null ? [] : [$value])),
            ],
        );
    }

    /**
     * @param  list<UserRole|string>  $roles
     * @return list<string>
     */
    public static function normalizeRoles(array $roles): array
    {
        $selected = [];

        foreach ($roles as $role) {
            $value = $role instanceof UserRole ? $role->value : (string) $role;

            if (UserRole::tryFrom($value) instanceof UserRole) {
                $selected[$value] = true;
            }
        }

        return array_values(array_filter(
            array_map(fn (UserRole $role): string => $role->value, UserRole::cases()),
            fn (string $role): bool => isset($selected[$role]),
        ));
    }

    /**
     * @return list<UserRole>
     */
    public function roleList(): array
    {
        return array_values(array_filter(array_map(
            fn (string $role): ?UserRole => UserRole::tryFrom($role),
            self::normalizeRoles($this->roles ?? []),
        )));
    }

    /**
     * @return list<string>
     */
    public function roleValues(): array
    {
        return array_map(fn (UserRole $role): string => $role->value, $this->roleList());
    }

    public function hasRole(UserRole|string $role): bool
    {
        $value = $role instanceof UserRole ? $role->value : $role;

        return in_array($value, $this->roleValues(), true);
    }

    public function hasAny(UserRole ...$roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  UserRole|string|array<int, UserRole|string>  $roles
     */
    public function scopeWithRole(Builder $query, UserRole|string|array $roles): void
    {
        $values = array_map(
            fn (UserRole|string $role): string => $role instanceof UserRole ? $role->value : $role,
            is_array($roles) ? $roles : [$roles],
        );

        $query->where(function (Builder $query) use ($values): void {
            foreach ($values as $value) {
                $query->orWhereJsonContains('roles', $value);
            }
        });
    }

    public function isOwner(): bool
    {
        return $this->hasRole(UserRole::Owner);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin);
    }

    public function isTeacher(): bool
    {
        return $this->hasRole(UserRole::Teacher);
    }

    public function isStudent(): bool
    {
        return $this->hasRole(UserRole::Student);
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
