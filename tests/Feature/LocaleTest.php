<?php

namespace Tests\Feature;

use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\FlexSuggestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_interface_defaults_to_greek_and_can_switch_to_english(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'el')
                ->where('ui.auth.submit', 'Είσοδος')
                ->where('ui.nav.schedule', 'Πρόγραμμα')
                ->etc()
            );

        $this->from(route('login'))
            ->post(route('locale.update'), ['locale' => 'en'])
            ->assertRedirect(route('login'))
            ->assertCookie('locale', 'en');

        $this->get(route('login'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'en')
                ->where('ui.auth.submit', 'Sign in')
                ->where('ui.nav.schedule', 'Schedule')
                ->etc()
            );
    }

    public function test_login_error_follows_the_active_language(): void
    {
        $this->withSession(['locale' => 'en'])
            ->post('/login', [
                'email' => 'missing@sxoligrid.test',
                'password' => 'wrong-password',
            ])
            ->assertSessionHasErrors([
                'email' => 'Those credentials are incorrect.',
            ]);
    }

    public function test_gap_text_follows_the_active_language(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = Course::query()->create([
            'title' => 'Salsa',
            'price' => 50,
            'type' => 'group',
        ]);

        foreach ([['10:00', '11:00'], ['14:00', '15:00']] as [$start, $end]) {
            ClassSchedule::query()->create([
                'course_id' => $course->id,
                'teacher_id' => null,
                'day_of_week' => 2,
                'start_time' => $start,
                'end_time' => $end,
                'room' => 'A1',
            ]);
        }

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->post(route('admin.flex-suggestions.scan'))
            ->assertRedirect();

        $this->assertTrue(
            FlexSuggestion::query()
                ->where('target_user_id', $admin->id)
                ->where('content', 'like', '%Tuesday (11:00–14:00)%')
                ->exists()
        );
    }

    public function test_an_unknown_locale_is_rejected(): void
    {
        $this->from(route('login'))
            ->post(route('locale.update'), ['locale' => 'fr'])
            ->assertSessionHasErrors('locale');

        $this->get(route('login'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'el')
                ->etc()
            );
    }
}
