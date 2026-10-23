<?php

use App\Models\Course;
use App\Services\CoursePackageSync;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        foreach (Course::query()->get() as $course) {
            app(CoursePackageSync::class)->sync($course);
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
