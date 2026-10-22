<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingProfile;
use App\Services\DatabaseSnapshot;
use App\Services\DemoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SettingsController extends Controller
{
    public function show(DemoData $demo): Response
    {
        return Inertia::render('Admin/Settings', [
            'demo_installed' => $demo->installed(),
            'landing' => LandingProfile::current()->formState(),
        ]);
    }

    public function updateLanding(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'el.name' => ['required', 'string', 'max:120'],
            'el.title' => ['required', 'string', 'max:160'],
            'el.subtitle' => ['required', 'string', 'max:200'],
            'el.description' => ['required', 'string', 'max:2000'],
            'el.details' => ['required', 'string', 'max:5000'],
            'en.name' => ['required', 'string', 'max:120'],
            'en.title' => ['required', 'string', 'max:160'],
            'en.subtitle' => ['required', 'string', 'max:200'],
            'en.description' => ['required', 'string', 'max:2000'],
            'en.details' => ['required', 'string', 'max:5000'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ], [], $this->landingAttributes());

        $profile = LandingProfile::current();
        $profile->fill([
            'name_el' => $validated['el']['name'],
            'title_el' => $validated['el']['title'],
            'subtitle_el' => $validated['el']['subtitle'],
            'description_el' => $validated['el']['description'],
            'details_el' => $validated['el']['details'],
            'name_en' => $validated['en']['name'],
            'title_en' => $validated['en']['title'],
            'subtitle_en' => $validated['en']['subtitle'],
            'description_en' => $validated['en']['description'],
            'details_en' => $validated['en']['details'],
        ]);

        if ($request->hasFile('logo')) {
            if (is_string($profile->logo_path) && $profile->logo_path !== '') {
                Storage::disk('public')->delete($profile->logo_path);
            }

            $stored = $request->file('logo')?->store('landing', 'public');
            $profile->logo_path = is_string($stored) ? $stored : null;
        }

        $profile->save();

        return redirect()->route('admin.settings.show')->with('success', __('ui.flash.landing_saved'));
    }

    /**
     * @return array<string, string>
     */
    private function landingAttributes(): array
    {
        $fields = [
            'name' => __('ui.settings.field_name'),
            'title' => __('ui.settings.field_title'),
            'subtitle' => __('ui.settings.field_subtitle'),
            'description' => __('ui.settings.field_description'),
            'details' => __('ui.settings.field_details'),
        ];
        $attributes = ['logo' => __('ui.settings.field_logo')];

        foreach (['el' => 'EL', 'en' => 'EN'] as $locale => $label) {
            foreach ($fields as $field => $name) {
                $attributes[$locale.'.'.$field] = $name.' ('.$label.')';
            }
        }

        return $attributes;
    }

    public function backup(DatabaseSnapshot $snapshot): BinaryFileResponse
    {
        $path = $snapshot->capture();

        return response()->download($path, 'sxoligrid-'.now()->format('Ymd-His').'.sqlite', [
            'Content-Type' => 'application/vnd.sqlite3',
        ])->deleteFileAfterSend(true);
    }

    public function restore(Request $request, DatabaseSnapshot $snapshot): RedirectResponse
    {
        $validated = $request->validate([
            'backup' => ['required', 'file', 'max:51200'],
        ]);

        $path = $validated['backup']->getRealPath();

        if (! is_string($path) || ! is_file($path)) {
            throw ValidationException::withMessages([
                'backup' => __('ui.errors.backup_invalid'),
            ]);
        }

        $snapshot->restore($path);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', __('ui.flash.database_restored'));
    }

    public function installDemo(DemoData $demo): RedirectResponse
    {
        $demo->install();

        return redirect()->route('admin.settings.show')->with('success', __('ui.flash.demo_installed'));
    }

    public function removeDemo(DemoData $demo): RedirectResponse
    {
        $demo->remove();

        return redirect()->route('admin.settings.show')->with('success', __('ui.flash.demo_removed'));
    }
}
