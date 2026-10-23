<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BillingDiscountController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'discounts' => ['present', 'array'],
            'discounts.*.min_courses' => ['required', 'integer', 'min:2', 'max:20', 'distinct'],
            'discounts.*.percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [], [
            'discounts' => __('ui.admin.discount_title'),
            'discounts.*.min_courses' => __('ui.fields.min_courses'),
            'discounts.*.percent' => __('ui.fields.percent'),
        ]);

        $tiers = collect($data['discounts'])
            ->map(fn (array $tier): array => [
                'min_courses' => (int) $tier['min_courses'],
                'percent' => round((float) $tier['percent'], 2),
            ])
            ->sortBy('min_courses')
            ->values()
            ->all();

        $policy = BillingPolicy::current();
        $policy->multi_course_discounts = $tiers;
        $policy->save();

        return back()->with('success', __('ui.flash.discounts_saved'));
    }
}
