<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\Directory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_user_can_hold_every_role_and_open_each_area(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Μαρία Παπαδοπούλου']);

        $this->actingAs($owner)
            ->put(route('admin.users.update', $student), [
                'name' => $student->name,
                'email' => $student->email,
                'roles' => ['student', 'teacher', 'admin', 'owner'],
                'phone' => null,
            ])
            ->assertRedirect(route('admin.users.index'));

        $student->refresh();
        $this->assertSame(
            [UserRole::Owner, UserRole::Admin, UserRole::Teacher, UserRole::Student],
            $student->roleList(),
        );
        $this->assertTrue($student->canViewFinances());

        $this->actingAs($student)->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
        $this->actingAs($student)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($student)->get(route('teacher.dashboard'))->assertOk();
        $this->actingAs($student)->get(route('student.dashboard'))->assertOk();
        $this->actingAs($student)->get(route('admin.settings.show'))->assertOk();

        $this->assertSame([$student->id], array_column(Directory::options(UserRole::Student), 'id'));
        $this->assertSame([$student->id], array_column(Directory::options(UserRole::Teacher), 'id'));
    }

    public function test_front_desk_can_combine_teacher_and_student_but_not_grant_owner(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $person = User::factory()->create(['role' => 'student']);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $person), [
                'name' => $person->name,
                'email' => $person->email,
                'roles' => ['teacher', 'student'],
                'phone' => null,
            ])
            ->assertRedirect(route('admin.users.index'));

        $person->refresh();
        $this->assertTrue($person->isTeacher());
        $this->assertTrue($person->isStudent());
        $this->assertFalse($person->isAdmin());

        $this->actingAs($admin)
            ->put(route('admin.users.update', $person), [
                'name' => $person->name,
                'email' => $person->email,
                'roles' => ['teacher', 'owner'],
                'phone' => null,
            ])
            ->assertSessionHasErrors('roles.1');

        $this->assertFalse($person->refresh()->isOwner());
    }

    public function test_an_owner_cannot_remove_their_own_owner_role(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $this->actingAs($owner)
            ->put(route('admin.users.update', $owner), [
                'name' => $owner->name,
                'email' => $owner->email,
                'roles' => ['admin'],
                'phone' => null,
            ])
            ->assertSessionHasErrors('roles');

        $this->assertTrue($owner->refresh()->isOwner());
    }
}
