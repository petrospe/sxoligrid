<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->boolean('visible_on_landing')->default(true);
        });

        Schema::create('class_schedule_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_schedule_id')->constrained('class_schedules')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->unique(['class_schedule_id', 'teacher_id']);
        });

        DB::statement('insert into class_schedule_teacher (class_schedule_id, teacher_id) select id, teacher_id from class_schedules where teacher_id is not null');
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedule_teacher');

        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropColumn('visible_on_landing');
        });
    }
};
