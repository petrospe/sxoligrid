<?php

namespace App\Services;

use App\Enums\BillingMethod;
use App\Enums\EnrollmentStatus;
use App\Models\BillingPolicy;
use App\Models\Enrollment;
use Illuminate\Support\Collection;

class TuitionQuote
{
    private ?BillingPolicy $policy = null;

    /**
     * Quotes for students with at least one active course that is not drop-in.
     *
     * @return list<array<string, mixed>>
     */
    public function summaries(): array
    {
        $grouped = Enrollment::query()
            ->with(['student:id,name', 'course'])
            ->where('status', EnrollmentStatus::Active)
            ->orderBy('id')
            ->get()
            ->groupBy('student_id');

        $summaries = [];

        foreach ($grouped as $rows) {
            /** @var Collection<int, Enrollment> $rows */
            $counted = $rows
                ->filter(fn (Enrollment $enrollment): bool => $enrollment->billing_method !== BillingMethod::DropIn)
                ->values();

            if ($counted->isEmpty()) {
                continue;
            }

            $percent = $this->percentFor($counted->count());
            $lines = [];
            $monthlyList = 0.0;
            $monthlyDue = 0.0;

            foreach ($counted as $enrollment) {
                $method = $enrollment->billing_method;
                $list = $enrollment->course->priceFor($method->value) ?? 0.0;
                $discounted = $method === BillingMethod::Monthly && $percent > 0;
                $amount = $discounted ? $this->discount($list, $percent) : $list;

                if ($method === BillingMethod::Monthly) {
                    $monthlyList += $list;
                    $monthlyDue += $amount;
                }

                $lines[] = [
                    'course_id' => $enrollment->course_id,
                    'course' => $enrollment->course->title,
                    'billing_method' => $method->value,
                    'list_price' => $list,
                    'amount' => $amount,
                    'discounted' => $discounted,
                ];
            }

            $student = $counted->first()->student;
            $summaries[] = [
                'student_id' => $student->id,
                'student' => $student->name,
                'course_count' => $counted->count(),
                'percent' => $percent,
                'lines' => $lines,
                'monthly_list' => round($monthlyList, 2),
                'monthly_due' => round($monthlyDue, 2),
            ];
        }

        usort($summaries, fn (array $left, array $right): int => strnatcasecmp($left['student'], $right['student']));

        return $summaries;
    }

    public function percentFor(int $courseCount): float
    {
        $match = null;

        foreach ($this->policy()->tiers() as $tier) {
            if ($courseCount >= $tier['min_courses']) {
                $match = $tier;
            }
        }

        return $match['percent'] ?? 0.0;
    }

    public function discount(float $amount, float $percent): float
    {
        if ($percent <= 0) {
            return round($amount, 2);
        }

        return round($amount * (100 - $percent) / 100, 2);
    }

    private function policy(): BillingPolicy
    {
        return $this->policy ??= BillingPolicy::current();
    }
}
