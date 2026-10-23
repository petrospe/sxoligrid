<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->json('prices')->nullable();
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->string('billing_method')->default('monthly');
        });

        Schema::create('billing_policies', function (Blueprint $table) {
            $table->id();
            $table->json('multi_course_discounts');
            $table->timestamps();
        });

        DB::table('billing_policies')->insert([
            'multi_course_discounts' => '[]',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (DB::table('courses')->get() as $course) {
            $methods = json_decode((string) ($course->billing_methods ?? ''), true);
            if (! is_array($methods) || $methods === []) {
                $methods = ['monthly'];
            }

            $prices = [];
            foreach ($methods as $method) {
                if (is_string($method)) {
                    $prices[$method] = round((float) $course->price, 2);
                }
            }

            DB::table('courses')->where('id', $course->id)->update([
                'prices' => json_encode($prices),
            ]);
        }

        foreach (DB::table('enrollments')->get() as $enrollment) {
            $course = DB::table('courses')->where('id', $enrollment->course_id)->first();
            $methods = json_decode((string) ($course->billing_methods ?? ''), true);
            if (! is_array($methods) || $methods === []) {
                $methods = ['monthly'];
            }

            $method = in_array('monthly', $methods, true) ? 'monthly' : (string) $methods[0];

            DB::table('enrollments')->where('id', $enrollment->id)->update([
                'billing_method' => $method,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_policies');

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn('billing_method');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('prices');
        });
    }
};
