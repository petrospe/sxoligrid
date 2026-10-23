<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->string('billing_method')->nullable()->after('course_id');
            $table->unique(['course_id', 'billing_method']);
        });

        foreach (DB::table('courses')->orderBy('id')->get() as $course) {
            $prices = json_decode((string) ($course->prices ?? ''), true);
            if (! is_array($prices)) {
                $prices = [];
            }

            $methods = json_decode((string) ($course->billing_methods ?? ''), true);
            if (! is_array($methods) || $methods === []) {
                $methods = ['monthly'];
            }

            foreach ($methods as $method) {
                if (! is_string($method)) {
                    continue;
                }

                $price = array_key_exists($method, $prices) && is_numeric($prices[$method])
                    ? round((float) $prices[$method], 2)
                    : round((float) $course->price, 2);

                DB::table('packages')->updateOrInsert(
                    [
                        'course_id' => $course->id,
                        'billing_method' => $method,
                    ],
                    [
                        'title' => $course->title,
                        'price' => $price,
                        'duration_days' => match ($method) {
                            'monthly' => 30,
                            'quarterly' => 90,
                            'semester' => 180,
                            default => null,
                        },
                        'sessions_count' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropUnique(['course_id', 'billing_method']);
            $table->dropConstrainedForeignId('course_id');
            $table->dropColumn('billing_method');
        });
    }
};
