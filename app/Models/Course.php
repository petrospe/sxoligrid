<?php

namespace App\Models;

use App\Enums\CourseType;
use App\Services\CoursePackageSync;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'price', 'prices', 'type', 'billing_methods'])]
class Course extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'prices' => 'array',
            'type' => CourseType::class,
            'billing_methods' => 'array',
        ];
    }

    /**
     * Price of each offered billing method. Missing entries fall back to the legacy single price.
     *
     * @return array<string, float>
     */
    public function pricesForMethods(): array
    {
        $methods = $this->billing_methods;
        if (! is_array($methods) || $methods === []) {
            $methods = ['monthly'];
        }

        $stored = is_array($this->prices) ? $this->prices : [];
        $prices = [];

        foreach ($methods as $method) {
            if (! is_string($method)) {
                continue;
            }

            if (array_key_exists($method, $stored) && is_numeric($stored[$method])) {
                $prices[$method] = round((float) $stored[$method], 2);

                continue;
            }

            if (is_numeric($this->price)) {
                $prices[$method] = round((float) $this->price, 2);
            }
        }

        return $prices;
    }

    public function priceFor(string $method): ?float
    {
        $prices = $this->pricesForMethods();

        return array_key_exists($method, $prices) ? $prices[$method] : null;
    }

    protected static function booted(): void
    {
        static::saved(function (Course $course): void {
            app(CoursePackageSync::class)->sync($course);
        });
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
