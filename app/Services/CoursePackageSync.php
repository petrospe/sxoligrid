<?php

namespace App\Services;

use App\Enums\BillingMethod;
use App\Models\Course;
use App\Models\Package;

class CoursePackageSync
{
    public function sync(Course $course): void
    {
        $kept = [];

        foreach ($course->pricesForMethods() as $method => $price) {
            $billing = BillingMethod::from($method);
            $package = Package::query()->updateOrCreate(
                [
                    'course_id' => $course->id,
                    'billing_method' => $billing->value,
                ],
                [
                    'title' => $course->title,
                    'price' => $price,
                    'duration_days' => $this->durationDays($billing),
                    'sessions_count' => null,
                ],
            );
            $kept[] = $package->id;
        }

        Package::query()
            ->where('course_id', $course->id)
            ->whereNotIn('id', $kept === [] ? [0] : $kept)
            ->whereDoesntHave('subscriptions')
            ->delete();
    }

    private function durationDays(BillingMethod $method): ?int
    {
        return match ($method) {
            BillingMethod::Monthly => 30,
            BillingMethod::Quarterly => 90,
            BillingMethod::Semester => 180,
            BillingMethod::OfferPackage, BillingMethod::DropIn => null,
        };
    }
}
