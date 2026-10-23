<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('class_schedules', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable()->after('course_id')->constrained('course_sections')->cascadeOnDelete();
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'course_id']);
            $table->foreignId('section_id')->nullable()->after('course_id')->constrained('course_sections')->cascadeOnDelete();
        });

        $now = now();

        foreach (DB::table('courses')->orderBy('id')->get() as $course) {
            $sectionId = DB::table('course_sections')->insertGetId([
                'course_id' => $course->id,
                'name' => $course->title,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('class_schedules')->where('course_id', $course->id)->update(['section_id' => $sectionId]);
            DB::table('enrollments')->where('course_id', $course->id)->update(['section_id' => $sectionId]);
        }

        Schema::table('enrollments', function (Blueprint $table) {
            $table->unique(['student_id', 'section_id']);
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'section_id']);
            $table->dropConstrainedForeignId('section_id');
            $table->unique(['student_id', 'course_id']);
        });

        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('section_id');
        });

        Schema::dropIfExists('course_sections');
    }
};
