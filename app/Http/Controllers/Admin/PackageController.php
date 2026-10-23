<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BillingMethod;
use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PackageController extends Controller
{
    public function index(): Response
    {
        $packages = Package::query()->with('course:id,title,billing_methods')->orderBy('title')->get();
        $order = array_flip(BillingMethod::values());

        $coursePackages = $packages
            ->filter(function (Package $package): bool {
                if ($package->course_id === null || ! $package->billing_method instanceof BillingMethod || $package->course === null) {
                    return false;
                }

                return in_array($package->billing_method->value, $package->course->billing_methods ?? [], true);
            })
            ->sortBy(fn (Package $package): array => [
                mb_strtolower($package->course->title),
                $order[$package->billing_method->value] ?? 99,
            ])
            ->values()
            ->map(fn (Package $package): array => $this->present($package))
            ->all();

        $customPackages = $packages
            ->filter(fn (Package $package): bool => $package->course_id === null)
            ->map(fn (Package $package): array => $this->present($package))
            ->all();

        return Inertia::render('Admin/Packages/Index', [
            'course_packages' => $coursePackages,
            'packages' => $customPackages,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Package::query()->create($this->validated($request));

        return redirect()->route('admin.packages.index')->with('success', __('ui.flash.package_created'));
    }

    public function edit(Package $package): Response
    {
        return Inertia::render('Admin/Packages/Edit', [
            'package' => $this->present($package),
        ]);
    }

    public function update(Request $request, Package $package): RedirectResponse
    {
        $package->update($this->validated($request));

        return redirect()->route('admin.packages.index')->with('success', __('ui.flash.package_updated'));
    }

    public function destroy(Package $package): RedirectResponse
    {
        try {
            $package->delete();
        } catch (QueryException) {
            return back()->with('error', __('ui.flash.package_delete_blocked'));
        }

        return redirect()->route('admin.packages.index')->with('success', __('ui.flash.package_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        foreach (['duration_days', 'sessions_count'] as $field) {
            if ($request->input($field) === '') {
                $request->merge([$field => null]);
            }
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'sessions_count' => ['nullable', 'integer', 'min:1'],
        ]);

        $data['duration_days'] = $data['duration_days'] ?? null;
        $data['sessions_count'] = $data['sessions_count'] ?? null;

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Package $package): array
    {
        return [
            'id' => $package->id,
            'title' => $package->title,
            'course_id' => $package->course_id,
            'course' => $package->course?->title,
            'billing_method' => $package->billing_method?->value,
            'price' => (float) $package->price,
            'duration_days' => $package->duration_days,
            'sessions_count' => $package->sessions_count,
        ];
    }
}
