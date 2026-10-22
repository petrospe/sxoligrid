<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\SubscriptionStatus;
use App\Models\Package;
use App\Models\Payment;
use App\Models\StudentSubscription;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class SubscriptionAssigner
{
    public function assign(User $student, Package $package, string $startsAt, PaymentStatus $paymentStatus): StudentSubscription
    {
        return DB::transaction(function () use ($student, $package, $startsAt, $paymentStatus): StudentSubscription {
            $starts = CarbonImmutable::parse($startsAt)->startOfDay();
            $expires = $package->duration_days !== null
                ? $starts->addDays((int) $package->duration_days)
                : null;

            $subscription = StudentSubscription::query()->create([
                'student_id' => $student->id,
                'package_id' => $package->id,
                'remaining_sessions' => $package->sessions_count,
                'starts_at' => $starts->toDateString(),
                'expires_at' => $expires?->toDateString(),
                'status' => SubscriptionStatus::Active,
            ]);

            Payment::query()->create([
                'student_id' => $student->id,
                'course_id' => null,
                'amount' => $package->price,
                'type' => $this->paymentType($package),
                'status' => $paymentStatus,
                'due_date' => $starts->toDateString(),
                'paid_at' => $paymentStatus === PaymentStatus::Paid ? now() : null,
            ]);

            return $subscription;
        });
    }

    private function paymentType(Package $package): PaymentType
    {
        if ($package->isSessionPack()) {
            return PaymentType::OfferPackage;
        }

        if ($package->duration_days !== null && $package->duration_days > 31) {
            return PaymentType::Quarterly;
        }

        return PaymentType::MonthlySubscription;
    }
}
