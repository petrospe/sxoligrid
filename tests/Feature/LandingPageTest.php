<?php

namespace Tests\Feature;

use App\Models\LandingProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_public_page_shows_the_landing_text_for_the_active_language(): void
    {
        $this->get(route('schedule.public'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Schedule')
                ->where('landing.name', 'SxoliGrid')
                ->where('landing.title', 'Σύστημα διαχείρισης σχολών και στούντιο')
                ->where('landing.subtitle', 'Σχολές · στούντιο · φροντιστήρια')
                ->where('landing.description', 'Δημόσια προβολή μαθημάτων, αιθουσών και ωρών. Τα ονόματα των καθηγητών δεν εμφανίζονται στο πρόγραμμα.')
                ->where('landing.details', 'Μαθήματα σε τμήματα και ιδιαίτερα, με ωράριο ανά ημέρα και αίθουσα. Η πληρωμή είναι συνδρομή μιας περιόδου ή μία φορά ανά μάθημα.')
                ->where('landing.logo_url', asset('branding/logo.png'))
                ->etc()
            );

        $this->withSession(['locale' => 'en'])
            ->get(route('schedule.public'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('landing.name', 'SxoliGrid')
                ->where('landing.title', 'Academy & Studio Management System')
                ->where('landing.subtitle', 'Schools · studios · tutoring')
                ->where('landing.description', 'Public view of courses, rooms, and times. Teacher names are not shown on the schedule.')
                ->where('landing.details', 'Group and private classes, with a timetable by day and room. Payment is a subscription for a period, or once per class.')
                ->etc()
            );
    }

    public function test_only_the_admin_can_edit_the_landing_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'owner']);
        $payload = $this->payload();

        $this->actingAs($admin)
            ->get(route('admin.settings.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('landing.el.name', 'SxoliGrid')
                ->where('landing.en.title', 'Academy & Studio Management System')
                ->where('landing.logo_url', asset('branding/logo.png'))
                ->etc()
            );

        $this->actingAs($owner)
            ->post(route('admin.settings.landing.update'), $payload)
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.settings.landing.update'), $payload)
            ->assertRedirect(route('admin.settings.show'));

        $this->get(route('schedule.public'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('landing.name', 'Νέα σχολή')
                ->where('landing.title', 'Τίτλος')
                ->where('landing.details', 'Λεπτομέρειες στα ελληνικά')
                ->etc()
            );

        $this->withSession(['locale' => 'en'])
            ->get(route('schedule.public'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('landing.name', 'New school')
                ->where('landing.title', 'Title')
                ->where('landing.subtitle', 'Subtitle')
                ->etc()
            );
    }

    public function test_the_admin_can_replace_the_logo_without_losing_it_on_the_next_text_save(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.settings.landing.update'), [
                ...$this->payload(),
                'logo' => UploadedFile::fake()->image('logo.png', 800, 400),
            ])
            ->assertRedirect(route('admin.settings.show'));

        $profile = LandingProfile::query()->first();
        $this->assertNotNull($profile);
        $this->assertNotNull($profile->logo_path);
        Storage::disk('public')->assertExists($profile->logo_path);

        $this->get(route('schedule.public'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('landing.logo_url', Storage::disk('public')->url($profile->logo_path))
                ->etc()
            );

        $this->actingAs($admin)
            ->post(route('admin.settings.landing.update'), $this->payload())
            ->assertRedirect(route('admin.settings.show'));

        $this->assertSame($profile->logo_path, $profile->fresh()?->logo_path);
    }

    public function test_landing_text_is_required_in_both_languages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.settings.landing.update'), [
                'el' => ['name' => ''],
                'en' => ['name' => 'School'],
            ])
            ->assertSessionHasErrors(['el.name', 'el.title', 'en.title']);
    }

    /**
     * @return array{el: array<string, string>, en: array<string, string>}
     */
    private function payload(): array
    {
        return [
            'el' => [
                'name' => 'Νέα σχολή',
                'title' => 'Τίτλος',
                'subtitle' => 'Υπότιτλος',
                'description' => 'Περιγραφή στα ελληνικά',
                'details' => 'Λεπτομέρειες στα ελληνικά',
            ],
            'en' => [
                'name' => 'New school',
                'title' => 'Title',
                'subtitle' => 'Subtitle',
                'description' => 'Description in English',
                'details' => 'Details in English',
            ],
        ];
    }
}
