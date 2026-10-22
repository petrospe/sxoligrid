<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use PDO;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_admin_can_open_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'owner']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($admin)
            ->get(route('admin.settings.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Settings')
                ->where('demo_installed', false)
                ->etc()
            );

        $this->actingAs($owner)->get(route('admin.settings.show'))->assertForbidden();
        $this->actingAs($teacher)->get(route('admin.settings.show'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.settings.show'))->assertForbidden();
        $this->actingAs($owner)->post(route('admin.settings.demo.install'))->assertForbidden();
        $this->actingAs($owner)->delete(route('admin.settings.demo.remove'))->assertForbidden();
        $this->actingAs($owner)->get(route('admin.settings.backup'))->assertForbidden();
        $this->actingAs($owner)->post(route('admin.settings.restore'))->assertForbidden();
    }

    public function test_an_admin_can_back_up_and_restore_the_database(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Course::query()->create([
            'title' => 'Ιστορία',
            'description' => 'Μάθημα που πρέπει να επιστρέψει.',
            'price' => 30,
            'type' => 'group',
        ]);

        $download = $this->actingAs($admin)->get(route('admin.settings.backup'));
        $download->assertOk();
        $download->assertDownload();

        $snapshot = $download->baseResponse->getFile()->getPathname();
        $this->assertSame("SQLite format 3\0", file_get_contents($snapshot, false, null, 0, 16));
        $copy = tempnam(sys_get_temp_dir(), 'sxoligrid-restore-');
        $this->assertNotFalse($copy);
        copy($snapshot, $copy);

        Course::query()->where('title', 'Ιστορία')->delete();
        Course::query()->create([
            'title' => 'Μετά το αντίγραφο',
            'description' => 'Δεν ανήκει στο αντίγραφο.',
            'price' => 10,
            'type' => 'group',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.settings.restore'), [
                'backup' => new UploadedFile($copy, 'sxoligrid.sqlite', 'application/vnd.sqlite3', null, true),
            ])
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertTrue(Course::query()->where('title', 'Ιστορία')->exists());
        $this->assertFalse(Course::query()->where('title', 'Μετά το αντίγραφο')->exists());
        $this->assertTrue(User::query()->where('email', 'admin@sxoligrid.test')->exists());
    }

    public function test_restore_rejects_a_file_that_is_not_an_academy_backup(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Course::query()->create([
            'title' => 'Ιστορία',
            'description' => 'Μένει στη θέση του.',
            'price' => 30,
            'type' => 'group',
        ]);

        $empty = tempnam(sys_get_temp_dir(), 'sxoligrid-empty-');
        $this->assertNotFalse($empty);
        unlink($empty);
        new PDO('sqlite:'.$empty);

        $this->actingAs($admin)
            ->post(route('admin.settings.restore'), [
                'backup' => UploadedFile::fake()->createWithContent('notes.txt', 'όχι βάση'),
            ])
            ->assertSessionHasErrors('backup');

        $this->actingAs($admin)
            ->post(route('admin.settings.restore'), [
                'backup' => new UploadedFile($empty, 'empty.sqlite', 'application/vnd.sqlite3', null, true),
            ])
            ->assertSessionHasErrors('backup');

        $this->assertTrue(Course::query()->where('title', 'Ιστορία')->exists());
        $this->assertAuthenticatedAs($admin);
    }

    public function test_the_admin_can_install_and_remove_demo_data_without_losing_real_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.settings.demo.install'))
            ->assertRedirect();

        $this->assertTrue(User::query()->where('email', 'owner@sxoligrid.test')->exists());
        $this->assertTrue(Course::query()->where('title', 'Salsa')->exists());
        $this->assertSame(1, User::query()->where('email', 'admin@sxoligrid.test')->count());

        $this->actingAs($admin)
            ->post(route('admin.settings.demo.install'))
            ->assertSessionHasErrors('demo');

        $this->assertSame(1, Course::query()->where('title', 'Salsa')->count());

        Course::query()->create([
            'title' => 'Ιστορία',
            'description' => 'Καταχώριση της γραμματείας.',
            'price' => 25,
            'type' => 'group',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.settings.demo.remove'))
            ->assertRedirect();

        $this->assertFalse(User::query()->where('email', 'owner@sxoligrid.test')->exists());
        $this->assertFalse(User::query()->where('email', 'teacher@sxoligrid.test')->exists());
        $this->assertFalse(User::query()->where('email', 'student@sxoligrid.test')->exists());
        $this->assertFalse(Course::query()->where('title', 'Salsa')->exists());
        $this->assertTrue(Course::query()->where('title', 'Ιστορία')->exists());
        $this->assertTrue(User::query()->whereKey($admin->id)->exists());
        $this->assertSame(1, User::query()->where('email', 'admin@sxoligrid.test')->count());
    }
}
