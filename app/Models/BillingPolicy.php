<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['multi_course_discounts'])]
class BillingPolicy extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'multi_course_discounts' => 'array',
        ];
    }

    public static function current(): self
    {
        $policy = static::query()->first();

        if ($policy instanceof self) {
            return $policy;
        }

        return static::query()->create([
            'multi_course_discounts' => [],
        ]);
    }

    /**
     * @return list<array{min_courses: int, percent: float}>
     */
    public function tiers(): array
    {
        $tiers = [];

        foreach ($this->multi_course_discounts ?? [] as $tier) {
            if (! is_array($tier)) {
                continue;
            }

            $minCourses = (int) ($tier['min_courses'] ?? 0);
            if ($minCourses < 2) {
                continue;
            }

            $tiers[] = [
                'min_courses' => $minCourses,
                'percent' => round((float) ($tier['percent'] ?? 0), 2),
            ];
        }

        usort($tiers, fn (array $left, array $right): int => $left['min_courses'] <=> $right['min_courses']);

        return $tiers;
    }
}
